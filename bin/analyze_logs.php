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

$aDates = array();

$f = fopen(dirname(__FILE__) . '/../service/log/api-request.log', "r");
while(!feof($f)){
    $s = fgets($f);
    $parts = explode(" ", $s);
    $date = str_replace('[', '', $parts[0]);

    if(!$date) continue;

    if(!isset($aDates[$date])){
        $aDates[$date] = array(
            'total_requests' => 0,
            '< 0.1s' => 0,
            '< 1s' => 0,
            '< 5s' => 0,
            '< 10s' => 0,
            '> 10s' => 0,
            'slow_queries' => array()
        );
    }
    $time = (float)$parts[10];

    if($time > 1000) continue;

    $aDates[$date]['total_requests']++;
    if($time < 0.1){
        $aDates[$date]['< 0.1s']++;
    }else if($time < 1){
        $aDates[$date]['< 1s']++;
    }else{
        if($time < 5){
            $aDates[$date]['< 5s']++;
        }else if($time < 10){
            $aDates[$date]['< 10s']++;
        }else{
            $aDates[$date]['> 10s']++;
        }
        $request = explode('?', $parts[7]);
        $request = $request[0];
        if(FALSE === strpos($request, 'widget')){
            if(!isset($aDates[$date]['slow_queries'][$request])){
                $aDates[$date]['slow_queries'][$request] = array('num' => 0, 'average_time' => 0);
            };
            $aDates[$date]['slow_queries'][$request]['num']++;
            $aDates[$date]['slow_queries'][$request]['average_time'] = ($aDates[$date]['slow_queries'][$request]['average_time'] + $time) / ($aDates[$date]['slow_queries'][$request]['num'] > 1 ? 2 : 1);
        }
    }
}
fclose($f);

var_export($aDates);
