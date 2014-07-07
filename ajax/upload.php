<?php
session_name('connect');
session_start();

if (!empty($_FILES) && isset($_FILES['file']) && !empty($_FILES['file']['name'])){
   $file = $_FILES['file'];
   $badchar=array("%","#","$","^","&","*","?"," ","|","'",'"');
   $name=str_replace($badchar,"_",$file["name"]);
   
   $ul=$_SESSION['_amember_login'];
   $success=0;
   $type=$_GET['type'];
   if ($type){
      if ($type=='video'){
         $formats=array('avi','mpg','flv','mp4','mov');
         $mime=eregi($type.'/',$file['type']);
      }else if ($type=='document'){
         $formats=array('pdf','fdf','doc','xls','ppt','pps','odt','ods','odp','docx','xlsx','pptx','ppsx','rtf','txt');
         $mime=eregi('application/|text/',$file['type']);
      }
   }else{
      $type='image';
      $mime=eregi($type.'/',$file['type']);
   }
   
   if (!$ul){
      $msg="Login has expired.";
   }else if ($file['error']>0){
      $msg="Error during file upload - #".$file['error'];
   }else if (!$mime){
      $msg="Only $type files are accepted here.";
   }else{
      include("../incl/common.php");
      // uploads into a temporary user directory
      $dir=user_dir($ul);error_reporting(0);
      try {
         if (file_exists($dir) || mkdir($dir,0777,true)){
            if ($type=='image'){
               $img=new SimpleImage();
               $img->load($file['tmp_name']);
               if ($img->limitType()){
                  $name=substr($name,0,strrpos($name,".")).".jpg";
                  $fname=user_dir($ul,$name);
                  if ($_GET['profile']){
                     $img->resize(198,264,true);
                  }else{
                     if ($img->getWidth()>1280) $img->resizeToWidth(1280);
                     if ($img->getHeight()>600) $img->resizeToHeight(600);
                  }
                  $img->save($fname);
                  $success=1;
                  $msg=substr($fname,strlen($root_dir));
               }else{
                  $msg="Image must be in one of jpg, gif, png or bmp formats.";
               }
            }else if (in_array(pathinfo($file['name'],PATHINFO_EXTENSION),$formats)){
               $fname=user_dir($ul,$name);
               if (move_uploaded_file($file['tmp_name'],$fname)){
                  $success=1;
                  $msg=substr($fname,strlen($root_dir));
               }else{
                  $msg="Could not upload file into user folder.";
               }
            }else{
               $msg="The $type file must be in one of ".implode(", ",$formats)." formats.".pathinfo($file['tmp_name'],PATHINFO_EXTENSION);
            }
         }else{
            $msg="Folder creation failed on Connect server.";
         }
      } catch (Exception $e) {
         $msg=$e;
      }
   }
}else{
    $poidsMax = ini_get('upload_max_filesize');
    $msg="The file is too big; maximum size allowed is $poidsMax.";
}
echo '{"name":"'.$name.'","type":"'.$file['type'].'","size":"'.$file['size'].'","success":"'.$success.'","msg":"'.$msg.'"}';
?>
