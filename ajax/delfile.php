<?php
session_name('connect');
session_start();
include("../incl/common.php");
$ul=$_SESSION['_amember_login'];

if (!$ul){
   echo "Login has expired.";
}else if ($_SERVER['REQUEST_METHOD']=="POST"){
   if (isset($_POST['fn'])){
      $fn=basename($_POST['fn']);
      $fp=user_dir($ul,$fn);
      if (file_exists($fp))
         echo (unlink($fp))?"success":$fn." - failed to delete file.";
      else
         echo $fn." - file not found.";
   }
}else{
   echo "Unauthorized request received.";
}
?>
