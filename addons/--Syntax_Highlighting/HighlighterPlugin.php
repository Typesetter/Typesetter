<?php
defined('is_running') or die('Not an entry point...');


class HighlighterPlugin{

	/**
	 * Add the ckeditor plugin
	 *
	 */
	static function CKEditorPlugins($plugins){
		global $addonRelativeCode;
		trigger_error('ckeditor plugins');
		$plugins['syntaxhighlight'] = $addonRelativeCode.'/syntaxhighlight/';
		return $plugins;
	}


	/**
	 * Add syntax highlighting to the page
	 * Check for <pre class="brush:jscript;">.. php...
	 * Add the appropriate js and css files
	 *
	 */
	static function CheckContent(){
		global $page, $addonRelativeCode;

		$content = ob_get_contents();

		$avail_brushes['css'] = 'shBrushCss.js';
		$avail_brushes['diff'] = 'shBrushDiff.js';
		$avail_brushes['ini'] = 'shBrushIni.js';
		$avail_brushes['jscript'] = 'shBrushJScript.js';
		$avail_brushes['php'] = 'shBrushPhp.js';
		$avail_brushes['plain'] = 'shBrushPlain.js';
		$avail_brushes['sql'] = 'shBrushSql.js';
		$avail_brushes['xml'] = 'shBrushXml.js';

		$brushes = array();

		preg_match_all('#<pre[^<>]*>#',$content,$matches);
		if( !count($matches) ){
			return;
		}
		foreach($matches[0] as $match){
			preg_match('#class=[\'"]([^\'"]+)[\'"]#',$match,$classes);
			if( !isset($classes[1]) ){
				continue;
			}

			preg_match('#brush:([^;\'"]+)[;"\']?#',$match,$type);
			if( !isset($type[1]) ){
				continue;
			}

			$type = strtolower(trim($type[1]));

			if( !isset($avail_brushes[$type]) ){
				continue;
			}

			$brushes[] = $avail_brushes[$type];
		}

		if( !count($brushes) ){
			return;
		}

		$config = gpPlugin::GetConfig();
		$theme =& $config['theme'];

		$page->head .= "\n\n";
		$page->head .= '<link rel="stylesheet" type="text/css" href="'.$addonRelativeCode.'/syntaxhighlighter/styles/shCore.css" />'."\n";

		$css_file = 'shThemeDefault.css';
		switch($theme){
			case 'django':
				$css_file = 'shThemeDjango.css';
			break;
			case 'eclipse':
				$css_file = 'shThemeEclipse.css';
			break;
			case 'emacs':
				$css_file = 'shThemeEmacs.css';
			break;
			case 'fadetogrey':
				$css_file = 'shThemeFadeToGrey.css';
			break;
			case 'midnight':
				$css_file = 'shThemeMidnight.css';
			break;
			case 'rdark':
				$css_file = 'shThemeRDark.css';
			break;
		}
		$page->head .= '<link rel="stylesheet" type="text/css" href="'.$addonRelativeCode.'/syntaxhighlighter/styles/'.$css_file.'" />'."\n";


		$page->head .= '<script language="javascript" type="text/javascript" src="'.$addonRelativeCode.'/syntaxhighlighter/scripts/shCore.js"></script>'."\n";
		foreach($brushes as $brush){
			$page->head .= '<script language="javascript" type="text/javascript" src="'.$addonRelativeCode.'/syntaxhighlighter/scripts/'.$brush.'"></script>'."\n";
		}
		$page->jQueryCode .= "\nSyntaxHighlighter.all();\n";
	}
}
