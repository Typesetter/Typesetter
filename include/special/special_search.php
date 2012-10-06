<?php
defined('is_running') or die('Not an entry point...');

class special_gpsearch{

	var $config_file;
	var $search_config;
	var $results = array();

	var $search_pattern = '';
	var $search_hidden = false;

	function special_gpsearch(){
		global $page, $langmessage, $dataDir;


		$this->config_file = $dataDir.'/data/_site/config_search.php';
		$this->GetConfig();


		if( $this->Admin() ){
			return;
		}
		$this->Search();
	}

	function Search(){
		$_GET += array('q'=>'');

		echo '<div class="search_results">';
		echo '<form action="'.common::GetUrl('special_gpsearch').'" method="get">';

		echo '<h2>';
		echo gpOutput::GetAddonText('Search');
		echo ' &nbsp; ';
		echo '<input name="q" type="text" class="text" value="'.htmlspecialchars($_GET['q']).'"/>';
		$html = '<input type="submit" name="" class="submit" value="%s" />';
		echo gpOutput::GetAddonText('Search',$html);
		echo '</h2>';
		echo '</form>';


		if( !empty($_GET['q']) ){
			if( !$this->search_config['search_hidden'] ){
				if( !common::LoggedIn() ){
					$this->search_hidden = false;
				}else{
					$this->search_hidden = true;
				}
			}

			$this->SearchPattern();
			$this->SearchPages();
			$this->SearchBlog();
		}

		$this->ShowResults();

		if( common::LoggedIn() ){
			echo common::Link('special_gpsearch','Configuration','cmd=config','name="gpabox"');
		}

		echo '</div>';
	}

	function ShowResults(){

		if( !count($this->results) ){
			echo '<p>';
			echo gpOutput::GetAddonText('Sorry, there weren\'t any results for your search. ');
			echo '</p>';
			return;
		}

		foreach($this->results as $result){
			echo $result;
		}
	}

	function SearchPattern(){
		$query = strtolower($_GET['q']);
		preg_match_all("/\S+/", $query, $words);
		$words = array_unique($words[0]);

		$pattern = '#(';
		$bar = '';
		foreach($words as $word){
			$pattern .= $bar.preg_quote($word,'#');
			$bar = '|';
		}
		$pattern .= ')#Si';
		$this->search_pattern = $pattern;
	}

	function Admin(){
		global $page;

		if( !common::LoggedIn() ){
			return false;
		}
		$page->admin_links[] = array('special_gpsearch','Configuration','cmd=config','name="gpabox"');
		$cmd = common::GetCommand();

		switch($cmd){
			case 'save_config':
				if( $this->SaveConfig() ){
					break;
				}
			return true;
			case 'config':
				$this->Config($this->search_config);
			return true;
		}
		return false;
	}

	function GetConfig(){

		$search_config = array();
		if( file_exists($this->config_file) ){
			include($this->config_file);
		}

		$search_config += array('search_blog'=>true,'search_hidden'=>false);
		$this->search_config = $search_config;
	}

	function SaveConfig(){
		global $langmessage;

		if( isset($_POST['search_hidden']) ){
			$search_config['search_hidden'] = true;
		}else{
			$search_config['search_hidden'] = false;
		}
		if( isset($_POST['search_blog']) ){
			$search_config['search_blog'] = true;
		}else{
			$search_config['search_blog'] = false;
		}

		if( gpFiles::SaveArray($this->config_file,'search_config',$search_config) ){
			message($langmessage['SAVED']);
			$this->search_config = $search_config;
			return true;
		}

		message($langmessage['OOPS']);
		$this->Config($_POST);
		return false;

	}


	function Config($array=array()){
		global $langmessage, $addonFolderName, $gp_index;


		echo '<h2>Search Configuration</h2>';

		echo '<form class="renameform" action="'.common::GetUrl('special_gpsearch').'" method="post">';
		echo '<table style="width:100%" class="bordered">';
		echo '<tr><th>Option</th><th>Value</th><th>Default</th></tr>';

		echo '<tr><td>Search Hidden</td><td>';
			if( isset($array['search_hidden']) && $array['search_hidden'] ){
				echo '<input type="checkbox" name="search_hidden" checked="checked" value="true" />';
			}else{
				echo '<input type="checkbox" name="search_hidden" value="true" />';
			}
			echo '</td><td>false</td></tr>';

		echo '<tr><td>Search Blog</td><td>';

			$disabled = ' disabled="disabled"';
			if( special_gpsearch::BlogInstalled() ){
				$disabled = '';
			}

			if( isset($array['search_blog']) && $array['search_blog'] ){
				echo '<input type="checkbox" name="search_blog" checked="checked" value="true" '.$disabled.'/>';
			}else{
				echo '<input type="checkbox" name="search_blog" value="true" '.$disabled.'/>';
			}
			echo '</td><td>true</td></tr>';


		echo '<tr><td></td><td>';
			echo '<input type="hidden" name="cmd" value="save_config" />';
			echo '<input type="submit" name="" value="'.$langmessage['save'].'" class="gpsubmit" /> ';
			echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" /> ';
			echo '</td><td></td></tr>';

		echo '</table>';

		echo '</form>';

		echo '<p>';
	}


	function SearchPages(){
		global $gp_index;
		includeFile('tool/SectionContent.php');
		includeFile('special.php');

		ob_start();
		foreach($gp_index as $title => $index){
			$type = common::SpecialOrAdmin($title);
			if( !$type ){
				$this->SearchPage($title,$index);
			}
		}
		ob_get_clean();
		$this->ReduceResults();
	}

	function SearchPage($title,$index){
		global $gp_menu;

		$full_path = gpFiles::PageFile($title);
		if( !file_exists($full_path) ){
			return;
		}

		//search hidden?
		if( !$this->search_hidden && !isset($gp_menu[$index]) ){
			return;
		}

		$file_sections = $file_stats = array();
		include($full_path);
		if( !isset($file_sections) || !is_array($file_sections) || !count($file_sections) ){
			return;
		}


		$content = section_content::Render($file_sections,$title,$file_stats);
		$label = common::GetLabel($title);
		$this->FindString($content, $title);
	}





	//try to search in the blog
	function SearchBlog(){
		global $dataDir, $gp_index, $gp_titles, $config;

		if( !$this->search_config['search_blog'] ){
			return;
		}

		$blog_index = special_gpsearch::BlogInstalled();
		if( !$blog_index ){
			return;
		}

		$slug = array_search($blog_index,$gp_index);
		$addon = $gp_titles[$blog_index]['addon'];
		$blog_label = $gp_titles[$blog_index]['label'];

		//blod data folder
		$addon_info = $config['addons'][$addon];
		if( isset($addon_info['data_folder']) ){
			$blog_data_folder = $dataDir.'/data/_addondata/'.$addon_info['data_folder'];
		}else{
			$blog_data_folder = $dataDir.'/data/_addondata/'.$addon;
		}


		// config of installed addon to get to know how many post files are
		$full_path = $blog_data_folder.'/index.php';
		if( !file_exists($full_path) ){
			//nothing in the blog yet
			return;
		}

		require($full_path);
		$fileIndexMax = floor($blogData['post_index']/20); // '20' I found in SimpleBlogCommon.php function GetPostFile (line 62)

		for ($fileIndex = 0; $fileIndex <= $fileIndexMax; $fileIndex++) {
			$postFile = $blog_data_folder.'/posts_'.$fileIndex.'.php';
			if( !file_exists($postFile) ){
				continue;
			}
			require($postFile);

			foreach($posts as $id => $post){
				$title = $blog_label.': '.str_replace('_',' ',$post['title']);
				$content = str_replace('_',' ',$post['title']).' '.$post['content'];
				$this->FindString($content, $title, $slug, '?cmd=post&id='.$id);
			}
			$posts = array();

		}

		$this->ReduceResults();
	}


	/**
	 * Reduce the results to reduce the memory used
	 *
	 */
	function ReduceResults(){

		//arrange in order
		krsort($this->results);

		while( count($this->results) > 20 ){
			array_pop($this->results);
		}
	}

	function FindString(&$content, &$title, $link='', $link_query = ''){

		$content = str_replace('>','> ',$content);
		$content = strip_tags($content);
		$match_count = preg_match_all($this->search_pattern,$content,$matches,PREG_OFFSET_CAPTURE);
		if( $match_count < 1 ){
			return;
		}
		$rating = $match_count/strlen($content);
		$rating = round($rating,8);

		$result = '<div>';
		$result .= '<b>';
		$label = common::GetLabel($title);
		if(empty($link)){
			$result .= common::Link($title,$label,$link_query);
		}else{
			$result .= common::Link($link,$label,$link_query);
		}

		$result .= '</b>';
		$result .= '<p>';

		$content = str_replace(array("\n","\r","\t"),array(' ',' ',' '),$content);

		//reduce a little bit
		$start = $matches[0][0][1];

		//find a space at the beginning to start from
		$i = 0;
		do{
			$i++;
			$start_offset = $i*10;
			$start = max(0,$start-$start_offset);
			$trimmed = substr($content,$start,300);
			$space = strpos($trimmed,' ');
			if( $space < $start_offset ){
				$content = substr($trimmed,$space);
				break;
			}
		}while( ($start-$start_offset) > 0);


		//find a space at the end
		if( strlen($content) > 250 ){
			$space2 = strpos($content,' ',$space+220);
			if( $space2 > 0 ){
				$content = substr($content,0,$space2);
			}
		}

		$result .= preg_replace($this->search_pattern,'<b>\1</b>',$content);

		$result .= '</p>';
		$result .= '</div>';

		//add to results
		while( isset($this->results[(string)$rating]) ){
			$rating *= 1.0001;
		}
		$this->results[(string)$rating] = $result;
	}

	/**
	 * Determine if the Simple Blog addon is also installed
	 * If installed return the index
	 *
	 */
	function BlogInstalled(){
		global $gp_index, $gp_titles;

		//pre 3.0 check
		if( isset($gp_index['Special_Blog']) ){
			return $gp_index['Special_Blog'];
		}


		//3.0+ check
		if( isset($gp_titles['special_blog']) ){
			return 'special_blog';
		}

		return false;
	}



}
