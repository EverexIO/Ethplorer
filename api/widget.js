ethplorerWidget = {
    // Ethplorer API URL
    api: 'https://api.ethplorer.io',

    // Ethplorer URL
    url: 'https://ethplorer.io',

    // Widget types
    Type: {},

    // Add Google loader for chart widgets
    addGoogleLoader: false,
    addGoogleAPI: false,

    chartWidgets: [],
    chartControlWidgets: [],

    cssVersion: 8,

    // Widget initialization
    init: function(selector, type, options, templates){
        ethplorerWidget.fixPath();
        options = options || {};
        templates = templates || {};
        type = type || 'tokenHistory';
        var widgetOptions = $.extend(true, {}, options);
        if(widgetOptions.onLoad) delete widgetOptions.onLoad;
        options.widgetOptions = widgetOptions;
        options.widgetType = type;
        if('undefined' === typeof(jQuery)){
            console.error('Cannot initialize Ethplorer widget: jQuery not found.');
            console.log('Add next string in the <head> section of the page:');
            console.log('<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>');
            return;
        }
        if(type == 'tokenHistoryGrouped'){
            ethplorerWidget.addGoogleLoader = true;
        }
        if(type == 'tokenPriceHistoryGrouped' || type == 'addressPriceHistoryGrouped'){
            ethplorerWidget.addGoogleAPI = true;
        }
        var el = $(selector);
        if(!el.length){
            console.error('Cannot initialize Ethplorer widget: element ' + selector + ' not found.');
            return;
        }
        if('undefined' === ethplorerWidget.eventsAdded){
            $(window).resize(ethplorerWidget.fixTilda);
            ethplorerWidget.eventsAdded = true;
        }   
        if('undefined' !== typeof(ethplorerWidget.Type[type])){
            return new ethplorerWidget.Type[type](el, options, templates);
        }else{
            console.error('Cannot initialize Ethplorer widget: invalid widget type "' + type + '".');
        }
    },
    loadScript: function(url, callback){
        var head = document.getElementsByTagName('head')[0];
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;
        script.onreadystatechange = callback;
        script.onload = callback;
        head.appendChild(script);
    },
    addStyles: function(){
        ethplorerWidget.fixPath();
        var linkElem = document.createElement('link');
        linkElem.setAttribute("rel", 'stylesheet');
        linkElem.setAttribute("type", 'text/css');
        linkElem.setAttribute("href", ethplorerWidget.api + '/widget.css?v=' + ethplorerWidget.cssVersion);
        document.getElementsByTagName("head")[0].appendChild(linkElem);
    },
    loadGoogleCharts: function(){
        if(google && google.charts){
            google.charts.load('current', {packages: ['corechart']});

            if(ethplorerWidget.chartWidgets && ethplorerWidget.chartWidgets.length)
                for(var i=0; i<ethplorerWidget.chartWidgets.length; i++)
                        ethplorerWidget.chartWidgets[i].load();
        }
    },
    loadGoogleControlCharts: function(){
        if(google){
            google.load('visualization', '1', {'packages': ['controls'], 'language': 'en', callback : ethplorerWidget.drawGoogleControlCharts});
        }
    },
    drawGoogleControlCharts: function(){
        if(ethplorerWidget.chartControlWidgets && ethplorerWidget.chartControlWidgets.length)
            for(var i=0; i<ethplorerWidget.chartControlWidgets.length; i++)
                    ethplorerWidget.chartControlWidgets[i].load();
    },
    appendEthplorerLink: function(obj){
        var host = ethplorerWidget.url.split('//')[1];
        var divLink = '<div style="text-align:center;font-size:11px;padding-top:10px;padding-bottom:4px;">';
        if((document.location.host !== host) && (document.location.host.indexOf("amilabs.cc") < 0)){
            obj.el.append(divLink + '<a class="tx-link" href="https://ethplorer.io/widgets" target="_blank">Ethplorer.io</a></a></div>');
        }else if('undefined' !== typeof(obj.options.getCode) && obj.options.getCode){
            var divLink = '<div style="text-align:center;font-size:16px;padding-top:10px;padding-bottom:4px;">';
            var popupId = obj.el.attr('id') + '-code';
            obj.el.append(divLink + '<a class="widget-code-link" href="javascript:void(0)" onclick="ethplorerWidget.getWidgetCode(this);">Get widget code</a></div>');
            obj.el.find('.widget-code-link').data("widget", obj);
            $("body").append('<div id="' + popupId + '" title="Widget code"></div>');
            $("#" + popupId).dialog({
                'autoOpen': false,
                'resizable': false,
                'width': $(window).width() / 2,
                'height': 'auto',
                'open': function(){
                    $(this).parents(".ui-dialog:first").find(".ui-dialog-content").click(function(){
                        ethplorerWidget.Utils.selectText(popupId);
                    });
                }
            }).css("font-size", "12px");
        }
    },
    getWidgetCode: function(obj){
        var widget = $(obj).data().widget,
            cr = "\n",
            id = widget.el.attr('id'),
            popupId = id + '-code',
            widgetOptions = $.extend(true, {}, widget.options.widgetOptions || {});

        if('undefined' !== typeof(widgetOptions.getCode)) delete widgetOptions.getCode;

        var widgetCode = '1. Add jQuery in the HEAD section of your page (if not present):' + cr;
        widgetCode += '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>' + cr + cr;
        widgetCode += '2. Put code displayed below somewhere in BODY section.' + cr + cr;
        widgetCode += '<div id="' + id + '"></div>' + cr;
        widgetCode += '<script type="text/javascript">' + cr;
        widgetCode += 'if(typeof(eWgs) === \'undefined\'){ document.write(\'<scr\' + \'ipt src="/api/widget.js?\' + new Date().getTime().toString().substr(0,7) + \'" async></scr\' + \'ipt>\'); var eWgs = []; }' + cr;
        widgetCode += 'eWgs.push(function(){ethplorerWidget.init(\'#' + id + '\', \'' + widget.options.widgetType + '\'';
        if(!$.isEmptyObject(widgetOptions)){
            widgetCode += ', ' + JSON.stringify(widgetOptions);
        }
        widgetCode += ');});' + cr + '</script>';

        if('undefined' !== typeof(widget.type) && (widget.type === 'tokenPriceHistoryGrouped' || widget.type === 'addressPriceHistoryGrouped')){
            widgetCode = '<center><br/><b>Coming soon!</b><br\>Follow <a href="https://twitter.com/ethplorer" target="_blank">Ethplorer\'s twitter</a> to know first.</center>';
            $("#" + popupId).html(widgetCode);
        }else{
            $("#" + popupId).text(widgetCode);
            var popupContent = $("#" + popupId).html();
            $("#" + popupId).html(popupContent.replace(/(\n)/gm, "<br/>"));
        }

        $("#" + popupId).dialog('open');
    },
    parseTemplate: function(template, data){
        var res = template;
        for(var key in data){
            var reg = new RegExp('%' + key + '%', 'g')
            res = res.replace(reg, data[key]);
        }
        return res;
    },
    // Tilda css hack
    fixTilda: function(){
        var height = parseInt($('.t-cover__wrapper').height());
        $('.t-cover, .t-cover__carrier, .t-cover__wrapper, .t-cover__filter').height(height + 'px');
    },
    // Use local path for develop instances
    fixPath: function(){
        if((document.location.host !== 'ethplorer.io') && (document.location.host.indexOf('ethplorer') >= 0)){
            ethplorerWidget.api = '//' + document.location.host + '/api';
            ethplorerWidget.url = '//' + document.location.host
        }
    },
    Utils: {
        link: function(data, text, title, hash, addClass){
            title = title || text;
            hash = hash || false;
            if((false !== hash) && hash){
                hash = '#' + hash;
            }else{
                hash = '';
            }
            var linkType = (data && (42 === data.toString().length)) ? 'address' : 'tx';
            if(!addClass){
                addClass = "";
            }
            return '<a class="tx-link ' + addClass + '" href="' + ethplorerWidget.url + '/' + linkType + '/' + data + hash + '" title="' + title + '" target="_blank">' + text + '</a>';
        },

        // Timestamp to local date
        ts2date: function(ts, withTime, withGMT){
            withGMT = 'undefined' !== typeof(withGMT) ? withGMT : true;
            ts *= 1000;
            function padZero(s){
                return (s < 10) ? '0' + s : s.toString();
            }        
            var res = '';
            var dt = new Date(ts);
            res += (dt.getFullYear() + '-' + padZero((dt.getMonth() + 1)) + '-' + padZero(dt.getDate()));
            if(withTime){
                res += ' ';
                res += ethplorerWidget.Utils.ts2time(ts, withGMT);
            }
            return res;
        },
        //Timestamp to local time
        ts2time: function(ts, withGMT){
            withGMT = 'undefined' !== typeof(withGMT) ? withGMT : true;
            ts *= 1000;
            function padZero(s){
                return (s < 10) ? '0' + s : s.toString();
            }        
            var res = '';
            var dt = new Date(ts);
            res += (padZero(dt.getHours()) + ':' + padZero(dt.getMinutes()) + ':' + padZero(dt.getSeconds()));
            if(withGMT){
                res += (' (' + Ethplorer.Utils.getTZOffset() + ')');
            }
            return res;
        },
        // Return local offset
        getTZOffset: function(){
            var offset = -Math.round(new Date().getTimezoneOffset() / 60);
            return 'GMT' + (offset > 0 ? '+' : '-') + offset;
        },
        /**
         * Number formatter (separates thousands with comma, adds zeroes to decimal part).
         *
         * @param {int} num
         * @param {bool} withDecimals
         * @param {int} decimals
         * @param {bool} cutZeroes
         * @returns {string}
         */
        formatNum: function(num, withDecimals /* = false */, decimals /* = 2 */, cutZeroes /* = false */){
            function math(command, val, decimals){
                var k = Math.pow(10, decimals ? parseInt(decimals) : 0);
                return Math[command](val * k) / k;
            }
            function padZero(s, len){
                while(s.length < len) s += '0';
                return s;
            }
            if(('object' === typeof(num)) && ('undefined' !== typeof(num.c))){
                num = parseFloat(Ethplorer.Utils.toBig(num).toString());
            }
            cutZeroes = !!cutZeroes;
            withDecimals = !!withDecimals;
//            decimals = decimals || (cutZeroes ? 0 : 2);
            
            if((num.toString().indexOf("e+") > 0)){
                return num.toString();
            }
            
            if((num.toString().indexOf("e-") > 0) && withDecimals){
                var parts = num.toString().split("e-");
                var res = parts[0].replace('.', '');
                for(var i=1; i<parseInt(parts[1]); i++){
                    res = '0' + res;
                }
                return '0.' + res;
            }

            if(withDecimals){
                num = math('round', num, decimals);
            }
            var parts = num.toString().split('.');
            var res = parts[0].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            var zeroCount = cutZeroes ? 0 : decimals;
            if(withDecimals && decimals){
                if(parts.length > 1){
                    res += '.';
                    var tail = parts[1].substring(0, decimals);
                    if(tail.length < zeroCount){
                        tail = padZero(tail, zeroCount);
                    }
                    res += tail;
                }else{
                    res += padZero('.', parseInt(zeroCount) + 1);
                }
            }
            res = res.replace(/\.$/, '');
            return res;
        },
        selectText: function(containerid){
            if(document.selection){
                var range = document.body.createTextRange();
                range.moveToElementText(document.getElementById(containerid));
                range.select();
            }else if(window.getSelection){
                var range = document.createRange();
                range.selectNode(document.getElementById(containerid));
                window.getSelection().addRange(range);
            }
        },
        pdiff: function(a, b, x){
            var res = 100;
            if(x && !b){
                return (a > 0) ? 'x' : 0;
            }
            if(a !== b){
                if(a && b){
                    res = (a / b) * 100 - 100;
                }else{
                    res *= ((a - b) < 0) ? -1 : 1;
                }
            }else{
                res = 0;
            }
            return res;
        }
    }
};

/**
 * Last Token transactions Widget.
 *
 * @param {type} element
 * @param {type} options
 * @param {type} templates
 * @returns {undefined}
 */
ethplorerWidget.Type['tokenHistory'] = function(element, options, templates){
    this.el = element;
    this.options = options;
    this.api = ethplorerWidget.api + '/getTokenHistory';
    if(options && options.address){
        this.api += ('/' + options.address.toString().toLowerCase());
    }
    this.ts = false;
    this.interval = false;

    this.templates = {
        header: '<div class="txs-header">Recent token transactions</div>',
        loader: '<div class="txs-loading">Loading...</div>',
        debug: '<div class="txs-debug"><div class="txs-stop"></div></div>',
        // Big table row
        bigScreenTable: '<tr>' + 
                    '<td class="tx-field tx-date">%time%</td>' + 
                    '<td class="tx-field tx-transfer"><span class="tx-send">from </span>%from%<span class="tx-send">to</span>%to%</td>' + 
                    '<td class="tx-field tx-amount">%amount%</td>' +
                    '<td class="tx-field tx-token">%token%</td>' +
                '</tr>',
        // Small table row
        smallScreenTable: '<tr>' +
                    '<td class="tx-field tx-date">%time%</td>' +
                    '<td class="tx-field tx-transfer"><span class="tx-send">from </span>%from%</td>' +
                '</tr><tr>' +
                    '<td class="tx-field">&nbsp;</td>' +
                    '<td class="tx-field tx-transfer"><span class="tx-send">to</span>%to%</td>' +
                '</tr><tr>' +
                    '<td colspan="2" class="tx-field tx-amount">%amount% <span class="tx-token">%token%</span></td>' +
                '</tr>'
    };

    // Override default templates with custom
    if('object' === typeof(templates)){
        for(var key in templates){
            this.templates[key] = templates[key];
        }
    }
   
    this.refresh = function(obj){
        return function(){
            $.getJSON(obj.api, obj.getRequestParams(obj.ts ? {timestamp: obj.ts} : false), obj.refreshWidget);
        }
    }(this);

    this.load = function(){
        this.el.html(this.templates.header + this.templates.loader);
        $.getJSON(this.api, this.getRequestParams(), this.refreshWidget);
    };

    this.init = function(){
        this.el.addClass('ethplorer-widget');
        this.el.addClass('widget-tokenHistory');
        this.el.addClass('theme-' + (this.options.theme ? this.options.theme : 'ethplorer'));
        this.interval = setInterval(this.refresh, 15000);
        this.load();
    };

    this.getRequestParams = function(additionalParams){
        var requestOptions = ['limit', 'address', 'timestamp'];
        var params = {
            apiKey: 'ethplorer.widget',
            type: 'transfer'
        };
        if('undefined' === typeof(this.pathReported)){
            params['domain'] = document.location.href;
            this.pathReported = true;
        }
        for(var key in this.options){
            if(requestOptions.indexOf(key) >= 0){
                params[key] = this.options[key];
            }
        }
        if('object' === typeof(additionalParams)){
            for(var key in additionalParams){
                if(requestOptions.indexOf(key) >= 0){
                    params[key] = additionalParams[key];
                }
            }
        }
        return params;
    };

    this.setPlayPause = function(obj){
        return function(){
            if(obj.interval){
                clearInterval(obj.interval);
                obj.interval = false;
                obj.el.find('.txs-stop').html('&#9658;');
                obj.el.find('.txs-stop').attr('title', 'Start refresh');
                obj.el.find('.txs-stop').css('color', '#00ff00');
            }else{
                obj.interval = setInterval(obj.refresh, 15000);
                obj.el.find('.txs-stop').html('&#10074;&#10074;');
                obj.el.find('.txs-stop').attr('title', 'Pause refresh');
                obj.el.find('.txs-stop').css('color', 'yellow');
            };
        };
    }(this);

    this.refreshWidget = function(obj){
        return function(data){
            if(!obj.ts){
                return obj.cbFirstLoad(data);
            }
            obj.el.find('tr').removeClass('hidden new');
            return obj.cbRefresh(data);
        };
    }(this);

    this.cbFirstLoad = function(data){
        if(data && !data.error && data.operations && data.operations.length){
            this.el.find('.txs-loading').remove();
            if(this.ts === data.operations[0].timestamp){
                // Skip redraw if nothing changed
                return;
            }
            this.ts = data.operations[0].timestamp;
            var txTable = '<table class="txs big-screen-table">';
            var txSmall = '<table class="txs small-screen-table">';
            for(var i=0; i<data.operations.length; i++){
                var rowData = this.prepareData(data.operations[i]);
                txTable += ethplorerWidget.parseTemplate(this.templates.bigScreenTable, rowData);
                txSmall += ethplorerWidget.parseTemplate(this.templates.smallScreenTable, rowData);
            }
            txSmall += '</table>';
            txTable += '</table>';
            this.el.append(txTable);
            this.el.append(txSmall);

            ethplorerWidget.appendEthplorerLink(this);
            
            // Debug mode
            if(this.options.debug){
                this.el.find(".txs-header").append(this.templates.debug);
                this.el.find('.txs-stop').click(this.setPlayPause);
                this.el.find('.txs-stop').html('&#10074;&#10074;');
                this.el.find('.txs-stop').attr('title', 'Pause refresh');
                this.el.find('.txs-stop').css('color', 'yellow');
            }
            if('function' === typeof(this.options.onLoad)){
                this.options.onLoad();
            }
            setTimeout(ethplorerWidget.fixTilda, 300);
        }
    };

    this.cbRefresh = function(data){
        if(data && !data.error && data.operations && data.operations.length){
            this.ts = data.operations[0].timestamp;
            var txTable = this.el.find(".txs.big-screen-table");
            var txSmall = this.el.find(".txs.small-screen-table");
            for(var i=0; i<data.operations.length; i++){
                var rowData = this.prepareData(data.operations[i]);
                var bigRows = $(ethplorerWidget.parseTemplate(this.templates.bigScreenTable, rowData));
                var smallRows = $(ethplorerWidget.parseTemplate(this.templates.smallScreenTable, rowData));
                bigRows.addClass('hidden');
                smallRows.addClass('hidden');
                txTable.prepend(bigRows);
                txSmall.prepend(smallRows);
                setTimeout(
                    function(el){
                        return function(){
                            el.find('.hidden').addClass('new');
                        }
                    }(this.el),
                    200
                );

                var limit = this.options.limit ? this.options.limit : 10;
                var rowsToKill = limit * bigRows.length;
                if(rowsToKill){
                    txTable.find('tr').each(function(i){
                        if(i >= rowsToKill){
                            $(this).remove();
                        }
                    });
                }
                var rowsToKill = limit * smallRows.length;
                if(rowsToKill){
                    txSmall.find('tr').each(function(i){
                        if(i >= rowsToKill){
                            $(this).remove();
                        }
                    });
                }
            }
        }
    };

    this.prepareData = function(tr){
        if(!tr.tokenInfo){
            tr.tokenInfo = {symbol: "", decimals: 0};
        }
        if(!tr.tokenInfo.symbol && tr.tokenInfo.name){
            tr.tokenInfo.symbol = tr.tokenInfo.name;
        }
        var k = Math.pow(10, tr.tokenInfo.decimals);
        var amount = ethplorerWidget.Utils.formatNum(tr.value / k, true, parseInt(tr.tokenInfo.decimals), true);

        var hash = tr.priority ? tr.priority : false;

        return {
            date: ethplorerWidget.Utils.link(tr.transactionHash, ethplorerWidget.Utils.ts2date(tr.timestamp, false, false), tr.transactionHash, hash),
            time: ethplorerWidget.Utils.link(tr.transactionHash, ethplorerWidget.Utils.ts2time(tr.timestamp, false), tr.transactionHash, hash),
            datetime: ethplorerWidget.Utils.link(tr.transactionHash, ethplorerWidget.Utils.ts2date(tr.timestamp, true, false), tr.transactionHash, hash),
            from:  ethplorerWidget.Utils.link(tr.from, tr.from),
            to: ethplorerWidget.Utils.link(tr.to, tr.to),
            amount: ethplorerWidget.Utils.link(tr.tokenInfo.address, amount, amount + ' ' + tr.tokenInfo.symbol),
            token: ethplorerWidget.Utils.link(tr.tokenInfo.address, tr.tokenInfo.symbol, tr.tokenInfo.symbol + ' ' + tr.tokenInfo.address)
        };
    };

    this.init();
}

/**
 * Top Tokens list Widget.
 *
 * @param {type} element
 * @param {type} options
 * @param {type} templates
 * @returns {undefined}
 */
ethplorerWidget.Type['topTokens'] = function(element, options, templates){
    this.el = element;

    this.options = {
        limit: 10,
        period: 30
    };

    if(options){
        for(var key in options){
            this.options[key] = options[key];
        }
    }

    var row = '<tr>' + 
        '<td class="tx-field">%position%</td>';

    var criteria = options.criteria ? options.criteria : false;

    this.api = ethplorerWidget.api + '/getTopTokens';

    this.templates = {
        header: '<div class="txs-header">Top %limit% tokens for %period% days</div>',
        loader: '<div class="txs-loading">Loading...</div>',
    };

    switch(criteria){
        case 'byPrice':
            row += '<td class="tx-field">%name%</td>';
            row = row +'<td class="tx-field" title="">%price%</td>';
            break;
        case 'byCurrentVolume':
            this.templates.header = '<div class="txs-header">Top %limit% tokens</div>';
        case 'byPeriodVolume':
            row += '<td class="tx-field">%name_symbol%</td>';
            row += '<td class="tx-field" title="">%volume%</td>';
            break;
        default:
            row += '<td class="tx-field">%name%</td>';
            row = row + '<td class="tx-field" title="%opCount% operations">%opCount%</td>' + '</tr>';
    }
    
    this.templates.row = row;

    // Override default templates with custom
    if('object' === typeof(templates)){
        for(var key in templates){
            this.templates[key] = templates[key];
        }
    }

    this.load = function(){
        this.el.html(ethplorerWidget.parseTemplate(this.templates.header, this.options) + this.templates.loader);
        $.getJSON(this.api, this.getRequestParams(), this.refreshWidget);
    };

    this.init = function(){
        this.el.addClass('ethplorer-widget');
        this.el.addClass('widget-topTokens');
        this.el.addClass('theme-' + (this.options.theme ? this.options.theme : 'ethplorer'));
        this.load();
    };

    this.getRequestParams = function(additionalParams){
        var requestOptions = ['limit', 'period', 'criteria'];
        var params = {
            apiKey: 'freekey'
        };
        if('undefined' === typeof(this.pathReported)){
            params['domain'] = document.location.href;
            this.pathReported = true;
        }
        for(var key in this.options){
            if(requestOptions.indexOf(key) >= 0){
                params[key] = this.options[key];
            }
        }
        if('object' === typeof(additionalParams)){
            for(var key in additionalParams){
                if(requestOptions.indexOf(key) >= 0){
                    params[key] = additionalParams[key];
                }
            }
        }
        return params;
    };

    this.refreshWidget = function(obj){
        return function(data){
            if(data && !data.error && data.tokens && data.tokens.length){
                obj.el.find('.txs-loading').remove();
                var txTable = '<table class="txs">';
                for(var i=0; i<data.tokens.length; i++){
                    var rowData = obj.prepareData(data.tokens[i]);
                    rowData['position'] = i+1;
                    txTable += ethplorerWidget.parseTemplate(obj.templates.row, rowData);
                }
                txTable += '</table>';
                obj.el.append(txTable);

                ethplorerWidget.appendEthplorerLink(obj);

                if('function' === typeof(obj.options.onLoad)){
                    obj.options.onLoad();
                }
                setTimeout(ethplorerWidget.fixTilda, 300);
            }
        };
    }(this);

    this.prepareData = function(data){
        var name = data.name ? data.name : data.address;
        var symbol = data.symbol ? data.symbol : '';
        return {
            address: ethplorerWidget.Utils.link(data.address, data.address, data.address),
            name: ethplorerWidget.Utils.link(data.address, name, name, false, data.name ? "" : "tx-unknown"),
            name_symbol: ethplorerWidget.Utils.link(data.address, name + (symbol ? ' (' + symbol + ')' : ''), name + (symbol ? ' (' + symbol + ')' : ''), false, data.name ? "" : "tx-unknown"),
            opCount: data.opCount,
            price: (data.price && data.price.rate) ? ('$ ' + ethplorerWidget.Utils.formatNum(data.price.rate, true, 2, true)) : '',
            volume: data.volume ? ('$ ' + ethplorerWidget.Utils.formatNum(data.volume, true, 2, true)) : ''
        };
    };

    this.init();
}


/**
 * Top list Widget.
 *
 * @param {type} element
 * @param {type} options
 * @param {type} templates
 * @returns {undefined}
 */
ethplorerWidget.Type['top'] = function(element, options, templates){
    this.el = element;

    this.options = {
        limit: 10,
        periods: [1, 7, 30]
    };

    if(options){
        for(var key in options){
            this.options[key] = options[key];
        }
    }

    this.cache = {};

    this.options.criteria = options.criteria || 'trade';

    this.api = ethplorerWidget.api + '/getTop';

    this.templates = {
        header: '<div class="txs-header">Top tokens</div>' +
                '<div style="text-align:center"><a data-criteria="trade">Trade</a> | <a data-criteria="cap">Cap</a> | <a data-criteria="count">Tx Count</a></div>',
        loader: '<div class="txs-loading">Loading...</div>',
        criteria: {
            cap: {
                rowHeader: '<tr>' +
                        '<th class="tx-field">#</th>' +
                        '<th class="tx-field">Token</th>' +
                        '<th class="tx-field">Cap</th>' +
                        '<th class="tx-field">Price</th>' +
                        '<th class="tx-field">Trend(24h)</th>' +
                        '<th class="tx-field">Trend(7d)</th>' +
                        '<th class="tx-field">Trend(30d)</th>' +
                   '</tr>',
                row: '<tr>' +
                        '<td class="tx-field">%position%</td>' +
                        '<td class="tx-field">%name_symbol%</td>' +
                        '<td class="tx-field">%cap%</td>' +
                        '<td class="tx-field">%price%</td>' +
                        '<td class="tx-field">%trend_1d%</td>' +
                        '<td class="tx-field">%trend_7d%</td>' +
                        '<td class="tx-field">%trend_30d%</td>' +
                   '</tr>'
            },
            trade: {
                rowHeader: '<tr>' +
                        '<th class="tx-field">#</th>' +
                        '<th class="tx-field">Token</th>' +
                        '<th class="tx-field ewDiff">Volume (24h)</th>' +
                        '<th class="tx-field ewDiff">Price</th>' +
                        '<th class="tx-field ewDiff">Trend(24h)</th>' +
                        '<th class="tx-field ewDiff">Trend(7d)</th>' +
                        '<th class="tx-field ewDiff">Trend(30d)</th>' +
                   '</tr>',
                row: '<tr>' +
                        '<td class="tx-field">%position%</td>' +
                        '<td class="tx-field">%name_symbol%</td>' +
                        '<td class="tx-field">%volume%</td>' +
                        '<td class="tx-field ewDiff">%price%</td>' +
                        '<td class="tx-field ewDiff">%trend_1d%</td>' +
                        '<td class="tx-field ewDiff">%trend_7d%</td>' +
                        '<td class="tx-field ewDiff">%trend_30d%</td>' +
                   '</tr>'
           }
        }
    };

    // Override default templates with custom
    if('object' === typeof(templates)){
        for(var key in templates){
            this.templates[key] = templates[key];
        }
    }

    this.load = function(){
        if('undefined' !== typeof(this.templates.criteria[this.options.criteria])){
            var criteriaTpl = this.templates.criteria[this.options.criteria];
            if(criteriaTpl.header){
                this.templates.header = criteriaTpl.header;
            }
            if(criteriaTpl.row){
                this.templates.row = criteriaTpl.row;
            }
            if(criteriaTpl.rowHeader){
                this.templates.rowHeader = criteriaTpl.rowHeader;
            }
        }
        if('undefined' === typeof(this.cache[this.options.criteria])){
            this.el.html(ethplorerWidget.parseTemplate(this.templates.header, this.options) + this.templates.loader);        
            $.getJSON(this.api, this.getRequestParams(), this.refreshWidget);
        }else{
            this.el.html(ethplorerWidget.parseTemplate(this.templates.header, this.options));
            this.refreshWidget(this.cache[this.options.criteria]);
        }
    };

    this.init = function(){
        this.el.addClass('ethplorer-widget');
        this.el.addClass('widget-topTokens');
        this.el.addClass('theme-' + (this.options.theme ? this.options.theme : 'ethplorer'));
        this.load();
    };

    this.getRequestParams = function(additionalParams){
        var requestOptions = ['limit', 'period', 'criteria'];
        var params = {
            apiKey: 'freekey'
        };
        if('undefined' === typeof(this.pathReported)){
            params['domain'] = document.location.href;
            this.pathReported = true;
        }
        for(var key in this.options){
            if(requestOptions.indexOf(key) >= 0){
                params[key] = this.options[key];
            }
        }
        if('object' === typeof(additionalParams)){
            for(var key in additionalParams){
                if(requestOptions.indexOf(key) >= 0){
                    params[key] = additionalParams[key];
                }
            }
        }
        return params;
    };

    this.refreshWidget = function(obj){
        return function(data){
            if(data && !data.error && data.tokens && data.tokens.length){
                if('undefined' === typeof(obj.cache[obj.options.criteria])){
                    obj.cache[obj.options.criteria] = data;
                }
                obj.el.find('.txs-loading, .txs').remove();
                var txTable = '<table class="txs">';
                txTable += obj.templates.rowHeader;
                for(var i=0; i<data.tokens.length; i++){
                    var rowData = obj.prepareData(data.tokens[i]);
                    rowData['position'] = i+1;
                    txTable += ethplorerWidget.parseTemplate(obj.templates.row, rowData);
                }
                txTable += '</table>';
                obj.el.append(txTable);

                ethplorerWidget.appendEthplorerLink(obj);
                obj.el.find('[data-criteria]').click(function(_obj){
                    return function(){
                        if(!$(this).hasClass('ewSelected')){
                            _obj.el.find('.ewSelected').removeClass('ewSelected');
                            $(this).addClass('ewSelected');                            
                            _obj.options.criteria = $(this).attr('data-criteria');
                            _obj.load();
                        }
                    };
                }(obj))

                obj.el.find('[data-criteria="' + obj.options.criteria + '"]').addClass('ewSelected');

                if('undefined' === typeof(obj.onLoadFired)){
                    if('function' === typeof(obj.options.onLoad)){
                        obj.options.onLoad();
                    }
                    obj.onLoadFired = true;
                }

                setTimeout(ethplorerWidget.fixTilda, 300);
            }
        };
    }(this);

    this.prepareData = function(data){
        var name = data.name ? data.name : data.address;
        var symbol = data.symbol ? data.symbol : '';

        // @todo: remove code duplicate and "x"s
        var ivdiff = ethplorerWidget.Utils.pdiff(data['volume-1d-current'], data['volume-1d-previous'], true);
        if('x' === ivdiff){
            var trend_1d = '--';
        }else{
            var vdiff = ethplorerWidget.Utils.formatNum(ivdiff, true, 2, false);
            var trend_1d = '<span class="ewDiff' + ((ivdiff > 0) ? 'Up' : 'Down') + '">' + ((ivdiff > 0) ? ('+' + vdiff) : vdiff) + '%' + '</span>';
        }

        var ivdiff = ethplorerWidget.Utils.pdiff(data['volume-7d-current'], data['volume-7d-previous'], true);
        if('x' === ivdiff){
            var trend_7d = '--';
        }else{
            var vdiff = ethplorerWidget.Utils.formatNum(ivdiff, true, 2, false);
            var trend_7d = '<span class="ewDiff' + ((ivdiff > 0) ? 'Up' : 'Down') + '">' + ((ivdiff > 0) ? ('+' + vdiff) : vdiff) + '%' + '</span>';
        }

        var ivdiff = ethplorerWidget.Utils.pdiff(data['volume-30d-current'], data['volume-30d-previous'], true);
        if('x' === ivdiff){
            var trend_30d = '--';
        }else{
            var vdiff = ethplorerWidget.Utils.formatNum(ivdiff, true, 2, false);
            var trend_30d = '<span class="ewDiff' + ((ivdiff > 0) ? 'Up' : 'Down') + '">' + ((ivdiff > 0) ? ('+' + vdiff) : vdiff) + '%' + '</span>';
        }

        return {
            address: ethplorerWidget.Utils.link(data.address, data.address, data.address),
            name: ethplorerWidget.Utils.link(data.address, name, name, false, data.name ? "" : "tx-unknown"),
            name_symbol: ethplorerWidget.Utils.link(data.address, name + (symbol ? ' (' + symbol + ')' : ''), name + (symbol ? ' (' + symbol + ')' : ''), false, data.name ? "" : "tx-unknown"),
            opCount: data.opCount,
            price: (data.price && data.price.rate) ? ('$ ' + ethplorerWidget.Utils.formatNum(data.price.rate, true, 2, false)) : '',
            volume: data.volume ? ('$ ' + ethplorerWidget.Utils.formatNum(data.volume, true, data.volume >= 1000 ? 0 : 2, true)) : '',
            trend_1d: trend_1d,
            trend_7d: trend_7d,
            trend_30d: trend_30d
        };
    };

    this.init();
}

/**
 * Token history grouped Widget.
 *
 * @param {type} element
 * @param {type} options
 * @param {type} templates
 * @returns {undefined}
 */
ethplorerWidget.Type['tokenHistoryGrouped'] = function(element, options, templates){
    this.el = element;
    this.widgetData = null;
    this.resizeTimer = null;

    this.options = {
        period: 30,
        type: 'area',
        theme: 'light',
        options: {}
    };

    if(options){
        for(var key in options){
            this.options[key] = options[key];
        }
    }

    this.api = ethplorerWidget.api + '/getTokenHistoryGrouped';
    if(options && options.address){
        this.api += ('/' + options.address.toString().toLowerCase());
    }
    
    this.templates = {
        loader: '<div class="txs-loading">Loading...</div>',
    };

    this.load = function(){
        $.getJSON(this.api, this.getRequestParams(), this.refreshWidget);
    };

    this.drawChart = function(aTxData){
        var aData = [];
        aData.push(['Day', 'Token operations']);

        var stDate = new Date(),
            fnDate = new Date();
        var date = stDate.getDate();
        stDate.setDate(date - 1);
        fnDate.setDate(date - this.options.period - 1);

        var aCountData = {};
        for(var i = 0; i < aTxData.length; i++){
            var aDayData = aTxData[i];
            aCountData[aDayData._id.year + '-' + aDayData._id.month + '-' + aDayData._id.day] = aDayData.cnt;
        }

        var curDate = true;
        while(stDate > fnDate){
            var key = stDate.getFullYear() + '-' + (stDate.getMonth() + 1) + '-' + stDate.getDate();
            var cnt = ('undefined' !== typeof(aCountData[key])) ? aCountData[key] : 0;
            if(curDate && cnt == 0){
                curDate = false;
                continue;
            }
            aData.push([new Date(stDate.getFullYear(), stDate.getMonth(), stDate.getDate()), cnt]);
            var newDate = stDate.setDate(stDate.getDate() - 1);
            stDate = new Date(newDate);
        }

        var data = google.visualization.arrayToDataTable(aData);
        var def = {
            title: '',
            legend: { position: 'none' },
            tooltip: {
                format: 'MMM d',
            },
            hAxis : {
                title: '',
                titleTextStyle: {
                    italic: false
                },
                textPosition: 'out',
                slantedText: false,
                maxAlternation: 1,
                maxTextLines: 1,
                format: 'MMM d',
                gridlines: {
                    count: 10,
                    color: "none"
                }
            },
            vAxis: {
                title: '',
                titleTextStyle: {
                    italic: false
                },
                minValue: 0,
                viewWindow: {
                    min: 0
                },
                gridlines: {
                    color: "none"
                },
                maxValue: 3,
                format: '#,###',
            },
            pointSize: 5,
        };
        if(this.options['theme'] == 'dark'){
            def.colors = ['#47C2FF'];
            def.titleTextStyle = {color: '#DEDEDE'};
            def.backgroundColor = {fill: 'transparent'};

            def.hAxis.textStyle = {color: '#DEDEDE'};
            def.hAxis.titleTextStyle.color = '#DEDEDE';
            def.hAxis.baselineColor = '#DEDEDE';

            def.vAxis.textStyle = {color: '#DEDEDE'};
            def.vAxis.titleTextStyle.color = '#DEDEDE';
            def.vAxis.baselineColor = 'none';
        }
        var options = $.extend(true, def, this.options['options']);

        var tooltipFormatter = new google.visualization.DateFormat({ 
            pattern: "MMM dd, yyyy '+UTC'"
        });
        tooltipFormatter.format(data, 0);

        if(this.options['type'] == 'area') var chart = new google.visualization.AreaChart(this.el[0]);
        else if(this.options['type'] == 'line') var chart = new google.visualization.LineChart(this.el[0]);
        else var chart = new google.visualization.ColumnChart(this.el[0]);
        chart.draw(data, options);
    };

    this.init = function(){
        this.el.addClass('ethplorer-widget');
        this.el.addClass('widget-tokenHistoryGrouped');
        this.el.addClass('theme-' + (this.options.theme ? this.options.theme : 'ethplorer'));
        this.el.html(this.templates.loader);
        //this.load();
    };

    this.getRequestParams = function(additionalParams){
        var requestOptions = ['period', 'address', 'type', 'theme'];
        var params = {
            apiKey: 'freekey'
        };
        if('undefined' === typeof(this.pathReported)){
            params['domain'] = document.location.href;
            this.pathReported = true;
        }
        for(var key in this.options){
            if(requestOptions.indexOf(key) >= 0){
                params[key] = this.options[key];
            }
        }
        if('object' === typeof(additionalParams)){
            for(var key in additionalParams){
                if(requestOptions.indexOf(key) >= 0){
                    params[key] = additionalParams[key];
                }
            }
        }
        return params;
    };

    this.refreshWidget = function(obj){
        return function(data){
            if(data && !data.error && data.countTxs /*&& data.countTxs.length*/){
                obj.widgetData = data.countTxs;
                google.charts.setOnLoadCallback(
                    function(){
                        obj.el.find('.txs-loading').remove();
                        obj.drawChart(data.countTxs);
                        ethplorerWidget.appendEthplorerLink(obj);
                        if('function' === typeof(obj.options.onLoad)){
                            obj.options.onLoad();
                        }
                        setTimeout(ethplorerWidget.fixTilda, 300);
                    }
                );
            }else{
                obj.el.find('.txs-loading').remove();
            }
        };
    }(this);

    $(window).resize(this, function(){
        var obj = arguments[0].data;
        if(obj.resizeTimer) clearTimeout(obj.resizeTimer);
        obj.resizeTimer = setTimeout(function(){
            if(obj.widgetData){
                obj.el.empty();
                obj.drawChart(obj.widgetData);
                ethplorerWidget.appendEthplorerLink(obj);
            }
        }, 500);
    });

    this.init();
    ethplorerWidget.chartWidgets.push(this);
};

/**
 * Token history with prices grouped Widget.
 *
 * @param {type} element
 * @param {type} options
 * @param {type} templates
 * @returns {undefined}
 */
ethplorerWidget.Type['tokenPriceHistoryGrouped'] = function(element, options, templates){
    this.type = 'tokenPriceHistoryGrouped';
    this.el = element;
    this.widgetData = null;
    this.widgetPriceData = null;
    this.resizeTimer = null;
    this.cachedWidth = $(window).width();

    this.options = {
        period: 365,
        type: 'area',
        theme: 'light',
        options: {},
        controlOptions: {}
    };

    if(options){
        for(var key in options){
            this.options[key] = options[key];
        }
    }
    if(this.options.period <= 0){
        this.options.period = 365;
    }else if(this.options.period < 7){
        this.options.period = 7;
    }

    this.api = ethplorerWidget.api + '/getTokenPriceHistoryGrouped';
    if(options && options.address){
        this.api += ('/' + options.address.toString().toLowerCase());
    }

    this.templates = {
        loader: '<div class="txs-loading">Loading...</div>',
    };

    this.load = function(){
        $.getJSON(this.api, this.getRequestParams(), this.refreshWidget);
    };

    this.getTooltip = function(noPrice, date, low, open, close, high, operations, volume, convertedVolume){
        var tooltipDateFormatter = new google.visualization.DateFormat({ 
            pattern: "MMM dd, yyyy '+UTC'"
        });
        var numFormatter = new google.visualization.NumberFormat({ 
            pattern: "#,###"
        });
        var currencyFormatter = new google.visualization.NumberFormat({ 
            pattern: '#,##0.00###'
        });
        var avgFormatter = new google.visualization.NumberFormat({ 
            pattern: '#,##0.00'
        });
        var tooltip = '<div style="display: block !important; text-align: left; opacity: 1 !important; color: #000000 !important; padding: 5px;">';
        tooltip += tooltipDateFormatter.formatValue(date) + '<br/>';
        if(noPrice){
            tooltip += '<span class="tooltipRow"><b>Token operations:</b> ' + operations + '</span><br/>';
        }else{
            if(volume > 0) var avg = convertedVolume / volume;
            else var avg = (open + close) / 2;
            tooltip += '<span class="tooltipRow"><b>Average:</b> ' + avgFormatter.formatValue(avg) + ' USD</span><br/>' +
                '<span class="tooltipRow"><b>Open:</b> ' + currencyFormatter.formatValue(open) + ' <b>Close:</b> ' + currencyFormatter.formatValue(close) + '</span><br/>' +
                '<span class="tooltipRow"><b>High:</b> ' + currencyFormatter.formatValue(high) + ' <b>Low:</b> ' + currencyFormatter.formatValue(low) + '</span><br/>' +
                '<span class="tooltipRow"><b>Token operations:</b> ' + numFormatter.formatValue(operations) + '</span><br/>' +
                '<span class="tooltipRow"><b>Volume:</b> ' + numFormatter.formatValue(volume.toFixed(0)) + ' (' + numFormatter.formatValue(convertedVolume.toFixed(2)) + ' USD)</span>';
        }
        tooltip += '</div>';
        return tooltip;
    }

    this.drawChart = function(aTxData, widgetPriceData){
        var aData = [];

        if(aTxData.length){
            var firstMonth = aTxData[0]._id.month,
                firstDay = aTxData[0]._id.day;
            if(firstMonth < 10) firstMonth = '0' + firstMonth;
            if(firstDay < 10) firstDay = '0' + firstDay;
            var strFirstDate = aTxData[0]._id.year + '-' + firstMonth + '-' + firstDay + 'T00:00:00Z';

            if(widgetPriceData && widgetPriceData.length){
                var strLastPriceDate = widgetPriceData[widgetPriceData.length - 1].date + 'T00:00:00Z';
            }
            if(new Date(strLastPriceDate) > new Date(strFirstDate)){
                strFirstDate = strLastPriceDate;
            }
        }else{
            return;
        }

        var stDate = new Date(strFirstDate);
            fnDate = new Date(strFirstDate),
            rangeStart = new Date(strFirstDate);
        var date = stDate.getDate();
        fnDate.setDate(date - this.options.period + 1);
        rangeStart.setDate(date - (this.options.period > 60 ? 60 : this.options.period) + 1);

        // prepare data
        var aCountData = {};
        var aPriceData = {};
        for(var i = 0; i < aTxData.length; i++){
            var aDayData = aTxData[i];
            aCountData[aDayData._id.year + '-' + aDayData._id.month + '-' + aDayData._id.day] = aDayData.cnt;
        }
        var noPrice = true,
            startPriceDate = new Date(),
            priceNotFound = true;
        if(widgetPriceData && widgetPriceData.length){
            for(var i = 0; i < widgetPriceData.length; i++){
                var aDayPriceData = widgetPriceData[i],
                    numZeroes = 0;
                if(aDayPriceData.low == 0) numZeroes++;
                if(aDayPriceData.open == 0) numZeroes++;
                if(aDayPriceData.close == 0) numZeroes++;
                if(aDayPriceData.high == 0) numZeroes++;

                if((numZeroes >= 3) && priceNotFound){
                    continue;
                }else{
                    aPriceData[aDayPriceData.date] = aDayPriceData;
                    if(priceNotFound){
                        var strPriceDate = aDayPriceData.date.substring(0, 4) + '-' + aDayPriceData.date.substring(5, 7) + '-' + aDayPriceData.date.substring(8) + 'T00:00:00Z';
                        startPriceDate = new Date(strPriceDate);
                    }
                    priceNotFound = false;
                }
            }

            if(!priceNotFound){
                noPrice = false;
                aData.push(['Day', 'Low', 'Open', 'Close', 'High', {type: 'string', role: 'tooltip', 'p': {'html': true}}, 'Token operations', {role: 'style'}, {type: 'string', role: 'tooltip', 'p': {'html': true}}, 'Volume', {role: 'style'}, {type: 'string', role: 'tooltip', 'p': {'html': true}}]);
                if(this.options.period > 60){
                    fnDate = startPriceDate;
                }
            }
        }
        if(noPrice){
            var strMonth = aTxData[aTxData.length - 1]._id.month < 10 ? ('0' + aTxData[aTxData.length - 1]._id.month) : aTxData[aTxData.length - 1]._id.month,
                strDay = aTxData[aTxData.length - 1]._id.day < 10 ? ('0' + aTxData[aTxData.length - 1]._id.day) : aTxData[aTxData.length - 1]._id.day;
            var strDate = aTxData[aTxData.length - 1]._id.year + '-' + strMonth + '-' + strDay + 'T00:00:00Z';
            fnDate = new Date(strDate);
            aData.push(['Day', 'Token operations', {role: 'style'}, {type: 'string', role: 'tooltip', 'p': {'html': true}}]);
        }
        //console.log(aCountData);
        //console.log(aPriceData);

        var timeDiff = Math.abs(new Date(strFirstDate).getTime() - fnDate.getTime());
        var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
        if(diffDays < 7) fnDate.setDate(fnDate.getDate() - (7 - diffDays));

        var curDate = true;
        for(var d = new Date(strFirstDate); d >= fnDate; d.setDate(d.getDate() - 1)){
            //console.log(d);
            // get tx count
            var key = d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
            var cnt = ('undefined' !== typeof(aCountData[key])) ? aCountData[key] : 0;

            // get price data
            var keyPrice = d.getFullYear() + '-' + (d.getMonth() < 9 ? '0' : '') + (d.getMonth() + 1) + '-' + (d.getDate() < 10 ? '0' : '') + d.getDate();
            //console.log(keyPrice);

            // 'Low', 'Open', 'Close', 'High'
            var low = 0, open = 0, high = 0, close = 0, volume = 0, volumeConverted = 0;
            if('undefined' !== typeof(aPriceData[keyPrice])){
                low = aPriceData[keyPrice]['low'];
                open = aPriceData[keyPrice]['open'];
                close = aPriceData[keyPrice]['close'];
                high = aPriceData[keyPrice]['high'];
                volume = ('undefined' !== typeof(aPriceData[keyPrice]['volume'])) ? aPriceData[keyPrice]['volume'] : 0;
                volumeConverted = ('undefined' !== typeof(aPriceData[keyPrice]['volumeConverted'])) ? aPriceData[keyPrice]['volumeConverted'] : 0;
            }

            var chartMonth = d.getMonth() + 1;
            if(chartMonth < 10) chartMonth = '0' + chartMonth;
            var chartDay = d.getDate();
            if(chartDay < 10) chartDay = '0' + chartDay;
            var strChartDate = d.getFullYear() + '-' + chartMonth + '-' + chartDay + 'T00:00:00Z';

            var tooltip = this.getTooltip(noPrice, new Date(strChartDate), low, open, close, high, cnt, volume, volumeConverted);
            if(noPrice){
                aData.push([new Date(strChartDate), cnt, 'opacity: 0.5', tooltip]);
            }else{
                aData.push([new Date(strChartDate), low, open, close, high, tooltip, cnt, 'opacity: 0.5', tooltip, volume, this.options['theme'] == 'dark' ? 'opacity: 0.15' : 'opacity: 0.5', tooltip]);
            }
        }
        //console.log(aData);
        var data = google.visualization.arrayToDataTable(aData);

        // create div's
        this.el.append($('<div>', {id: 'chart'}));
        this.el.append($('<div>', {id: 'control'}));
        $('#control').attr('style', 'height: 50px;');
        if(this.options.period < 2){
            $('#control').hide();
        }

        // create dashboard and control wrapper
        var dashboard = new google.visualization.Dashboard(this.el);
        var controlSeries = {
            0: {
                type: 'area',
                lineWidth: 0
            },
            1: {
                targetAxisIndex: 1,
                type: 'area',
                lineWidth: 1
            }
        };
        if(noPrice){
            controlSeries = {
                0: {
                    type: 'area',
                    targetAxisIndex: 0,
                    lineWidth: 1
                }
            };
        }
        var defControlOptions = {
            controlType: 'ChartRangeFilter',
            containerId: 'control',
            state: {
                range: {
                    start: rangeStart,
                    end: new Date(strFirstDate)
                }
            },
            options: {
                filterColumnIndex: 0,
                ui: {
                    chartType: 'ComboChart',
                    minRangeSize: 86400000 * 7,
                    chartOptions: {
                        /*chartArea: {
                            height: '30%',
                        },*/
                        colors: ['#65A5DF'],
                        lineWidth: 0,
                        hAxis : {
                            title: '',
                            titleTextStyle: {
                                italic: false
                            },
                            slantedText: false,
                            maxAlternation: 1,
                            maxTextLines: 1,
                            format: 'MM/dd',
                            gridlines: {
                                color: "none"
                            },
                        },
                        series: controlSeries
                    }
                }
            }
        };
        if(this.options['theme'] == 'dark'){
            defControlOptions.options.ui.chartOptions.colors = ['#DEDEDE'];
            defControlOptions.options.ui.chartOptions.backgroundColor = {fill: 'transparent'};

            defControlOptions.options.ui.chartOptions.hAxis.textStyle = {color: '#DEDEDE'};
            defControlOptions.options.ui.chartOptions.hAxis.titleTextStyle.color = '#DEDEDE';
            defControlOptions.options.ui.chartOptions.hAxis.baselineColor = '#DEDEDE';
        }
        var controlOptions = $.extend(true, defControlOptions, this.options['controlOptions']);
        var control = new google.visualization.ControlWrapper(controlOptions);

        // create combo chart
        var series = {
            0: {
                type: 'candlesticks',
                targetAxisIndex: 0
            },
            1: {
                type: 'line',
                targetAxisIndex: 1
            },
            2: {
                type: 'bars',
                targetAxisIndex: 2,
            },
        };
        var vAxes = {
            0: {
                title: 'Price',
                format: '$ #,##0.00##'
                //format: 'currency'
            },
            1: {
                title: 'Token operations',
                format: 'decimal',
            },
            2: {
                textStyle: {
                    color: 'none'
                }
            }
        };
        if(noPrice){
            series = {
                0: {
                    type: noPrice ? 'area' : 'line',
                    targetAxisIndex: 0
                },
            };
            vAxes = {
                0: {
                    title: 'Token operations',
                    format: 'decimal',
                }
            };
        }
        var def = {
            chartType: 'ComboChart',
            containerId: 'chart',
            options: {
                //theme: 'maximized',
                title: '',
                legend: { position: 'none' },
                tooltip: {
                    //format: 'MMM d',
                    isHtml: true
                },
                colors: ['#65A5DF', 'black'],
                series: series,
                hAxis : {
                    title: '',
                    titleTextStyle: {
                        italic: false
                    },
                    textPosition: 'out',
                    slantedText: false,
                    maxAlternation: 1,
                    maxTextLines: 1,
                    format: 'MM/dd',
                    gridlines: {
                        count: 10,
                        color: "none"
                    },
                },
                vAxis: {
                    viewWindowMode: 'maximized',
                    title: '',
                    titleTextStyle: {
                        italic: false
                    },
                    gridlines: {
                        color: "none"
                    },
                    //format: '#,###',
                    /*minValue: 0,
                    maxValue: 3,
                    viewWindow: {
                        min: 0
                    },*/
                },
                vAxes: vAxes,
                pointSize: noPrice ? 2 : 0,
                lineWidth: 1,
                bar: { groupWidth: '70%' },
                candlestick: {
                    fallingColor: {
                        // red
                        strokeWidth: 1,
                        fill: '#951717',
                        stroke: '#8b0000'
                    },
                    risingColor: {
                        // green
                        strokeWidth: 1,
                        fill: '#177217',
                        stroke: '#006400'
                    }
                }
            }
        };
        if(this.options['theme'] == 'dark'){
            def.options.colors = noPrice ? ['#FCEC0F']: ['#999999', '#FCEC0F', '#DEDEDE'];
            def.options.titleTextStyle = {color: '#DEDEDE'};
            def.options.backgroundColor = {fill: 'transparent'};

            def.options.hAxis.textStyle = {color: '#DEDEDE'};
            def.options.hAxis.titleTextStyle.color = '#DEDEDE';
            def.options.hAxis.baselineColor = '#DEDEDE';

            def.options.vAxis.textStyle = {color: '#DEDEDE'};
            def.options.vAxis.titleTextStyle.color = '#DEDEDE';
            def.options.vAxis.baselineColor = 'none';
        }
        def.options = $.extend(true, def.options, this.options['options']);
        var chart = new google.visualization.ChartWrapper(def);

        // draw chart
        dashboard.bind(control, chart);
        dashboard.draw(data);
    };

    this.init = function(){
        this.el.addClass('ethplorer-widget');
        this.el.addClass('widget-tokenHistoryGrouped');
        this.el.addClass('theme-' + (this.options.theme ? this.options.theme : 'ethplorer'));
        this.el.html(this.templates.loader);
    };

    this.getRequestParams = function(additionalParams){
        var requestOptions = ['period', 'address', 'type', 'theme'];
        var params = {
            apiKey: 'freekey'
        };
        if('undefined' === typeof(this.pathReported)){
            params['domain'] = document.location.href;
            this.pathReported = true;
        }
        for(var key in this.options){
            if(requestOptions.indexOf(key) >= 0){
                params[key] = this.options[key];
            }
        }
        if('object' === typeof(additionalParams)){
            for(var key in additionalParams){
                if(requestOptions.indexOf(key) >= 0){
                    params[key] = additionalParams[key];
                }
            }
        }
        return params;
    };

    this.refreshWidget = function(obj){
        return function(data){
            if(data && !data.error && data.history){
                //console.log(data);
                obj.widgetData = data.history.countTxs;
                obj.widgetPriceData = data.history.prices;
                obj.el.find('.txs-loading').remove();
                obj.drawChart(data.history.countTxs, data.history.prices);
                ethplorerWidget.appendEthplorerLink(obj);
                if('function' === typeof(obj.options.onLoad)){
                    obj.options.onLoad();
                }
                setTimeout(ethplorerWidget.fixTilda, 300);
            }else{
                obj.el.find('.txs-loading').remove();
            }
        };
    }(this);

    $(window).resize(this, function(){
        var newWidth = $(window).width();
        var obj = arguments[0].data;
        if(newWidth !== obj.cachedWidth){
            obj.cachedWidth = newWidth;
        }else{
            return;
        }
        if(obj.resizeTimer) clearTimeout(obj.resizeTimer);
        obj.resizeTimer = setTimeout(function(){
            if(obj.widgetData){
                obj.el.empty();
                obj.drawChart(obj.widgetData, obj.widgetPriceData);
                ethplorerWidget.appendEthplorerLink(obj);
            }
        }, 500);
    });

    this.init();
    ethplorerWidget.chartControlWidgets.push(this);
};

/**
 * Address history with prices grouped Widget.
 *
 * @param {type} element
 * @param {type} options
 * @param {type} templates
 * @returns {undefined}
 */
ethplorerWidget.Type['addressPriceHistoryGrouped'] = function(element, options, templates){
    this.type = 'addressPriceHistoryGrouped';
    this.el = element;
    this.widgetData = null;
    this.widgetPriceData = null;
    this.resizeTimer = null;
    this.cachedWidth = $(window).width();

    this.options = {
        period: 365,
        type: 'area',
        theme: 'light',
        options: {},
        controlOptions: {}
    };

    if(options){
        for(var key in options){
            this.options[key] = options[key];
        }
    }
    if(this.options.period <= 0){
        this.options.period = 365;
    }else if(this.options.period < 7){
        this.options.period = 7;
    }

    this.api = ethplorerWidget.api + '/getAddressPriceHistoryGrouped';
    if(options && options.address){
        this.api += ('/' + options.address.toString().toLowerCase());
    }

    this.templates = {
        loader: '<div class="txs-loading">Loading...</div>',
    };

    this.load = function(){
        $.getJSON(this.api, this.getRequestParams(), this.refreshWidget);
    };

    this.getTooltip = function(noPrice, date, balance, volume, txs, dteUpdated){
        var tooltipDateFormatter = new google.visualization.DateFormat({ 
            pattern: "MMM dd, yyyy '+UTC'"
        });
        var numFormatter = new google.visualization.NumberFormat({ 
            pattern: "#,###"
        });
        var currencyFormatter = new google.visualization.NumberFormat({ 
            pattern: '#,##0'
        });
        var tooltip = '<div style="display: block !important; text-align: left; opacity: 1 !important; color: #000000 !important; padding: 5px;">';
        tooltip += '<span class="tooltipRow">' + tooltipDateFormatter.formatValue(date) + '</span><br/>' +
            (noPrice ? '' : '<span class="tooltipRow"><b>Volume:</b> ' + currencyFormatter.formatValue(volume) + ' USD</span><br/>') +
            (noPrice ? '' : '<span class="tooltipRow"><b>Balance:</b> ' + currencyFormatter.formatValue(balance) + ' USD</span><br/>') +
            '<span class="tooltipRow"><b>Transfers:</b> ' + numFormatter.formatValue(txs) + '</span>' +
            (dteUpdated ? ('<br/><span class="tooltipRow"><b>Updated:</b> ' + dteUpdated + '</span>') : '') +
            '</div>';
        return tooltip;
    }

    this.drawChart = function(widgetData){
        var aData = [];

        if('undefined' === typeof(widgetData['volume']) && 'undefined' === typeof(widgetData['txs'])){
            return;
            /*var firstMonth = aTxData[0]._id.month,
                firstDay = aTxData[0]._id.day;
            if(firstMonth < 10) firstMonth = '0' + firstMonth;
            if(firstDay < 10) firstDay = '0' + firstDay;
            var strFirstDate = aTxData[0]._id.year + '-' + firstMonth + '-' + firstDay + 'T00:00:00Z';*/
        }
        if('undefined' === typeof(widgetData['volume'])) widgetData['volume'] = [];
        if('undefined' === typeof(widgetData['balances'])) widgetData['balances'] = [];
        if('undefined' === typeof(widgetData['txs'])) widgetData['txs'] = [];

        var noPrice = true;

        // prepare prices
        var lastAverage = 0;
        var aPrices = {};
        if('undefined' !== typeof(widgetData['prices'])){
            for(var token in widgetData['prices']){
                aPrices[token] = {};
                if(widgetData['prices'][token].length > 0) noPrice = false;
                for(var i = widgetData['prices'][token].length - 1; i >= 0; i--){
                    var priceData = widgetData['prices'][token][i];
                    if(priceData['average'] > 0) lastAverage = priceData['average'];
                    aPrices[token][priceData['date']] = lastAverage;
                }
            }
        }
        console.log('noPrice ' + noPrice);

        if(noPrice){
            aData.push(['Day', 'Transfers', {role: 'style'}, {type: 'string', role: 'tooltip', 'p': {'html': true}}]);
        }else{
            aData.push(['Day', 'Balance', {role: 'style'}, {type: 'string', role: 'tooltip', 'p': {'html': true}}, 'Transfers', {role: 'style'}, {type: 'string', role: 'tooltip', 'p': {'html': true}}, 'Volume', {role: 'style'}, {type: 'string', role: 'tooltip', 'p': {'html': true}}]);
        }

        var rangeStart = null,
            rangeEnd,
            curDate = new Date(),
            fnMonth = curDate.getUTCMonth() + 1,
            fnDay = curDate.getUTCDate(),
            fnDate = new Date(curDate.getUTCFullYear() + '-' + (fnMonth < 10 ? '0' + fnMonth : fnMonth) + '-' + (fnDay < 10 ? '0' + fnDay : fnDay) + 'T00:00:00Z'),
            aBalances = {},
            strFirstDate = widgetData['firstDate'] + 'T00:00:00Z',
            dteUpdated;

        for(var d = new Date(strFirstDate); d <= fnDate; d.setDate(d.getDate() + 1)){
            var month = 1 + d.getMonth(),
                day = d.getDate(),
                volumeDate = d.getFullYear() + '-' + (month < 10 ? '0' + month : month) + '-' + (day < 10 ? '0' + day : day),
                strVolumeDate = volumeDate + 'T00:00:00Z';

            // get volumes
            var volume = 0;
            if(!noPrice && 'undefined' !== typeof(widgetData['volume'][volumeDate])){
                for(var token in widgetData['volume'][volumeDate]){
                    if(d.getTime() == fnDate.getTime() && ('undefined' !== typeof(widgetData['tokenPrices'][token]['rate']))){
                        aPrices[token][volumeDate] = widgetData['tokenPrices'][token]['rate'];
                    }
                    if('undefined' !== typeof(aPrices[token]) && 'undefined' !== typeof(aPrices[token][volumeDate])){
                        volume += parseFloat(widgetData['volume'][volumeDate][token]) * parseFloat(aPrices[token][volumeDate]);
                    }
                }

                if(!rangeStart && volume != 0){
                    rangeStart = strVolumeDate;
                }
            }

            // get balances
            var balance = 0;
            if(!noPrice){
                if('undefined' !== typeof(widgetData['balances'][volumeDate])){
                    for(var token in widgetData['balances'][volumeDate]){
                        aBalances[token] = parseFloat(widgetData['balances'][volumeDate][token]);
                    }
                }
                for(var token in aBalances){
                    if(d.getTime() == fnDate.getTime() && ('undefined' !== typeof(widgetData['tokenPrices'][token]['rate']))){
                        aPrices[token][volumeDate] = widgetData['tokenPrices'][token]['rate'];
                    }
                    if('undefined' !== typeof(aPrices[token]) && 'undefined' !== typeof(aPrices[token][volumeDate])){
                        balance += parseFloat(aBalances[token]) * parseFloat(aPrices[token][volumeDate]);
                    }
                }
            }

            // get transfers
            var transfers = 0;
            if('undefined' !== typeof(widgetData['txs'][volumeDate])){
                transfers = widgetData['txs'][volumeDate];
            }

            if(d.getTime() == fnDate.getTime() && ('undefined' !== typeof(widgetData['updated']))){
                dteUpdated = widgetData['updated'];
            }

            rangeEnd = strVolumeDate;
            var tooltip = this.getTooltip(noPrice, new Date(strVolumeDate), balance, volume, transfers, dteUpdated);
            if(noPrice){
                aData.push([new Date(strVolumeDate), transfers, 'opacity: 0.5', tooltip]);
            }else{
                aData.push([new Date(strVolumeDate), balance, 'opacity: 0.5', tooltip, transfers, 'opacity: 0.5', tooltip, volume, this.options['theme'] == 'dark' ? 'opacity: 0.15' : 'opacity: 0.5', tooltip]);
            }
        }

        var dteRangeStart = new Date(rangeStart),
            dteRangeEnd = new Date(rangeEnd);

        var timeDiff = Math.abs(dteRangeEnd.getTime() - dteRangeStart.getTime());
        var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
        if(diffDays < 7) dteRangeStart.setDate(dteRangeStart.getDate() - (7 - diffDays));
        else if(diffDays > 90) dteRangeStart.setDate(dteRangeStart.getDate() + (diffDays - 90));

        //console.log(dteRangeStart);
        //console.log(dteRangeEnd);
        //console.log(aData);
        var data = google.visualization.arrayToDataTable(aData);

        // create div's
        this.el.append($('<div>', {id: 'chart'}));
        this.el.append($('<div>', {id: 'control'}));
        $('#control').attr('style', 'height: 50px;');
        if(this.options.period < 2){
            $('#control').hide();
        }

        // create dashboard and control wrapper
        var dashboard = new google.visualization.Dashboard(this.el);
        var controlSeries = {
            0: {
                targetAxisIndex: 0,
                type: 'area',
                lineWidth: 1
            }
        };
        var defControlOptions = {
            controlType: 'ChartRangeFilter',
            containerId: 'control',
            state: {
                range: {
                    start: dteRangeStart,
                    end: dteRangeEnd
                }
            },
            options: {
                filterColumnIndex: 0,
                ui: {
                    chartType: 'ComboChart',
                    minRangeSize: (this.options.period <= 7) ? 86400000 * 2 : 86400000 * 7,
                    chartOptions: {
                        colors: ['#65A5DF'],
                        lineWidth: 0,
                        hAxis : {
                            title: '',
                            titleTextStyle: {
                                italic: false
                            },
                            slantedText: false,
                            maxAlternation: 1,
                            maxTextLines: 1,
                            format: 'MM/dd',
                            gridlines: {
                                color: "none"
                            },
                        },
                        series: controlSeries
                    }
                }
            }
        };
        if(this.options['theme'] == 'dark'){
            defControlOptions.options.ui.chartOptions.colors = ['#DEDEDE'];
            defControlOptions.options.ui.chartOptions.backgroundColor = {fill: 'transparent'};

            defControlOptions.options.ui.chartOptions.hAxis.textStyle = {color: '#DEDEDE'};
            defControlOptions.options.ui.chartOptions.hAxis.titleTextStyle.color = '#DEDEDE';
            defControlOptions.options.ui.chartOptions.hAxis.baselineColor = '#DEDEDE';
        }
        var controlOptions = $.extend(true, defControlOptions, this.options['controlOptions']);
        var control = new google.visualization.ControlWrapper(controlOptions);

        // create combo chart
        var series = {
            0: {
                type: 'area',
                targetAxisIndex: 0
            },
            1: {
                type: 'line',
                targetAxisIndex: 2
            },
            2: {
                type: 'bars',
                targetAxisIndex: 1,
            },
        };
        var vAxes = {
            0: {
                title: 'Price',
                format: '$ #,##0'
            },
            1: {
                title: 'Volume',
                format: 'decimal',
            },
            2: {
                textStyle: {
                    color: 'none'
                }
            }
        };
        if(noPrice){
            series = {
                0: {
                    type: 'line',
                    targetAxisIndex: 0
                },
            };
            vAxes = {
                0: {
                    title: 'Transfers',
                    format: "#,###",
                }
            };
        }
        var def = {
            chartType: 'ComboChart',
            containerId: 'chart',
            options: {
                //theme: 'maximized',
                title: '',
                legend: { position: 'none' },
                tooltip: {
                    //format: 'MMM d',
                    isHtml: true
                },
                colors: ['#65A5DF', 'black'],
                series: series,
                hAxis : {
                    title: '',
                    titleTextStyle: {
                        italic: false
                    },
                    textPosition: 'out',
                    slantedText: false,
                    maxAlternation: 1,
                    maxTextLines: 1,
                    format: 'MM/dd',
                    gridlines: {
                        count: 10,
                        color: "none"
                    },
                },
                vAxis: {
                    viewWindowMode: 'maximized',
                    minValue: 0,
                    viewWindow: {
                        min: 0
                    },
                    title: '',
                    titleTextStyle: {
                        italic: false
                    },
                    gridlines: {
                        color: "none"
                    },
                },
                vAxes: vAxes,
                pointSize: 3,
                lineWidth: 2,
                bar: { groupWidth: '70%' },
                candlestick: {
                    fallingColor: {
                        // red
                        strokeWidth: 1,
                        fill: '#951717',
                        stroke: '#8b0000'
                    },
                    risingColor: {
                        // green
                        strokeWidth: 1,
                        fill: '#177217',
                        stroke: '#006400'
                    }
                }
            }
        };
        if(this.options['theme'] == 'dark'){
            def.options.colors = noPrice ? ['#FCEC0F']: ['#47C2FF', '#FCEC0F', '#DEDEDE'];
            def.options.titleTextStyle = {color: '#DEDEDE'};
            def.options.backgroundColor = {fill: 'transparent'};

            def.options.hAxis.textStyle = {color: '#DEDEDE'};
            def.options.hAxis.titleTextStyle.color = '#DEDEDE';
            def.options.hAxis.baselineColor = '#DEDEDE';

            def.options.vAxis.textStyle = {color: '#DEDEDE'};
            def.options.vAxis.titleTextStyle.color = '#DEDEDE';
            def.options.vAxis.baselineColor = 'none';
        }
        def.options = $.extend(true, def.options, this.options['options']);
        var chart = new google.visualization.ChartWrapper(def);

        // draw chart
        dashboard.bind(control, chart);
        dashboard.draw(data);
    };

    this.init = function(){
        this.el.addClass('ethplorer-widget');
        this.el.addClass('widget-tokenHistoryGrouped');
        this.el.addClass('theme-' + (this.options.theme ? this.options.theme : 'ethplorer'));
        this.el.html(this.templates.loader);
    };

    this.getRequestParams = function(additionalParams){
        var requestOptions = ['period', 'address', 'type', 'theme'];
        var params = {
            apiKey: 'ethplorer.widget'
        };
        if('undefined' === typeof(this.pathReported)){
            params['domain'] = document.location.href;
            this.pathReported = true;
        }
        for(var key in this.options){
            if(requestOptions.indexOf(key) >= 0){
                params[key] = this.options[key];
            }
        }
        if('object' === typeof(additionalParams)){
            for(var key in additionalParams){
                if(requestOptions.indexOf(key) >= 0){
                    params[key] = additionalParams[key];
                }
            }
        }
        return params;
    };

    this.refreshWidget = function(obj){
        return function(data){
            if(data && !data.error && data.history){
                //console.log(data);
                obj.widgetData = data.history;
                obj.el.find('.txs-loading').remove();
                obj.drawChart(data.history);
                ethplorerWidget.appendEthplorerLink(obj);
                if('function' === typeof(obj.options.onLoad)){
                    obj.options.onLoad();
                }
                setTimeout(ethplorerWidget.fixTilda, 300);
            }else{
                obj.el.find('.txs-loading').remove();
            }
        };
    }(this);

    $(window).resize(this, function(){
        var newWidth = $(window).width();
        var obj = arguments[0].data;
        if(newWidth !== obj.cachedWidth){
            obj.cachedWidth = newWidth;
        }else{
            return;
        }
        if(obj.resizeTimer) clearTimeout(obj.resizeTimer);
        obj.resizeTimer = setTimeout(function(){
            if(obj.widgetData){
                obj.el.empty();
                obj.drawChart(obj.widgetData);
                ethplorerWidget.appendEthplorerLink(obj);
            }
        }, 500);
    });

    this.init();
    ethplorerWidget.chartControlWidgets.push(this);
};

/**
 * Document on ready widgets initialization.
 * Initializes all widgets added using eWgs array.
 */
(function(){
    function ethpWiInit(){
        var eWgs = window.eWgs || [];
        if(eWgs && eWgs.length)
            for(var i=0; i<eWgs.length; i++)
                if('function' === typeof(eWgs[i]))
                    eWgs[i]();

        if(ethplorerWidget.addGoogleLoader){
            ethplorerWidget.addGoogleLoader = false;
            ethplorerWidget.loadScript("https://www.gstatic.com/charts/loader.js", ethplorerWidget.loadGoogleCharts);
        }
        if(ethplorerWidget.addGoogleAPI){
            ethplorerWidget.addGoogleAPI = false;
            ethplorerWidget.loadScript("https://www.google.com/jsapi", ethplorerWidget.loadGoogleControlCharts);
        }
    }
    // add widget css
    ethplorerWidget.addStyles();
    // autoload
    if(document.readyState === "interactive" || document.readyState === "complete"){
        ethpWiInit();
    }else{
        if(document.addEventListener){
            document.addEventListener("DOMContentLoaded", ethpWiInit);
        }else if(document.attachEvent){
            document.attachEvent("onreadystatechange", function(){
                if(document.readyState === "interactive" || document.readyState === "complete"){
                    ethpWiInit()
                }
            });
        }
    }
}());