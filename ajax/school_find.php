<?php

$root_dir=$_SERVER["DOCUMENT_ROOT"];
$tmpl=$root_dir."/tmpl/";$incl=$root_dir."/incl/";
require_once $incl."db.class.php";

if ($_SERVER['REQUEST_METHOD']=="POST"){
	$str=$_POST['s'];
	$lim=(int) $_POST['l'];
	$off=(int) $_POST['o'];
	if (strlen($str)<3) die();
	$str=trim($str);
	if (is_numeric($str)) $cond="zip LIKE '$str%'";
	else $cond="school LIKE '%$str%'";
	$qry=$dbc->query("SELECT school,city,st,zip,country FROM live_school WHERE $cond ORDER BY school,city LIMIT $lim OFFSET $off");
	if ($row=$dbc->fetch($qry)){
		do {
			extract($row);
			echo "<dd>$school   : ".ucwords(strtolower($city)).", $st $zip, $country</dd>";
		} while($row=$dbc->fetch($qry));
	}else{
		if ($off==0) echo "<dd class='disabled'> - not found - </dd>";
	}
}else{
	echo "-";
}