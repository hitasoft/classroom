<?php
/*
 *      badge.php
 *      
 *      Create a badge
 *      
 */

session_name('connect');
session_start();

if (!include('incl/base.php')) die('Base classes not found');

if (checkuser()){
	$vars = & get_vars();
   $user_image=getUserImage($ul);
   $liveUser=getUser($ul);
   $uid=$liveUser['user_id'];

   if (!$uid){
      die("Login has expired");
   }else{
      $dir=LIVE_DIR."/users/badges";
      if (file_exists($dir) || mkdir($dir,0777,true)){
         $dir=$dir.'/';
         $adata=$dbc->arrays('SELECT badge FROM live_profile WHERE amember_id='.$uid);
         $fnData=$adata[0];
         if (count($fnData)>0 && isset($fnData['badge']) && $fnData['badge']){
            $fnData=$fnData['badge'];
            if (file_exists($dir.$fnData))
               $fnBadge=$fnData;
         }else{
            unset($fnData);
         }
      }else{
         die("Folder creation failed on Classroom server.");
      }
   }
   if (isset($vars['preview']) || isset($vars['save'])){
      define(OW,76);    // original/standard width
      
      $w=(isset($vars['w']))?$vars['w']:OW;
      $h=(isset($vars['h']))?$vars['h']:110;
      $hh=round((17/OW)*$w,0);   // header height
      $badge=imagecreatetruecolor($w,$h); // create blank canvas
      imagefilledrectangle($badge,0,0,$w-1,$h-1,0xcccccc);  // gray border
      imagefilledrectangle($badge,1,1,$w-2,$h-2,0xffffff);  // fill with white
      imagefilledrectangle($badge,0,0,$w-1,$hh-1,0xb10501); // header

      $logo=imagecreatefrompng(LIVE_DIR.'/image/educator.png');   // get logo
      $lh=round($hh*9/17);   // height of logo in badge
      $lw=min($w-12,$lh*imagesx($logo)/imagesy($logo)); // keep ratio if possible
      
      imagecopyresampled($badge,$logo,round(($w-$lw)/2,0),$hh*4/17,0,0,round($lw,0),$lh,imagesx($logo),imagesy($logo));
      imagedestroy($logo);

      $logo=imagecreatefromjpeg(LIVE_DIR.getUserImage($ul));
      $lh=$h-$hh-8;
      $lw=$w-8;
      $lr=imagesx($logo)/imagesy($logo);
      if ($lr<$lw/$lh){
         // too tall
         $lw=$lr*$lh;   // reduce width so that height remains at max
      }elseif ($lr>$lw/$lh){
         // too wide
         $lh=$lr*$lw;
      }
      imagecopyresampled($badge,$logo,round(($w-$lw)/2,0),$hh+round(($h-$hh-$lh)/2,0),0,0,round($lw,0),round($lh,0),imagesx($logo),imagesy($logo));
      imagedestroy($logo);

      if (isset($vars['save'])){
         if (isset($fnData)){
            $fnBadge=$fnData; // use existing filename even if file was deleted
            $ret=1;  // nothing to update in database
         }
         if (!isset($fnBadge)){
            $fnBadge='eb'.$uid.'.'.mt_rand().'.png';
         }
         imagepng($badge,$dir.$fnBadge);
         if (!$ret)
            $ret=$dbc->update('live_profile',array('badge'=>$fnBadge),'amember_id='.$uid);
         $msg=($ret==1)?$fnBadge:'Database update failed';
         echo $msg;
      }else{
         // Put the data of the resized image into a variable
         ob_start();
         imagepng($badge, null);
         $data	= ob_get_contents();
         ob_end_clean();

         // Send the image to the browser with some delicious headers
         header("Expires: 0");
         header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
         header("Cache-Control: private",false); // required for certain browsers
         header("Content-type: image/png");
         header('Content-Length: ' . strlen($data));
         echo $data;
      }

      // Clean up the memory
      ImageDestroy($badge);
   }elseif (isset($vars['delete'])){
      if ($fnBadge) 
         $msg=unlink($dir.$fnBadge);
      echo $msg;
   }else{
      include($tmpl."badge.html");
   }
}else{
   // not logged in amember - show the login page
   Am_Lite::getInstance()->checkAccess(Am_Lite::ANY, 'Edit Badge'); 
}

?>
