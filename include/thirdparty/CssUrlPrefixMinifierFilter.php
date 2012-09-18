<?php


class CssUrlPrefixMinifierFilter extends aCssMinifierFilter {

	var $import_tokens = array();
	var $imported_tokens = array();
	var $tokens = array();

	/**
	 * Implements {@link aCssMinifierPlugin::minify()}.
	 *
	 * @param aCssToken $token Token to process
	 * @return boolean Return TRUE to break the processing of this token; FALSE to continue
	 */
	public function apply(array &$tokens){

		foreach($tokens as $key => $token){

			switch(get_class($token)){
				case 'CssAtFontFaceDeclarationToken':
					$this->FixUrl($token);
				break;
				case 'CssRulesetDeclarationToken':
					$this->FixUrl($token);
				break;
				case 'CssAtImportToken':
					$this->Import($token);
				continue 2;
			}
			$this->tokens[] = $token;
		}

		$tokens = array_merge($this->import_tokens,$this->imported_tokens,$this->tokens);
	}


	function Import($token){

		//make sure the file exists
		$file_path = realpath($this->configuration['BasePath'].'/'.$token->Import);
		$token->Import = $this->FixPath($token->Import);
		if( !$file_path ){
			$this->import_tokens[] = $token;
			return;
		}

		//check the media type
		if( count($token->MediaTypes) && !in_array('screen',$token->MediaTypes) && !in_array('all',$token->MediaTypes) ){
			$this->import_tokens[] = $token;
			return;
		}

		$content = file_get_contents($file_path);
		if( !$content ){
			$this->import_tokens[] = $token;
			return;
		}

		$filters = array(
					'UrlPrefix' => array( 'BaseUrl' => $token->Import, 'BasePath' => dirname($file_path) )
					);
		$minifier = new CssMinifier(null, $filters);
		$tokens = $minifier->minifyTokens($content);
		$token->Imported = $file_path;
		$this->import_tokens[] = $token;
		$this->imported_tokens = array_merge($this->imported_tokens,$tokens);
	}

	function FixUrl(&$token){
		$offset = 0;
		do{
			$url = $token->Value;
			$pos = strpos($url,'url(',$offset);
			if( !is_numeric($pos) ){
				return;
			}
			$pos += 4;
			$pos2 = strpos($url,')',$pos);
			if( !is_numeric($pos2) ){
				return;
			}
			$url = substr($url,$pos,$pos2-$pos);
			$url = trim($url);
			$url = trim($url,'"\'');
			$replacement = $this->FixPath($url);

			$replacement = '"'.$replacement.'"';
			$token->Value = substr_replace($token->Value,$replacement,$pos,$pos2-$pos);
			$offset = $pos2;
		}while( true );
	}


	function FixPath($path){


		//relative url
		if( $path{0} == '/' ){
			return $path;
		}elseif( strpos($path,'://') > 0 ){
			return $path;
		}elseif( preg_match('/^data:/i', $path) ){
			return $path;
		}

		//use a relative path so sub.domain.com and domain.com/sub both work
		// ../.. for /data/_cache/
		$replacement = '../..'.dirname($this->configuration['BaseUrl']).'/'.$path;
		return $this->ReduceUrl($replacement);

	}


	/**
	 * Canonicalize a path by resolving references to '/./', '/../'
	 * Does not remove leading "../"
	 * @param string path or url
	 * @return string Canonicalized path
	 *
	 */
	function ReduceUrl($url){

		$temp = explode('/',$url);
		$result = array();
		foreach($temp as $i => $path){
			if( $path == '.' ){
				continue;
			}
			if( $path == '..' ){
				for($j=$i-1;$j>0;$j--){
					if( isset($result[$j]) ){
						unset($result[$j]);
						continue 2;
					}
				}
			}
			$result[$i] = $path;
		}

		return implode('/',$result);
	}

}

