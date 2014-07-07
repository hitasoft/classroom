<?php
/*
 * get_files.php : Get list of uploaded files of live user
 *      
 */
session_name('connect');
session_start();
if ($_SERVER['HTTP_HOST']!="classroom.educator.com") $liveDir="/classroom";
include_once("../incl/base.php");
define('LIMIT',25);

function expandLink($type){
   global $post,$pcount,$liveDir;
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
      $rowClass=($post['privacy']>0)?"private ":"";
      switch ($type){
         case 'pDocs':
            $ul=$post['login'];$pid=$post['post_id'];
            foreach ($rData as $link){
               extract($link);
               $pText=txt2lnk($fdesc);
               $path=user_dir($ul,$pid.'_'.$fname,false,false);
               $fname=pathinfo($fname);
               $ext=$fname['extension'];
               $fname=$fname['filename'];
               $rpath=($liveDir)?substr($path,strlen($liveDir)):$path;
               $fsize=formatBytes(filesize(LIVE_DIR.$rpath));
               $oname=$fname.".".$ext;
               $rowClass.=($pcount % 2)?"":"even";
               $code.=<<<EOF
   <tr id='{$post['post_id']}_pid$dcount' class='$rowClass'>
      <td class='pDocIcon'><img src='$liveDir/image/icon/docs/$ext.png' alt='$ext'></div>
      <td class='pDocName'>
         <p>
            <a class='pdDown ellipsis' href='$liveDir/download.php?file=$rpath' title="Download $oname">$fname</a>
            <a class='pdView' href='$path' target='_blank' title="View $oname"></a>
         </p>
         <p class='pDocDesc'>$pText</p>
      </td>      
      <td class='pDocSize'>$fsize</td>
      <td class='pDocDate'>{$post['postdate']}</td>
   </tr>
EOF;
               $pcount++;
               $dcount++;
            }
            break;
         default:
      }
   }
   return $code;
}

if ($_SERVER['REQUEST_METHOD']=="POST"){
	$vars = & get_vars();
   if ($vars['uid']){
      $sort="ORDER BY posted DESC";
      if ($vars['live']){
         $limit_cl="AND privacy=0 ";
      }else{
         $limit_cl="";
      }
      $limit_cl.="AND p_type LIKE 'pDocs' ";
      if ($vars['postid']){
         $limit_cl.="AND post_id>{$vars['postid']} ";
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

      $pquery=$dbc->query("SELECT lp.*, DATE_FORMAT(lp.posted,'%M %d %Y') as postdate, lm.login FROM live_posts lp INNER JOIN am_user lm ON lp.amember_id=lm.user_id WHERE lm.user_id={$vars['uid']} $limit_cl");
      $pcount=0;
      if ($post=$dbc->fetch($pquery)){
         do{
            print expandLink($post['p_type']);
         }while ($post=$dbc->fetch($pquery));
         //if ($init>0 && $pcount==LIMIT) echo "<div id='morePost' onclick='loadPost(null,$init)'>Show older files</div>\n";
      }
   }else{
      echo "<tr><td colspan='4'>Misleading request received</td></tr>";
   }
}else{
   echo "<tr><td colspan='4'>Malformed request received</td></tr>";
}
?>
