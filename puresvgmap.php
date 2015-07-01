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

function get_ap_position($aplayer, $bssid) {
  $result = $aplayer->xpath("//svg:circle[@id = '".$bssid."']");
  if (!$result) {
    return false;
  }
  return array(floatval($result[0]->attributes()->cx), floatval($result[0]->attributes()->cy), $result[0]);
}
function euclidean_distance($a,$b) {
  return sqrt(pow(($a[0]-$b[0]),2)+pow(($b[1]-$a[1]),2));
}

$log = array();
$svgxml = file_get_contents("map.svg");

// don't parse the whole map. find the layer containing the APs
$aplayerlabelpos = strpos($svgxml,'inkscape:label="APs"') or die ("Could not find Layer");
$aplayerbeginpos = strrpos($svgxml,'<g',$aplayerlabelpos-strlen($svgxml));
$innergrouppos = strpos($svgxml,'<g',$aplayerlabelpos);
if ($innergrouppos) { die ("Inner groups are not supported."); }
$aplayerendpos = strpos($svgxml,'</g>',$aplayerlabelpos)+4;
$aplayerxml = substr($svgxml,$aplayerbeginpos,$aplayerendpos-$aplayerbeginpos);
$aplayerxml = '<svg
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:cc="http://creativecommons.org/ns#"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:svg="http://www.w3.org/2000/svg"
   xmlns="http://www.w3.org/2000/svg"
   xmlns:xlink="http://www.w3.org/1999/xlink"
   xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
   xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">'.$aplayerxml.'</svg>'; // import da namespaces
$aplayer = new SimpleXMLElement($aplayerxml);
$aplayer = $aplayer->{"g"};
// $aplayer now holds the parsed SVG XML document

$stations = $selected["stations"];
$bssid2radius = array();
foreach ($stations as $station) {
  $level = $station["level"];
  $radius = level2radius($level);
  $opacity = level2opacity($level);
  $bssid = $station["bssid"]; // TODO: strip frequency and vlan-part of the BSSIDs, then use [contains(@id, '".relevantbssid."')] for xpath selector or mangle the ids programatically
  
  if ($result = $aplayer->xpath("//svg:circle[@id = '".$bssid."']")) {
    $result[0]->attributes()->r = $radius;
    $result[0]->attributes()->style = str_replace("fill:#000000;","fill:#FF0000;fill-opacity:".$opacity.";",$result[0]->attributes()->style);
    $bssid2radius[$bssid] = $radius;
  }
}

// if too small, force circles to overlap by making big circles even bigger
if (count($bssid2radius) > 0) {
  $bestBSSID = array_keys($bssid2radius, min($bssid2radius));
  $bestBSSID = $bestBSSID[0];
  $bestBSSIDpos = get_ap_position($aplayer, $bestBSSID);
  $bestRadius = $bssid2radius[$bestBSSID];
  foreach($bssid2radius as $bssid => $radius) {
    if ($bssid != $bestBSSID) {
      $BSSIDpos = get_ap_position($aplayer, $bssid);
      $dist = euclidean_distance($bestBSSIDpos,$BSSIDpos);
      $radius = 1.1*$dist - $bestRadius;
      if ($BSSIDpos[2]->attributes()->r < $radius) {
        $BSSIDpos[2]->attributes()->r = $radius;
      }
    }
  }
}

if ($result = $aplayer->xpath("//svg:text")) {
  $result[0][0] = "Letztes Update: ".$selected["date"]." ".$selected["time"]." ".$selected["comment"];
}

$aplayerxml = $aplayer->asXML();
$svgxml = substr($svgxml,0,$aplayerbeginpos).$aplayerxml.substr($svgxml,$aplayerendpos);
header('Content-Type: image/svg+xml');
echo $svgxml;
?>
