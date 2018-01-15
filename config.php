<?php

header('Content-Type: application/javascript');

$esCfg = require_once dirname(__FILE__) . '/service/config.php';
unset($esCfg['client']['tokens']);

// Remove client-side unwanted options
foreach(array('ethereum', 'mongo', 'apiKeys', 'redirects') as $key) unset($esCfg[$key]);

// Build JS config from PHP code
echo "if('undefined' === typeof(Ethplorer)) Ethplorer = {}; ";
echo "var ethplorerConfig = Ethplorer.Config = " . json_encode($esCfg['client'], JSON_OBJECT_AS_ARRAY);


