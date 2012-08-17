<?php
defined('is_running') or die('Not an entry point...');

/*
	original code for searching blogs provided by Stefan Benicke of http://www.opusonline.at

*/


/*

Using shell_exec?


if( function_exists('shell_exec') ){
	$test = 'grep --help'; //this works .. may be a way to determine usability
	$test = 'grep -nHIirF --include=*.php -- feed /var/www/gpeasy/glacier/data/_pages'; //

	//$test = 'grep function *.php';
	//$test = 'ls -lart';
	//message('shell_exec(<i>\''.$test.'\'</i>)');
	$hmm = shell_exec($test);
	//message('<b>Result:</b><br/> '.nl2br(htmlspecialchars($hmm)));
	//message('<b>Result:</b><textarea> '.htmlspecialchars($hmm).'</textarea>');

	$hmm = explode("\n",$hmm);
	foreach($hmm as $line){
		message('<b>LINE:</b> '.htmlspecialchars($line));
	}

}
*/

/* example output
/var/www/gpeasy/glacier/data/_pages/gpEasy_Descriptions.php:9:<p>gpEasy was designed with the feedback and input from actual end users. Tthe operators and staff of Bitterroot Gymnastics (bittgym.com/index.php) continue to give critical feedback for the development process.<br />
/var/www/gpeasy/glacier/data/_pages/To_Do.php:20:<li>News Feed Addon
/var/www/gpeasy/glacier/data/_pages/To_Do.php:22:<li>Need rss feed for news!</li>
*/


class SimpleSearch{

	var $config_file;
	var $search_config;
	var $files = array();

	function SimpleSearch(){
		global $page, $langmessage, $addonPathData;

		$this->config_file = $addonPathData.'/search_config.php';
		$this->GetConfig();

		if( common::LoggedIn() ){
			$page->admin_links[] = array('Special_Search','Configuration','cmd=config');
			$cmd = common::GetCommand();

			switch($cmd){
				case 'save_config':
					if( $this->SaveConfig() ){
						break;
					}
				return;
				case 'config':
					$this->Config($this->search_config);
				return;

			}
		}

		$query =& $_GET['q'];

		echo '<div class="search_results">';
		echo '<form action="'.common::GetUrl('Special_Search').'" method="get">';

		echo '<h2>';
		echo gpOutput::GetAddonText('Search');
		echo ' &nbsp; ';
		echo '<input name="q" type="text" class="text" value="'.htmlspecialchars($query).'"/>';
		echo '<input type="hidden" name="src" value="gadget" /> ';
		$html = '<input type="submit" name="" class="submit" value="%s" />';
		echo gpOutput::GetAddonText('Search',$html);
		echo '</h2>';
		echo '</form>';


		if( !empty($query) ){
			$query = strtolower($query);
			preg_match_all("/\S+/", $query, $words);
			$words = array_unique($words[0]);

			$pattern = '#(';
			$bar = '';
			foreach($words as $word){
				$pattern .= $bar.preg_quote($word,'#');
				$bar = '|';
			}
			$pattern .= ')#Si';

			$this->SearchPages($pattern);
			$this->SearchBlog($pattern);
		}

		if( count($this->files) > 0 ){
			foreach($this->files as $result){
				echo $result;
			}
		}else{
			echo '<p>';
			echo gpOutput::GetAddonText('Sorry, there weren\'t any results for your search. ');
			echo '</p>';
		}

		echo '</div>';

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

		echo '<form class="renameform" action="'.common::GetUrl('Special_Search').'" method="post">';
		echo '<table style="width:100%" class="bordered">';
		echo '<tr>';
			echo '<th>';
			echo 'Option';
			echo '</th>';
			echo '<th>';
			echo 'Value';
			echo '</th>';
			echo '<th>';
			echo 'Default';
			echo '</th>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Search Hidden';
			echo '</td>';
			echo '<td>';
			if( isset($array['search_hidden']) && $array['search_hidden'] ){
				echo '<input type="checkbox" name="search_hidden" checked="checked" value="true" />';
			}else{
				echo '<input type="checkbox" name="search_hidden" value="true" />';
			}
			echo '</td>';
			echo '<td>';
			echo 'false';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo 'Search Blog';
			echo '</td>';
			echo '<td>';

			$disabled = ' disabled="disabled"';
			if( SimpleSearch::BlogInstalled() ){
				$disabled = '';
			}

			if( isset($array['search_blog']) && $array['search_blog'] ){
				echo '<input type="checkbox" name="search_blog" checked="checked" value="true" '.$disabled.'/>';
			}else{
				echo '<input type="checkbox" name="search_blog" value="true" '.$disabled.'/>';
			}
			echo '</td>';
			echo '<td>';
			echo 'true';
			echo '</td>';
			echo '</tr>';


		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="save_config" />';
			echo '<input type="submit" name="" value="'.$langmessage['save'].'" /> ';
			echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" /> ';
			echo '</td>';
			echo '<td>';
			echo '</td>';
			echo '</tr>';


		echo '</table>';

		echo common::Link('Admin_Theme_Content',$langmessage['editable_text'],'cmd=addontext&addon='.urlencode($addonFolderName),' title="'.urlencode($langmessage['editable_text']).'" name="gpabox" ');

		echo '</form>';

		echo '<p>';
	}


	function SearchPages($pattern){
		global $gp_titles, $gp_index;

		$this->files = array();


		ob_start();
		foreach($gp_index as $title => $index){
			$this->SearchPage($pattern,$title,$index);
		}
		$empty = ob_get_clean();


		$this->ReduceResults();
	}

	function SearchPage($pattern,$title,$index){
		global $gp_menu;

		$full_path = gpFiles::PageFile($title);
		if( !file_exists($full_path) ){
			return;
		}

		//search hidden?
		if( !$this->search_config['search_hidden'] ){
			if( !isset($gp_menu[$index]) ){
				return;
			}
		}

		include($full_path);
		if( !isset($file_sections) || !is_array($file_sections) ){
			return;
		}
		$content = '';
		foreach($file_sections as $section){
			if( !isset($section['content']) ){
				continue;
			}
			if( $section['type'] == 'exec_php' ){
				continue;
			}
			$content .= $section['content'].' ';
		}
		$this->findString($content, $pattern, $title);
	}



	//try to search in the blog
	function SearchBlog($pattern){
		global $dataDir, $gp_index, $gp_titles, $config;

		if( !$this->search_config['search_blog'] ){
			return;
		}

		$blog_index = SimpleSearch::BlogInstalled();
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
				$this->findString($content, $pattern, $title, $slug, '?cmd=post&id='.$id);
			}
			$posts = array();

		}

		$this->ReduceResults();
	}


	//reduce the results to reduce the memory used
	function ReduceResults(){

		//arrange in order
		krsort($this->files);

		while( count($this->files) > 20 ){
			array_pop($this->files);
		}
	}


	// same as before in SerchPreg() but saves in $this->file
	// and uses different links for blog findings
	function findString(&$content, &$pattern, &$title, $link='', $link_query = ''){

		$content = str_replace('>','> ',$content);
		$content = strip_tags($content);
		$match_count = preg_match_all($pattern,$content,$matches,PREG_OFFSET_CAPTURE);
		if( $match_count < 1 ){
			return;
		}
		$rating = $match_count/strlen($content);

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

		$result .= preg_replace($pattern,'<b>\1</b>',$content);

		$result .= '</p>';
		$result .= '</div>';
		while( isset($this->files[(string)$rating]) ){
			$rating++;
		}
		$this->files[(string)$rating] = $result;
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
