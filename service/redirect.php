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

$config = require_once 'config.php';

$keywords = isset($_GET['key']) ? strtolower($_GET['key']) : FALSE;
if(isset($config['redirects']) && $keywords){
    foreach($config['redirects'] as $keyword => $address){
        if(FALSE !== strpos($keywords, strtolower($keyword))){
            header('Location: /address/' . strtolower($address) . '?' . $_SERVER['QUERY_STRING']);
            die();
        }
    }
}

header('Location: /?from=getTokenNotFound&' . $_SERVER['QUERY_STRING']);