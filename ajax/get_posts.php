<?php
/*
 * get_posts.php : Get content of live user
 *      
 */
define('LIMIT',5);

function expandLink($type){
   global $post,$user,$icon,$liveDir;
   $raw=unserialize($post['p_data']);
   $num=(count($raw,COUNT_RECURSIVE)-count($raw))/count($raw);
   $rData=array();
   for ($i=0;$i<$num;$i++){
      foreach ($raw as $k=>$v){
         $rData[$i][$k]=$v[$i];
      }
   }
   $code="";
   if (is_array($rData)){
      $icon=$type.'Icon';
      $code.="<div class='$type'>";
      switch ($type){
         case 'pLinks':
            foreach ($rData as $pData){
               $pText=txt2lnk($pData['utext']);
               $code.="<div class='pTextData'>$pText</div>";
               if (preg_match('/<(embed|object|iframe)/',$pData['ulink'])){
                  $code.="<center>{$pData['ulink']}</center>";
                  $icon="pVideosIcon";
               }else{
                  $url=parse_url($pData['ulink']);
                  $code.="<div class='atcCont'>";
                  $code.="<div class='atcImages'>";
                  if (!empty($pData['uvideo'])){
                     $code.="<a href='{$pData['uvideo']}' target='_blank' rel='nofollow'></a>";
                     $icon="pVideosIcon";
                  }
                  if (empty($pData['uimage'])||$pData['uimage']=='undefined')
                     $code.="<img src='$liveDir/image/icon/docs/url.png' alt='No image found'></div>";
                  else
                     $code.="<img src='{$pData['uimage']}' alt=''></div>";
                  $code.="<div class='atcInfo'><h3 class='atcTitle'><a href='{$pData['ulink']}' target='_blank' rel='nofollow'>{$pData['utitle']}</a></h3>";
                  $code.="<label class='atcUrl'><a href='".$url['scheme']."://".$url['host']."' target='_blank' rel='nofollow'>{$url['host']}</a></label>";
                  $pText=txt2lnk($pData['udesc']);
                  $code.="<div class='atcDesc'>$pText</div></div>";
                  $code.="</div>\n";
               }
            }
            break;
         case 'pText':
            foreach ($rData as $pData){
               $time=postTime();
               $code.="<div class='pHeader'>posted $time by {$post['name_f']} {$post['name_l']} <span></span> <span class='share'></span></div>";
               $pText=(preg_match('/<(embed|object|iframe)/',$pData['ptext']))?$pData['ptext']:txt2lnk($pData['ptext']);
               $code.="<div class='pTextData'>$pText</div>";
            }
            break;
         case 'pImages':
         case 'pVideos':
         case 'pDocs':
            $ul=$post['login'];$pid=$post['post_id'];
            foreach ($rData as $link){
               extract($link);
               $pText=txt2lnk($fdesc);
               $path=user_dir($ul,$pid.'_'.$fname,false,false);
               if ($type=="pImages"){
                  $code.="<p>$pText</p>";
                  $num=count($rData);
                  $w=floor(min(350.0,700/$num));$h=floor($w*0.75);
                  $code.="<a href='$path' target='_blank'><img src='$liveDir/image.php?image=$path&width=$w&height=$h'></a>";
               }else if ($type=="pVideos"){
                  $code.="<video src='$path' controls='controls' width='500px'>Your browser may not support HTML5 video</video>";
               }else{
                  if ($fgroup) {
                     $code.=<<<EOF
   <div class='pGroupRow'>
      <div class='pDocIcon'><img src='$liveDir/image/icon/16/folder-open.png' alt='dir'></div>
      <div class='pDocName'>
         <span title="$fgroup" class="ellipsis">$fgroup</span>
      </div>
   </div>
EOF;
                  }
                  $fname=pathinfo($fname);
                  $ext=$fname['extension'];
                  $fname=$fname['filename'];
                  $rpath=($liveDir)?substr($path,strlen($liveDir)):$path;
                  $fsize=formatBytes(filesize(LIVE_DIR.$rpath));
                  /*if (strlen($fname) > 30){
                     $oname=$fname;
                     $fname=substr($fname,0, 19).'&hellip;'.substr($fname,-10);
                  }*/
                  $code.=<<<EOF
   <div class='pDocsRow'>
      <div class='pDocTick'><input type='checkbox' value='0'></div>
      <div class='pDocIcon'><img src='$liveDir/image/icon/docs/$ext.png' alt='$ext'></div>
      <div class='pDocName'>
         <span title="$fname.$ext" class="ellipsis">$fname</span>
         <a class='pdView' href='$path' target='_blank'>View</a>
         <a class='pdDown' href='$liveDir/download.php?file=$rpath'>Download</a>
      </div>
      <div class='pDocDesc'>$pText</div>
      <div class='pDocSize'>$fsize</div>
   </div>
EOF;
               }
            }
            break;
         default:
      }
      $code.="</div>\n";
   }
   return $code;
}

function postTime($edit=false){
   global $post;
   $elapsed=$post['elapsed'];$posted=$post['posted'];$postdate=$post['postdate'];
   
   $days = floor($elapsed / (60 * 60 * 24));
   $remainder = $elapsed % (60 * 60 * 24);
   $hours = floor($remainder / (60 * 60));
   $remainder = $remainder % (60 * 60);
   $minutes = floor($remainder / 60);
   $seconds = $remainder % 60;

   if (substr($posted,0,4)<date('Y'))
      $time=", ".substr($posted,0,4);
   else
      $time=date(' \a\t g\:ia',mktime(substr($posted,11,2),substr($posted,14,2),0,0,0,0));

   if ($edit){
      $edited=$post['edited'];$editdate=$post['editdate'];
      if (substr($edited,0,4)<date('Y'))
         $edittime=", ".substr($edited,0,4);
      else
         $edittime=date(' \a\t g\:ia',mktime(substr($edited,11,2),substr($edited,14,2),0,0,0,0));
         
      $edited=(strtotime($edited)>strtotime($posted))?' : Edited '.$editdate.$edittime:'';
   }else{
      $edittime='';
   }

   $plain=true;
   if($days > 0 || $plain){
      $code.=$postdate.$time;
   }elseif($hours>0)
      $code.=$hours." hours ago";
   elseif($minutes>0)
      $code.=$minutes." minutes ago";
   else
      $code.="few seconds ago";	

   return $code.$edited;
}

if ($_SERVER['REQUEST_METHOD']=="POST"){
   session_name('connect');
   session_start();
   if (strpos($_SERVER['HTTP_HOST'],"classroom.educator.com")===false) $liveDir="/classroom"; // reqd for subsequent ajax calls
   include_once("../incl/base.php");
	$vars = & get_vars();
}elseif(!$liveDir){
   die("Malformed request received");
}

if ($vars['ul']){
   $sort="ORDER BY posted DESC";
   if ($vars['live']){
      $limit_cl="AND privacy=0 ";
   }else{
      $limit_cl="";
   }
   if ($vars['postid']){
      $limit_cl.="AND post_id={$vars['postid']} ".$sort;
      $init=0;
   }else{
      if ($vars['ptype']){
         $limit_cl.="AND p_type LIKE '{$vars['ptype']}' ";
      }
      if ($vars['pclass']){
         $limit_cl.="AND p_class={$vars['pclass']} ";
      }
      $init=LIMIT;
      $limit_cl.=$sort." LIMIT $init";
      if ($vars['offset']){
         $limit_cl.=" OFFSET {$vars['offset']}";
         $init=$init+$vars['offset'];
      }
   }
   $psql="lp.*, DATE_FORMAT(lp.posted,'%M %d') as postdate, DATE_FORMAT(lp.edited,'%M %d') as editdate, TIMESTAMPDIFF(SECOND,lp.posted,CURRENT_TIMESTAMP()) AS elapsed,lm.login,lm.title,lm.name_f,lm.name_l,lm.ptitle FROM live_posts lp INNER JOIN am_user lm ON lp.amember_id=lm.user_id WHERE lm.login LIKE '{$vars['ul']}'";
   if ($vars['p']){
      $psql="(SELECT 1 as ssort, $psql AND post_id={$vars['p']}) UNION (SELECT 2, $psql AND post_id!={$vars['p']}";
      $extra_cl=") ORDER BY ssort";
   }else{
      $psql="SELECT $psql";
      $extra_cl="";
   }
   $pquery=$dbc->query($psql." $limit_cl".$extra_cl);
   $pcount=0;
   if ($post=$dbc->fetch($pquery)){
      do{
         $private=($post['privacy']>0)?" private":"";
         if ($post['ssort']==1){
            $selectp=" selected";
            $pcount--;
         }else{
            $selectp="";
         }
         echo "\n<div id='pid{$post['post_id']}' class='posts$private$selectp'>\n";
         if (!$vars['live'])
         echo <<<EOF
            <div class="actionBar">
               <div class="actionLinksBtn">
                  <span> </span>
               </div>
               <ul class="actionLinks">
                  <li class="actionDelete">
                     <a title="Delete this post">Delete</a>
                  </li>
                  <li class="actionShare">
                     <a title="Share this post">Share</a>
                  </li>
               </ul>
            </div>
EOF;
         
         $userTn=getUserImage($post['login'],true);
         echo "<img class='prPhoto' src='$userTn'>";
         $ptitle=($post['ptitle'])?$post['ptitle']:$post['title'].' '.$post['name_f'].' '.$post['name_l'];
         echo "<div class='prName'>$ptitle</div>";

         $icon='';   //init
         print expandLink($post['p_type']);
         
         echo "<div class='pFooter'>";
         print "<div class='pTime $icon'>".postTime(true)."<span class='share'></span></div>\n";
         echo "<a class='fbCommentBtn' href='/{$post['login']}#fbc{$post['post_id']}' title='Students please comment via Facebook'>Comment</a>";
         if ($vars['live']) echo "&nbsp;| <a class='shareBtn' href='http://{$_SERVER['HTTP_HOST']}/{$post['login']}&p={$post['post_id']}' title='Share this post'>Share</a>";
         echo "</div>";
         echo "<div class='fbComments' id='fbc{$post['post_id']}'></div>\n";
         echo "</div>";
         $pcount++;
         //echo "<pre>";print_r($_POST);print_r($post);echo "</pre>";
      }while ($post=$dbc->fetch($pquery));
      if ($init>0 && $pcount==LIMIT) echo "<div id='morePost' onclick='loadPost(null,$init)'>Show older posts</div>\n";
   }else{
      if ($dbc->exists("SELECT lp.*,lm.login FROM live_posts lp INNER JOIN am_user lm ON lp.amember_id=lm.user_id WHERE lm.login LIKE '{$vars['ul']}' LIMIT 1")){
         echo "<div class='placeholder'>";
         if ($init==0)
            echo "No such post found";
         elseif ($init==5){
            echo "No post found";
         }else
            echo "No more posts";
         echo "</div>";
      }else{
         if ($vars['live']){
            $luser=$dbc->arrays("SELECT lm.* FROM am_user lm WHERE lm.login LIKE '{$vars['ul']}' LIMIT 1");
            echo <<<EOF
            <div id="pAnnounce">
               <div id="pAnnHdr">Announcements</div>
               <h2>Welcome to {$luser[0]['title']} {$luser[0]['name_f']} {$luser[0]['name_l']}'s Class Website!</h2>
               <p>{$luser[0]['title']} {$luser[0]['name_f']} {$luser[0]['name_l']} is using Educator-Classroom but has not
               published any public content.<br>Please check back to receive updates from {$luser[0]['title']} {$luser[0]['name_f']} {$luser[0]['name_l']}.</p>
            </div>
EOF;
         }else{
            echo <<<EOF
            <div id="pAnnounce"><div class="closeBtn"><a class="close"></a></div>
               <p>Welcome to Educator-Classroom, the best free way to keep your classroom updated!<br>
               In this account,
               <span>a teacher can make announcements, post useful links, share videos, add photos and upload important documents for your class to download.</span>
               </p>
               <div id="pAnnProgress"><img src="/image/progressbar.png" alt="">70% Profile Completeness</div>
               <div id="pAnnTask">
                  <a href="/editprofile.php">Complete your Online Teacher Profile</a>
                  <a>Verify your email address by clicking on the confirmation link</a>
               </div>
            </div>
EOF;
         }
      }
   }
}else{
   echo "Misleading request received";
}
?>
