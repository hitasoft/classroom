<?php
if (!(isset($_GET['file']) && $_GET['file']))
   die('There is nothing here. Go away.');

include('incl/common.php');
$file=$root_dir.$_GET['file'];
header('Content-disposition: attachment; filename='.basename($file));
header('Content-type: '.mime_content_type($file));
readfile($file);
?> 
