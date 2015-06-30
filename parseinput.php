<?php
function sortByLevel($a, $b){
  if ($a["level"] == $b["level"]) {
    return strcmp($a["bssid"],$b["bssid"]);
  }
  return ($a["level"] > $b["level"]) ? -1 : 1;
}

function parse_input() {
  // parse input
  $requests = file("requests.log");
  $data = array();
  foreach ($requests as $request) {
    $parts = explode("\t",$request);
    if (count($parts) != 3) {
      continue;
    }
    list ($date, $time, $json) = $parts;
    $jsonobj = json_decode($json);
    if (!isset($jsonobj->{"phone"})) {
      continue;
    }
    $phone = $jsonobj->{"phone"};
    $comment = "";
    if (isset($jsonobj->{"comment"})) {
      $comment = $jsonobj->{"comment"};
    }
    $stations = array();
    if (isset($jsonobj->{"stations"})) {
      $jsonstations = $jsonobj->{"stations"};
      if (!is_array($jsonstations)) {
        $jsonstations = array($jsonstations);
      }
      foreach ($jsonstations as $jsonstation) {
        $bssid = $jsonstation->{"bssid"};
        $level = intval($jsonstation->{"level"});
        $stations[] = array("bssid"=>$bssid, "level"=>$level);
      }
      usort($stations,"sortByLevel");
    }
    $data[] = array("date"=>$date, "time"=>$time, "phone"=>$phone, "stations"=>$stations, "comment"=>$comment);
  }
return $data;
}

function select_data($data) {
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
    $index = intval($_GET["i"]);
  }
  // select data
  if ($index < 0) {
    $selected = $data[count($data)+$index];
  } else {
    $selected = $data[$index];
  }
  return $selected;
}
?>
