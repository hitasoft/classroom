<?php
session_name('connect');
session_start();
include("../incl/common.php");
require_once '../account/library/Am/Lite.php';
$ul=Am_Lite::getInstance()->getUsername();

class SimpleImage {
   
   var $image;
   var $image_type;
 
   function load($filename) {
      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
         $this->image = imagecreatefrompng($filename);
       } elseif( $this->image_type == IMAGETYPE_WBMP ) {
         $this->image = imagecreatefromwbmp($filename);
     }
   }
   function save($filename, $image_type=IMAGETYPE_JPEG, $compression=85, $permissions=null) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image,$filename);         
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image,$filename);
      }   
      if( $permissions != null) {
         chmod($filename,$permissions);
      }
   }
   function output($image_type=IMAGETYPE_JPEG) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image);         
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image);
      }   
   }
   function getWidth() {
      return imagesx($this->image);
   }
   function getHeight() {
      return imagesy($this->image);
   }
   function resizeToHeight($height) {
      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }
   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }
   function scale($scale) {
      $width = $this->getWidth()*$scale/100;
      $height = $this->getheight()*$scale/100; 
      $this->resize($width,$height);
   }
   function resize($width,$height,$crop=false) {
      $new_image = imagecreatetruecolor($width, $height);
      $ow=$this->getWidth();$oh=$this->getHeight();
      $cw=0;$ch=0;$dw=0;$dh=0;
      $nw=$width;$nh=$height;
      if ($crop){
         $wr=abs(1-($height/$oh))<abs(1-($width/$ow));   // height change vs width change
         if ($wr){  // width change is larger, so resize height, crop width
            $nw=$ow*$height/$oh;$nh=$height;
            $cw=($nw-$width)/2;$ch=0;
            $dw=0;$dh=0;
            if ($cw<0){  // width is less than reqd
               $dw=-$cw;$cw=0;
            }
         }else{  // height change is larger
            $nw=$width;$nh=$oh*$width/$ow;
            $cw=0;$ch=($nh-$height)/2;
            $dw=0;$dh=0;
            if ($ch<0){  // height is less than reqd
               $dh=-$ch;$ch=0;
            }
         }
      }
      $white = imagecolorallocate($new_image, 255, 255, 255);
      imagefill($new_image, 0, 0, $white);
      imagecopyresampled($new_image, $this->image, $dw, $dh, $cw, $ch, $nw, $nh, $ow, $oh);
      imagefill($new_image, 0, 0, $white);   // no idea why this is needed
      $this->image = $new_image;
   }
   function limitType(){
      $t=$this->image_type;
      return ($t==IMAGETYPE_JPEG || $t==IMAGETYPE_GIF || $t==IMAGETYPE_PNG || $t==IMAGETYPE_WBMP);
   }
}
/**
* Handle file uploads via XMLHttpRequest
*/
class qqUploadedFileXhr {
   private $tmpfile; // temporary saved file
   /**
   * Save the file to a temporary file
   * @return boolean TRUE on success
   */
   function save($filename) {

      $target = fopen($filename, "w");
      $temp=fopen($this->tmpfile,"r");
      $realSize = stream_copy_to_stream($temp, $target);
      fclose($target);fclose($temp);
      unlink($this->tmpfile);

      if ($realSize != $this->getSize()){
         return false;
      }
      return true;
   }
   function getFile() {
      $input = fopen("php://input", "r");
      $this->tmpfile = tempnam(LIVE_DIR,'~live-');
      if ($this->tmpfile) {
         $target = fopen($this->tmpfile, "w");
         $realSize = stream_copy_to_stream($input, $target);
         fclose($input);fclose($target);
      }else{
         return false;
      }

      if ($realSize != $this->getSize()){
         return false;
      }
      return $this->tmpfile;
   }
   function getName() {
      return $_GET['qqfile'];
   }
   function getSize() {
      if (isset($_SERVER["CONTENT_LENGTH"])){
         return (int)$_SERVER["CONTENT_LENGTH"];
      } else {
         throw new Exception("{'error':'Getting content length is not supported'}");
      }
   } 
}

/**
* Handle file uploads via regular form post (uses the $_FILES array)
*/
class qqUploadedFileForm {
   
   /**
   * Check if the file was uploaded 
   * @return boolean TRUE on success
   */
   function save($filename) {
      if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $filename)){
         return false;
      }
      return true;
   }
   function getFile() {
      if (file_exists($_FILES['qqfile']['tmp_name']))
         return $_FILES['qqfile']['tmp_name'];
      else
         return false;
   }
   function getName() {
      return $_FILES['qqfile']['name'];
   }
   function getSize() {
      return $_FILES['qqfile']['size'];
   }
}

class qqFileUploader {
   private $allowedExtensions = array();
   private $sizeLimit = 10485760;
   private $file;private $type;

   function __construct($sizeLimit = 10485760, $type = 'image'){

      // list of valid extensions, ex. array("jpeg", "xml", "bmp")
      // the extensions can be set before upload, in which case this becomes redundant
      if ($type=='video'){
         $allowedExtensions=array('avi','mpg','flv','mp4','mov');
      }elseif ($type=='document'){
         $allowedExtensions=array('pdf','fdf','doc','xls','ppt','pps','odt','ods','odp','docx','xlsx','pptx','ppsx','rtf','txt');
      }else{
         $type='image';
         // images have a built-in type check
         $allowedExtensions = array();
      }

      $this->allowedExtensions = $allowedExtensions;
      $this->sizeLimit = $sizeLimit;
      $this->type = $type;

      $this->checkServerSettings(); 

      if (isset($_GET['qqfile'])) {
         $this->file = new qqUploadedFileXhr();
      } elseif (isset($_FILES['qqfile'])) {
         $this->file = new qqUploadedFileForm();
      } else {
         $this->file = false; 
      }
   }

   private function checkServerSettings(){
      $postSize = $this->toBytes(ini_get('post_max_size'));
      $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

      if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
         $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M'; 
         die("{'error':'Increase post_max_size and upload_max_filesize to $size'}");
      }
   }

   private function toBytes($str){
      $val = trim($str);
      $last = strtolower($str[strlen($str)-1]);
      switch($last) {
         case 'g': $val *= 1024;
         case 'm': $val *= 1024;
         case 'k': $val *= 1024;
      }
      return $val;
   }

   /**
   * Returns array('success'=>true) or array('error'=>'error message')
   */
   function handleUpload($user){
      if (!is_writable(user_dir($user))){
         return array('error' => "Server error. Upload directory isn't writable.");
      }

      if (!$this->file){
         return array('error' => 'No files were uploaded.');
      }

      $size = $this->file->getSize();
      if ($size == 0) {
         return array('error' => 'File is empty');
      }

      if ($size > $this->sizeLimit) {
         return array('error' => 'File is too large');
      }

      $pathinfo = pathinfo($this->file->getName());
      $badchar=array("%","#","$","^","&","*","?"," ","|","'",'"');
      $filename = str_replace($badchar,"_",$pathinfo['filename']);
      //$filename = md5(uniqid());
      $ext = $pathinfo['extension'];

      if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
         $these = implode(', ', $this->allowedExtensions);
         return array('error' => 'File has an invalid extension; it should be one of '. $these . '.');
      }

      $tmpfile=$this->file->getFile();
      $success=false;$msg='';
      if ($tmpfile){
         if ($this->type=='image'){
            $img=new SimpleImage();
            $img->load($tmpfile);
            if ($img->limitType()){
               $name=$filename.".jpg";
               $fname=user_dir($user,$name);
               if ($_GET['profile']){
                  $img->resize(200,260,true);
               }elseif ($_GET['mascot']){
                  $img->resize(150,150,true);
               }else{
                  if ($img->getWidth()>1280) $img->resizeToWidth(1280);
                  if ($img->getHeight()>600) $img->resizeToHeight(600);
               }
               $img->save($fname);
               $success=true;
               $msg=substr($fname,strlen(LIVE_DIR));
            }else{
               $msg="Image format is not recognized. It could be a bad file or the upload failed.";
            }
         }else{
            $name=$filename.'.'.$ext;
            $fname=user_dir($user,$name);
            if ($this->file->save($fname)){
               $success=true;
               $msg=substr($fname,strlen(LIVE_DIR));
            }
         }
      }
      if ($success){
         return array(
            'success'=>true,
            'filename'=>$msg,
            'name'=>$name,
            'size'=>$this->file->getSize(),
            'type'=>$this->type
            );
      }else{
         if (empty($msg))
            $msg="Could not save uploaded file." .
            "The upload was cancelled, or server error encountered";
         return array('error'=>$msg);
      }
   }
}

// max file size in bytes
$sizeLimit = 2 * 1024 * 1024;
$type=$_GET['type'];

if (!$ul){
   $result=array('error'=>"Login has expired.");
}else{
   // all uploads go to users/_tmp folder, which must exist
   $uploader = new qqFileUploader($sizeLimit, $type);
   $result = $uploader->handleUpload($ul);
}
// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
?>
