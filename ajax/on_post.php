<?php
/*
 * on_post.php : Submit a post
 *
 */
session_name('connect');
session_start();
include("../incl/base.php");
$ul=$user['login'];$uid=$user['user_id'];

function move_file($fn){
   global $ul,$pid;
   if (empty($fn)) return true;
   $ft=user_dir($ul,$fn);  // temp file
   $fp=user_dir($ul,$pid.'_'.$fn,false);  // perm file
   if (file_exists($ft))   // may not be there in case of edit
      rename($ft,$fp);
   return (file_exists($fp));
}

$msg="Nothing to post";$pid=0;
if (!$uid){
   $msg="Login has expired";
}else if ($_SERVER['REQUEST_METHOD']=="POST"){
	$vars = & get_vars();
   if ($vars['submit_pd']){
      $data=array_values_recursive($vars['pd_data']);
      if (count($data)>0){
         $dir=user_dir($ul,'',false);  // main user folder
         $pidEdit=($vars['postid']=='null')?false:strtr($vars['postid'],array('pid'=>""));
         error_reporting(0);
         try {
            if (file_exists($dir) || mkdir($dir,0777,true)){
               $p_data=array();
               foreach ($vars as $k=>$v){
                  if (substr($k,0,3)=="pd_"){
                     if (substr($k,3,4)=="data"){
                        //process data array(s)
                        foreach ($v as $k=>$p){
                           $data=array_filter($p); //remove blanks
                           if (count($data)>0){
                              $p_data[$k]=$data;
                           }
                        }
                        if (count($p_data)>0) $values['p_data']=serialize($p_data);
                     }else{
                        if (!empty($v)) $values[substr($k,3)]=$v;
                     }
                  }
               }
               if (count($p_data)>0){
                  if ($pidEdit){
                     $pid=$dbc->update('live_posts',$values,'post_id='.$pidEdit);
                     if ($pid==0)
                        $emsg="Nothing to update";
                     else
                        $pid=$pidEdit;
                  }else{
                     $values['amember_id']=$uid;$values['posted']=date("Y-m-d H:i:s");
                     $pid=$dbc->insert('live_posts',$values);
                  }
                  if ($pid!=0){
                     $msg="success";
                     $fail=array();
                     foreach ($p_data as $k=>$v){
                        if ($k=="fname"){
                           $n=array_filter($v,"move_file");
                           $fail[$k]=array_diff($v,$n);
                        }
                     }
                     $failed=array_values_recursive($fail);
                     if (count($failed)>0) $msg=implode(', ',$failed)." failed!";
                     else{
                        // clean up tmp files
                        $files=glob(pathinfo(user_dir($ul,'test'),PATHINFO_DIRNAME).'/'.$ul.'_*.*');
                        if (is_array($files) && count($files) > 0){
                           foreach ($files as $file){
                              unlink($file);
                           }
                        }
                        if ($pidEdit){
                           // clean up deleted files in user dir
                           $udir=pathinfo(user_dir($ul,'test',false),PATHINFO_DIRNAME).'/';
                           $files=glob($udir.$pid.'_*.*');
                           if (is_array($files) && count($files) > 0){
                              foreach ($p_data['fname'] as $k=>$v){
                                 $p_data['fname'][$k]=$udir.$pid.'_'.$v;
                              }
                              $delfiles=array_diff($files,$p_data['fname']);
                              foreach ($delfiles as $file){
                                 unlink($file);
                              }
                           }
                        }
                     }
                  }else
                     $msg=($emsg)?$emsg:"A database error occurred";
               }
            }else{
               $msg="Folder creation failed on Classroom server.";
            }
         } catch(Exception $e) {
            $msg=$e;
         }
      }
   }else{
      $msg="Malformed request received";
   }
}else{
   $msg="Unauthorized request received";
}
echo '{"pid":"'.$pid.'","msg":"'.$msg.'"}';
?>
