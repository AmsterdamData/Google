<?php
require_once 'bigquery.php'; // or wherever autoload.php is located
include(dirname(__FILE__) ."/settings.php");

$bq = new MyBigQueryClass(BIGQUERY_PROJECT_ID,BIGQUERY_CLIENT_ID,BIGQUERY_SERVICE_ACCOUNT_NAME);
date_default_timezone_set("CET");

$json = json_decode(file_get_contents("http://www.trafficlink-online.nl/trafficlinkdata/wegdata/TrajectSensorsNH.GeoJSON"));
$sum = 0;
$count = 0;
$timestamp = null;
$rows = Array();

foreach($json->features as $feature){
    if($feature->properties->Velocity > 0){
        $length = $feature->properties->Length;
        if($length > 0){
            $ff = round($length / 13.8889,0); 
        } else {
            $ff = 0;
        } 
        $row = Array(
            "id" => $feature->Id,
            "timestamp" => date("Y-m-d H:i:s", strtotime($feature->properties->Timestamp)),
            "velocity" => $feature->properties->Velocity,
            "traveltime" => $feature->properties->Traveltime,
            "traveltime_freeflow" => $ff
        );
        $bq->insertRow("traveltimes", "traveltimes", $row);
    }  
}
?>
