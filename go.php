<?php

ob_clean();
header("HTTP/1.0 200 OK");

$aConfig = require_once dirname(__FILE__) . '/service/config.php';

$sLink = "https://ethplorer.io";

if(isset($_GET["link"]) && strlen($_GET["link"])>0){
    $sLink = $_GET["link"];
    $fp = @fopen(dirname(__FILE__) . "/service/log/go-links.log", "a+");
    if($fp){
	$t = date('Y-m-d H:i:s')." - ".$sLink." - ".@getenv("REMOTE_ADDR")." - ".@getenv("HTTP_REFERER"). " - ".@getenv("HTTP_USER_AGENT")."\r\n";
	@fwrite($fp, $t);
    }
}

header("HTTP/1.1 301 Moved Permanently"); 
header("Location: " . $sLink); 

?>
		