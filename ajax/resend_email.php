<?php
/*
 * resend_email.php : Resend verification email
 *      
 */
session_name('connect');
session_start();
include("../incl/base.php");
include "../account/config.inc.php";

$uid=$_SESSION['_amember_id'];
if (!$uid){
   $msg="Login has expired";
}else{
   $payments = $db->get_user_payments($uid);

   //Get first payment
   end($payments);
   $payment  = current($payments);

   $payment_id = $payment['payment_id'];
   $code       = $payment['data']['email_confirm']['code'];
   $u          = $db->get_user($uid);

   if ( $payment['completed'] ) {
      $msg='Verification already completed';
   } elseif (!$payment_id || !$code) {
      $msg='Could not send verification email';
   } else {
      mail_verification_email($u, $config['root_url'] . "/signup.php?cs=" . $payment_id . "-" . $code);
      $msg='success';
   }
}
echo $msg;
?>
