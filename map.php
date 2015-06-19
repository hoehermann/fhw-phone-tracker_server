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
?>
<!DOCTYPE html>
<html>
<head>
	<title>Map</title>
	<meta charset="UTF-8" />
  <script>
    function markAPs() {
      svgmap = document.getElementsByTagName("svg")[0]
      <?php
      // TODO: replace this by in-svg css
      /*
        foreach ($bssids as $bssid) {
          echo "svgmap.getElementById(\"".$bssid."\").style[\"fill\"] = \"red\";\n";
        }
        */
      ?>
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
?>
</body>
</html>
