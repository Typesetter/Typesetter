<?php
defined('is_running') or die('Not an entry point...');

class admin_about{

	function admin_about(){

		echo '<div style="width:600px;padding:0 20px;">';
		echo '<h2>gpEasy CMS Version '.gpversion.'</h2>';
		echo '<p>';
		echo 'Free, Open Source and easy to use.';
		echo '</p>';
		echo '<p>';
		echo ' The gpEasy CMS code is <a href="https://github.com/oyejorge/gpEasy-CMS">hosted on github</a> ';
		echo 'and licensed under version 2 of the <a href="http://www.gnu.org/licenses/gpl-2.0.html">GNU General Public License</a>. ';
		echo '</p>';


		echo '<h3>Thanks For Using gpEasy</h3>';
		echo '<p>';
		echo 'Thanks for using gpEasy CMS. We\'ve worked very hard to find a balance between the ease of use and functionality in Content Management Systems and we think we\'ve done a pretty good job. ';
		echo ' You may agree or disagree though, and the only way for us to know is to hear from you. ';
		echo ' We want to know what you think. Here\'s how:';
		echo '</p>';

		echo '<i>Does gpEasy Work?</i>';
		echo '<p>Obviously the first step is to get gpEasy working correctly.';
		echo ' If it\'s not working for you and you think it\'s because of a bug, you can <a href="https://github.com/oyejorge/gpEasy-CMS/issues">report it</a> and we\'ll work on fixing it.';
		echo '</p>';

		echo '<i>Does gpEasy Work Well?</i>';
		echo '<p>This one is a bit more subjective, but just as important.';
		echo ' There are multiple ways to give us feedback. The following services allow you to rate and comment on gpEasy. ';
		echo '</p>';
		echo '<ul>';
		echo '<li><a href="<a href="https://github.com/oyejorge/gpEasy-CMS">Fork on Github</a></li>';
		echo '<li><a href="http://php.opensourcecms.com/scripts/details.php?scriptid=360&amp;name=gpEasy%20CMS">OpensourceCMS.com</a></li>';
		echo '<li><a href="http://freshmeat.net/projects/gpeasy">Freshmeat.net</a></li>';
		echo '<li><a href="https://sourceforge.net/projects/gpeasy/">Sourceforge.net</a></li>';
		echo '</ul>';


		$projects['ckEditor']		= 'ckeditor.com';
		$projects['elFinder']		= 'elfinder.org';
		$projects['ColorBox']		= 'colorpowered.com/colorbox/';
		$projects['Bootstrap']		= 'twitter.github.io/bootstrap/';
		$projects['ArchiveTar']		= 'pear.php.net/manual/en/package.filesystem.archive-tar.php';
		$projects['jQuery'] 		= 'jquery.com';
		$projects['jQuery UI'] 		= 'jqueryui.com';
		$projects['Pcl Zip'] 		= 'phpconcept.net/pclzip/';
		$projects['PHPMailer'] 		= 'github.com/Synchro/PHPMailer';


		echo '<h3>Our Thanks</h3>';
		echo '<p>gpEasy would not have been possible if it wasn\'t for the prosperous open source community and rich selection of successful open source projects. ';
		echo ' We have benefited tremendously from the community and have borrowed ideas as well as integrated other freely available code. ';
		echo ' Here are some of the projects we have benefited the most from. ';
		echo '</p>';

		echo '<table class="bordered"><tr><th>Project</th><th>Website</th></tr>';
		foreach($projects as $name => $url){
			echo '<tr><td>';
			echo $name;
			echo '</td><td>';
			echo '<a href="http://'.$url.'">'.$url.'</a>';
			echo '</td></tr>';
		}
		echo '</table>';

		echo '</div>';

	}

}


