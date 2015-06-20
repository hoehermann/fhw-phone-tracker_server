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
  $selected = $data[count($data)-1];
} else {
  $selected = $data[$_GET["i"]];
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Map</title>
  <meta charset="UTF-8" />
  <style type="text/css">
      body, html {
        height:100%;
        width:100%;
        margin: 0px;
        font-family: sans-serif;
      }
  </style>
  <script>
    function level2radius(x) {
      return 1.09491*(Math.exp(-0.0561178*x)+10);
    }
    function markAPs() {
      var svgmap = document.getElementsByTagName("svg")[0];
      svgmap.style.height="100%";
      <?php
      // TODO: replace this by in-svg css
        $stations = $selected["stations"];
        $bssids = array();
        $levels = array();
        foreach ($stations as $station) {
          $bssids[] = $station["bssid"];
          $levels[] = $station["level"];
        }
        echo "var bssids = [\"".implode("\", \"",$bssids)."\"];";
        echo "var levels = [\"".implode("\", \"",$levels)."\"];";
      ?>
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
            ap.height.baseVal.value = radius*2;
            ap.width.baseVal.value = radius*2;
            ap.x.baseVal.value -= radius;
            ap.y.baseVal.value -= radius;
          }
        }
      }
    }
  </script>
</head>
<body onload="markAPs();">
<?php
if (false) {
  foreach ($data as $d) {
    echo $d["comment"]."<br/>\n";
    foreach ($d["stations"] as $station) {
      if ($station["level"] > -70) {
        echo "BSSID: ".$station["bssid"]." LEVEL: ".$station["level"]."<br/>\n";
      }
    }
    echo "<br/>\n";
  }
}
echo file_get_contents("map.svg");
echo "\n<br/>".$selected["comment"];
?>
</body>
</html>
