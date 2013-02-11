<?php
/**
 * Smallify - PHP class to minify and cache HTML, JS and CSS on the fly
 * 
 * Copyright (c) 2013  Andrew Lim, Lim Industries. All rights reserved.
 *
 * <pre>
 *   Permission is hereby granted, free of charge, to any person obtaining 
 *   a copy of this software and associated documentation files (the 'Software'), 
 *   to deal in the Software without restriction, including without limitation 
 *   the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 *   and/or sell copies of the Software, and to permit persons to whom the 
 *   Software is furnished to do so, subject to the following conditions:
 *   
 *   The above copyright notice and this permission notice shall be included in 
 *   all copies or substantial portions of the Software.
 *   
 *   THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
 *   THE SOFTWARE.
 * </pre>
 * 
 * With an object of this class you may lookup a Gravatar and cache the images locally. 
 * 
 * Example:
 * <code>
 * $smallify = Smallify::getInstance();
 * $smallify = $smallify->minify_html($html_output);
 * </code>
 * 
 * By default caching is not enabled, to enable it you wil have to set the cache
 * directory.
 * 
 * Example:
 * <code>
 * $minitise = $minitise->setCacheDir("cache/");
 * </code>
 * 
 * @todo 		find way not to cache
 * @copyright 	Andrew Lim 2013
 * @author 		Andrew Lim <hiya@andrew-lim.net>
 * @version 	1.1
 * @credit 		http://stackoverflow.com/questions/3095424/minify-html-php
 */

 
/**
 * Class Smallify
 * Provide xxx
 */
class Smallify
{
	const CACHE_PREFIX = "SMALLIFY_";
	
	private $_cache_dir = null;
	private $_use_cache = false;
	
	# Holds instance
	private static $_instance;
	
	# Singelton-patterned class. No need to make an instance of this object 
	# outside it self. 
	private function __construct()
	{
		
	}
	
	/**
	 * Get new instance of this object.
	 */
	public static function getInstance()
	{
		if (!isset(self::$_instance))
		{
			$class = __CLASS__;
			self::$_instance = new $class;
		}
		
		return self::$_instance;
	}
	
	/**
	 * Sets the cache directory and sets use_cache to true
	 * 
	 * @param string $dir
	 */
	public function setCacheDir($dir)
	{
		$this->_cache_dir = $dir;
		$this->_use_cache = true;
	}
	
	private function minify_css($text)
	{
		$from   = array(
	        //                  '%(#|;|(//)).*%',               // comments:  # or //
	            '%/\*(?:(?!\*/).)*\*/%s',       // comments:  /*...*/
	            '/\s{2,}/',                     // extra spaces
	            "/\s*([;{}])[\r\n\t\s]/",       // new lines
	            '/\\s*;\\s*/',                  // white space (ws) between ;
	            '/\\s*{\\s*/',                  // remove ws around {
	            '/;?\\s*}\\s*/',                // remove ws around } and last semicolon in declaration block
	            //                  '/:first-l(etter|ine)\\{/',     // prevent triggering IE6 bug: http://www.crankygeek.com/ie6pebug/
	        //                  '/((?:padding|margin|border|outline):\\d+(?:px|em)?) # 1 = prop : 1st numeric value\\s+/x',     // Use newline after 1st numeric value (to limit line lengths).
	        //                  '/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i',
	        );
	        $to     = array(
	        //                  '',
	            '',
	            ' ',
	            '$1',
	            ';',
	            '{',
	            '}',
	            //                  ':first-l$1 {',
	        //                  "$1\n",
	        //                  '$1#$2$3$4$5',
	        );
	        $text   = preg_replace($from,$to,$text);
	        return $text;
	    }
	
	private function minify_js($text)
	{
	        $file_cache     = self::CACHE_PREFIX.strtolower(md5($text));
	        $folder         = $this->_cache_dir;//.substr($file_cache,0,2).DIRECTORY_SEPARATOR;
	        if(!is_dir($folder))            @mkdir($folder, 0766, true);
	        if(!is_dir($folder)){
	            echo 'Impossible to create the cache folder:'.$folder;
	            return 1;
	        }
	        $file_cache     = $folder.$file_cache.'_content.js';
	        if(!file_exists($file_cache)){
	            if(strlen($text)<=100){
	                $contents = $text;
	            } else {
	                $contents = '';
	                $post_text = http_build_query(array(
	                                'js_code' => $text,
	                                'output_info' => 'compiled_code',//($returnErrors ? 'errors' : 'compiled_code'),
	                                'output_format' => 'text',
	                                'compilation_level' => 'SIMPLE_OPTIMIZATIONS',//'ADVANCED_OPTIMIZATIONS',//'SIMPLE_OPTIMIZATIONS'
	                            ), null, '&');
	                $URL            = 'http://closure-compiler.appspot.com/compile';
	                $allowUrlFopen  = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
	                if($allowUrlFopen){
	                    $contents = file_get_contents($URL, false, stream_context_create(array(
	                            'http'          => array(
	                                'method'        => 'POST',
	                                'header'        => 'Content-type: application/x-www-form-urlencoded',
	                                'content'       => $post_text,
	                                'max_redirects' => 0,
	                                'timeout'       => 15,
	                            )
	                    )));
	                }elseif(defined('CURLOPT_POST')) {
	                    $ch = curl_init($URL);
	                    curl_setopt($ch, CURLOPT_POST, true);
	                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
	                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_text);
	                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	                    $contents = curl_exec($ch);
	                    curl_close($ch);
	                } else {
	                    //"Could not make HTTP request: allow_url_open is false and cURL not available"
	                    $contents = $text;
	                }
	                if($contents==false || (trim($contents)=='' && $text!='') || strtolower(substr(trim($contents),0,5))=='error' || strlen($contents)<=50){
	                    //No HTTP response from server or empty response or error
	                    $contents = $text;
	                }
	            }
	            if(trim($contents)!=''){
	                $contents = trim($contents);
	                $f = fopen($file_cache, 'w');
	                fwrite($f, $contents);
	                fclose($f);
	            }
	        } else {
	            touch($file_cache);     //in the future I will add a timetout to the cache
	            $contents = file_get_contents($file_cache);
	        }
	        return $contents;
	    }
		
		public function minify_html($text)
		{
			if(isset($_GET['no_mini']))
			{
				return $text;
	        }
	        $file_cache     = self::CACHE_PREFIX.strtolower(md5($text));
	        $folder         = $this->_cache_dir;//.substr($file_cache,0,2).DIRECTORY_SEPARATOR;
	        if(!is_dir($folder))            @mkdir($folder, 0766, true);
	        if(!is_dir($folder))
			{
	            echo 'Impossible to create the cache folder:'.$folder;
	            return 1;
	        }
	        $file_cache     = $folder.$file_cache.'_content.html';
	        if(!file_exists($file_cache)){
	            //get CSS and save it
	            $search_css = '/<\s*style\b[^>]*>(.*?)<\s*\/style>/is';
	            $ret = preg_match_all($search_css, $text, $tmps);
	            $t_css = array();
	            if($ret!==false && $ret>0){
	                foreach($tmps as $k=>$v){
	                    if($k>0){
	                        foreach($v as $kk=>$vv){
	                            $t_css[] = $vv;
	                        }
	                    }
	                }
	            }
	            $css = self::minify_css(implode('\n', $t_css));
	
	/*
	            //get external JS and save it
	            $search_js_ext = '/<\s*script\b.*?src=\s*[\'|"]([^\'|"]*)[^>]*>\s*<\s*\/script>/i';
	            $ret = preg_match_all($search_js_ext, $text, $tmps);
	            $t_js = array();
	            if($ret!==false && $ret>0){
	                foreach($tmps as $k=>$v){
	                    if($k>0){
	                        foreach($v as $kk=>$vv){
	                            $t_js[] = $vv;
	                        }
	                    }
	                }
	            }
	            $js_ext = $t_js;
	*/
	            //get inline JS and save it
	            $search_js_ext  = '/<\s*script\b.*?src=\s*[\'|"]([^\'|"]*)[^>]*>\s*<\s*\/script>/i';
	            $search_js      = '/<\s*script\b[^>]*>(.*?)<\s*\/script>/is';
	            $ret            = preg_match_all($search_js, $text, $tmps);
	            $t_js           = array();
	            $js_ext         = array();
	            if($ret!==false && $ret>0){
	                foreach($tmps as $k=>$v){
	                    if($k==0){
	                        //let's check if we have a souce (src="")
	                        foreach($v as $kk=>$vv){
	                            if($vv!=''){
	                                $ret = preg_match_all($search_js_ext, $vv, $ttmps);
	                                if($ret!==false && $ret>0){
	                                    foreach($ttmps[1] as $kkk=>$vvv){
	                                        $js_ext[] = $vvv;
	                                    }
	                                }
	                            }
	                        }
	                    } else {
	                        foreach($v as $kk=>$vv){
	                            if($vv!=''){
	                                $t_js[] = $vv;
	                            }
	                        }
	                    }
	                }
	            }
	            $js = self::minify_js(implode('\n', $t_js));
	
	            //get inline noscript and save it
	            $search_no_js = '/<\s*noscript\b[^>]*>(.*?)<\s*\/noscript>/is';
	            $ret = preg_match_all($search_no_js, $text, $tmps);
	            $t_js = array();
	            if($ret!==false && $ret>0){
	                foreach($tmps as $k=>$v){
	                    if($k>0){
	                        foreach($v as $kk=>$vv){
	                            $t_js[] = $vv;
	                        }
	                    }
	                }
	            }
	            $no_js = implode('\n', $t_js);
	
	            //remove CSS and JS
	            $search = array(
	                $search_js_ext,
	                $search_css,
	                $search_js,
	                $search_no_js,
	                '/\>[^\S ]+/s', //strip whitespaces after tags, except space
	                '/[^\S ]+\</s', //strip whitespaces before tags, except space
	                '/(\s)+/s',  // shorten multiple whitespace sequences
	            );
	            $replace = array(
	                '',
	                '',
	                '',
	                '',
	                '>',
	                '<',
	                '\\1',
	            );
	            $buffer = preg_replace($search, $replace, $text);
	
	            $append = '';
	            //add CSS and JS at the bottom
	            if(is_array($js_ext) && count($js_ext)>0){
	                foreach($js_ext as $k=>$v){
	                    $append .= '<script type="text/javascript" language="javascript" src="'.$v.'" ></script>';
	                }
	            }
	            if($css!='')        $append .= '<style>'.$css.'</style>';
	            if($js!=''){
	                //remove weird '\n' strings
	                $js = preg_replace('/[\s]*\\\n/', "\n", $js);
	                $append .= '<script>'.$js.'</script>';
	            }
	            if($no_js!='')      $append .= '<noscript>'.$no_js.'</noscript>';
	            $buffer = preg_replace('/(.*)(<\s*\/\s*body\s*>)(.*)/','\\1'.$append.'\\2\\3', $buffer);
	            if(trim($buffer)!=''){
	                $f = fopen($file_cache, 'w');
	                fwrite($f, trim($buffer));
	                fclose($f);
	            }
	        } else {
	            touch($file_cache);     //in the future I will add a timetout to the cache
	            $buffer = file_get_contents($file_cache);
	        }
	
	        return $buffer;
	    }
	    
	    public function shrink($file)
	    {
	    	$content = $this->buffer_include($file);
	    	
	    	return $this->minify_html($content);
	    }
	    
	    private function buffer_include($file)
	    {
	    	ob_start();
	    	include $file;
	    	return ob_get_clean();
	    }
}