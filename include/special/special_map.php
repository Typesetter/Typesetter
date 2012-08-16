<?php
defined('is_running') or die('Not an entry point...');


class special_map{
	function special_map(){
		global $page,$langmessage,$config;


		/*
		An xml site map will not show any of the pages from dynamic add-ons
		... which is precisely what the regular sitemap shows
		*/

		if( isset($_GET['xml']) ){
			$this->xml();
			return;
		}

		$this->MultiSiteData();

		echo '<div class="sitemap_xml">';
		echo common::Link('Special_Site_Map','XML','xml');
		echo '</div>';
		echo '<h2>';
		echo gpOutput::ReturnText('site_map');
		echo '</h2>';


		gpOutput::GetFullMenu();

	}

	function MultiSiteData(){
		global $page, $config;

		$page->head .= '<meta name="mdu" content="'.substr(md5($config['gpuniq']),0,20).'" />';

		if( defined('multi_site_unique') ){
			$page->head .= '<meta name="multi_site_unique" content="'.multi_site_unique.'" />';
		}
		if( defined('service_provider_id') && is_numeric(service_provider_id) ){
			$page->head .= '<meta name="service_provider_id" content="'.service_provider_id.'" />';
		}
	}


	/*
	<url>
	    <loc>http://www.example.com/</loc>
	    <lastmod>2005-01-01</lastmod>
	    <changefreq>monthly</changefreq>
	    <priority>0.8</priority>
	</url>
	*/
	function xml(){
		global $gp_menu;

		header('Content-Type: text/xml; charset=UTF-8');
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';


		foreach($gp_menu as $key => $info){

			$title = common::IndexToTitle($key);

			if( isset($info['level']) ){
				echo "\n";
				echo '<url>';
				echo '<loc>';
				echo 'http://';
				echo $_SERVER['SERVER_NAME'];
				echo common::GetUrl(urlencode($title));
				echo '</loc>';
				echo '</url>';
			}
		}

		echo '</urlset>';


		die();
	}
}
