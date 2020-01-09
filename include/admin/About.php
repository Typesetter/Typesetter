<?php

namespace gp\admin;

defined('is_running') or die('Not an entry point...');

class About{

	function __construct(){

		echo '<div style="min-width:600px;width:75%;">';

		echo '<h2>'.\CMS_NAME.' CMS</h2>';

		echo '<hr/>';


		echo '<p>You\'re currently using version '.\gpversion.' of our free, open source and easy to use content management system.';
		echo ' Our code is <a href="https://github.com/Typesetter/Typesetter">hosted on GitHub</a> ';
		echo 'and licensed under version 2 of the <a href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html">GNU General Public License</a>. ';
		echo '</p>';

		echo '<br/>';



		echo '<h3>Thanks For Using '.\CMS_NAME.'</h3>';
		echo '<hr/>';

		echo '<p>';
		echo 'We\'ve worked very hard to find a balance between the ease of use and functionality in Content Management Systems and we think we\'ve done a pretty good job. ';
		echo ' You may agree or disagree though, and the only way for us to know is to hear from you. ';
		echo ' We want to know what you think. Here\'s how:';
		echo '</p>';

		echo '<i>Does '.\CMS_NAME.' Work?</i>';
		echo '<p>Obviously the first step is to get '.\CMS_NAME.' working correctly.';
		echo ' If it\'s not working for you and you think it\'s because of a bug, you can <a href="https://github.com/Typesetter/Typesetter/issues">report it</a> and we\'ll work on fixing it.';
		echo '</p>';

		echo '<i>Does '.\CMS_NAME.' Work Well?</i>';
		echo '<p>This one is a bit more subjective, but just as important. ';
		echo ' There are multiple ways to give us feedback. The following services allow you to rate and comment on '.\CMS_NAME.'. ';
		echo '</p>';
		echo '<p><a href="https://github.com/Typesetter/Typesetter" target="_blank">Fork on GitHub</a></p>';
		echo '<p><a href="https://www.opensourcecms.com/typesetter-cms/" target="_blank">OpensourceCMS.com</a></p>';


		$projects					= [];
		$projects['ckEditor']		= 'ckeditor.com/ckeditor-4';
		$projects['elFinder']		= 'studio-42.github.io/elFinder';
		$projects['ColorBox']		= 'www.jacklmoore.com/colorbox';
		$projects['Bootstrap']		= 'getbootstrap.com';
		$projects['jQuery']			= 'jquery.com';
		$projects['jQuery UI']		= 'jqueryui.com';
		$projects['ScssPhp']		= 'https://github.com/leafo/scssphp';
		$projects['PHPMailer']		= 'github.com/PHPMailer/PHPMailer';


		echo '<br/>';
		echo '<h3>Our Thanks</h3>';
		echo '<hr/>';
		echo '<p>'.\CMS_NAME.' would not have been possible if it wasn\'t for the prosperous open source community and rich selection of successful open source projects. ';
		echo ' We have benefited tremendously from the community and have borrowed ideas as well as integrated other freely available code. ';
		echo ' Here are some of the projects we have benefited the most from. ';
		echo '</p>';

		echo '<table class="bordered"><tr><th>Project</th><th>Website</th></tr>';
		foreach($projects as $name => $url){
			echo '<tr><td>';
			echo $name;
			echo '</td><td>';
			echo '<a target="_blank" href="https://'.$url.'">'.$url.'</a>';
			echo '</td></tr>';
		}
		echo '</table>';

		echo '</div>';
	}

}
