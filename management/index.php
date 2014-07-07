<?php
/*
 *      index.php
 *      
 *      Management
 *      
 */

session_name('connect');
session_start();

if (!include('../incl/base.php')) die('Base classes not found');
$vars = & get_vars();

if (checkuser()){
   $user_image=getUserImage($ul);
   $liveUser=getUser($ul);
   $uid=$liveUser['member_id'];

   if (!$uid){
      die("Login has expired");
   }elseif(in_array($uid,array(1,2))){
      //nop
   }else{
      header("HTTP/1.1 302 Found");
      header("Location: http://{$_SERVER['HTTP_HOST']}/{$user['login']}");
      die();
   }

   define('LIMIT',15);
   
   $offset=($vars['offset'])?$vars['offset']:0;
   $sortClause="ORDER BY ".(($vars['orderby'])?"{$vars['orderby']} {$vars['orderdir']}":"fname");
   $limitClause="LIMIT $offset, ".LIMIT;
   
   // get the stuff
   $parray=$dbc->arrays("SELECT m.member_id, LOWER(m.login) as login, LOWER(m.email) as email, CONCAT(m.name_f, ' ', m.name_l) as fname, m.added, ".
      "(SELECT count( * ) FROM live_posts p WHERE p.amember_id = m.member_id) AS posts ".
      "FROM live_members m $sortClause $limitClause");

   $uif='getUserImage';
   $trs='';
   foreach ($parray as $info){
      $_name=ucwords($info['fname']);
      $trs.="<tr><td sorttable_customkey='{$info['login']}'><a href='http://www.educator.com/{$info['login']}' title='{$info['login']}' target='_blank'><img src='{$uif($info['login'],1)}' alt='{$info['login']}'></a></td><td>$_name</td><td>{$info['email']}</td><td>{$info['added']}</td><td>{$info['posts']}</td></tr>";
   }

   if ($vars['submit']){
      echo $trs;
      die();
   }
   
   include("manage.html");
}else{
   // not logged in amember - show the login page
   $_GET['amember_redirect_url']=$_SERVER['PHP_SELF'];
   include(LIVE_DIR.'/login.php');
}

?>
