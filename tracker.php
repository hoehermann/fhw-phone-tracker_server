<?php
$filename = "requests.log";
if (filesize($filename) > 200000) {
  file_put_contents($filename.".old",file_get_contents($filename),FILE_APPEND|LOCK_EX);
  file_put_contents($filename,"",LOCK_EX);
}
$date = date("Y-m-d\tH:i:s");
$post = file_get_contents('php://input');
$tofile = $date."\t".$post."\n";
file_put_contents($filename,$tofile,FILE_APPEND|LOCK_EX);
?>
