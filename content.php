<?php
/*
 *      content.php
 *      
 *      Content edit of live user
 *      
 */
if (!(isset($_GET['u']) && $_GET['u']))
   die('There is nothing here. Go away.');

session_name('connect');
session_start();

if (!include('incl/base.php')) die('Base classes not found');

if (strpos($_SERVER['HTTP_HOST'],"classroom.educator.com")===false) $liveDir="/classroom";

$public=0;
if (isset($_GET['l']) && $_GET['l']) $public=1;
   
$ul=$_GET['u'];
if (checkuser($ul,$public)){
   $liveUser=getUser($ul);
   //$links=getLinks($liveUser['member_id']);
   $fname=$liveUser['name_f'].' '.$liveUser['name_l'];
   if ($public){
      $title=($liveUser['title'])?$liveUser['title']:"Welcome to $fname's Classroom Updates &amp; Resources";
      $page="pg-live";
      $fbEnable=true;
      $target="target='_blank'";
   }else{
   // edit content
      $title="Status update";
      $page = "pg-status";
      if ($ul!=$user['login']) die('Sorry, there was a problem with the login authorization.');  // sanity check
   }
   $title.=" - ".$_title;
   foreach (array('school','subject','classes','links','social') as $field){
      $data[$field]=unserialize($liveUser[$field]);
   }
   extract($data);
   if (!array($school)||count($school)==0) $noSchool=true;
   if (!array($classes)||count($classes)==0) $noClass=true;
   $userPhoto=getUserImage($ul);
   /*if (!$public && ($noSchool || $noClass || strpos($userPhoto,'dummy.png')>0)){
      header('Location: http://classroom.educator.com/setprofile');
      exit();
   }*/
   $schoolLogo=getMascot(1);
   include($tmpl.'content.html');
}else{
   if ($public){
      include($tmpl."header.html");
      echo "Sorry, $ul has not published yet.";
      include($tmpl."footer.html");
   }elseif ($own || empty($user['login'])){
      // not logged in amember - show the login page
      Am_Lite::getInstance()->checkAccess(Am_Lite::ANY, 'Status Page'); 
   }else{
      // some other user - show them off
      $title="Unauthorized user - ".$_title;
      $text="You are being redirected to your own page.";
      $url="/".$user['login'];
      include($tmpl.'redirect.html');
   }
}

?>
