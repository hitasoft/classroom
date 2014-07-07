<?php
session_name('connect');
session_start();
include("../incl/base.php");
$uid=$_SESSION['_amember_id'];

if (!$uid){
   $msg="Login has expired";
}else if ($_SERVER['REQUEST_METHOD']=="POST"){
   if (isset($_POST['pub'])){
      $dbc->update('live_profile',array('live'=>$_POST['pub']),'amember_id='.$uid);
      $msg="success";
   }else{
      $msg="Malformed request received";
   }
}else{
   $msg="Unauthorized request received";
}
echo $msg;
?>
