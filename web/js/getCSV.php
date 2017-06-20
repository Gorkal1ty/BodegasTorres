<?php
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\..\bd\"stock.csv");
$data=stripcslashes($_REQUEST['csv_text']);
echo $data; 
?>