<?php
/*
*      setprofile.php
*      
*      Update the profile information of live user
*      
*/
require_once 'account/bootstrap.php';
session_name('connect');
session_start();

if (!include('incl/base.php')) die('Base classes not found');

if (checkuser()){
	// edit profile
	$error = '';$ajax=false;
	$vars = & get_vars();
	$parray=$dbc->arrays("SELECT * FROM live_profile WHERE amember_id={$user['user_id']}");
	$first_time=count($parray)==0;
	if ($vars['submit_pi']){
		foreach ($vars as $k=>$v){
			if (substr($k,0,2)=="f_")
			if (is_array($v)){
				$v=array_filter($v);
				$values[substr($k,2)]=serialize($v);
			}else{
				$values[substr($k,2)]=$v;
			}
		}
		if ($values['classes']){
			$profile=$values;
			$ret=1;
		}else{
			if ($values['state']) $ret=$dbc->insert("live_school",$values);
			else $ret=1;
			$profile['school']=$values['school'];
		}
		if ($ret>0){
			if ($first_time){
				$profile['amember_id']=$user['user_id'];
				$ret=$dbc->insert("live_profile",$profile);
			}else{
				$ret=$dbc->update("live_profile",$profile,"amember_id={$user['user_id']}");
			}
		}
		$submit_msg=(($ret<0)?"Could not save the data":"success");
		
		//print_r($values);
		if ($vars['submit_pi']=="ajax"){
			echo $submit_msg;
			$ajax=true;
		}
	}else{
		if (!$first_time){
			foreach ($parray[0] as $k=>$v){
				$vars['f_'.$k]=$v;
			}
		}
	}
	if (!$ajax){
		include($tmpl."setprofile.html");
	}
}else{
	// not logged in amember - show the login page
	Am_Lite::getInstance()->checkAccess(Am_Lite::ANY, 'Profile set'); 
}

?>
