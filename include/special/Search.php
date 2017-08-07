<?php

namespace gp\special;

defined('is_running') or die('Not an entry point...');

class Search extends \gp\special\Base{

	public $config_file;
	public $search_config;
	public $results = array();

	public $search_pattern = '';
	public $search_hidden = false;
	public $search_count = 0;
	public $show_stats = false;
	public $gpabox = false;


	public function __construct($args){
		global $langmessage, $dataDir;

		parent::__construct($args);

		$this->config_file = $dataDir.'/data/_site/config_search.php';
		$this->GetConfig();

		if( $this->Admin() ){
			return;
		}

		//admin popup or visitor
		$_REQUEST += array('q'=>'');
		if( \gp\tool::LoggedIn() && isset($_REQUEST['gpx_content']) && $_REQUEST['gpx_content'] == 'gpabox' ){
			$this->AdminSearch();
		}else{
			$this->Search();
		}
	}

	public function AdminSearch(){
		global $langmessage;

		$this->gpabox = true;
		$this->show_stats = true;
		$this->search_hidden = true;

		echo '<div id="admin_search">';
		echo '<form action="'.\gp\tool::GetUrl('special_gpsearch').'" method="get">';
		echo '<h3>'.$langmessage['Search'].'</h3>';
		echo '<input name="q" type="text" class="gpinput" value="'.htmlspecialchars($_REQUEST['q']).'" required />';
		echo '<input type="submit" name="" value="'.$langmessage['Search'].'" class="gpabox gpsubmit gpvalidate" />';
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

	public function Search(){

		echo '<div class="GPAREA filetype-special_search search_results">';
		echo '<form action="'.\gp\tool::GetUrl('special_gpsearch').'" method="get">';

		echo '<h2>';
		echo \gp\tool\Output::GetAddonText('Search');
		echo '</h2>';
		echo '<input name="q" type="text" class="text" value="'.htmlspecialchars($_REQUEST['q']).'"/> ';
		$html = '<input type="submit" name="" class="submit" value="%s" />';
		echo \gp\tool\Output::GetAddonText('Search',$html);

		echo '</form>';

		if( \gp\tool::LoggedIn() ){
			$this->search_hidden = true;
		}else{
			$this->search_hidden = $this->search_config['search_hidden'];
		}
		$this->RunQuery();

		if( \gp\tool::LoggedIn() ){
			echo \gp\tool::Link('special_gpsearch','Configuration','cmd=config','data-cmd="gpabox"');
		}

		echo '</div>';
	}

	public function Gadget(){

		$query = '';
		if( isset($_GET['q']) ){
			$query = $_GET['q'];
		}

		echo '<h3>';
		echo \gp\tool\Output::GetAddonText('Search');
		echo '</h3>';
		echo '<form action="'.\gp\tool::GetUrl('special_gpsearch').'" method="get">';
		echo '<div>';
		echo '<input name="q" type="text" class="text" value="'.htmlspecialchars($query).'"/>';
		echo '<input type="hidden" name="src" value="gadget" />';

		$html = '<input type="submit" class="submit" name="" value="%s" />';
		echo \gp\tool\Output::GetAddonText('Search',$html);

		echo '</div>';
		echo '</form>';

	}


	public function RunQuery(){

		if( !empty($_REQUEST['q']) ){
			$this->SearchPattern();
			$this->SearchPages();
			\gp\tool\Plugins::Action('Search',array($this));
		}

		$this->ShowResults();
	}

	public function ShowResults(){
		global $langmessage;

		if( !count($this->results) ){
			echo '<p>';
			echo \gp\tool\Output::GetAddonText($langmessage['search_no_results']);
			echo '</p>';
			return;
		}

		$this->RemoveDups();
		usort( $this->results, array($this, 'sort') );


		$total			= count($this->results);
		$len			= 20;
		$total_pages	= ceil($total/$len);
		$current_page	= self::ReqPage('pg', $total_pages );

		$start			= $current_page*$len;
		$end			= min($start+$len,$total);

		$this->results = array_slice($this->results,$start,$len,true);
		echo '<p class="search_nav search_nav_top">';
		echo sprintf($langmessage['SHOWING'],($start+1),$end,$total);
		echo '</p>';


		echo '<div class="result_list">';
		foreach($this->results as $result){
			echo '<div><h4>';
			echo isset($result['link']) ? $result['link'] : \gp\tool::Link($result['slug'],$result['label'],$result['query']);
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


		$attr = '';
		if( $this->gpabox ){
			$attr = 'data-cmd="gpabox"';
		}

		$query = 'q='.rawurlencode($_REQUEST['q']);
		self::PaginationLinks($current_page, $total_pages, 'special_gpsearch', $query, 'pg', $attr);
	}


	/**
	 * Get the requested page number
	 *
	 * @param string $key
	 * @param int $total_pages
	 */
	public static function ReqPage($key = 'pg', $total_pages = null){

		if( isset($_REQUEST[$key]) ){

			$pg = (int)$_REQUEST[$key];

			if( !is_null($total_pages) && $total_pages > 0 ){
				$pg	= min($pg, $total_pages-1 );
			}

			return max(0,$pg);
		}

		return 0;
	}



	/**
	 * Pagination links
	 *
	 */
	public static function PaginationLinks($current_page, $total_pages, $slug, $query, $page_key = 'pg', $attr=''){
		global $langmessage;

		if( $total_pages < 1 ){
			return;
		}
		echo '<ul class="search_nav search_nav_bottom pagination">';

		//previous
		echo '<li>';
		if( $current_page > 0 ){
			self::PaginationLink($slug, '&laquo;', $query, $page_key, $attr, ($current_page-1));
		}else{
			echo '<li class="disabled"><span>&laquo;</span></li>';
		}

		// i
		$min_page	= max(0, $current_page-3);
		$max_page	= min($min_page+6, $total_pages);
		for($i=$min_page;$i<$max_page;$i++){

			if( $i == $current_page ){
				echo '<li class="active"><span>'.($i+1).'</span></li> ';
				continue;
			}
			self::PaginationLink($slug, ($i+1), $query, $page_key, $attr, $i);
		}

		// next
		if( ($current_page+1) < $total_pages ){
			self::PaginationLink($slug, '&raquo;', $query, $page_key, $attr, $current_page+1);
		}else{
			echo '<li class="disabled"><span>&raquo;</span></li>';
		}

		echo '</ul>';
	}

	public static function PaginationLink($slug, $label, $query, $page_key, $attr, $page){

		if( $page > 0){
			$query .= '&'.$page_key.'='.$page;
		}

		echo '<li>'.\gp\tool::Link($slug,$label,$query,$attr).'</li>';
	}


	/**
	 * Remove duplicate matches
	 *
	 */
	public function RemoveDups(){
		$links = array();
		foreach($this->results as $key => $result){

			$link	= isset($result['url']) ? $result['url'] : \gp\tool::GetUrl( $result['slug'], $result['query'] );
			$link	= mb_strtolower($link);

			if( in_array($link,$links) ){
				unset($this->results[$key]);
			}else{
				$links[] = $link;
			}
		}
	}


	public function Sort($resulta,$resultb){
		return $resulta['strength'] < $resultb['strength'];
	}

	public function SearchPattern(){
		$query = mb_strtolower($_REQUEST['q']);
		// Search for the exact query when it is doubled quoted
		if (substr($query, 0, 1) == '"' && substr($query, -1) == '"') {
			$query = substr($query, 1, -1);
			$words = array($query);
		} else {
			preg_match_all("/\S+/", $query, $words);
			$words = array_unique($words[0]);
		}

		$sub_pattern1 = $sub_pattern2 = array();
		foreach($words as $word){
			$sub_pattern1[] = '\b'.preg_quote($word,'#').'\b';
			$sub_pattern2[] = preg_quote($word,'#');
		}

		$this->search_pattern = '#(?:('.implode('|',$sub_pattern1).')|('.implode('|',$sub_pattern2).'))#Si';
	}

	public function Admin(){

		if( !\gp\tool::LoggedIn() ){
			return false;
		}
		$this->page->admin_links[] = array('special_gpsearch','Configuration','cmd=config','data-cmd="gpabox"');
		$cmd = \gp\tool::GetCommand();

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

	/**
	 * Get the search configuration
	 *
	 */
	public function GetConfig(){
		$this->search_config 	= \gp\tool\Files::Get($this->config_file,'search_config');
		$this->search_config	+= array('search_hidden'=>false);
	}

	public function SaveConfig(){
		global $langmessage;

		if( isset($_POST['search_hidden']) ){
			$search_config['search_hidden'] = true;
		}else{
			$search_config['search_hidden'] = false;
		}

		if( \gp\tool\Files::SaveData($this->config_file,'search_config',$search_config) ){
			msg($langmessage['SAVED']);
			$this->search_config = $search_config;
			return true;
		}

		msg($langmessage['OOPS']);
		$this->Config($_POST);
		return false;

	}


	public function Config($array=array()){
		global $langmessage, $addonFolderName, $gp_index;


		echo '<h2>Search Configuration</h2>';

		echo '<form class="renameform" action="'.\gp\tool::GetUrl('special_gpsearch').'" method="post">';
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


	public function SearchPages(){
		global $gp_index;

		ob_start();
		foreach($gp_index as $title => $index){
			if( \gp\tool::SpecialOrAdmin($title) === false ){
				$this->SearchPage($title,$index);
			}
		}
		ob_get_clean();
	}


	public function SearchPage($title,$index){
		global $gp_menu, $gp_titles;

		//search hidden?
		if( !$this->search_hidden && !isset($gp_menu[$index]) ){
			return;
		}

		//private pages
		if( !\gp\tool::LoggedIn() ){

			if( isset($gp_titles[$index]['vis']) ){
				return;
			}
		}


		$full_path			= \gp\tool\Files::PageFile($title);
		$file_sections		= \gp\tool\Files::Get($full_path,'file_sections');

		if( !$file_sections ){
			return;
		}

		$content			= \gp\tool\Output\Sections::Render($file_sections,$title,\gp\tool\Files::$last_stats);
		$label				= \gp\tool::GetLabel($title);

		$this->FindString($content, $label, $title);
	}


	public function FindString(&$content, $label, $slug, $link_query = ''){
		$this->search_count++;

		//search all of the content include html
		$content= mb_strtolower($content);
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

}
