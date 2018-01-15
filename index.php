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
$aConfig = require dirname(__FILE__) . '/service/config.php';
require dirname(__FILE__) . '/service/lib/ethplorer.php';
$es = Ethplorer::db(array());

$codeVersion = isset($aConfig['codeVersion']) ? $aConfig['codeVersion'] : "182";

$error = TRUE;
$header = "";
$uri = $_SERVER['REQUEST_URI'];

// Uri to lowercase
if(preg_match("/[A-Z]+/", $uri) && (FALSE === strpos($uri, '1dea4'))){
    header("Location: " . strtolower($uri));
    die();
}
if(FALSE !== strpos($uri, '?')){
    $uri = substr($uri, 0, strpos($uri, '?'));
}
$rParts = explode('/', $uri);
foreach($rParts as $i => $part){
    $rParts[$i] = strtolower($part);
}
if(4 === count($rParts)){
    if(('tx' === $rParts[2]) && $es->isValidTransactionHash($rParts[3])){
        $header = "Transaction hash: " . $rParts[3];
        $error = FALSE;
    }
    if(('address' === $rParts[2]) && $es->isValidAddress($rParts[3])){
        $header = "Address: " . $rParts[3];
        $address = $rParts[3];
        $error = FALSE;
    }
    if(('token' === $rParts[2]) && $es->isValidAddress($rParts[3])){
        $header = "Token address: " . $rParts[3];
        $error = FALSE;
    }
}
if($error){
    if(isset($rParts[2]) && !$rParts[2]){
        header('Location:/page568524.html');
        die();
    }
}
$testWidget = true;
if(isset($_GET['test'])){
    $testWidget = true;
}
$debugEnabled = false;
if(isset($_GET['debug']) && $_GET['debug']){
    $debugId = $_GET['debug'];
    $debugEnabled = true;
}

$hasNotes = isset($aConfig['adv']) && count($aConfig['adv']);

$csvExport = '';
if(is_array($rParts) && isset($rParts[3])){
    $csvExport = ' <span class="export-csv-spinner"><i class="fa fa-spinner fa-spin"></i> Export...</span><span class="export-csv"><a class="download" rel="nofollow" target="_blank" href="https://www.rebellious.io/explorer/service/csv.php?data=' . $rParts[3] . '">Export as CSV</a></span>';
}
?><!DOCTYPE html>
<html>
<head>
    <title>Rebellious Expoler<?php if($header){ echo ": " . $header; } ?></title>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://www.rebellious.io/explorer/css/ethplorer.css?v=<?=$codeVersion?>">
<?php
    // Load extensions CSS
    if(isset($aConfig['extensions']) && is_array($aConfig['extensions'])){
        foreach($aConfig['extensions'] as $extName => $aExtension){
            $cv = isset($aExtension['version']) ? (int)$aExtension['version'] : false;
            if(isset($aExtension['css'])){
                $aExtension['js'] = (array)$aExtension['js'];
                foreach($aExtension['css'] as $js){
                    $jsf = "/extensions/" . $extName . "/" . $js;
                    if(file_exists(dirname(__FILE__) . $jsf)){
                        echo '    <link rel="stylesheet" href="' . $jsf . ($cv ? ("?v=" . $cv) : "") . '">' . "\n";
                    }
                }
            }
        }
    } ?>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="referrer" content="never" />
    <meta name="referrer" content="no-referrer" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="https://www.rebellious.io/explorer/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="https://www.rebellious.io/explorer/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="https://www.rebellious.io/explorer/favicon-16x16.png" sizes="16x16">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
    <script src="https://www.google.com/jsapi"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="https://www.rebellious.io/explorer/js/bignumber.js"></script>
    <script src="https://www.rebellious.io/explorer/js/ethplorer.js?v=<?=$codeVersion?>"></script>
    <script src="https://www.rebellious.io/explorer/js/ethplorer-search.js?v=<?=$codeVersion?>"></script>
<?php if($hasNotes):?><script src="https://www.rebellious.io/explorer/js/ethplorer-note.js?v=<?=$codeVersion?>"></script><?php endif; ?>
<?php
    // Load extensions JS
    if(isset($aConfig['extensions']) && is_array($aConfig['extensions'])){
        foreach($aConfig['extensions'] as $extName => $aExtension){
            $cv = isset($aExtension['version']) ? (int)$aExtension['version'] : false;
            if(isset($aExtension['js'])){
                $aExtension['js'] = (array)$aExtension['js'];
                foreach($aExtension['js'] as $js){
                    $jsf = "/extensions/" . $extName . "/" . $js;
                    if(file_exists(dirname(__FILE__) . $jsf)){
                        echo '    <script src="' . $jsf . ($cv ? ("?v=" . $cv) : "") . '"></script>' . "\n";
                    }
                }
            }
        }
    } ?>
    <script src="https://www.rebellious.io/explorer/js/config.js"></script>
    <script src="https://www.rebellious.io/explorer/js/md5.min.js"></script>
    <script src="https://www.rebellious.io/explorer/js/sha3.min.js"></script>
    <script src="https://www.rebellious.io/explorer/js/qrcode.min.js"></script>
    <?php if(isset($address)){ ?>
        <script>
        var ethplorerWidgetPreload = [
            {
                method: "getPriceHistoryGrouped",
                options: {address: '<?php echo $address; ?>'}
            }
        ];
        </script>
    <?php } ?>
    <script src="https://www.rebellious.io/explorer/api/widget.js?v=<?=$codeVersion?>"></script>
</head>
<body>
<div style="position: relative; min-height: 100vh;">
    <nav class="navbar navbar-inverse">
        <div class="container">
            <div class="navbar-header">
                <a class="navbar-logo-small" href="https://www.rebellious.io/explorer/"><img title="Ethplorer" src="https://www.rebellious.io/explorer/favicon.ico"></a>
                <a class="navbar-logo" href="https://www.rebellious.io/explorer/"><img title="Ethplorer" src="https://www.rebellious.io/explorer/images/rebl-logo.png"></a>
                <a class="navbar-logo-extra" href="https://www.everex.io/?from=ethp-hd"></a>
            </div>
            <div id="navbar" class="navbar" style="margin-bottom: 0px;">
                <ul class="nav navbar-nav navbar-right" id="searchform">
                    <form id="search-form">
                        <input id="search" class="form-control" placeholder="Token name or symbol / TX hash / address" autocomplete="off" />
                        <div id="search-quick-results"></div>
                    </form>
                </ul>
                <ul class="nav navbar-nav navbar-right" id="topmenu">
                    <li onclick="document.location.href='https://www.rebellious.io/explorer/top';">TOP-50</li>
                    <li onclick="document.location.href='https://www.rebellious.io/explorer/widgets';">Widgets</li>
                </ul>
            </div>
        </div>
    </nav>
    <div id="ethplorer-note"></div>
    <div class="container">
        <div class="starter-template">
            <div id="page-create" class="page">
                <?php if($error): ?>
                <div id="error" class="content-page text-center">
                    <h1 class="text-danger">ERROR</h1>
                    <h3 id="error-reason" class="text-danger">Invalid request</h3>
                </div>
                <?php else: ?>

                <div id="loader" class="text-center">
                    <div class="timer"></div>
                    <div id="searchInProgressText">search in progress...</div>
                </div>

                <div id="error" class="content-page text-center">
                    <h1 class="text-danger"></h1>
                    <h3 id="error-reason" class="text-danger"></h3>
                </div>

                <div>
                <?php if(true){ ?>
                    <div class="col-xs-12 col-sm-12">
                        <h1 id="ethplorer-path"><?=$header?></h1>
                    </div>
                <?php }else{ ?>
                    <div class="hidden-xs col-sm-2"></div>
                    <div class="col-xs-12 col-sm-8">
                        <h1 id="ethplorer-path"><?=$header?></h1>
                    </div>
                    <div class="hidden-xs col-sm-2"></div>
                <?php } ?>
                </div>

                <div class="clearfix"></div>

                <?php if($testWidget){ ?>
                    <script type="text/javascript">
                        var testWidget = true;
                    </script>
                <?php }else{ ?>
                    <script type="text/javascript">
                        var testWidget = false;
                    </script>
                <?php } ?>

                <?php if(true){ ?>
                    <div id="widget-block" style="display:none;">
                        <div class="col-xs-12 col-sm-12 token-price-history-grouped-widget">
                            <div id="token-price-history-grouped-widget"></div>
                        </div>
                    </div>
                <?php }else{ ?>
                <style>
                    #token-history-grouped-widget {
                        margin-top: 0 !important;
                        margin-bottom: 0 !important;
                        padding: 0 !important;
                        max-width: 86% !important;
                        margin-left: auto;
                        margin-right: auto;
                    }
                </style>
                <div>
                    <div class="hidden-xs col-sm-1"></div>
                    <div class="col-xs-12 col-sm-10 token-history-grouped-widget">
                        <div id="token-history-grouped-widget"></div>
                    </div>
                    <div class="hidden-xs col-sm-1"></div>
                </div>
                <?php } ?>
                <script type="text/javascript">
                    if(typeof(eWgs) === 'undefined'){ var eWgs = []; }
                </script>
                <div class="col-xs-12 col-sm-12">
                    <h1 id="ethplorer-alert"></h1>
                </div>

                <div id="txDetails" class="content-page">
                    <div>
                        <div class="col-xs-12 multiop">
                            <div class="block">
                                <div class="block-header">
                                    <h3>Internal operations</h3>
                                </div>
                                <table class="table">
                                </table>
                            </div>
                        </div>

                        <div class="col-xs-12 col-md-6 chainy">
                            <div class="block">
                                <div class="block-header"><h3><a href="https://www.rebellious.io/explorer/address/0xf3763c30dd6986b53402d41a8552b8f7f6a6089b" style="text-decoration: none;">Chainy</a> <span id="chainy-op"><span></h3></div>
                                <table class="table">
                                <tr>
                                    <td>URL</td>
                                    <td id="chainy-url" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>SHA256 Hash</td>
                                    <td id="chainy-hash" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Filename</td>
                                    <td id="chainy-filename" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Filesize</td>
                                    <td id="chainy-filesize" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Data</td>
                                    <td id="chainy-message" class="list-field" style="white-space: normal !important;"></td>
                                </tr>
                                <tr class="blue">
                                    <td>Short Link</td>
                                    <td id="chainy-link" class="list-field"></td>
                                </tr>
                                </table>
                            </div>
                            <div class="text-center">
                                <a class="tx-details-link">Transaction details</a>
                            </div>
                        </div>
                        <div class="col-xs-12 col-md-6 token-related" id="token-operation-block">
                            <div class="block">
                                <div class="block-header"><h3><span class="token-name"></span> <span class="token-operation-type"></span></h3></div>
                                <table class="table">
                                <tr class="blue">
                                    <td>Value</td>
                                    <td id="transfer-operation-value" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Date</td>
                                    <td id="transfer-tx-timestamp" data-type="localdate" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>From</td>
                                    <td id="transfer-operation-from" data-type="ethplorer" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>To</td>
                                    <td id="transfer-operation-to" data-type="ethplorer" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Message</td>
                                    <td id="transfer-tx-message" class="list-field"></td>
                                </tr>
                                <tr id="operation-status">
                                    <td>Status</td>
                                    <td id="txTokenStatus" class="list-field"></td>
                                </tr>
                                </table>
                            </div>
                            <div class="text-center visible-md visible-lg visible-xl">
                                <a class="tx-details-link">Transaction details</a>
                            </div>
                        </div>
                        <div class="col-xs-12 col-md-6 token-related" id="token-information-block">
                            <div class="block">
                                <div class="block-header"><h3>Token <span class="token-name"></span> Information</h3></div>
                                <table class="table">
                                <tr>
                                    <td>Contract</td>
                                    <td id="transaction-token-contract" class="list-field" data-type="ethplorer" data-options="no-contract"></td>
                                </tr>
                                <tr>
                                    <td>Symbol</td>
                                    <td id="transaction-token-symbol" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Price</td>
                                    <td id="transaction-token-price" data-type="price" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Decimals</td>
                                    <td id="transaction-token-decimals" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Owner</td>
                                    <td id="transaction-token-owner" data-type="ethplorer" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Total Supply</td>
                                    <td id="transaction-token-totalSupply" class="list-field"></td>
                                </tr>
                                </table>
                            </div>
                            <div class="text-center hidden-md hidden-lg hidden-xl">
                                <a class="tx-details-link">Transaction details</a>
                            </div>
                        </div>
                    </div>
                    <div id="tx-details-block" style="display:none;">
                        <div class="col-xs-12">
                            <div class="block">
                                <div class="block-header">
                                    <h3>Transaction details</h3>
                                    <div class="tx-details-close">
                                        &times;
                                    </div>
                                </div>
                                <table class="table">
                                <tr>
                                    <td>Tx</td>
                                    <td id="transaction-tx-hash" class="list-field" data-type="etherscan"></td>
                                </tr>
                                <tr>
                                    <td>Date</td>
                                    <td id="transaction-tx-timestamp" data-type="localdate" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Message</td>
                                    <td id="transaction-tx-message" class="list-field"></td>
                                </tr>
                                <tr>
                                    <td>Block</td>
                                    <td id="transaction-tx-block">
                                        <span id="transaction-tx-blockNumber" class="list-field"></span> (<span id="transaction-tx-confirmations" class="list-field"></span> confirmations)
                                    </td>
                                </tr>
                                <tr>
                                    <td>From</td>
                                    <td id="transaction-tx-from" class="list-field" data-type="ethplorer"></td>
                                </tr>
                                <tr>
                                    <td>To</td>
                                    <td id="transaction-tx-to" class="list-field" data-type="ethplorer"></td>
                                </tr>
                                <tr>
                                    <td>Creates</td>
                                    <td id="transaction-tx-creates" class="list-field" data-type="ethplorer"></td>
                                </tr>
                                <tr>
                                    <td>Value</td>
                                    <td id="transaction-tx-value" class="list-field" data-type="ether-full"></td>
                                </tr>
                                <tr>
                                    <td>Gas Limit</td>
                                    <td id="transaction-tx-gasLimit" class="list-field" data-type="int"></td>
                                </tr>
                                <tr>
                                    <td>Gas Used</td>
                                    <td id="transaction-tx-gasUsed" class="list-field" data-type="int"></td>
                                </tr>
                                <tr>
                                    <td>Gas Price</td>
                                    <td id="transaction-tx-gasPrice" class="list-field" data-type="ether"></td>
                                 </tr>
                                 <tr>
                                    <td>Tx Cost</td>
                                    <td id="transaction-tx-cost" class="list-field" data-type="ether-full"></td>
                                </tr>
                                <tr>
                                    <td>Nonce</td>
                                    <td id="transaction-tx-nonce" class="list-field"></td>
                                </tr>
                                <tr id="tx-method">
                                    <td>Method</td>
                                    <td style="font-family: monospace;color:#f8f577;" id="transaction-tx-method" class="list-field text-left"></td>
                                </tr>
                                <tr id="tx-parsed">
                                    <td>Parsed Data</td>
                                    <td class="text-right">
                                        <pre id="transaction-tx-parsed" class="list-field text-left"></pre>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Input Data</td>
                                    <td class="text-right">
                                        <a onclick="Ethplorer.convert('transaction-tx-input', this);" class="pre-switcher">ASCII</a>
                                        <pre id="transaction-tx-input" class="list-field text-left" data-mode="hex"></pre>
                                    </td>
                                </tr>
                                <tr id="tx-status">
                                    <td>Status</td>
                                    <td id="txEthStatus" class="list-field"></td>
                                </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="addressDetails" class="content-page">
                    <div class="col-xs-12 col-md-6">
                        <div class="block">
                            <div class="block-header"><h3><span class="address-type"></span> Information</h3></div>
                            <table class="table">
                            <tr>
                                <td><span class="address-type"></span></td>
                                <td id="address-address" data-type="etherscan" data-options="no-contract" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Created At</td>
                                <td id="address-token-createdAt" data-type="localdate" data-options="no-contract" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Creator</td>
                                <td id="address-contract-creator" data-type="ethplorer" data-options="no-contract" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Create Tx</td>
                                <td id="address-token-createdTx" data-type="ethplorer" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Balance</td>
                                <td id="address-balance" data-type="ether-full" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Total In</td>
                                <td id="address-balanceIn" data-type="ether" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Total Out</td>
                                <td id="address-balanceOut" data-type="ether" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Transactions</td>
                                <td id="address-token-txsCount" data-type="int" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Transactions</td>
                                <td id="address-contract-txsCount" data-type="int" class="list-field"></td>
                            </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="block" id="address-token-balances">
                            <div class="block-header">
                                <h3>Token Balances
                                    <div id="address-balances-total"></div>
                                </h3>
                            </div>
                            <table class="table"></table>
                        </div>
                        <div class="block" id="address-chainy-info">
                            <div class="block-header">
                                <img src="https://www.rebellious.io/explorer/images/chainy.png?new" class="token-logo" align="left">
                                <h3>Chainy Information</h3>
                            </div>
                            <table class="table">
                            <tr>
                                <td style="padding-bottom: 12px;">
                                    Chainy is a smart contract which allows to create and read different kind of data in Ethereum blockchain:
                                    <br><br>
                                    <b>AEON short links</b><br>
                                    Irreplaceable short URLs (similar to bit.ly but impossible to change)
                                    <br><br>
                                    <b>Proof of Existence + Files</b><br>
                                    Permanent proof of existence of the document (file) together with link to the file at one page
                                    <br><br>
                                    <b>Broadcast Messages</b><br>
                                    Public text message on the Ethereum blockchain. Also may be encrypted
                                    <br><br>
                                    Read more: <a href="https://chainy.link" class="external-link" target="_blank">https://chainy.link</a><br>
                                    Post your data: <a href="https://chainy.link/add" class="external-link" target="_blank">https://chainy.link/add</a>
                                </td>
                            </tr>
                            </table>
                        </div>
                        <div class="block" id="address-token-details">
                            <div class="block-header"><h3>Token <span class="address-token-name"></span> Information</h3></div>
                            <table class="table">
                            <tr>
                                <td colspan="2" id="address-token-description" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Symbol</td>
                                <td id="address-token-symbol" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Price</td>
                                <td id="address-token-price" data-type="price" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Total Supply</td>
                                <td id="address-token-totalSupply" class="list-field"></td>
                            </tr>
                            <!--tr>
                                <td>Total In</td>
                                <td id="address-token-totalIn" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Total Out</td>
                                <td id="address-token-totalOut" class="list-field"></td>
                            </tr-->
                            <tr>
                                <td>Decimals</td>
                                <td id="address-token-decimals" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Owner</td>
                                <td id="address-token-owner" data-type="ethplorer" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Transfers</td>
                                <td id="address-token-transfersCount" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Issuances</td>
                                <td id="address-token-issuancesCount" class="list-field"></td>
                            </tr>
                            <tr>
                                <td>Holders</td>
                                <td id="address-token-holdersCount" class="list-field"></td>
                            </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-xs-12">
                        <ul class="nav nav-tabs">
                            <li id="tab-transfers" class="active">
                                <a data-toggle="tab" href="#token-transfers-tab"><span class="dashed">Transfers</span></a>
                            </li>
                            <li id="tab-issuances">
                                <a data-toggle="tab" href="#token-issuances-tab"><span class="dashed">Issuances</span></a>
                            </li>
                            <li id="tab-holders">
                                <a data-toggle="tab" href="#token-holders-tab"><span class="dashed">Holders</span></a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-xs-12 filter-box">
                        <form class="filter-form">
                            <input id="filter_list" type="text" placeholder="Filter by address or hash">
                            <div class="filter-clear" title="Clear filter">&times;</div>
                        </form>
                    </div>
                    <div class="tab-content">
                        <div id="token-transfers-tab" class="tab-pane fade in active">
                            <div class="col-xs-12" id="address-token-transfers">
                                <div class="block" style="margin-top: 0;">
                                    <div class="block-header">
                                        <h3>Token <span class="address-token-name"></span> Transfers</h3>
                                        <div class="total-records"></div>
                                    </div>
                                    <table class="table"></table>
                                </div>
                                <small>* all dates are displayed for <span class="local-time-offset"></span> timezone<?php echo $csvExport;?></small>
                            </div>
                        </div>
                        <div id="token-issuances-tab" class="tab-pane fade">
                            <div class="col-xs-12" id="address-issuances">
                                <div class="block" style="margin-top: 0;">
                                    <div class="block-header">
                                        <h3>Token <span class="address-token-name"></span> Issuances</h3>
                                        <div class="total-records"></div>
                                    </div>
                                    <table class="table"></table>
                                </div>
                                <small>* all dates are displayed for <span class="local-time-offset"></span> timezone</small>
                            </div>
                        </div>
                        <div id="token-holders-tab" class="tab-pane fade">
                            <div class="col-xs-12" id="address-token-holders">
                                <div class="block" style="margin-top: 0;">
                                    <div class="block-header">
                                        <h3>Token <span class="address-token-name"></span> Holders</h3>
                                        <div class="total-records"></div>
                                    </div>
                                    <table class="table"></table>
                                    <div id="address-token-holders-totals"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12" id="address-transfers" style="display:none;">
                        <div class="block">
                            <div class="block-header">
                                <h3>Transfers</h3>
                                <div class="total-records"></div>
                            </div>
                            <table class="table"></table>
                        </div>
                        <small>* all dates are displayed for <span class="local-time-offset"></span> timezone<?php echo $csvExport;?></small>
                    </div>
                    <div class="col-xs-12" id="address-chainy-tx" style="display:none;">
                        <div class="block">
                            <div class="block-header">
                                <h3>Chainy Transactions</h3>
                                <div class="total-records"></div>
                            </div>
                            <table class="table"></table>
                        </div>
                        <small>* all dates are displayed for <span class="local-time-offset"></span> timezone</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="container-fluid" style="position: relative;margin-bottom: -20px;z-index: 10;width: 100%;">
        <div class="row">
            <div class="col-md-12 text-center">
                <div style="color: #00aab5;font-size: 22px;font-family: 'Saira Condensed', sans-serif;line-height: 1.3;font-weight: 500;background-position: center center;border-color: transparent;border-style: solid;display: block;text-transform: uppercase;margin-bottom: 20px">
                    Rebellious is currently listed on:
                </div>
                <a href="https://www.bit-z.com/user/signup?pid=1135052&lang=en"><img src="https://www.rebellious.io/explorer/images/bitz.png" width="168"></a>
                <br><br>
                <div class="social-links">
                    <a href="https://www.reddit.com/r/RebelliousCoin/">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_04.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://twitter.com/RebelliousCoin">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_0.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://t.me/RebelliousCoin">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_02.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://www.youtube.com/RebelliousCoin">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_03.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://bitcointalk.org/index.php?topic=2357352.0">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_06.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://discordapp.com/invite/q4yBxct">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_07.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://github.com/RebelliousToken">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_08.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://www.cryptocompare.com/coins/rebl/forum/BTC">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_010.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://www.facebook.com/RebelliousCoin/">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_011.png" alt="" width="50" height="50">
                    </a>
                    <a href="https://www.rebellious.io/rebellious-whitepaper/">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_09.png" alt="" width="50" height="50">
                    </a>
                    <a href="http://qm.qq.com/cgi-bin/qm/qr?k=bPP7xx07QibitJnyOzAqF-VNgyCno0vR">
                        <img src="https://www.rebellious.io/explorer/images/Social_00aab5_15.png" alt="" width="50" height="50">
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <div class="container-fluid">
            <div class="container">
                <div class="row">
                    <div class="col-xs-7 col-sm-5">
                        <a href="#">
                            <img src="https://www.rebellious.io/explorer/images/rebl-logo-white.png" style="max-width: 270px;" alt="">
                        </a>
                    </div>
                    <div class="col-xs-5 col-sm-2 col-md-2 footer-links">
                        <ul>
                            <li><a href="https://www.rebellious.io/explorer/widgets">Widgets</a></li>
                            <li><a href="https://github.com/RebelliousToken">Sources</a></li>
                            <li><a href="https://twitter.com/RebelliousCoin">Twitter</a></li>
                        </ul>
                    </div>
                    <div class="col-xs-5 col-sm-2 col-md-3 footer-links">
                        <ul>
                            <li><a href="mailto:info@rebellious.io">Contact</a></li>
                            <li><a href="https://www.reddit.com/r/RebelliousCoin/">Discuss at Reddit</a></li>
                        </ul>
                    </div>
                </div>
        </div>
        </div>
    </div>
</div>
<div id="qr-code-popup" title="Address QR-Code" style="padding:5px;"><span id="qr-code-address"></span><br/><br/><center><div id="qr-code"></div></center><br/></div>
<script>
$(document).ready(function(){
    $.fn.bootstrapBtn = $.fn.button.noConflict();
    <?php if($debugEnabled): ?>
    Ethplorer.debug = true;
    Ethplorer.debugId = "<?=htmlspecialchars($debugId)?>";
    <?php endif; ?>
    Ethplorer.init();
    $("#qr-code-popup").dialog({
        'autoOpen': false,
        'resizable': false,
        'width': 'auto',
        'height': 'auto',
        'open': function(){
        }
    });

});
if(Ethplorer.Config.ga){
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

    ga('create', Ethplorer.Config.ga, 'auto');
    ga('send', 'pageview');
}
if(Ethplorer.Config.fb){
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
    document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', Ethplorer.Config.fb);
    fbq('track', 'PageView');
}
<?php if(isset($aConfig['scriptAddon'])) echo $aConfig['scriptAddon']; ?></script>
</body>
</html>
