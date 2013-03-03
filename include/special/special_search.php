<?php
defined('is_running') or die('Not an entry point...');

class special_gpsearch{

	var $config_file;
	var $search_config;
	var $results = array();

	var $search_pattern = '';
	var $search_hidden = false;
	var $search_count = 0;
	var $show_stats = false;
	var $gpabox = false;


	function special_gpsearch(){
		global $page, $langmessage, $dataDir;

		$this->config_file = $dataDir.'/data/_site/config_search.php';
		$this->GetConfig();

		if( $this->Admin() ){
			return;
		}

		//admin popup or visitor
		$_REQUEST += array('q'=>'');
		$start_time = microtime();
		if( common::LoggedIn() && isset($_REQUEST['gpx_content']) && $_REQUEST['gpx_content'] == 'gpabox' ){
			$this->AdminSearch();
		}else{
			$this->Search();
		}

		//echo '<p>'.microtime_diff($start_time,microtime()).' seconds</p>';
	}

	function AdminSearch(){
		global $langmessage;

		$this->gpabox = true;
		$this->show_stats = true;
		$this->search_hidden = true;

		echo '<div id="admin_search">';
		echo '<form action="'.common::GetUrl('special_gpsearch').'" method="get">';
		echo '<h3>'.$langmessage['Search'].'</h3>';
		echo '<input name="q" type="text" class="gpinput" value="'.htmlspecialchars($_REQUEST['q']).'"/>';
		echo '<input type="submit" name="" value="'.$langmessage['Search'].'" class="gpabox gpsubmit" />';
		echo '<input type="submit" name="" value="'.$langmessage['cancel'].'" class="admin_box_close gpcancel" />';
		echo '</form>';

		echo '<p>';
		if( !empty($_REQUEST['q']) ){
			$this->RunQuery();
		}
		echo '</p>';

		if( $this->search_count > 0 ){
			echo '<p>'.$this->search_count.' files searched</p>';
		}
		echo '</div>';

	}

	function Search(){

		echo '<div class="search_results">';
		echo '<form action="'.common::GetUrl('special_gpsearch').'" method="get">';

		echo '<h2>';
		echo gpOutput::GetAddonText('Search');
		echo ' &nbsp; ';
		echo '<input name="q" type="text" class="text" value="'.htmlspecialchars($_REQUEST['q']).'"/>';
		$html = '<input type="submit" name="" class="submit" value="%s" />';
		echo gpOutput::GetAddonText('Search',$html);
		echo '</h2>';
		echo '</form>';

		if( common::LoggedIn() ){
			$this->search_hidden = true;
		}else{
			$this->search_hidden = $this->search_config['search_hidden'];
		}
		$this->RunQuery();

		if( common::LoggedIn() ){
			echo common::Link('special_gpsearch','Configuration','cmd=config','data-cmd="gpabox"');
		}

		echo '</div>';
	}

	function Gadget(){

		$query = '';
		if( isset($_GET['q']) ){
			$query = $_GET['q'];
		}

		echo '<h3>';
		echo gpOutput::GetAddonText('Search');
		echo '</h3>';
		echo '<form action="'.common::GetUrl('special_gpsearch').'" method="get">';
		echo '<div>';
		echo '<input name="q" type="text" class="text" value="'.htmlspecialchars($query).'"/>';
		echo '<input type="hidden" name="src" value="gadget" />';

		$html = '<input type="submit" class="submit" name="" value="%s" />';
		echo gpOutput::GetAddonText('Search',$html);

		echo '</div>';
		echo '</form>';

	}


	function RunQuery(){

		if( !empty($_REQUEST['q']) ){
			$this->SearchPattern();
			$this->SearchPages();
			$this->SearchBlog();
			gpPlugin::Action('Search',array($this));
		}

		$this->ShowResults();
	}

	function ShowResults(){
		global $langmessage;

		if( !count($this->results) ){
			echo '<p>';
			echo gpOutput::GetAddonText($langmessage['search_no_results']);
			echo '</p>';
			return;
		}

		usort( $this->results, array('special_gpsearch', 'sort') );

		// remove duplicates
		$links = array();
		foreach($this->results as $key => $result){
			$link = common::GetUrl( $result['slug'], $result['query'] );
			$link = strtolower($link);
			if( in_array($link,$links) ){
				unset($this->results[$key]);
			}else{
				$links[] = $link;
			}
		}


		$total = count($this->results);
		$len = 20;
		$total_pages = ceil($total/$len);
		$current_page = 0;
		if( isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']) ){
			if( $_REQUEST['pg'] <= $total_pages ){
				$current_page = $_REQUEST['pg'];
			}else{
				$current_page = ($total_pages-1);
			}
		}
		$start = $current_page*$len;
		$end = min($start+$len,$total);

		$this->results = array_slice($this->results,$start,$len,true);
		echo '<p class="search_nav search_nav_top">';
		echo sprintf($langmessage['SHOWING'],($start+1),$end,$total);
		echo '</p>';


		echo '<div class="result_list">';
		foreach($this->results as $result){
			echo '<div><h4>';
			echo common::Link($result['slug'],$result['label'],$result['query']);
			echo '</h4>';

			echo $result['content'];

			if( $this->show_stats ){
				echo ' <span class="match_stats">';
				echo $result['matches'].' match(es) out of '.$result['words'].' words ';
				echo ' </span>';
			}
			echo '</div>';
		}
		echo '</div>';

		if( $total_pages > 1 ){
			echo '<p class="search_nav search_nav_bottom">';
			for($i=0;$i<$total_pages;$i++){
				if( $i == $current_page ){
					echo '<span>'.($i+1).'</span> ';
					continue;
				}
				$query = 'q='.rawurlencode($_REQUEST['q']);
				if( $i > 0 ){
					$query .= '&pg='.$i;
				}
				$attr = '';
				if( $this->gpabox ){
					$attr = 'data-cmd="gpabox"';
				}
				echo common::Link('special_gpsearch',($i+1),$query,$attr).' ';
			}
			echo '</p>';
		}
	}

	function Sort($resulta,$resultb){
		return $resulta['strength'] < $resultb['strength'];
	}

	function SearchPattern(){
		$query = strtolower($_REQUEST['q']);
		preg_match_all("/\S+/", $query, $words);
		$words = array_unique($words[0]);

		$sub_pattern1 = $sub_pattern2 = array();
		foreach($words as $word){
			$sub_pattern1[] = '\b'.preg_quote($word,'#').'\b';
			$sub_pattern2[] = preg_quote($word,'#');
		}

		$this->search_pattern = '#(?:('.implode('|',$sub_pattern1).')|('.implode('|',$sub_pattern2).'))#Si';
	}

	function Admin(){
		global $page;

		if( !common::LoggedIn() ){
			return false;
		}
		$page->admin_links[] = array('special_gpsearch','Configuration','cmd=config','data-cmd="gpabox"');
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

		$search_config += array('search_hidden'=>false);
		$this->search_config = $search_config;
	}

	function SaveConfig(){
		global $langmessage;

		if( isset($_POST['search_hidden']) ){
			$search_config['search_hidden'] = true;
		}else{
			$search_config['search_hidden'] = false;
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
		echo '<tr><th>'.$langmessage['options'].'</th><th>'.$langmessage['Value'].'</th><th>'.$langmessage['default'].'</th></tr>';

		echo '<tr><td>'.$langmessage['Search Hidden Files'].'</td><td>';
			if( isset($array['search_hidden']) && $array['search_hidden'] ){
				echo '<input type="checkbox" name="search_hidden" checked="checked" value="true" />';
			}else{
				echo '<input type="checkbox" name="search_hidden" value="true" />';
			}
			echo '</td><td>'.$langmessage['False'].'</td></tr>';


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

		ob_start();
		foreach($gp_index as $title => $index){
			if( !common::SpecialOrAdmin($title) ){
				$this->SearchPage($title,$index);
			}
		}
		ob_get_clean();
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
		$this->FindString($content, $label, $title);
	}


	/**
	 * @deprecated gpEasy 3.5b2
	 */
	function SearchBlog(){
		global $dataDir, $gp_index, $gp_titles, $config;

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
				$this->FindString($content, $title, 'Special_Blog', 'cmd=post&id='.$id);
			}
			$posts = array();
		}
	}

	public function FindString(&$content, $label, $slug, $link_query = ''){
		$this->search_count++;

		//search all of the content include html
		$content = $label.' '.$content;
		$match_count = preg_match_all($this->search_pattern,$content,$matches,PREG_OFFSET_CAPTURE);
		if( $match_count < 1 ){
			return;
		}
		$words = str_word_count($content);
		$strength = $this->Strength($matches,$words);


		//format content, remove html
		$label_len = strlen($label);
		$content = substr($content,$label_len);
		$content = str_replace('>','> ',$content);
		$content = preg_replace('/\s+/', ' ', $content);
		$content = strip_tags($content);
		preg_match($this->search_pattern,$content,$matches,PREG_OFFSET_CAPTURE);
		$start = 0;
		if( isset($matches[0][1]) ){
			$start = $matches[0][1];
		}

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

		$result = array();
		$result['label'] = $label;
		$result['slug'] = $slug;
		$result['query'] = $link_query;
		$result['content'] = preg_replace($this->search_pattern,'<b>\1\2</b>',$content);
		$result['words'] = $words;
		$result['matches'] = $match_count;
		$result['strength'] = $strength;
		$this->results[] = $result;
	}

	function Strength($matches,$len){

		//space around search terms
		$factor = 0;
		foreach((array)$matches[1] as $match){
			if( is_array($match) && $match[1] >= 0 ){
				$factor += 3;
			}
		}

		//no space around search term
		foreach((array)$matches[2] as $match){
			if( is_array($match) && $match[1] >= 0 ){
				$factor += 1;
			}
		}

		$strength = $factor/$len;
		return round($strength,8);
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
