<?php
function sortByLevel($a, $b){
  if ($a["level"] == $b["level"]) {
    return strcmp($a["bssid"],$b["bssid"]);
  }
  return ($a["level"] > $b["level"]) ? -1 : 1;
}
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
    $stations[] = array("bssid"=>$jsonstation->{"bssid"}, "level"=>$jsonstation->{"level"});
  }
  usort($stations,"sortByLevel");
  $data[] = array("date"=>$date, "time"=>$time, "phone"=>$phone, "stations"=>$stations, "comment"=>$comment);
}
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
if ($index < 0) {
  $selected = $data[count($data)+$index];
} else {
  $selected = $data[$index];
}
$js = <<<EOT
    function level2radius(x) {
      return 1.09491*Math.exp(-0.0561178*x)+10;
    }
    function markAPs() {
      var svgmap = document;
EOT;
        $js .= "svgmap.getElementById(\"note\").firstChild.innerHTML = \"".$selected["date"]." ".$selected["time"]." ".$selected["comment"]."\";\n";
        // TODO: replace this by in-svg css
        $stations = $selected["stations"];
        $bssids = array();
        $levels = array();
        foreach ($stations as $station) {
          $bssids[] = $station["bssid"];
          $levels[] = $station["level"];
        }
        $js .= "var bssids = [\"".implode("\", \"",$bssids)."\"];\n";
        $js .= "var levels = [\"".implode("\", \"",$levels)."\"];\n";
$js .= <<<EOT
      for (var i = 0; i < bssids.length ; ++i)  {
        var bssid = bssids[i];
        var level = parseInt(levels[i]);
        if (level < -100) {
          level = -100;
        }
        if (level > -30) {
          level = -30;
        }
        var ap = svgmap.getElementById(bssid);
        if (ap == null) {
          console.log("unknown bssid "+bssid);
        } else {
          console.log("marked bssid "+bssid+" with level "+level);
          ap.style["fill"] = "red";
          ap.style["fill-opacity"] = (130 + level)/100.0/2;
          var radius = level2radius(level);
          if (ap.tagName == "circle") {
            ap.r.baseVal.value = radius;
          }
          else if (ap.tagName == "rect") {
            var x = ap.x.baseVal.value + ap.width.baseVal.value/2;
            var y = ap.y.baseVal.value + ap.height.baseVal.value/2;
            ap.height.baseVal.value = radius*2;
            ap.width.baseVal.value = radius*2;
            ap.x.baseVal.value = x-radius;
            ap.y.baseVal.value = y-radius;
          }
        }
      }
    }
EOT;
$mapdata = file_get_contents("map.svg");
header('Content-Type: image/svg+xml');
echo str_replace("/*JAVASCRIPTGOESHERE*/",$js,$mapdata);
