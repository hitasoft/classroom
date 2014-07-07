<?php
/*
 * on_mod.php : Modify or delete post
 *      
 */
session_name('connect');
session_start();
include("../incl/base.php");
$ul=$user['login'];$uid=$user['user_id'];

function getLink($type){
   global $post;
   $raw=unserialize($post['p_data']);
   switch ($type){
      case 'pLinks':
      case 'pText':
         break;
      case 'pImages':
      case 'pVideos':
      case 'pDocs':
         $rData=array_filter($raw['fname']);
         break;
      default:
   }
   if (!is_array($rData)){
      $rData=array();
   }
   return $rData;
}

if (!$uid){
   $msg="Login has expired";
}else if ($_SERVER['REQUEST_METHOD']=="POST"){
	$vars = & get_vars();
   if ($vars['postid'] && $vars['action']){
      $pid=strtr($vars['postid'],array('pid'=>""));
      $limit_cl="amember_id=$uid AND post_id=$pid LIMIT 1";
      $pquery=$dbc->query("SELECT * FROM live_posts WHERE $limit_cl");
      if ($post=$dbc->fetch($pquery)){
         if ($vars['action']=='close'){
            $count=0;
            do{
               $media[$count]=getLink($post['p_type']);
               $count++;
            }while ($post=$dbc->fetch($pquery));
            $dbc->query("DELETE FROM live_posts WHERE $limit_cl");
            $pcount=$dbc->affected_rows();
            if ($pcount>0){
               //delete the media files
               foreach ($media as $v){
                  foreach ($v as $fn){
                     if (empty($fn)) continue;
                     $fp=user_dir($ul,$pid."_".$fn,false);
                     if (file_exists($fp)) unlink($fp);
                  }
               }
               $msg="success";
            }else{
               $msg="Could not delete this post.";
            }
         }elseif ($vars['action']=='share'){
            $ret=$dbc->update('live_posts',array('privacy'=>$vars['privacy']),$limit_cl);
            if ($ret>0){
               $msg="success";
            }else{
               $msg="Could not change privacy setting.";
            }
         }
      }else{
         $msg="Could not find this post";
      }
   }else{
      $msg="Vague request received";
   }
}else{
   $msg="Malformed request received";
}
//echo "<pre>";print_r($_GET);print_r($_POST);echo "</pre>";
echo $msg;
?>
