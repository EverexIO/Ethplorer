<?php

require dirname(__FILE__) . '/../../service/lib/ethplorer.php';
$es = Ethplorer::db(array('cacheDriver' => 'memcached'));

$oCache = $es->getCache();

$api = "https://api.cryptokitties.co/kitties";

if(isset($_GET["action"]) && ("getKitty" === $_GET["action"])){
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : false;
    if($id){
        $cacheId = "ck-kitty-" . $id;
        $result = $oCache->get($cacheId, FALSE, TRUE, 600);
        if(!$result){
            $result = file_get_contents($api . "/" . $id);
            $oCache->save($cacheId, $result);
        }
        if($result){
            echo $result;
            die();
        }
    }
}

if(isset($_GET["action"]) && ("getAddress" === $_GET["action"])){
    $address = isset($_GET["address"]) ? strtolower(preg_replace("/[^a-fA-F0-9x]/", "", $_GET["address"])) : false;
    if($address){
        $cacheId = "ck-kitty-" . $address;
        $result = $oCache->get($cacheId, FALSE, TRUE, 600);
        if(!$result){
            $result = file_get_contents($api . "?owner_wallet_address=" . $address . '&limit=20');
            $oCache->save($cacheId, $result);
        }
        if($result){
            echo $result;
            die();
        }
    }
}

// ?owner_wallet_address=

echo '{"error":"Invalid Request"}';