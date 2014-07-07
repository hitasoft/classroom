<?php
include '../incl/common.php';
if ($_SERVER['REQUEST_METHOD']=="POST"){
	require_once '../account/bootstrap.php';
	$user=Am_Di::getInstance()->auth->getUser();
	$nl=$_POST['login'];
	if ($nl){
		$invalid=strlen($nl) < Am_Di::getInstance()->config->get('login_min_length');
		$invalid=$invalid || strlen($nl) > Am_Di::getInstance()->config->get('login_max_length');
		$invalid=$invalid || !preg_match("/^[0-9a-zA-Z_]+$/",$nl);
		$invalid=$invalid || file_exists($_SERVER['DOCUMENT_ROOT'].'/../'.$nl);
		if ($invalid) die();
		$ol=$user->get('login');
		$oud=user_dir($ol,'',false);
		$user->updateQuick('login',$nl);
		Am_Di::getInstance()->auth->setUser($user);
		// update user dir
		if (file_exists($oud)) {
			$nud=user_dir($nl,'',false);
			if (rename($oud,$nud)) rename("$nud/pp_$ol.jpg","$nud/pp_$nl.jpg");
		}
		echo "success";
	}
}
?>