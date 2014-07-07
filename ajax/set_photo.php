<?php
session_name('connect');
session_start();
include("../incl/common.php");
require_once '../account/library/Am/Lite.php';
$ul=Am_Lite::getInstance()->getUsername();

if (!$ul){
   $msg="Login has expired.";
}else if ($_SERVER['REQUEST_METHOD']=="POST"){
   if (isset($_POST['sm'])){
      $fp=user_dir($ul,'sm-'.$_POST['sm'].'.jpg',false);
   }else{
      $fp=user_dir($ul,$ul);
   }
   if (isset($_POST['fn'])){
      $dir=user_dir($ul,'',false);  // main user folder
      error_reporting(0);
      try {
         if (file_exists($dir) || mkdir($dir,0777,true)){
            $fn=user_dir($ul,basename($_POST['fn']));
            if (file_exists($fn)){
               if (rename($fn,$fp)){
                  $msg="success";
                  $fn=substr($fp,strlen(LIVE_DIR));
               }else
                  $msg="Could not set profile photo.";
            }else
               $msg="File not found.";
         }else{
            $msg="Folder creation failed on Classroom server.";
         }
      } catch(Exception $e) {
         $msg=$e;
      }
   }else{
      if (file_exists($fp))
         $msg=(unlink($fp))?"success":"Image removal failed.";
      else
         $msg="Image file not found.";
   }
}else{
   $msg="Unauthorized request received.";
}
echo '{"name":"'.$fn.'","status":"'.$msg.'"}';
?>
