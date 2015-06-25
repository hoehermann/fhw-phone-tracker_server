<?php
if (!class_exists("SimpleXMLElement")) {
  die("class SimpleXMLElement is missing!");
}
require("parseinput.php");
$data = parse_input();
$selected = select_data($data);

function level2radius($x) {
  return 1.09491*exp(-0.0561178*$x)+10;
}

function level2opacity($x) {
  $op = 3.81878*exp(0.0417204*$x);
  if ($op > 1.0) {
    $op = 1.0;
  } else if ($op < 0.01) {
    $op = 0.01;
  }
  return $op;
}

$log = array();
$svgxml = file_get_contents("map.svg");
$aplayerlabelpos = strpos($svgxml,'inkscape:label="APs"') or die ("Could not find Layer");
$aplayerbeginpos = strrpos($svgxml,'<g',$aplayerlabelpos-strlen($svgxml));
$innergrouppos = strpos($svgxml,'<g',$aplayerlabelpos);
if ($innergrouppos) { die ("Inner groups are not supported."); }
$aplayerendpos = strpos($svgxml,'</g>',$aplayerlabelpos)+4;
$aplayerxml = substr($svgxml,$aplayerbeginpos,$aplayerendpos-$aplayerbeginpos);
$aplayer = new SimpleXMLElement('<svg
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:cc="http://creativecommons.org/ns#"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:svg="http://www.w3.org/2000/svg"
   xmlns="http://www.w3.org/2000/svg"
   xmlns:xlink="http://www.w3.org/1999/xlink"
   xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
   xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">'.$aplayerxml.'</svg>');
$aplayer = $aplayer->{"g"};
$stations = $selected["stations"];
foreach ($stations as $station) {
  $level = $station["level"];
  $radius = level2radius($level);
  $opacity = level2opacity($level);
  $bssid = $station["bssid"];
  
  if ($result = $aplayer->xpath("//svg:circle[@id = '".$bssid."']")) {
    $result[0]->attributes()->r = $radius;
    $result[0]->attributes()->style = "fill:#FF0000;fill-opacity:".$opacity.";";
  }
  if ($result = $aplayer->xpath("//svg:rect[@id = '".$bssid."']")) {
    $x = $result[0]->attributes()->x - $result[0]->attributes()->width/2;
    $y = $result[0]->attributes()->y - $result[0]->attributes()->height/2;
    $result[0]->attributes()->x = $x - $radius;
    $result[0]->attributes()->y = $y - $radius;
    $result[0]->attributes()->width = $radius*2;
    $result[0]->attributes()->height = $radius*2;
    $result[0]->attributes()->style = "fill:#FF0000;fill-opacity:".$opacity.";";
  }
}
$result = $aplayer->xpath("//svg:text")[0];
$result->children()->tspan[0] = "Letztes Update: ".$selected["date"]." ".$selected["time"]." ".$selected["comment"];

$aplayerxml = $aplayer->asXML();
$svgxml = substr($svgxml,0,$aplayerbeginpos).$aplayerxml.substr($svgxml,$aplayerendpos);
header('Content-Type: image/svg+xml');
echo $svgxml;
?>
