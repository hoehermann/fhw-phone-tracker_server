<?php
require("parseinput.php");
$data = parse_input();
$selected = select_data($data);
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
        margin:0px;
        padding:0px;
        font-family: sans-serif;
      }
      svg {
        position: absolute;
      }
  </style>
  <script>
    function level2radius(x) {
      return 1.09491*Math.exp(-0.0561178*x)+10;
    }
    function markAPs() {
      var svgmap = document.getElementsByTagName("svg")[0];
      try {
        var becauseieistupid = svgmap.getSVGDocument();
        if (becauseieistupid != null) {
          svgmap = becauseieistupid;
        }
      } catch (err) {}
      <?php
        echo "svgmap.getElementById(\"note\").firstChild.innerHTML = \"".$selected["date"]." ".$selected["time"]." ".$selected["comment"]."\";";
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
  </script>
</head>
<body onload="markAPs();"><?php
if (isset($_GET["list"])) {
  for ($i = 0; $i < count($data) ; $i++) {
    $d = $data[$i];
    echo $i."/".($i-count($data)).": ".$d["date"]." ".$d["time"]." ".$d["comment"]."<br/>\n";
    foreach ($d["stations"] as $station) {
      if ($station["level"] > -70) {
        echo "BSSID: ".$station["bssid"]." LEVEL: ".$station["level"]."<br/>\n";
      }
    }
    echo "<br/>\n";
  }
}
echo file_get_contents("map.svg");
?></body>
</html>
