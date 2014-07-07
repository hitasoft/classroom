<?php
include("../incl/common.php");

class cURL {
   # defaul global options
   var $opts = array(
         CURLOPT_HEADER => FALSE,
         CURLOPT_RETURNTRANSFER => TRUE
   );

   function cURL(){
   }

   function r($ch,$opt){
     # assign global options array
     $opts = $this->opts;
     # assign user's options
     foreach($opt as $k=>$v){$opts[$k] = $v;}
     curl_setopt_array($ch,$opts);
     curl_exec($ch);
     $r['code'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
     $r['cr'] = curl_exec($ch);
     $r['ce'] = curl_errno($ch);
     curl_close($ch);
     return $r;
   }

   function get($url='',$opt=array()){
     # create cURL resource
     $ch = curl_init($url);
     return $this->r($ch,$opt);
   }

   function post($url='',$data=array(),$opt=array()){
     # set POST options
     $opts[CURLOPT_POST] = TRUE;
     $opts[CURLOPT_POSTFIELDS] = $data;

     # create cURL resource
     $ch = curl_init($url);
     return $this->r($ch,$opt);
   }
}

$url = urldecode($_POST['url']);
$url = checkValues($url);
$return_array = array();

if ($url){
 
   $base_url = substr($url,0, strpos($url, "/",8));
   $relative_url = substr($url,0, strrpos($url, "/")+1);
    
   // Get Data
   $cc = new cURL();
   $page=$cc->get($url);
   if ($page['code']==200){
      $string = str_replace(array("\n","\r"), '', $page['cr']);
       
      // Parse Title
      $nodes = extract_tags( $string, 'title' );
      $return_array['title'] = trim($nodes[0]['contents']);
       
      // Parse Description / title / image / video
      $return_array['description'] = '';
      $return_array['video'] = '';
      $nodes = extract_tags( $string, 'meta' );
      foreach($nodes as $node){
         $name=strtolower($node['attributes']['name']);
         $property=trim($node['attributes']['property']);
         $content=trim($node['attributes']['content']);
         if ($name == 'description') $return_array['description'] = html2txt($content);
         if (in_array($name,array('title','og:title'))) $return_array['title'] = html2txt($content);
         if ($name == 'video_type') $return_array['video_type']=$content;
         if (in_array($name, array('embed_video_url','video_src','og:video')) || $property == 'og:video') $return_array['video'] = $content;
         if ($name == 'image_src' || $property == 'og:image') $tmp_image = $content;
      }
      $nodes = extract_tags( $string, 'link' );    // metacafe has stuff in link tag
      foreach($nodes as $node){
         $name=strtolower($node['attributes']['rel']);
         $content=trim($node['attributes']['href']);
         if ($name=='video_src') $return_array['video'] = $content;
         if ($name=='image_src' && !$tmp_image) $tmp_image = $content;
      }

      $images_array=array();
      if (empty($return_array['video_type'])){
         // Parse Images
         $image_regex = '/<img[^>]*'.'src=[\"|\'](.*)[\"|\']/Ui';
         preg_match_all($image_regex, $string, $img, PREG_PATTERN_ORDER);
         $images_array = $img[1];
      }
      if ($tmp_image) array_unshift($images_array,$tmp_image);
       
      // Validate Images
      $images = array();
      $k=1;
      for ($i=0;$i<=sizeof($images_array);$i++){
         $img = trim(@$images_array[$i]);
         $ext = pathinfo($img,PATHINFO_EXTENSION);
         if (strpos($ext,'?')>0){
            $ext = strtolower(substr($ext,0,3));
         }

         if($img && ($ext == 'jpg' || $ext == 'png')){
            if (substr($img,0,7) == 'http://')
               ;
            else  if (substr($img,0,1) == '/')
               $img = $base_url . $img;
            else
               $img = $relative_url . $img;
       
            $details = @getimagesize($img);

            if(is_array($details)){
               list($width, $height, $type, $attr) = $details;
               $min=64;
               if($width >= $min && $height >= $min/2 ){
                  $images[] = array("img" => $img, "width" => $width, "height" => $height);
                  $k++;
               }
            }
         }
      }
       
      // Sort and prepare images
      $return_array['images'] = array_values(sortArrayByField($images, 'width', true));
      $return_array['total_images'] = count($return_array['images']);
      if (strpos($base_url,'vimeo.com')>0 && is_numeric(substr($url,strrpos($url,'/')+1)))
		   $return_array['video']=$base_url.'/moogaloop.swf?clip_id='.substr($url,strrpos($url,'/')+1);
   }else{
      $return_array['title']='Failed to load the url';
      $return_array['description']='The response code received was '.$page['code'].'|'.$page['ce'].'.';
      $return_array['images'] = array();
      $return_array['total_images'] = -1;
      $return_array['video'] = '';
   }
}
 
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
 
echo json_encode($return_array);
exit;
?>
