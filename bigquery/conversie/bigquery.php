<?php
require_once 'google-api-php-client-master/src/Google/autoload.php'; // or wherever autoload.php is located
session_start();

class MyBigQueryClass {
    var $project_id;
    var $application_name;
    var $client_id;
    var $service_account_name;
    var $service;
 
    function MyBigQueryClass($project_id, $client_id, $service_account_name) {
        $this->project_id = $project_id;
        $this->application_name = "BigQuery PHP Example";
        $this->client_id = $client_id;
        $this->service_account_name = $service_account_name;
        $this->setGoogleServiceBigquery();
    }
 
    function getGoogleServiceBigquery(){
        return $this->service; // Google_Service_Bigquery
    }
 
    /* @throws Exception Google_Service_Exception if operation fail
    */
    function loadFileToBigQuery($uri_array, $schema_array, $dataset, $table_name){
 
        $loadConfig = new Google_Service_Bigquery_JobConfigurationLoad();
        $loadConfig->setSchema($this->createSchema($schema_array));
        $loadConfig->setSourceUris($uri_array);
        $loadConfig->setDestinationTable($this->createDestinationTable($dataset, $table_name));
        $loadConfig->setCreateDisposition("CREATE_IF_NEEDED");
        $loadConfig->setWriteDisposition("WRITE_APPEND");
        $loadConfig->sourceFormat = 'CSV';
 
        $config = new Google_Service_Bigquery_JobConfiguration();
        $config->setDryRun(false);
        $config->setLoad($loadConfig);
 
        $job = new Google_Service_Bigquery_Job();
        $job->setConfiguration($config);
 
        // this may throw exception: Google_Service_Exception
        $job = $this->service->jobs->insert($this->project_id, $job);
 
        return $job;
    }
 
    function queryBigQuery($sql, $dataset, $table_name){
 
        $queryConfig = new Google_Service_Bigquery_JobConfigurationQuery();
        $queryConfig->setDestinationTable($this->createDestinationTable($dataset, $table_name));
        $queryConfig->setQuery($sql);
 
        $config = new Google_Service_Bigquery_JobConfiguration();
        $config->setDryRun(false);
        $config->setQuery($queryConfig);
 
        $job = new Google_Service_Bigquery_Job();
        $job->setConfiguration($config);
        
        print("<PRE>"); print_r($job); exit();
 
        $job = $this->service->jobs->insert($this->project_id, $job);
        return $job;
    }
 
    function requestBigQuery($sql){
        $query = new Google_Service_Bigquery_QueryRequest();
        $query->setQuery($sql);
        $query->setTimeoutMs(0);
        $response = $this->service->jobs->query($this->project_id, $query);
        $job_id = $response->getJobReference()->getJobId();
        $pageToken = null;
        do {
            $queryResults = $this->service->jobs->getQueryResults($this->project_id, $job_id);
            $queryResults->setPageToken($pageToken);
        } while (!$queryResults->getJobComplete());
        return $queryResults;
    }
 
 
    private function setGoogleServiceBigquery(){
        $client = new Google_Client();
        $client->setApplicationName($this->application_name);
        $client->setClientId($this->client_id);
        $key = file_get_contents('privatekey.p12');
        $cred = $client->loadServiceAccountJson($this->service_account_name, "https://www.googleapis.com/auth/bigquery");
        /*
        $cred = new Google_Auth_AssertionCredentials(
                        $this->service_account_name,
                        array('https://www.googleapis.com/auth/bigquery'),
                        $key);
        */
        $client->setAssertionCredentials($cred);
        if ($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion($cred);
            $service_token = $client->getAccessToken();
        }
        $this->service = new Google_Service_Bigquery($client); // Google_Service_Bigquery
    }
 
    private function createSchema($schema_array){
        $fields = array();
        foreach ($schema_array as $field){
            $table_field_schema = new Google_Service_Bigquery_TableFieldSchema;
            $table_field_schema->setName($field["name"]);
            $table_field_schema->setType($field["type"]);
            $fields[] = $table_field_schema;
        }
        // Create a tableschema
        $schema = new Google_Service_Bigquery_TableSchema();
        $schema->setFields($fields);
        return $schema;
    }
 
    private function createDestinationTable($dataset, $table_name){
        $destinationTable = new Google_Service_Bigquery_TableReference();
        $destinationTable->setProjectId($this->project_id);
        $destinationTable->setDatasetId($dataset);
        $destinationTable->setTableId($table_name);
        return $destinationTable;
    }
    
    function insertRow($dataset, $tableid, $arr){
        $rows = array();
        $row = new Google_Service_Bigquery_TableDataInsertAllRequestRows;
        $row->setJson((object)$arr); //Also, the json must be an object, not a json string. $data = the json_decode of the json string that was in the original question.
        $row->setInsertId( strtotime('now'). rand(0,1000000));
        $rows[0] = $row;

        $request = new Google_Service_Bigquery_TableDataInsertAllRequest;
        $request->setKind('bigquery#tableDataInsertAllRequest');
        $request->setRows($rows);
        
        $this->service->tabledata->insertAll($this->project_id, $dataset, $tableid, $request);
    }
}
?>