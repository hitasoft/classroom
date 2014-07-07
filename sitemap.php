<?php
include('incl/base.php');
$liveUser=getUser($user);
$fname=$liveUser['name_f'].' '.$liveUser['name_l'];
print <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

EOF;
$sql="SELECT `login`,
      (select max(edited) from live_posts where amember_id=live_members.member_id) as posts,
      (select max(edited) from live_profile where amember_id=live_members.member_id) as profile 
   FROM `live_members` WHERE member_id NOT IN 
   (SELECT `member_id` FROM `live_members` WHERE member_id NOT IN 
      (SELECT `amember_id` FROM `live_profile` WHERE concat(`address`,`primary`,`officehrs`,`phoneo`,`phonep`,`fax`,`email`,`website`,`about`)!='') 
      AND member_id NOT IN (SELECT amember_id FROM live_posts)
   )";
$uquery=$dbc->query($sql);
if ($users=$dbc->fetch($uquery)){
   do{
      echo "<url>\n";
      echo "   <loc>http://${_SERVER['HTTP_HOST']}/${users['login']}</loc>\n";
      $lastmod=($users['posts']>$users['profile'])?$users['posts']:$users['profile'];
      $lastmod=substr($lastmod,0,10);   // only date
      echo "   <lastmod>$lastmod</lastmod>\n";
      echo "   <changefreq>weekly</changefreq>\n";
      echo "</url>\n";
   }while ($users=$dbc->fetch($uquery));
}

echo "</urlset>";
?>
