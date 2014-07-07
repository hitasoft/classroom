<?php
	include 'incl/common.php';
	require_once 'account/bootstrap.php';
	$user=Am_Di::getInstance()->auth->getUser();
	echo "<pre>";
	$nl=$user->get('login');
	$ol='newtest';
	$oud=user_dir($ol,'',false);
	$nud=user_dir($nl,'',false);
	echo "$oud -> $nud\n";
	if (rename($oud,$nud)) echo 'MOVED';
	if (file_exists($nud)) echo " - yes!";
	if (file_exists($oud)) echo " - no!";
	rename("$nud/pp_$ol.jpg","$nud/pp_$nl.jpg");
	echo "</pre>";

?>