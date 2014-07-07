<?php
session_name('connect');
session_start();
include("../incl/base.php");

if (!$ul){
	$msg="Login has expired.";
}else if ($_SERVER['REQUEST_METHOD']=="POST"){
	extract($_POST);
	$uid=$user['user_id'];
	if (isset($school)){
		$school=serialize(array($school));
		$ret=$dbc->update('live_profile',array('school'=>$school),'amember_id='.$uid);
		if (!$ret) $ret=$dbc->insert('live_profile',array('amember_id'=>$uid,'school'=>$school));
		$msg=1;
	}elseif (isset($title)){
		$ret=$dbc->update('am_user',array('title'=>$title),'user_id='.$uid);
		$msg=1;
	}else{
		$msg="Unknown request received";
	}
}else{
	$msg="Unauthorized request received.";
}
echo '{"status":"'.$msg.'"}';
?>
