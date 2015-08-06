<?php
require_once 'bigquery.php';
include(dirname(__FILE__) ."/settings.php");

$bq = new MyBigQueryClass(BIGQUERY_PROJECT_ID,BIGQUERY_CLIENT_ID,BIGQUERY_SERVICE_ACCOUNT_NAME);

date_default_timezone_set("CET");

    $xml = simplexml_load_file("http://xml.buienradar.nl");
    //print_r($xml);
    
    foreach($xml->weergegevens->actueel_weer->weerstations->weerstation as $station){
        if($station->stationcode == 6240){
            $row = Array(
                "datetime" => date("Y-m-d H:i:s", strtotime($station->datum)),
                "temperatureDC" => (float)$station->temperatuurGC,
                "humidity" => (float)$station->luchtvochtigheid,
                "windspeedMPS" => (float)$station->windsnelheidMS,
                "winddirectionDEG" => (float)$station->windrichtingGR,
                "airpressureMBAR" => (float)$station->luchtdruk,
                "sightM" => (float)$station->zichtmeters,
                "windgustsMPS" => (float)$station->windstotenMS,
                "rainMMPH" => (float)$station->regenMMPU
            );
            $bq->insertRow("weather", "weather", $row);
        }
    }
?>