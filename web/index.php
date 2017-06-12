<?php
header('Content-Type: application/json');
ob_start();
$json = file_get_contents('php://input'); 
$request = json_decode($json, true);
$action = $request["result"]["action"];
$parameters = $request["result"]["parameters"];

#[Code to set $outputtext, $nextcontext, $param1, $param2 values]
$outputtext = "HOLA";
$param1value = "parametro1";
$param2value = "parametro2";

$output["contextOut"] = array(array("name" => "$next-context", "parameters" =>
array("param1" => $param1value, "param2" => $param2value)));
$output["speech"] = $outputtext;
$output["displayText"] = $outputtext;
$output["source"] = "consultarStock.php";
ob_end_clean();
echo json_encode($output);
?>