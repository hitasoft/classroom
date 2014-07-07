<?php
//ini_set("session.gc_maxlifetime", "18000");
$root_dir=$_SERVER["DOCUMENT_ROOT"];
//if (substr($root_dir,-9)!='classroom') $root_dir.="/classroom";

define('LIVE_DIR',$root_dir);

function user_dir($ul,$fn='',$tmp=true,$root=true){
   global $root_dir,$liveDir;
   if ($fn==$ul){
      $fn="pp_".$ul.".jpg";
      $tmp=false;
   }
   $dir=($tmp)?"_tmp":substr($ul,0,1)."/".$ul;  // temp dir is _tmp/; main is u/user/
   if ($fn && $tmp) $fn=$ul."_".$fn;   // temp files are named user_*
   $prefix=($root)?$root_dir:$liveDir;    // return full host path if $root
   return ($prefix."/users/".$dir.(($fn)?"/".$fn:""));
}
function formatBytes($bytes, $precision = 2) {
    $units = array('b', 'k', 'Mb', 'Gb', 'Tb');
 
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
 
    $bytes /= pow(1024, $pow);
    if ($pow<2) $precision=0;
 
    return round($bytes, $precision) . '' . $units[$pow];
}
function array_values_recursive($array) {
   $flat = array();
   if ($array){
      foreach ($array as $value) {
              if (is_array($value)) $flat = array_merge($flat, array_values_recursive($value));
              else $flat[] = $value;
      }
   }
   return $flat;
}
function checkValues($value){
   $value = trim($value);
   if (get_magic_quotes_gpc()) {
      $value = stripslashes($value);
   }
   $value = strtr($value, array_flip(get_html_translation_table(HTML_ENTITIES)));
   $value = strip_tags($value);
   $value = htmlspecialchars($value);
   return $value;
}	
function sortArrayByField($original,$field,$descending = false){
   $sortArr = array();
   foreach ( $original as $key => $value ){
      $sortArr[ $key ] = $value[ $field ];
   }
   if ( $descending ){
      arsort( $sortArr );
   }else{
      asort( $sortArr );
   }  
   $resultArr = array();
   foreach ( $sortArr as $key => $value ){
      $resultArr[ $key ] = $original[ $key ];
   }
   return $resultArr;
}           
function html2txt($document){
   $search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
                  '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
                  '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
                  '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
   );
   $text = preg_replace($search, ' ', $document);
   return $text;
}
function autoVer($url){
    $path = pathinfo($url);
    if (file_exists($_SERVER['DOCUMENT_ROOT'].$url)){
      $ver = '.'.filemtime($_SERVER['DOCUMENT_ROOT'].$url).'.';
    }else{
      $ver = '.';
      trigger_error($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],E_USER_WARNING);
    }
    echo $path['dirname'].'/'.str_replace('.', $ver, $path['basename']);
}
function txt2lnk($text){
   // convert any urls to links and newlines to line break tags
   $validTlds = array_fill_keys(explode(" ", ".aero .asia .biz .cat .com .coop .edu .gov .info .int .jobs .mil .mobi .museum .name .net .org .pro .tel .travel .ac .ad .ae .af .ag .ai .al .am .an .ao .aq .ar .as .at .au .aw .ax .az .ba .bb .bd .be .bf .bg .bh .bi .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cc .cd .cf .cg .ch .ci .ck .cl .cm .cn .co .cr .cu .cv .cx .cy .cz .de .dj .dk .dm .do .dz .ec .ee .eg .er .es .et .eu .fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gp .gq .gr .gs .gt .gu .gw .gy .hk .hm .hn .hr .ht .hu .id .ie .il .im .in .io .iq .ir .is .it .je .jm .jo .jp .ke .kg .kh .ki .km .kn .kp .kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mk .ml .mm .mn .mo .mp .mq .mr .ms .mt .mu .mv .mw .mx .my .mz .na .nc .ne .nf .ng .ni .nl .no .np .nr .nu .nz .om .pa .pe .pf .pg .ph .pk .pl .pm .pn .pr .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si .sj .sk .sl .sm .sn .so .sr .st .su .sv .sy .sz .tc .td .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .tt .tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .ye .yt .yu .za .zm .zw .xn--0zwm56d .xn--11b5bs3a9aj6g .xn--80akhbyknj4f .xn--9t4b11yi5a .xn--deba0ad .xn--g6w251d .xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--jxalpdlp .xn--kgbechtv .xn--zckzah .arpa"), true);

   $position = 0;
   $rexProtocol = '(https?://)?';
   $rexDomain   = '((?:[-a-zA-Z0-9]{1,63}\.)+[-a-zA-Z0-9]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})';
   $rexPort     = '(:[0-9]{1,5})?';
   $rexPath     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
   $rexQuery    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
   $rexFragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
   while (preg_match("{\\b$rexProtocol$rexDomain$rexPort$rexPath$rexQuery$rexFragment(?=[?.!,;:\"]?(\s|$))}", $text, $match, PREG_OFFSET_CAPTURE, $position))
   {
       list($url, $urlPosition) = $match[0];

       // Print the text leading up to the URL.
       $ptext=htmlspecialchars(substr($text, $position, $urlPosition - $position));

       $protocol=$match[1][0];
       $domain = $match[2][0];
       $port   = $match[3][0];
       $path   = $match[4][0];

       // Check if the TLD is valid - or that $domain is an IP address.
       $tld = strtolower(strrchr($domain, '.'));
       if (preg_match('{\.[0-9]{1,3}}', $tld) || isset($validTlds[$tld]))
       {
           // Prepend http:// if no protocol specified
           $completeUrl = $match[1][0] ? $url : "http://$url";

           // Print the hyperlink.
           $ptext.=sprintf('<a href="%s" rel="nofollow" target="_blank">%s</a>', htmlspecialchars($completeUrl), htmlspecialchars("$protocol$domain$port$path"));
       }
       else
       {
           // Not a valid URL.
           $ptext.=htmlspecialchars($url);
       }

       // Continue text parsing from after the URL.
       $position = $urlPosition + strlen($url);
   }

   // Print the remainder of the text.
   $ptext.=htmlspecialchars(substr($text, $position));
   return preg_replace("/\\r\\n|\\r|\\n/","<br>",$ptext);
}

/**
 * extract_tags()
 * Extract specific HTML tags and their attributes from a string.
 *
 * You can either specify one tag, an array of tag names, or a regular expression that matches the tag name(s). 
 * If multiple tags are specified you must also set the $selfclosing parameter and it must be the same for 
 * all specified tags (so you can't extract both normal and self-closing tags in one go).
 * 
 * The function returns a numerically indexed array of extracted tags. Each entry is an associative array
 * with these keys :
 * 	tag_name	- the name of the extracted tag, e.g. "a" or "img".
 *	offset		- the numberic offset of the first character of the tag within the HTML source.
 *	contents	- the inner HTML of the tag. This is always empty for self-closing tags.
 *	attributes	- a name -> value array of the tag's attributes, or an empty array if the tag has none.
 *	full_tag	- the entire matched tag, e.g. '<a href="http://example.com">example.com</a>'. This key 
 *		          will only be present if you set $return_the_entire_tag to true.	   
 *
 * @param string $html The HTML code to search for tags.
 * @param string|array $tag The tag(s) to extract.							 
 * @param bool $selfclosing	Whether the tag is self-closing or not. Setting it to null will force the script to try and make an educated guess. 
 * @param bool $return_the_entire_tag Return the entire matched tag in 'full_tag' key of the results array.  
 * @param string $charset The character set of the HTML code. Defaults to ISO-8859-1.
 *
 * @return array An array of extracted tags, or an empty array if no matching tags were found. 
 */
function extract_tags( $html, $tag, $selfclosing = null, $return_the_entire_tag = false, $charset = 'ISO-8859-1' ){
	if ( is_array($tag) ){
		$tag = implode('|', $tag);
	}
	//If the user didn't specify if $tag is a self-closing tag we try to auto-detect it
	//by checking against a list of known self-closing tags.
	$selfclosing_tags = array( 'area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta', 'col', 'param' );
	if ( is_null($selfclosing) ){
		$selfclosing = in_array( $tag, $selfclosing_tags );
	}
	//The regexp is different for normal and self-closing tags because I can't figure out 
	//how to make a sufficiently robust unified one.
	if ( $selfclosing ){
		$tag_pattern = 
			'@<(?P<tag>'.$tag.')			# <tag
			(?P<attributes>\s[^>]+)?		# attributes, if any
			\s*/?>					# /> or just >, being lenient here 
			@xsi';
	} else {
		$tag_pattern = 
			'@<(?P<tag>'.$tag.')			# <tag
			(?P<attributes>\s[^>]+)?		# attributes, if any
			\s*>					# >
			(?P<contents>.*?)			# tag contents
			</(?P=tag)>				# the closing </tag>
			@xsi';
	}
	$attribute_pattern = 
		'@
		(?P<name>\w+)							# attribute name
		\s*=\s*
		(
			(?P<quote>[\"\'])(?P<value_quoted>.*?)(?P=quote)	# a quoted value
			|							# or
			(?P<value_unquoted>[^\s"\']+?)(?:\s+|$)			# an unquoted value (terminated by whitespace or EOF) 
		)
		@xsi';
	//Find all tags 
	if ( !preg_match_all($tag_pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ){
		//Return an empty array if we didn't find anything
		return array();
	}
	$tags = array();
	foreach ($matches as $match){
		//Parse tag attributes, if any
		$attributes = array();
		if ( !empty($match['attributes'][0]) ){ 
			if ( preg_match_all( $attribute_pattern, $match['attributes'][0], $attribute_data, PREG_SET_ORDER ) ){
				//Turn the attribute data into a name->value array
				foreach($attribute_data as $attr){
					if( !empty($attr['value_quoted']) ){
						$value = $attr['value_quoted'];
					} else if( !empty($attr['value_unquoted']) ){
						$value = $attr['value_unquoted'];
					} else {
						$value = '';
					}
					//Passing the value through html_entity_decode is handy when you want
					//to extract link URLs or something like that. You might want to remove
					//or modify this call if it doesn't fit your situation.
					$value = html_entity_decode( $value, ENT_QUOTES, $charset );
					$attributes[$attr['name']] = $value;
				}
			}
		}
		$tag = array(
			'tag_name' => $match['tag'][0],
			'offset' => $match[0][1], 
			'contents' => !empty($match['contents'])?$match['contents'][0]:'', //empty for self-closing tags
			'attributes' => $attributes, 
		);
		if ( $return_the_entire_tag ){
			$tag['full_tag'] = $match[0][0]; 			
		}
		$tags[] = $tag;
	}
	return $tags;
}
if (!function_exists('mime_content_type')){
   function mime_content_type($file, $method = 0){
      if ($method == 0){
         ob_start();
         system('/usr/bin/file -i -b ' . realpath($file));
         $type = ob_get_clean();

         $parts = explode(';', $type);

         return trim($parts[0]);
      }else if ($method == 1){
         // another method here
      }
   }
}
?>
