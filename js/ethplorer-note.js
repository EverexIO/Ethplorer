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

EthplorerNote = {
    service: "/service/service.php",
    next: 0,
    notes: [],
    init: function(container){
        EthplorerNote.container = container;
        if((document.location.host !== 'ethplorer.io') && (document.location.host.indexOf('ethplorer') < 0)){
            EthplorerNote.service = '//ethplorer.io' + EthplorerNote.service;
        }
        var inner = $('<DIV>');
        inner.addClass('ethplorer-note');
        EthplorerNote.container.append(inner);
        EthplorerNote.inner = inner;        
        EthplorerNote.loadNotes();
    },
    loadNotes: function(){
        $.getJSON(EthplorerNote.service, {notes: 1}, function(data, status, xhr){
            if(('undefined' !== typeof(data)) && data.length){
                EthplorerNote.notes = data;
                EthplorerNote.showNext();
            }
        });
    },
    showNext: function(){
        if(!EthplorerNote.notes.length){
            return;
        }
        if(EthplorerNote.next >= EthplorerNote.notes.length){
            EthplorerNote.next = 0;
        }

        EthplorerNote.container.show();
        
        var note = EthplorerNote.notes[EthplorerNote.next];
        EthplorerNote.inner.fadeOut(500, function(_data){
            return function(){
                var link = "/go.php?link=" + _data.link;
                EthplorerNote.inner.html(_data.html.replace('%link%', link));
                if(_data.image){
                    var img = $('<IMG>');
                    img.attr('src', _data.image);
                    EthplorerNote.inner.prepend(img);
                }
                EthplorerNote.inner.fadeIn(800);
            }
        }(note));
        
        EthplorerNote.next++;
        setTimeout(EthplorerNote.showNext, 15000);
    }
};