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
		echo ' gpEasy is licensed under version 2 of the <a href="http://www.gnu.org/licenses/gpl-2.0.html">GNU General Public License</a>. ';
		echo '</p>';


		echo '<h3>Thanks For Using gpEasy</h3>';
		echo '<p>';
		echo 'Thanks for using gpEasy CMS. We\'ve worked very hard to find a balance between the ease of use and functionality in Content Management Systems and we think we\'ve done a pretty good job. ';
		echo ' You may agree or disagree though, and the only way for us to know is to hear from you. ';
		echo ' We want to know what you think. Here\'s how:';
		echo '</p>';

		echo '<b>Does gpEasy Work?</b>';
		echo '<p>Obviously the first step is to get gpEasy working correctly.';
		echo ' If it\'s not working for you and you think it\'s because of a bug, you can <a href="https://sourceforge.net/tracker/?group_id=264307&amp;atid=1127698">report it</a> and we\'ll work on fixing it.';
		echo '</p>';

		echo '<b>Does gpEasy Work Well?</b>';
		echo '<p>This one is a bit more subjective, but just as important.';
		echo ' There are multiple ways to give us feedback. The following services allow you to rate and comment on gpEasy. ';
		echo '</p>';
		echo '<ul>';
		echo '<li><a href="http://php.opensourcecms.com/scripts/details.php?scriptid=360&amp;name=gpEasy%20CMS">OpensourceCMS.com</a></li>';
		echo '<li><a href="http://freshmeat.net/projects/gpeasy">Freshmeat.net</a></li>';
		echo '<li><a href="https://sourceforge.net/projects/gpeasy/">Sourceforge.net</a></li>';
		echo '</ul>';

		echo '<h3>Credits</h3>';
		echo '<p>';
		echo 'gpEasy is made possible by the open source project hosted at <a href="https://sourceforge.net/projects/gpeasy/">Sourceforge.net</a> and the many successful GPL projects we\'ve taken inspiration from. ';
		echo '</p>';
		echo '<p>';
		echo 'The <a href="http://www.gnu.org/licenses/gpl-2.0.html">GPL</a> from the Free Software Foundation is the license that the gpEasy software is under.';
		echo '</p>';
		echo '</div>';

	}

}


