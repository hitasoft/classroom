<?php

$root_dir=$_SERVER["DOCUMENT_ROOT"];
//if (substr($root_dir,-9)!='classroom') $root_dir.="/classroom";
require_once $root_dir.'/account/library/Am/Lite.php';
$_title="Classroom@Educator.com";
$user=Am_Lite::getInstance()->getUser();$own=false;
//echo "<pre>".print_r($user,true)."</pre>";die();

$ul=$_login=$user['login'];
$tmpl=$root_dir."/tmpl/";$incl=$root_dir."/incl/";
$description="Educator-Classroom is a free service for teachers to create effective websites for their classroom. It gives teachers access to an array of free tools to better express themselves, as well as a social utility for networking amongst teachers.";
require_once $incl."db.class.php";
require_once $incl."common.php";

function checkuser($id='x',$public=false) {
	if ($public){
		return (checkLive($id));
	}else{
		global $user,$own;
		$own=$id==$user['login'] || $id=='x';

		if (substr($user['login'],0,5)=='_tmp_' && strpos($_SERVER['PHP_SELF'],'setprofile')===false) {	// dummy username - redirect to setprofile
            header("HTTP/1.1 302 Found");
            header("Location: http://{$_SERVER['HTTP_HOST']}/setprofile");
			die();
		}
		return ($user['status'] && $user['status']<2 && $own);
	}
}
function checkLive($id){
   $user=getUser($id);
   return ($user['live']);
}
function getUser($ul){
   global $dbc;
//   $alive=$dbc->arrays("SELECT lp.school,lp.bio,lp.subject,lp.classes,lm.member_id,lm.login,lm.name_f,lm.name_l FROM live_members lm LEFT OUTER JOIN live_profile lp ON lp.amember_id=lm.member_id WHERE lm.login LIKE '$ul' LIMIT 1");
   $alive=$dbc->arrays("SELECT lp.school,lp.bio,lp.subject,lp.classes,lp.hometab,lp.title,ll.links,ll.social,lm.user_id,lm.login,lm.name_f,lm.name_l,lm.live FROM (am_user lm LEFT OUTER JOIN live_profile lp ON lp.amember_id=lm.user_id) LEFT OUTER JOIN live_links ll ON ll.amember_id=lm.user_id WHERE lm.login LIKE '$ul' LIMIT 1");
   return ($alive[0]);
}
function getLinks($uid){
   global $dbc;
   $links=$dbc->arrays("SELECT * FROM live_links WHERE amember_id=$uid ORDER BY linkType, link_id");
   return ($links);
}
function get_vars(){
    $vars = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_GET;
    am_filter_var($vars, '');
    return $vars;
}
function am_filter_var(&$s, $key){
    if (is_array($s)){
        array_walk($s, 'am_filter_var') ;
    } else {
        $s = trim($s);
        if (get_magic_quotes_gpc())
            $s = stripslashes($s);
    }
}
function getUserImage($ul,$tn=false){
   global $liveDir;
   $user_image=($tn?'ptn-':'').user_dir($ul,$ul);
   if (file_exists($user_image)){
      return (($tn?'ptn-':'').user_dir($ul,$ul,false,false));
   }else{
      if ($tn)
         $user_image=user_dir($ul,$ul);
      if (file_exists($user_image))
         $user_image=user_dir($ul,$ul,false,false);
      else
         $user_image=$liveDir.'/image/dummy.png';
      return ($tn?"$liveDir/image.php?image=$user_image&width=80&cropratio=1:1":$user_image);
   }
}
function getMascot($no){
   global $ul;
   $fn='sm-'.$no.'.jpg';
   $logo=user_dir($ul,$fn,false);
   if (file_exists($logo))
      return (user_dir($ul,$fn,false,false));
   else
      return ('/image/dummy-logo.png');
}
function setUserImage(){
   global $user_image, $dummy_image, $user_image_disp, $root_dir, $ul;
   $user_image=user_dir($ul,$ul);
   $dummy_image="/image/dummy.png";
   if (file_exists($user_image)){
      $user_image=substr($user_image,strlen($root_dir));
      $user_image_disp="";
   }else{
      $user_image=$dummy_image;
      $user_image_disp="style='display:none;'";
   }
}
function check_dup($lecture){
	global $cid;
	return ($lecture['id']==$cid);
}

function shorten($str,$len=25){
	if (strlen($str)<=$len)
		return $str;
	else
		return (substr($str,0,$len-1)."…");
}

?>
