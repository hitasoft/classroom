<?php
   include('account/config.inc.php');
   $t = & new_smarty();
   $_product_id[0] = 'ONLY_LOGIN';

   include($config['plugins_dir']['protect'] . '/php_include/check.inc.php');
   if ($_GET['amember_redirect_url'])
      $redirect=$_GET['amember_redirect_url'];
   else
      $redirect = "/".$_SESSION['_amember_login'];
   html_redirect("$redirect", 0, 'Redirect', _LOGIN_REDIRECT);
?>
