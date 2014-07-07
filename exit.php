<?php
session_start();
echo 'cc<br>';

print_r($_SESSION);
session_destroy();

?>
