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
?><!DOCTYPE html>
<html>
    <head>
        <title>Ethplorer: Widgets and Charts for Ethereum Tokens</title>
        <link rel="stylesheet" href="/css/jquery-ui.min.css">
        <link rel="stylesheet" href="/css/bootstrap.min.css">
	<link rel="stylesheet" href="/css/font-awesome.min.css">
        <link rel="stylesheet" href="/css/ethplorer.css?v=102>">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="referrer" content="never" />
        <meta name="referrer" content="no-referrer" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
        <script src="/js/jquery-1.10.2.min.js"></script>
        <script src="/js/jquery-ui.min.js?v=102"></script>
        <script src="/js/bootstrap.min.js"></script>
        <script src="/js/bignumber.js"></script>
        <script src="/js/ethplorer.js?v=102"></script>
        <script src="/js/ethplorer-search.js?v=102"></script>
        <script src="/js/ethplorer-note.js?v=102"></script>
        <script src="/js/ace.js"></script>
        <script src="/js/config.js"></script>
        <style type="text/css">
            body {
                background: url(/images/bg.jpg) top left repeat-x black;
                background-size: 100%;
                /* background: linear-gradient(to top, #000, #152e47); */
                font-family: 'Open Sans', sans-serif;
            }
            .topdiv {
                position: relative;
                min-height: 100vh;
                padding-bottom: 250px;
            }
            .bg-white{
                color: #000;
                background-color: #fff;
            }
            .text-white {
                color: #dedede;
            }
            div.bg-gray	{
                background-color: #ccc !important;
            }
            div.bg-dark	{
                background-color: #333 !important;
            }
            .round-top {
                border-top-left-radius: 8px;
                border-top-right-radius: 8px;
                min-height: 8px;
            }
            .round-bottom {
                border-bottom-left-radius: 8px;
                border-bottom-right-radius: 8px;
                min-height: 8px;
            }
            .space-bottom {
                margin-bottom: 40px;
            }
            .space-top {
                margin-top: 40px;
            }
            .add-paddings {
                padding: 20px;
            }
            .widget-code {
                background: #000;
                color: #dedede;
                /* width:70%; */
                min-height: 300px;
                font-size: 0.9em;
                /* margin-right: auto; margin-left: auto; */
                margin: 6px;
            }
            h1, h2 {
                margin-bottom: 20px;
            }
            h1 {
                line-height: 50px;
            }
            h2 {
                font-size: 36px;
            }
            p {
                font-size: 1.2em;
            }
            .ui-dialog {
                background: white;
                border-radius: 8px;
                border: 1px solid black;
            }
            .content-page .block{
                background-color: transparent;
            }
            .block h3 {
                padding: 0px;
                line-height: 32px;
            }
            .ui-dialog-titlebar-close {
                float: right;
                height: 32px;
                background: red;
                border: 0px;
                width: 32px;
                border-radius: 8px;
                color: white;
                font-size: 20px;
                line-height: 32px;
                padding-left: 8px;
                margin: 2px;
            }
            .ui-widget-header {
                text-align: left;
                font-size: 32px;
                padding-left: 12px;
            }
            .ui-dialog-titlebar-close:focus {
                border: 0px;
            }
            .ui-dialog-titlebar-close:after {
                content: "X";
            }
            .popup-descr {
                padding-left: 12px;
                padding-top: 8px;
                font-size: 16px;
                padding-right: 12px;
            }
            .wide-stripe {
                min-height: 200px;
            }
            .wide-stripe-layer {
                position: absolute;
                width: 100%;
                min-height: 250px;
                background: white;
                left:0px;
                display: none;
            }
            .widget-title,
            .widget-type-title,
            .widget-title-charts {
                max-width: 700px;
                margin-left: auto !important;
                margin-right: auto !important;
            }
            .widget-title-charts {
                margin-bottom: 16px;
            }
            @media screen and (min-width:768px){
                .wide-stripe-layer {
                    display: block;
                }
            }
            @media screen and (min-width:1200px){
                .wide-stripe-layer {
                    display: block;
                    min-height: 200px;
                }
            }
            @media screen and (max-width:767px){
                .topdiv {
                    padding-bottom: 350px;
                }
            }
        </style>

        <script>
            function getWidgetCode(ta) {
                var res = '';
                var el = $('#' + ta);
                if (!el.length)
                    return;

                $('[data-textarea="' + ta + '"]').each(function () {
                    var val = $('<div>').append($(this).clone()).html().replace(/\sdata\-textarea\=\".*\"/g, '');
                    res = res + val + "\n";
                });

                var editor = ace.edit(ta);
                editor.setReadOnly(true);
                editor.setTheme("ace/theme/monokai");
                editor.setValue(res);
                editor.resize();

                editor.getSession().setMode("ace/mode/html");
                editor.renderer.setShowGutter(false);
                editor.setDisplayIndentGuides(false);
                editor.setShowPrintMargin(false);

                if($("#" + ta + '-popup').attr('data-description')){
                    $("#" + ta + '-popup').prepend('<div class="popup-descr">' + $("#" + ta + '-popup').attr('data-description') + '</div>');
                }

                var butt = $('<BUTTON>');
                butt.addClass('btn btn-info');
                butt.text('Get Code');
                butt.click(function(_ta){
                    return function(){
                        $("#" + _ta + '-popup').dialog('open');
                    };
                }(ta));
                $("#" + ta + '-popup').after(butt);
                $("#" + ta + '-popup').attr('title', 'Widget Code');

                var w = ($(window).width() > 640) ? $(window).width() / 2 : $(window).width() - 20;
                $("#" + ta + '-popup').dialog({
                    'autoOpen': false,
                    'resizable': false,
                    'width': w,
                    'height': 'auto',
                    'open': function(ta){
                }(ta)}).css("font-size", "12px");
            }

            $(document).ready(function () {
                getWidgetCode('ethplorer-textarea-1');
                getWidgetCode('ethplorer-textarea-2');
                getWidgetCode('ethplorer-textarea-3');
                getWidgetCode('ethplorer-textarea-4');
                getWidgetCode('ethplorer-textarea-10');
                getWidgetCode('ethplorer-textarea-11');
                getWidgetCode('ethplorer-textarea-12');
                getWidgetCode('ethplorer-textarea-13');
            });
        </script>
    </head>
    <body>
        <div class="topdiv">
            <nav class="navbar navbar-inverse" style="padding-bottom:0px; padding-top:0px;">
                <div class="container">
                    <div class="navbar-header">
                        <a class="navbar-logo-small" href="/"><img title="Ethplorer" src="/favicon.ico"></a>
                        <a class="navbar-logo" href="/"><img title="Ethplorer" src="/images/ethplorer-logo-48.png"></a>
                    </div>
                    <div id="navbar" class="navbar" style="margin-bottom: 0px;">
                        <ul class="nav navbar-nav navbar-right" id="searchform">
                            <form id="search-form">
                                <input id="search" class="form-control" placeholder="Token name or symbol / TX hash / address" autocomplete="off" />
                                <div id="search-quick-results"></div>
                            </form>
                        </ul>
                        <ul class="nav navbar-nav navbar-right" id="topmenu">
                            <li onclick="document.location.href='/top';">TOP-50</li>
                            <li onclick="document.location.href='/widgets';">Widgets</li>
                            <li onclick="document.location.href='https://github.com/EverexIO/Ethplorer/wiki/Ethplorer-API';">API</li>
                            <li onclick="document.location.href='https://ethplorer.io/#subscribe';">Subscribe</li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div id="ethplorer-note"></div>
            <div class="container">
                <div class="starter-template">
                    <div id="page-create" class="page">
                        <div class="block">
                            <div class="row text-white">
                                <div class="hidden-xs col-sm-1 col-md-2"></div>
                                <div class="col-xs-12 col-sm-10 col-md-8">
                                    <h1>Widgets and Charts<br />for Ethereum Tokens</h1>
                                </div>
                                <div class="hidden-xs col-sm-1 col-md-2"></div>
                            </div>
                            <div class="row text-white space-bottom">
                                <div class="hidden-xs col-sm-1 col-md-2"></div>
                                <div class="col-xs-12 col-sm-6 col-md-5">
                                    <p>Display the activity of your token right from your website! Ethplorer offers a few modes of highly customizable widgets. Popular widget modes are Token Activity Chart (for the last month), Recent Transactions, Top Ethereum tokens activity.</p>
                                    <p>Charts are based on Google Charts, so you can customize it as you like by referring to <a href="https://developers.google.com/chart/" target="_blank">Google Charts instructions.</a></p>
                                    <p>Below see different samples of widgets and customizations.</p>
                                </div>
                                <div class="col-xs-12 col-sm-4 col-md-3">
                                    <div class="alert alert-info">
                                        <p style="font-size: 1.0em;">
                                            <i>Note: the most simple way to get a widget for your token is
                                            <br />1) find your token in Ethplorer, and
                                            <br />2) use the widget link there. </i>
                                        </p>
                                    </div>
                                </div>
                                <div class="hidden-xs col-sm-1 col-md-2"></div>
                            </div>

                            <div class="row bg-white wide-stripe">
                                <div class='wide-stripe-layer'></div>
                                <div class="hidden-xs col-sm-1 col-md-2"></div>
                                <div class="col-xs-12 col-sm-10 col-md-8 add-paddings">
                                    <h3>How to install widget code</h3>
1. Add jQuery in the HEAD section of your page (if not present):
<pre>&lt;script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"&gt;&lt;/script&gt;</pre>
2. Get any widget code displayed below, put div placeholder and code somewhere in BODY section.<br/>
3. Customize widget data and style using options and styles (optional).
                                </div>
                                <div class="hidden-xs col-sm-1 col-md-2"></div>
                            </div>
                        </div>

                        <div class="block text-white" style="margin-top: 42px;">
                            <div class="widget-type-title">
                                <h2>Chart Widgets</h2>
                            </div>
                        </div>

                        <div class="content-page space-bottom" style="display:block">
                            <div class="block">
                                <div class="row widget-title-charts">
                                    <div class="col-xs-8 text-white">
                                        <h3>Token Activity Chart</h3>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <div id="ethplorer-textarea-1-popup" data-description="Setup in options 'token' and 'title' parameters to address and name of your token.">
                                            <div id="ethplorer-textarea-1" class="widget-code"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row widget-title">
<div id="token-history-grouped-1" data-textarea="ethplorer-textarea-1"></div>
<script type="text/javascript" data-textarea="ethplorer-textarea-1">
if (typeof (eWgs) === 'undefined') {
    document.write('<scr' + 'ipt src="https://api.ethplorer.io/widget.js?' + new Date().getTime().toString().substr(0, 7) + '" async></scr' + 'ipt>');
    var eWgs = [];
}
eWgs.push(function () {
    ethplorerWidget.init(
        '#token-history-grouped-1', // Placeholder element
        'tokenHistoryGrouped', // Widget type
        {
            // token address
            'address': '0xe94327d07fc17907b4db788e5adf2ed424addff6',
            options: {
                'title': 'Augur Rep Token Pulse',
                'pointSize': 5,
                'vAxis': {
                    'title': 'Token operations',
                }
            }
        }

    );
});
</script>
                                </div>
                            </div>
                        </div>

                        <div class="content-page space-bottom" style="display:block">
                            <div class="block">
                                <div class="row widget-title-charts">
                                    <div class="col-xs-8 text-white">
                                        <h3>Customized Tokens Activity Chart</h3>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <div id="ethplorer-textarea-2-popup">
                                            <div id="ethplorer-textarea-2" class="widget-code"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row widget-title">
                                    <div class="bg-gray">
<div id="token-history-grouped-2" data-textarea="ethplorer-textarea-2"></div>
<script type="text/javascript" data-textarea="ethplorer-textarea-2">
    if (typeof (eWgs) === 'undefined') {
        document.write('<scr' + 'ipt src="https://api.ethplorer.io/widget.js?' + new Date().getTime().toString().substr(0, 7) + '" async></scr' + 'ipt>');
        var eWgs = [];
    }
    eWgs.push(function () {
        ethplorerWidget.init(
            '#token-history-grouped-2', // Placeholder element
            'tokenHistoryGrouped', // Widget type
            {
                'period': 10,
                'type': 'column', // supported types: area, column, line
                options: {
                    'title': 'Ethereum Tokens Pulse',
                    'pointSize': 2,
                    'hAxis': {
                        'title': '10 days token operations chart',
                        'titleTextStyle': {
                            'color': '#3366CC',
                            'italic': true
                        }
                    },
                    'vAxis': {
                        'title': 'Token operations',
                    }
                }
            }

        );
    });
</script>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="content-page space-bottom" style="display:block">
                            <div class="block">
                                <div class="row widget-title-charts">
                                    <div class="col-xs-8 text-white">
                                        <h3>Dark Theme Chart</h3>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <div id="ethplorer-textarea-3-popup">
                                            <div id="ethplorer-textarea-3" class="widget-code"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row widget-title">
                                    <div class="bg-dark">
<div id="token-history-grouped-3" data-textarea="ethplorer-textarea-3"></div>
<script type="text/javascript" data-textarea="ethplorer-textarea-3">
    if (typeof (eWgs) === 'undefined') {
        document.write('<scr' + 'ipt src="https://api.ethplorer.io/widget.js?' + new Date().getTime().toString().substr(0, 7) + '" async></scr' + 'ipt>');
        var eWgs = [];
    }
    eWgs.push(function () {
        ethplorerWidget.init(
            '#token-history-grouped-3', // Placeholder element
            'tokenHistoryGrouped', // Widget type
            {
                'theme': 'dark',
                options: {

                    'title': 'Ethereum Tokens Pulse',
                    'hAxis': {
                        'title': '90 days token operations chart',
                    },
                    'vAxis': {
                        'title': 'Token operations',
                    }
                }
            }

        );
    });
</script>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="block text-white">
                            <div class="widget-type-title">
                                <h2>Text Widgets</h2>
                            </div>
                        </div>

                        <div class="content-page space-bottom" style="display:block">
                            <div class="block">
                                <div class="row widget-title">
                                    <div class="col-xs-8 text-white">
                                        <h3>Recent Token Activity</h3>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <div id="ethplorer-textarea-10-popup">
                                            <div id="ethplorer-textarea-10" class="widget-code"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="padding-top: 20px;">
<div id="token-txs-10" data-textarea="ethplorer-textarea-10"></div>
<script type="text/javascript" data-textarea="ethplorer-textarea-10">
    if (typeof (eWgs) === 'undefined') {
        document.write('<scr' + 'ipt src="https://api.ethplorer.io/widget.js?' + new Date().getTime().toString().substr(0, 7) + '" async></scr' + 'ipt>');
        var eWgs = [];
    }
    eWgs.push(function () {
        ethplorerWidget.init(
            '#token-txs-10', // Placeholder element
            'tokenHistory', // Widget type
            {
                address: '0xe94327d07fc17907b4db788e5adf2ed424addff6', // keep empty to show all tokens
                limit: 5, // Number of records to show
            }

        );
    });
</script>
                                </div>
                            </div>
                        </div>

                        <div class="content-page space-bottom" style="display:block">
                            <div class="block">
                                <div class="row widget-title">
                                    <div class="col-xs-8 text-white">
                                        <h3>Customized Token Activity Widget</h3>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <div id="ethplorer-textarea-11-popup">
                                            <div id="ethplorer-textarea-11" class="widget-code"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="padding-top: 20px;">
<style data-textarea="ethplorer-textarea-11">
    #token-txs-11 {
        line-height: 1.8;
        border-radius: 8px;
        max-width: 700px !important;
        background-blend-mode: darken;
        background-color: rgba(21,12,70,0.9);
    }
    #token-txs-11 .txs.big-screen-table td .tx-amount{
        display: inline-block;
        width: 150px;
        white-space: nowrap;
    }
    #token-txs-11 .txs.big-screen-table td .tx-link{
        max-width: 120px;
    }
    #token-txs-11 .txs.big-screen-table td .tx-link:last-child{
        max-width: 150px;
    }
    #token-txs-11 .txs.big-screen-table td .tx-amount a:nth-child(1){
        max-width: 110px !important;
    }
    #token-txs-11 .txs.big-screen-table td .tx-amount  a:nth-child(2){
        max-width: 50px !important;
    }

</style>
<div id="token-txs-11" data-textarea="ethplorer-textarea-11"></div>
<script type="text/javascript" data-textarea="ethplorer-textarea-11">
    if (typeof (eWgs) === 'undefined') {
        document.write('<scr' + 'ipt src="https://api.ethplorer.io/widget.js?' + new Date().getTime().toString().substr(0, 7) + '" async></scr' + 'ipt>');
        var eWgs = [];
    }
    eWgs.push(function () {
        ethplorerWidget.init(
            '#token-txs-11', // Placeholder element
            'tokenHistory', // Widget type
            {
                address: '0xe94327d07fc17907b4db788e5adf2ed424addff6', // keep empty to show all tokens
                limit: 5, // Number of records to show
            },
            {
                header: '<div class="txs-header">WETH Recent Transactions</div>', // customized header
                loader: '<div class="txs-loading">* L * O * A * D * I * N * G *<br><small>Please wait...</small></div>', // customized loader
                bigScreenTable: '<tr><td>%from% <span class="tx-send">sent</span> <span class="tx-amount">%amount% %token%</span> <span class="tx-send">to</span> %to% <span class="tx-send">at</span><span class="tx-date"> %datetime%</span></td></tr>'

            }

        );
    });
</script>
                                </div>
                            </div>
                        </div>

                        <div class="content-page space-bottom" style="display:block">
                            <div class="block">
                                <div class="row widget-title">
                                    <div class="col-xs-8 text-white">
                                        <h3>Top Tokens by Activity</h3>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <div id="ethplorer-textarea-12-popup">
                                            <div id="ethplorer-textarea-12" class="widget-code"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="padding-top: 20px;">
<div id="token-txs-12" data-textarea="ethplorer-textarea-12"></div>
<script type="text/javascript" data-textarea="ethplorer-textarea-12">
    if (typeof (eWgs) === 'undefined') {
        document.write('<scr' + 'ipt src="https://api.ethplorer.io/widget.js?' + new Date().getTime().toString().substr(0, 7) + '" async></scr' + 'ipt>');
        var eWgs = [];
    }
    eWgs.push(function () {
        ethplorerWidget.init(
            '#token-txs-12', // Placeholder element
            'topTokens', // Widget type
            {
                limit: 10, // Number of records to show
                period: 30 // period of calculating
            }

        );
    });
</script>
                                </div>
                            </div>
                        </div>

                        <div class="content-page space-bottom" style="display:block">
                            &nbsp;
                        </div>

                    </div>
                </div>
            </div>
            <div id="disqus_thread" class="container"></div>
            <script>
                (function () { // DON'T EDIT BELOW THIS LINE
                    var d = document, s = d.createElement('script');
                    s.src = '//https-ethplorer-io.disqus.com/embed.js';
                    s.setAttribute('data-timestamp', +new Date());
                    (d.head || d.body).appendChild(s);
                })();
            </script>
            <div class="footer">
                <div class="container">
                    <div class="row">
                        <div class="col-xs-7 col-sm-3">
                            <a href="#"><img src="/images/ethplorerlogowhite400.png" style="max-width: 140px;" alt=""></a>
                            <div>
                                <div style="color:#eeeeee;">© 2016-2017 <a href="https://everex.one/" target="_blank" class="small-link">Everex</a>
                                    <br><a href="mailto:support@ethplorer.io" class="small-link">support@ethplorer.io</a>
                                    <br><a href="/privacy" class="small-link">Privacy &amp; Terms</a><br>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-5 col-sm-2 col-md-2 footer-links">
                            <ul>
                                <li><a href="/widgets">Widgets</a></li>
                                <li><a href="https://github.com/EverexIO/Ethplorer">Sources</a></li>
                                <li><a href="https://github.com/EverexIO/Ethplorer/wiki/Ethplorer-API">API</a></li>
                                <li><a href="https://github.com/EverexIO/Ethplorer/issues">Github Issue</a></li>
                                <li><a href="https://twitter.com/ethplorer">Twitter</a></li>
                            </ul>
                        </div>
                        <div class="col-xs-5 col-sm-2 col-md-3 footer-links">
                            <ul>
                                <li><a href="https://ethplorer.io/#contact">Contact</a></li>
                                <li><a href="https://ethplorer.io/#subscribe">Subscribe</a></li>
                                <li><a href="https://www.reddit.com/r/ethplorer/">Discuss at Reddit</a></li>
                                <li><a href="https://docs.google.com/forms/d/e/1FAIpQLSemDE0-vqUnJ7ToRdt1qR95iTbaMfq0FRXt7INMMJrm1IO4dQ/viewform?c=0&w=1">Update your Token info</a></li>
                            </ul>
                        </div>
                        <div class="col-xs-7 col-sm-5 col-md-4 footer-donation">
                            <strong>Donation:</strong><br>
                            <br>ETH:<br>0x0dE0BCb0703ff8F1aEb8C892eDbE692683bD8030
                            <br>BTC:<br>1MKVGqyJA9YkVRuDsGCvnYVJ6ivNtfe289
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(document).ready(function () {
                EthplorerSearch.init($('#search-form'), $('#search'), Ethplorer.search);
                EthplorerNote.init($('#ethplorer-note'));
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
            <?php if(isset($aConfig['scriptAddon'])) echo $aConfig['scriptAddon']; ?>
        </script>
    </body>
</html>