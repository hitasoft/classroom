<?php
/*
 * about.php : Detailed about-me info of live user
 *      
 */

function showData($data,$live,$multi=0){
   if (count($data)>0){
      $id=$data['id'];
      $val=$data['value'];
      if ($live){
         $val=preg_replace("/\\r\\n|\\r|\\n/","<br>",$val);
         echo "<p>";
         if ($data['ftitle'])
            echo "<span>{$data['ftitle']}: </span>";
         echo "</p>$val";
      }else{
         if ($data['ftitle'])
            echo "<label for='$id'>{$data['ftitle']}</label>";
         elseif ($multi)
            $addBtn='<div class="addBtn"></div>';
         echo "<input type='hidden' name='{$data['name']}' value='".str_replace("'","&#039;",$val)."'>";
         echo "<textarea id='$id'>$val</textarea>$addBtn";
      }
   }else{
      echo "No data";
   }
}

if ($_SERVER['REQUEST_METHOD']=="POST"){
   session_name('connect');
   session_start();
   include_once("../incl/base.php");
	$vars = & get_vars();
}elseif(!$liveDir){
   die("Malformed request received");
}
if ($vars['uid']){
   $uid=$vars['uid'];
   $parray=$dbc->arrays("SELECT lp.*,lm.login,lm.title as prefix,lm.name_f,lm.name_l FROM live_profile lp INNER JOIN am_user lm ON lp.amember_id=lm.user_id WHERE lp.amember_id=$uid");
   $first_time=count($parray)==0;
   if ($vars['save']){
      $uid=$_SESSION['_amember_id'];
      if ($uid==$vars['uid']){
         foreach ($vars as $k=>$v){
            if (substr($k,0,2)=="an"){
               $adata[$k]=$v;
            }
         }
         $values['about']=serialize($adata);
         if ($first_time){
            $values['amember_id']=$uid;
            $ret=$dbc->insert("live_profile",$values);
         }else{
            $ret=$dbc->update("live_profile",$values,"amember_id=$uid");
         }
         echo (($ret<0)?"Could not save profile data":"success");
      }else{
         echo "Login expired. Please login and try again.";
      }
   }else{
      if (!$first_time){
         foreach ($parray[0] as $k=>$v){
            $vars['pi_'.$k]=$v;
         }
      }
      
      $contact=array(
         'address'=>'Address',
         'primary'=>'Primary Office',
         'officehrs'=>'Office Hours',
         'phoneo'=>'Office Phone',
         'phonep'=>'Personal Phone',
         'fax'=>'Fax',
         'email'=>'Email',
         'website'=>'Website'
      );
      foreach (array('school','subject','about') as $field){
         $adata=unserialize($vars['pi_'.$field]);
         if (!is_array($adata) || count($adata)==0){
            $adata=array("");
         }
         if ($field=='about'){
            $category=array(
               'Bio'=>array(
                  'name'=>'anBio',
                  'type'=>0
               ),
               'Courses'=>array(
                  'name'=>'anCourses',
                  'type'=>0
               ),
               'Education'=>array(
                  'name'=>'anEdu',
                  'type'=>1,
                  'id'=>array('afDegrees'=>'Degrees','afCollege'=>'College/University','afSchool'=>'High School')
               ),
               'Positions'=>array(
                  'name'=>'anPos',
                  'type'=>0
               ),
               'Professional Service'=>array(
                  'name'=>'anProf',
                  'type'=>0
               ),
               'Areas of Specialization/Expertise'=>array(
                  'name'=>'anSpec',
                  'type'=>0
               ),
               'Honors and Awards'=>array(
                  'name'=>'anHon',
                  'type'=>0
               ),
               'Certifications'=>array(
                  'name'=>'anCert',
                  'type'=>0
               ),
               'Research'=>array(
                  'name'=>'anRes',
                  'type'=>0
               ),
               'Funded Projects'=>array(
                  'name'=>'anProj',
                  'type'=>0
               ),
               'Selected Publications'=>array(
                  'name'=>'anPub',
                  'type'=>0
               ),
               'Journal Articles and Book Chapters'=>array(
                  'name'=>'anArt',
                  'type'=>0
               ),
               'Presentations'=>array(
                  'name'=>'anPres',
                  'type'=>0
               ),
               'Media Appearances'=>array(
                  'name'=>'anMed',
                  'type'=>0
               ),
               'Papers in Proceedings'=>array(
                  'name'=>'anPap',
                  'type'=>0
               ),
               'Technical Reports'=>array(
                  'name'=>'anRep',
                  'type'=>0
               ),
               'Students'=>array(
                  'name'=>'anStu',
                  'type'=>0
               ),
               'Personal'=>array(
                  'name'=>'anPer',
                  'type'=>1,
                  'id'=>array('afInterests'=>'Interests','afMusic'=>'Music','afMovies'=>'Movies','afBooks'=>'Books','afQuotes'=>'Quotes','afLinks'=>'Links')
               ),
            );
            $count=0;
            foreach ($category as $cat=>$subcat){
               $aCat[$count]['name']=$subcat['name'];
               $aCat[$count]['title']=$cat;
               $aCat[$count]['type']=$subcat['type'];
               if ($subcat['type']==0){
                  $val=$adata[$subcat['name']];
                  if ($val || !$vars['live']){ 
                     $aCat[$count]['id']='af'.substr($subcat['name'],2);
                     $aCat[$count]['value']=$val;
                  }else{
                     array_pop($aCat);
                  }
               }elseif ($subcat['type']==1){
                  if (is_array($subcat['id'])){
                     $subcount=0;
                     foreach ($subcat['id'] as $id=>$title){
                        $name='an'.substr($id,2);
                        $val=$adata[$name];
                        if ($val || !$vars['live']){ 
                           $aCat[$count]['sub'][$subcount]['id']=$id;
                           $aCat[$count]['sub'][$subcount]['name']=$name;
                           $aCat[$count]['sub'][$subcount]['ftitle']=$title;
                           $aCat[$count]['sub'][$subcount]['value']=$val;
                           $subcount++;
                        }
                     }
                     if ($subcount==0){
                        array_pop($aCat);
                     }
                  }else{
                     // todo: will not work for retrieving data
                     $name='an'.substr($subcat['id'].'0',2);
                     $aCat[$count]['sub'][0]['id']=$subcat['id'].'0';
                     $aCat[$count]['sub'][0]['name']=$name;
                     $aCat[$count]['sub'][0]['value']=$adata[$name];
                  }
               }elseif ($subcat['type']==2){
               }
               $count++;
            }
         }else{
            //$aProfile.="<p>".implode(', ',$adata)."</p>";
         }
      }
      
      $cInfo="\n<div id='abtProfile'><h2>{$vars['pi_prefix']}</h2>\n";
      if (!$vars['live'])
         $cInfo.=<<<EOF
         <div class="actionBar">
            <div class="actionLinksBtn">
               <span> </span>
            </div>
            <ul class="actionLinks">
               <li class="actionEdit">
                  <a class="" href="/editprofile.php" title="Edit profile information">Edit</a>
               </li>
            </ul>
         </div>\n
EOF;
      foreach ($contact as $field=>$title){
         $val=$vars['pi_'.$field];
         if ($val || !$vars['live'])
            $cInfoDet.="<p><span>$title: </span>$val</p>\n";
      }
      if ($cInfoDet) $cInfo.=$cInfoDet;
      $cInfo.="</div>\n";
      if (!$cInfoDet && $vars['live'] && count($aCat)==0){
         $luser=$dbc->arrays("SELECT lm.* FROM am_user lm WHERE lm.user_id=$uid LIMIT 1");
         echo <<<EOF
         <div id="pAnnounce">
            <div id="pAnnHdr">Announcements</div>\n
            <h2>Welcome to {$luser[0]['title']} {$luser[0]['name_f']} {$luser[0]['name_l']}'s Class Website!</h2>
            <p>{$luser[0]['title']} {$luser[0]['name_f']} {$luser[0]['name_l']} is using Educator-Connect but has not
            published any public content.<br>Please check back to receive updates from {$luser[0]['title']} {$luser[0]['name_f']} {$luser[0]['name_l']}.</p>
         </div>\n
EOF;
      }else{
         echo $cInfo;
      }
      
      if (count($aCat)>0){
         echo "<div id='abtLinks'>\n<h3>Section Links</h3>";
         foreach ($aCat as $data)
            echo "<a href='#{$data['name']}'>{$data['title']}</a> ";
         echo "</div>\n";
         
         echo "<div id='abtCat'>\n";
         foreach ($aCat as $data){
            echo "<div class='abtSubCat'><a name='{$data['name']}'><h3>{$data['title']}</h3></a><div class='abtFields type{$data['type']}' id='fld_{$data['name']}'>\n";
            if ($data['type']==1){
               echo "<ul>";
               foreach ($data['sub'] as $sub){
                  echo "<li>";
                  showData($sub,$vars['live'],1);
                  echo "</li>";
               }
               echo "</ul>\n";
            }else{
               showData($data,$vars['live']);
            }
            if (!$vars['live']){
               echo "<div class='actionRow'><button id='btnSave_{$data['name']}'>Save</button><button id='btnCancel_{$data['name']}'>Cancel</button></div>\n";
            }
            echo "</div></div>\n";
         }
         echo "</div>";
      }
      if (!$liveDir) echo "<script type='text/javascript' src='/js/tiny_mce/jquery.tinymce.js'></script>";
   }
}else{
   echo "Misleading request received";
}
?>
