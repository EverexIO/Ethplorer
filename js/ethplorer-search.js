/*!
 * Copyright 2017 Everex https://everex.io
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

EthplorerSearch = {
    data: {},
    service: "/service/service.php",
    searchCache: {},
    init: function(form, el, cb){
        EthplorerSearch.form = form;
        EthplorerSearch.el = el;
        EthplorerSearch.cb = cb;
        EthplorerSearch.form.submit(function(e){
            if('function' === typeof(EthplorerSearch.cb)){
                EthplorerSearch.cb(EthplorerSearch.el.val(), true);
            }
            e.preventDefault();
        });
        EthplorerSearch.el.autocomplete({
            source: function(request, response){
                var search = request.term;
                if(search in EthplorerSearch.searchCache){
                    response(EthplorerSearch.searchCache[search]);
                    return;
                }
                $.getJSON(EthplorerSearch.service, {search: search}, function(data, status, xhr){
                    if(!data.total){
                        data.results = [false];
                    }else{
                        if(data.total > data.results.length){
                            data.results.push({more: (data.total - data.results.length)});
                        }
                    }
                    EthplorerSearch.searchCache[search] = data.results;
                    response(data.results);
                });
            },
            minLength: 1,
            select: function(event, ui){
                if('undefined' !== typeof(ui.item[2])){
                    document.location.href = '/address/' + ui.item[2];
                }
            }
        })
        .autocomplete( "instance" )._renderItem = function(ul, res){
            if(!res) return;
            if('undefined' !== typeof(res[0])){
                var address = res[2];
                var text = (res[0] ? $('<span>').text(res[0]).html() : "")  + (res[1] ? (' (' + res[1] + ')') : '');
                text = text.replace(new RegExp(EthplorerSearch.el.val(), 'ig'), "<b>$&</b>");
                address = address.replace(new RegExp(EthplorerSearch.el.val(), 'ig'), "<b>$&</b>");
                text += (' <span style="color:#aaa;">' + address + '</span>')
                return $('<li class="ui-menu-item">').append(text).appendTo(ul);
            }
            if('undefined' !== typeof(res.more)){
                return $('<li class="have-more ui-state-disabled">').append(res.more + ' results more...').appendTo(ul);
            }
            return $('<li class="not-found ui-state-disabled">').append('No results').appendTo(ul);
        };
    }
};
