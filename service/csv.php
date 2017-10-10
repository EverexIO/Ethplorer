<?php
/*!
 * Copyright 2016 Everex https://everex.io
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require dirname(__FILE__) . '/lib/ethplorer.php';

$es = Ethplorer::db(require_once dirname(__FILE__) . '/config.php');

$data = isset($_GET["data"]) ? $_GET["data"] : false;
$hash = isset($_GET["hash"]) ? $_GET["hash"] : false;

// Allow cross-domain ajax requests
// header('Access-Control-Allow-Origin: *');

if($hash){
    if((false !== $data) && $es->isValidAddress($data)){
        $md5 = md5('/service/csv.php?data=' . $data . date("Y-n-j"));
        if($md5 == $hash){
            $result = $es->getAddressOperationsCSV($data);
            header('Content-Description: File Transfer');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=ethplorer.csv');
            header('Content-Length: ' . strlen($result));
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');
            header('Pragma: public');
            echo $result;
            die;
        }
    }
}

header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
header("Status: 404 Not Found");
die;
