<?php
function sortByLevel($a, $b){
  if ($a["level"] == $b["level"]) {
    return strcmp($a["bssid"],$b["bssid"]);
  }
  return ($a["level"] > $b["level"]) ? -1 : 1;
}
// parse input
$requests = file("requests.log");
$data = array();
foreach ($requests as $request) {
  $parts = explode("\t",$request);
  list ($date, $time, $json) = $parts;
  $jsonobj = json_decode($json);
  $phone = $jsonobj->{"phone"};
  $comment = $jsonobj->{"comment"};
  $jsonstations = $jsonobj->{"stations"};
  $stations = array();
  foreach ($jsonstations as $jsonstation) {
    $stations[] = array("bssid"=>$jsonstation->{"bssid"}, "level"=>intval($jsonstation->{"level"}));
  }
  usort($stations,"sortByLevel");
  $data[] = array("date"=>$date, "time"=>$time, "phone"=>$phone, "stations"=>$stations, "comment"=>$comment);
}
// filter based on user supplied parameters
if (isset($_GET["phone"])) {
  function phone($var) {
    return $var["phone"] == $_GET["phone"];
  }
  $data = array_filter($data, "phone");
}
if (!isset($_GET["i"])) {
  $index = -1;
} else {
  $index = $_GET["i"];
}
// select data
if ($index < 0) {
  $selected = $data[count($data)+$index];
} else {
  $selected = $data[$index];
}
function level2radius($x) {
  return 1.09491*exp(-0.0561178*$x)+10;
}

$log = array();
$svgxml = file_get_contents("map.svg");
$aplayerlabelpos = strpos($svgxml,'inkscape:label="APs"') or die ("Could not find Layer");
$aplayerbeginpos = strrpos($svgxml,'<g',$aplayerlabelpos-strlen($svgxml));
$innergrouppos = strpos($svgxml,'<g',$aplayerlabelpos);
if ($innergrouppos) { die ("Inner groups are not supported."); }
$aplayerendpos = strpos($svgxml,'</g>',$aplayerlabelpos)+4;
$aplayerxml = substr($svgxml,$aplayerbeginpos,$aplayerendpos-$aplayerbeginpos);
$aplayer = new SimpleXMLElement($aplayerxml);
$stations = $selected["stations"];
foreach ($stations as $station) {
  $level = $station["level"];
  $radius = level2radius($level);
  $opacity = (130 + $level)/150.0;
  $bssid = $station["bssid"];
  
  if ($result = $aplayer->xpath("//circle[@id = '".$bssid."']")) {
    $result[0]->attributes()->r = $radius;
    $result[0]->attributes()->style = "fill:#FF0000;fill-opacity:".$opacity.";";
  }
  if ($result = $aplayer->xpath("//rect[@id = '".$bssid."']")) {
    $x = $result[0]->attributes()->x - $result[0]->attributes()->width/2;
    $y = $result[0]->attributes()->y - $result[0]->attributes()->height/2;
    $result[0]->attributes()->x = $x - $radius;
    $result[0]->attributes()->y = $y - $radius;
    $result[0]->attributes()->width = $radius*2;
    $result[0]->attributes()->height = $radius*2;
    $result[0]->attributes()->style = "fill:#FF0000;fill-opacity:".$opacity.";";
  }
}
$result = $aplayer->xpath("//text")[0];
$result->children()->tspan[0] = "Letztes Update: ".$selected["date"]." ".$selected["time"]." ".$selected["comment"];

$aplayerxml = $aplayer->asXML();
$aplayerxml = substr($aplayerxml,strpos($aplayerxml,"\n"));
$svgxml = substr($svgxml,0,$aplayerbeginpos).$aplayerxml.substr($svgxml,$aplayerendpos);
header('Content-Type: image/svg+xml');
echo $svgxml;
?>
