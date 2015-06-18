<?php
#echo "Begin";
$date = date("Y-m-d\tH:i:s");
$post = file_get_contents('php://input'); #var_export($_POST["name"]);
$tofile = $date."\t".$post."\n";
file_put_contents("requests.log",$tofile,FILE_APPEND|LOCK_EX);
var_dump($tofile);
#echo "OK";
?>
