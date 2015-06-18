<?php
$requests = file("requests.log");
$lastline = $requests[count($requests)-1];
$parts = explode("\t",$lastline);
$json = $parts[2];
$jsonobj = json_decode($json);

$phone = $jsonobj->{"phone"};
$bssids = $jsonobj->{"bssids"};
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
        foreach ($bssids as $bssid) {
          echo "svgmap.getElementById(\"".$bssid."\").style[\"fill\"] = \"red\";\n";
        }
      ?>
    }
  </script>
</head>
<body onload="markAPs();">
<?php
foreach ($bssids as $bssid) {
  echo "BSSID: ".$bssid."<br>\n";
}
echo file_get_contents("map.svg");
?>
</body>
</html>
