<?php
/*
 *      editprofile.php
 *      
 *      About (profile) page edit of live user
 *      
 */

session_name('connect');
session_start();

if (!include('incl/base.php')) die('Base classes not found');

if (checkuser()){
   $liveUser=getUser($ul);
   //$links=getLinks($liveUser['member_id']);
   $fname=$liveUser['name_f'].' '.$liveUser['name_l'];

   // edit content
     $title="Profile update";
     $page = "pg-status";

   include($tmpl.'editprofile.html');
}else{
   // not logged in amember - show the login page
	Am_Lite::getInstance()->checkAccess(Am_Lite::ANY, 'Profile update'); 
}

?>
