// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

function BrRequest() {
  
  this.continueRoute = true;
  this.get = function(name, defaultValue) {
    var vars = document.location.search.replace('?', '').split('&');
    for (var i = 0; i < vars.length; i++) {
      var pair = vars[i].split("=");
      if (pair[0] == name) {
        return unescape(pair[1]);
      }
    }
    return defaultValue;
  };
  this.anchor = function(defaultValue) {
    var value = document.location.hash.replace('#', '');
    if (value.length == 0) {
      value = defaultValue;
    }
    return value;
  };
  this.route = function(path, func) {
    if (this.continueRoute) {
      var l = document.location.toString();
      l = l.replace(/[?].*/, '');
      if (l.search(path) != -1) {
        this.continueRoute = false;
        func.call();
      }
    }
    return this;
  }

}

function BrEditable(ctrl, saveCallback) {

  var _this = this;
  _this.ctrl = $(ctrl);
  _this.saveCallback = saveCallback;
  _this.editor = null;
  _this.tooltip = null;
  _this.click = function(element, e) {
    if (!_this.activated()) {
      var content = _this.ctrl.text();
      _this.ctrl.data('original-content', content);
      var width = _this.ctrl.innerWidth();
      _this.ctrl.text('');
      _this.editor = $('<input />');
      _this.editor.addClass('justified');
      _this.editor.val(content);
      _this.ctrl.append(_this.editor);
      _this.ctrl.css('width', width - 10);
      _this.editor.focus();
      _this.editor.attr('data-original-title', 'Press [Enter] to save changes, [Esc] to cancel changes.');
      _this.editor.tooltip({placement: 'bottom', trigger: 'focus'});
      _this.editor.tooltip('show');
      _this.tooltip = _this.editor.data('tooltip');
      $(_this.editor).keyup(function(e) {
        if (e.keyCode == 13) {
          var content = $(this).val();
          if (typeof _this.saveCallback == 'function') {
            _this.editor.tooltip('hide');
            _this.saveCallback.call(_this.ctrl, content);
          } else {
            _this.apply(content);
          }
        }
        if (e.keyCode == 27) {
          _this.cancel();
        }
      });
    }
  }
  _this.activated = function() {
    return _this.editor != null;
  }
  _this.apply = function(content) {
    _this.tooltip.hide();
    _this.editor.remove();
    _this.editor = null;
    _this.ctrl.text(content);
  }
  _this.cancel = function() {
    _this.tooltip.hide();
    _this.editor.remove();
    _this.editor = null;
    _this.ctrl.text(_this.ctrl.data('original-content'));
  }

}

function Br() {

  this.request = new BrRequest();

  var baseUrl = '';
  $('script').each(function(i, e) {
    var s = $(e).attr('src');
    if (s) {
      var idx = s.indexOf('breeze/js/breeze.js');
      if (idx > 0) {
        baseUrl = s.substring(0, idx);
        return true;
      }
    }
  });  
  this.baseUrl = baseUrl;
  this.log = function(msg) {
    if (typeof(console) != 'undefined') {
      console.log(msg);
    }
  };
  this.isTouchScreen = function() {
    var ua = navigator.userAgent;
    return /iPad/i.test(ua) || /iPhone/i.test(ua) || /Android/i.test(ua);
  };
  this.isiOS = function() {
    var ua = navigator.userAgent;
    return /iPad/i.test(ua) || /iPhone/i.test(ua);
  };
  this.redirect = function(url) {
    if ((url.search(/^\//) == -1) && (url.search(/^http[s]?:\/\//) == -1)) {
      url = this.baseUrl + url;
    }
    document.location = url;
  };
  this.preloadImages = function(images) {
    try {
      var div = document.createElement("div");
      var s = div.style;
      s.position = "absolute";
      s.top = s.left = 0;
      s.visibility = "hidden";
      document.body.appendChild(div);
      div.innerHTML = "<img src=\"" + images.join("\" /><img src=\"") + "\" />";
    } catch(e) {
        // Error. Do nothing.
    }    
  };
  this.randomInt = function(min, max) {
    if (max == undefined) {
      max = min;
      min = 0;
    }
    return Math.floor(Math.random() * (max - min + 1)) + min;
  };
  this.forHtml = function(text) {
    if (text) {
      text = text.split('<').join('&lt;').split('>').join('&gt;');
    }
    return text;
  };
  this.extend = function(Child, Parent) {
    var F = function() { };
    F.prototype = Parent.prototype;
    Child.prototype = new F();
    Child.prototype.constructor = Child;
    Child.superclass = Parent.prototype;
  };
  this.showError = function(s) {
    alert(s);
  };
  this.growlError = function(s) {
    if (!br.isEmpty(s)) {
      if (typeof window.humane != 'undefined') {
        humane.log(s, { addnCls: 'humane-jackedup-error humane-original-error'});
      } else {
        alert(s);
      }
    }
  };
  this.showMessage = function(s) {
    if (!br.isEmpty(s)) {
      alert(s);
    }
  };
  this.growlMessage = function(s) {
    if (!br.isEmpty(s)) {
      if (typeof window.humane != 'undefined') {
        humane.log(s);
      } else {
        alert(s);
      }
    }
  };
  this.confirm = function(title, message, buttons, callback) {
    var s = '<div class="modal">'+
            '<div class="modal-header"><a class="close" data-dismiss="modal">×</a><h3>' + title + '</h3></div>' + 
            '<div class="modal-body">' + message + '</div>' + 
            '<div class="modal-footer">';
    if (typeof buttons == 'function') {
      callback = buttons;
      s = s + '<a href="javascript:;" class="btn btn-primary action-confirm-close" rel="confirm">Yes</a>';
    } else {
      for(i in buttons) {
        s = s + '<a href="javascript:;" class="btn action-confirm-close" rel="' + i + '">' + buttons[i] + '</a>';
      }
    }
    s = s + '<a href="javascript:;" class="btn" data-dismiss="modal">&nbsp;Cancel&nbsp;</a>';
    s = s + '</div></div>';
    var dialog = $(s);
    $(dialog)
      .on('show', function(e) {
        $(this).find('.action-confirm-close').click(function() {
          $(dialog).modal('hide');
          callback.call(this, $(this).attr('rel'));
        });
      })
      .on('hide', function(e) {
        dialog.remove();
      });
    $(dialog).modal();
  }
  this.prompt = function(title, fields, callback, options) {
    options = options || {};
    var s = '<div class="modal">'+
            '<div class="modal-header"><a class="close" data-dismiss="modal">×</a><h3>' + title + '</h3></div>' + 
            '<div class="modal-body">';
    var inputs = {}
    if (br.isObject(fields)) {
      inputs = fields;
    } else {
      inputs[fields] = '';
    }
    for(i in inputs) {
      s = s + '<label>' + i + '</label>' +
              '<input type="text" class="span4" name="' + i + '" value="' + inputs[i] + '" data-click-on-enter=".action-confirm-close" />';        
    }
    s = s + '</div>' + 
            '<div class="modal-footer">';
    s = s + '<a href="javascript:;" class="btn btn-primary action-confirm-close" rel="confirm" >Ok</a>';
    s = s + '<a href="javascript:;" class="btn" data-dismiss="modal">&nbsp;Cancel&nbsp;</a>';
    s = s + '</div></div>';
    var dialog = $(s);
    $(dialog)
      .on('shown', function(e) {
        $(this).find('input[type=text]')[0].focus();
      })
      .on('show', function(e) {
        $(this).find('.action-confirm-close').click(function() {
          $(dialog).modal('hide');
          var results = {};
          if (br.isObject(fields)) {
            $(this).closest('div.modal').find('input[type=text]').each(function() {
              results[$(this).attr('name')] = $(this).val();
            });
          } else {
            results = $(this).closest('div.modal').find('input[type=text]').val();
          }
          callback.call(this, results);
        });
      })
      .on('hide', function(e) {
        dialog.remove();
      });
    $(dialog).modal();
  }
  this.toInt = function(value) {
    if (typeof value == 'string') {
      if (value.length > 0) {
        return parseInt(value);
      }
    }
    //return null;
  };
  this.toReal = function(value) {
    if (typeof value == 'string') {
      if (value.length > 0) {
        return parseFloat(value);
      }
    }
    //return null;
  };
  this.openPopup = function(url, w, h) {

    if (w == null) { 
      if (screen.width)
        if (screen.width >= 1280)
          w = 1000;
        else
        if (screen.width >= 1024)
          w = 800;
        else
          w = 600;
    }
    if (h == null) { 
      if (screen.height)
        if (screen.height >= 900)
          h = 700;
        else
        if (screen.height >= 800)
          h = 600;
        else
          h = 500;
    }
    var left = (screen.width) ? (screen.width-w)/2 : 0;
    var settings = 'height='+h+',width='+w+',top=20,left='+left+',menubar=0,scrollbars=1,resizable=1'
    var win = window.open(url, '_blank', settings)
    if (win) {
      win.focus();
    }

  };
  this.fetch = function(template, data, tags) {
    data = data || {};
    if (template) {
      if (typeof window.Mustache == 'undefined') {
        if (typeof window.Handlebars == 'undefined') {
          this.showError('Template engine not found. Please link breeze/3rdparty/mustache.js or breeze/3rdparty/handlebars.js.');
        } else {
          var t = Handlebars.compile(template);
          return t(data);
        }
      } else {
        return Mustache.render(template, data);
      }
    } else {
      return '';
    }
  };
  var modifiedTimeout;
  this.modifiedDeferred = function(selector, callback) {
    $(selector).live('change', function() {
      var _this = $(this);
      window.clearTimeout(modifiedTimeout);
      modifiedTimeout = window.setTimeout(function() { 
        callback.call(_this);
      }, 500);
    });
    $(selector).live('keyup', function(e) {
      if ((e.keyCode == 8) || (e.keyCode == 32) || ((e.keyCode >= 48) && (e.keyCode <= 90))) {
        var _this = $(this);
        window.clearTimeout(modifiedTimeout);
        modifiedTimeout = window.setTimeout(function() { 
          callback.call(_this);
        }, 500);
      }
    });
  }
  this.modified = function(selector, callback) {
    $(selector).live('change', function() {
      callback.call(this);
    });
    $(selector).live('keyup', function(e) {
      if ((e.keyCode == 8) || (e.keyCode == 32) || ((e.keyCode >= 48) && (e.keyCode <= 90))) {
        callback.call(this);
      }
    });
  }
  this.editable = function(selector, callback, value) {
    if (typeof callback == 'string') {
      var data = $(selector).data('editable');
      if (data) {
        data[callback](value);
      }
    } else {
      $(selector).live('click', function(e) {
        var $this = $(this)
          , data = $this.data('editable');
        if (!data) $this.data('editable', (data = new BrEditable(this, callback)));
        data.click(e);
      });
    }
  }

  var closeConfirmationMessage = 'Some changes have been made. Are you sure you want to close current window?';
  function breezeConfirmClose() {
    return closeConfirmationMessage;
  }
  this.confirmClose = function(message) {
    if (message) {
      closeConfirmationMessage = message;
    }
    window.onbeforeunload = breezeConfirmClose;
  }
  this.resetCloseConfirmation = function(message) {
    window.onbeforeunload = null;
  }
  var progressCounter = 0;
  this.showProgress = function() {
    progressCounter++;
    $('.ajax-in-progress').css('visibility', 'visible');
  }
  this.hideProgress = function() {
    progressCounter--;
    if (progressCounter == 0) {
      $('.ajax-in-progress').css('visibility', 'hidden');
    }
  }

}

var br = new Br();

function BrDataSource(name, options) {
 
  options = options || {};
  
  if (name.indexOf('/') != -1) {
    options.restServiceUrl = name;
    name = '-';
  } else
  if (typeof name != 'string') {
    name = '-';
    options = name;
  }

  var $ = jQuery;
  var datasource = this;
  var ajaxRequest = null;

  this.cb = {};

  this.before = function(event, callback) {
    this.cb['before:' + event] = this.cb['before:' + event] || new Array();
    this.cb['before:' + event][this.cb['before:' + event].length] = callback;      
  }

  this.on = function(event, callback) {
    this.cb[event] = this.cb[event] || new Array();
    this.cb[event][this.cb[event].length] = callback;      
  }

  this.after = function(event, callback) {
    this.cb['after:' + event] = this.cb['after:' + event] || new Array();
    this.cb['after:' + event][this.cb['after:' + event].length] = callback;      
  }

  function callEvent(event, context1, context2, context3) {

    datasource.cb[event] = datasource.cb[event] || new Array();

    for (i in datasource.cb[event]) {

      switch(event) {
        case 'request':
        case 'removeAfterUpdate':
          return datasource.cb[event][i].call(datasource, context1, context2, context3);
          break;
        default:
          datasource.cb[event][i].call(datasource, context1, context2, context3);
          break;
      }

    }

  }

  this.refreshTimeout;
  //this.data = {};
  this.name = name;
  this.options = options;
  this.options.refreshDelay = this.options.refreshDelay || 500;
  if (this.options.restServiceUrl.charAt(this.options.restServiceUrl.length-1) != '/') {
    this.options.restServiceUrl = this.options.restServiceUrl + '/';
  }
  
  // this.options.skip = this.options.skip || 0;
  // this.options.limit = this.options.limit || 0;

  if (this.options.offlineMode) {
    this.db = TAFFY();
    this.db.store('taffy-db-' + name);
  }
  //this.dbSynced = false;

  this.insert = function(item, callback) {

    request = item;

    callEvent('before:insert', request);

    if (this.options.crossdomain) {
      request.crossdomain = 'put';
    }

    function returnInsert(data) {

      var result;

      if (datasource.options.crossdomain) {
        if (typeof data == 'string') {
          result = false;
          callEvent('error', 'insert', data.length > 0 ? data : 'Empty response. Was expecting new created records with ROWID.');
        } else {
          result = true;
          callEvent('insert', data);
        }
      } else {
        if (data) {
          result = true;
          callEvent('insert', data);
        } else {
          result = false;
          callEvent('error', 'insert', 'Empty response. Was expecting new created records with ROWID.');
        }  
      }
      callEvent('after:insert', result, data, request);
      if (result) {
        callEvent('change', 'insert', data);
      }
      if (typeof callback == 'function') { callback.call(datasource, result, data, request); }

    }

    if (datasource.options.offlineMode) {
      datasource.db.insert(request);
      request.rowid = request.___id;
      request.syncState = 'n';
      returnInsert(request);
    } else {
      $.ajax({ type: this.options.crossdomain ? 'GET' : 'PUT'
             , data: request
             , dataType: this.options.crossdomain ? 'jsonp' : 'json'
             , url: this.options.restServiceUrl + (this.options.authToken ? '?token=' + this.options.authToken : '')
             , success: function(response) {
                 returnInsert(response);
               }
             , error: function(jqXHR, textStatus, errorThrown) {
                 callEvent('error', 'insert', jqXHR.responseText);
                 callEvent('after:insert', false, jqXHR.responseText, request);
                 if (typeof callback == 'function') { callback.call(datasource, false, jqXHR.responseText, request); }
               }
             });    
    }

  }

  this.update = function(rowid, item, callback) {

    request = item;

    callEvent('before:update', rowid, request);

    function returnUpdate(data) {
      var operation = 'update';
      if (data) {
        var res = callEvent('removeAfterUpdate', item, data);
        if ((res != null) && res) {
          operation = 'remove';
          callEvent('remove', rowid);
        } else {
          callEvent('update', data, rowid);              
        }
      }  
      callEvent('after:' + operation, true, data, request);
      callEvent('change', operation, data);
      if (typeof callback == 'function') { callback.call(datasource, true, data, request); }
    }

    if (datasource.options.offlineMode) {
      datasource.db({rowid: rowid}).update(request);
      returnUpdate(request);
    } else {
      $.ajax({ type: 'POST'
             , data: request
             , dataType: 'json'
             , url: this.options.restServiceUrl + rowid + (this.options.authToken ? '?token=' + this.options.authToken : '')
             , success: function(response) {
                 returnUpdate(response);
               }
             , error: function(jqXHR, textStatus, errorThrown) {
                 callEvent('error', 'update', jqXHR.responseText);
                 callEvent('after:update', false, jqXHR.responseText, request);
                 if (typeof callback == 'function') { callback.call(datasource, false, jqXHR.responseText, request); }
               }
             });
    }

  }

  this.remove = function(rowid, callback) {

    request = {};

    callEvent('before:remove', null, rowid);

    function returnRemove(data) {
      callEvent('remove', rowid);
      callEvent('after:remove', true, data, request);
      callEvent('change', 'remove', data);
      if (typeof callback == 'function') { callback.call(datasource, true, data, request); }
    }

    if (datasource.options.offlineMode) {
      var data = datasource.db({rowid: rowid}).get();
      datasource.db({rowid: rowid}).remove();
      returnRemove(data);
    } else {
      $.ajax({ type: 'DELETE'
             , data: request
             , dataType: 'json'
             , url: this.options.restServiceUrl + rowid + (this.options.authToken ? '?token=' + this.options.authToken : '')
             , success: function(response) {
                 returnRemove(response);
               }
             , error: function(jqXHR, textStatus, errorThrown) {
                 callEvent('error', 'remove', jqXHR.responseText);
                 callEvent('after:remove', false, jqXHR.responseText, request);
                 if (typeof callback == 'function') { callback.call(datasource, false, jqXHR.responseText, request); }
               }
             });
    }

  }

  this.selectCount = function(filter, callback, options) {

    var newFilter = {};
    for(i in filter) {
      newFilter[i] = filter[i];
    }
    newFilter.__result = 'count';

    options = options || {};
    options.result = 'count';
    
    this.select(newFilter, callback, options);

  }  

  this.selectOne = function(rowid, callback, options) {

    return this.select({ rowid: rowid }, callback, options);

  }

  this.select = function(filter, callback, options) {

    var disableEvents = options && options.disableEvents;
    var disableGridEvents = options && (options.result == 'count');

    var request = { };
    var requestRowid;
    
    if (typeof filter == 'function') {
      options = callback;
      callback = filter;
    } else
    if (filter) {
      for(i in filter) {
        if (i != 'rowid') {
          request[i] = filter[i];
        } else {
          requestRowid = filter[i];
        }
      }
    }

    var url = this.options.restServiceUrl;
    if (requestRowid) {
      url = url + requestRowid;
    }

    if (!disableEvents) {
      callEvent('before:select', request, options);
    }

    if (this.options.limit) {
      request.__limit = this.options.limit;
    }

    if (options && options.skip) {
      request.__skip = options.skip;
    }

    if (options && options.limit) {
      request.__limit = options.limit;
    }

    if (options && options.fields) {
      request.__fields = options.fields;
    }

    function handleSuccess(data) {
      if (!disableEvents && !disableGridEvents) {
        callEvent('select', data);
        callEvent('after:select', true, data, request);
      }
      if (typeof callback == 'function') { callback.call(datasource, true, data, request); }
    }

    function handleError(error, response) {
      if (!disableEvents) {
        callEvent('error', 'select', error);
        callEvent('after:select', false, error, request);
      }
      if (typeof callback == 'function') { callback.call(datasource, false, error, request); }
    }

    if (datasource.options.offlineMode) {
      handleSuccess(datasource.db(request).get());
    } else {
      this.ajaxRequest = $.ajax({ type: 'GET'
                                , data: request
                                , dataType: 'json'
                                , url: url + (this.options.authToken ? '?token=' + this.options.authToken : '')
                                , success: function(response) {
                                    datasource.ajaxRequest = null;
                                    if (response) {
                                      handleSuccess(response);
                                    } else {
                                      handleError('', response);
                                    }
                                  }
                                , error: function(jqXHR, textStatus, errorThrown) {
                                    datasource.ajaxRequest = null;
                                    var error = (jqXHR.statusText == 'abort') ? '' : jqXHR.responseText;
                                    handleError(error, jqXHR);
                                  }
                                });
    }

  }
  this.requestInProgress = function() {
    return (this.ajaxRequest != null);
  }
  this.abortRequest = function() {
    if (this.ajaxRequest != null) {
      this.ajaxRequest.abort();
    }
  }
  this.invoke = function(method, params, callback) {

    var datasource = this;
    
    request = params;

    callEvent('before:' + method, request);

    if (this.options.crossdomain) {
      request.crossdomain = 'post';
    }

    $.ajax({ type: this.options.crossdomain ? 'GET' : 'POST'
           , data: request
           , dataType: this.options.crossdomain ? 'jsonp' : 'json'
           , url: this.options.restServiceUrl + method + (this.options.authToken ? '?token=' + this.options.authToken : '')
           , success: function(response) {
               if (datasource.options.crossdomain && (typeof response == 'string')) {
                 callEvent('error', method, response);
                 callEvent('after:' + method, false, response, request);
                 if (typeof callback == 'function') { callback.call(datasource, false, response, request); }
               } else {
                 callEvent(method, response, params);
                 callEvent('after:' + method, true, response, request);
                 if (typeof callback == 'function') { callback.call(datasource, true, response, request); }
               }
             }
           , error: function(jqXHR, textStatus, errorThrown) {
               callEvent('error', method, jqXHR.responseText);
               callEvent('after:' + method, false, jqXHR.responseText, request);
               if (typeof callback == 'function') { callback.call(datasource, false, jqXHR.responseText, request); }
             }
           });

  }

  this.fillCombo = function(selector, data, options) {
    options = options || { };
    valueField = options.valueField || 'rowid';
    nameField = options.nameField || 'name';
    hideEmptyValue = options.hideEmptyValue || false;
    emptyValue = options.emptyValue || '-- any --';
    selectedValue = options.selectedValue || null;
    $(selector).each(function() {
      var val = $(this).val();
      if (br.isEmpty(val)) {
        val = $(this).attr('data-value');
        $(this).removeAttr('data-value');
      }
      $(this).html('');
      var s = '';
      if (!hideEmptyValue) {
        s = s + '<option value="">' + emptyValue + '</option>';
      }
      for(i in data) {
        s = s + '<option value="' + data[i][valueField] + '">' + data[i][nameField] + '</option>';
      }
      $(this).html(s);
      if (!br.isEmpty(selectedValue)) {
        $(this).val(selectedValue);
      } else {
        $(this).val(val);
      }
    })
  }


  this.deferredSelect = function(filter, callback, msec) {
    var savedFilter = {}
    for(i in filter) {
      savedFilter[i] = filter[i];
    }
    msec = msec || this.options.refreshDelay;
    window.clearTimeout(this.refreshTimeout);
    this.refreshTimeout = window.setTimeout(function() { 
      datasource.select(savedFilter, callback); 
    }, msec);
  }

}

function BrDataGrid(selector, options) {

  var $ = jQuery;
  var datagrid = this;

  this.cb = {};

  this.on = function(event, callback) {
    this.cb[event] = this.cb[event] || new Array();
    this.cb[event][this.cb[event].length] = callback;      
  }

  function callEvent(event, data) {

    datagrid.cb[event] = datagrid.cb[event] || new Array();

    for (i in datagrid.cb[event]) {

      switch(event) {
        case 'insert':
        case 'update':
        case 'remove':
        case 'select':
        case 'change':
          datagrid.cb[event][i].call($(datagrid.selector), data);
          break;
        case 'renderRow':
        case 'renderHeader':
          return datagrid.cb[event][i].call($(datagrid.selector), data);
          break;
      }

    }

    switch(event) {
      case 'insert':
      case 'update':
      case 'remove':
      case 'select':
        callEvent('change', data);
        break;
    }

  }

  this.renderHeader = function(data) {
    var data = callEvent('renderHeader', data) || data;
    var template = $(datagrid.options.templates.header).html(); 
    var result = $(br.fetch(template, data));
    return result;
  }

  this.renderFooter = function(data) {
    var data = callEvent('renderFooter', data) || data;
    var template = $(datagrid.options.templates.footer).html(); 
    var result = $(br.fetch(template, data));
    return result;
  }

  this.renderRow = function(data) {
    var data = callEvent('renderRow', data) || data;
    var template = $(datagrid.options.templates.row).html();
    var result = $(br.fetch(template, data));
    result.data('data-row', data);
    return result;
  }

  this.prepend = function(row) {
    $(datagrid.selector).prepend(row);
  }

  function isGridEmpty() {
    return ($(datagrid.selector).find('[data-rowid]').length == 0);
  }

  function checkForEmptyGrid() {
    if (isGridEmpty()) {
      $(datagrid.selector).html($(datagrid.options.templates.noData).html());
      callEvent('nodata');
    } 
  }

  this.selector = selector;
  this.options = options;

  if (this.options.dataSource) {    
      
    this.options.dataSource.before('select', function() {
      $(datagrid.selector).html('');
      $(datagrid.selector).addClass('progress-big');
    });

    this.options.dataSource.after('select', function() {
      $(datagrid.selector).removeClass('progress-big');
    });

    this.options.dataSource.on('select', function(data) {
      $(datagrid.selector).removeClass('progress-big');
      datagrid.render(data); 
    });

    this.options.dataSource.after('insert', function(success, response) {
      if (success) {
        if (isGridEmpty()) {
          $(datagrid.selector).html(''); // to remove No-Data box
        }
        datagrid.prepend(datagrid.renderRow(response));
      }
    });

    this.options.dataSource.on('update', function(data) { 
      var row = $(datagrid.selector).find('[data-rowid=' + data.rowid + ']');
      if (row.length == 1) {
        var ctrl = datagrid.renderRow(data);
        var s = ctrl.html();
        ctrl.remove();
        if (s.length > 0) {
          $(row[0]).html(s).hide().fadeIn();
          callEvent('update');
        } else {
          datagrid.options.dataSource.select();              
        }
      } else {
        datagrid.options.dataSource.select();
      }
    });

    this.options.dataSource.on('remove', function(rowid) {
      var row = $(datagrid.selector).find('[data-rowid=' + rowid + ']');
      if (row.length > 0) {
        if (br.isTouchScreen()) {
          row.remove();
          checkForEmptyGrid();
          callEvent('remove');
        } else {
          row.fadeOut(function() {
            $(this).remove(); 
            checkForEmptyGrid(); 
            callEvent('remove');
          });
        }
      } else {
        datagrid.options.dataSource.select();
      }
    });

    if (this.options.deleteSelector) {
      $(datagrid.selector).on('click', this.options.deleteSelector, function() {
        var row = $(this).closest('[data-rowid]');
        if (row.length > 0) {
          var rowid = $(row).attr('data-rowid');
          if (!br.isEmpty(rowid)) {
            br.confirm( 'Delete confirmation'
                      , 'Are you sure you want delete this record?'
                      , function() {
                          datagrid.options.dataSource.remove(rowid);
                        }
                      );
          }
        }
      });
    }

  }

  this.render = function(data) {
    if (data) {
      if (datagrid.options.freeGrid) {
        if (data.headers) {
          for (i in data.headers) {
            if (data.headers[i]) {
              $(datagrid.options.headersSelector).append(datagrid.renderHeader(data.headers[i]));
            }
          }
        }
        if (data.footers) {
          for (i in data.footers) {
            if (data.footers[i]) {
              $(datagrid.options.footersSelector).append(datagrid.renderFooter(data.headers[i]));
            }
          }
        }
        $(datagrid.selector).html('');
        if (data.rows) {
          if (data.rows.length == 0) {
            $(datagrid.selector).html($(this.options.templates.noData).html());
          } else {
            for (i in data.rows) {
              if (data.rows[i]) {
                if (data.rows[i].row) {
                  $(datagrid.selector).append(datagrid.renderRow(data.rows[i].row));
                }
                if (data.rows[i].header) {
                  $(datagrid.selector).append(datagrid.renderHeader(data.rows[i].header));
                }
              }
            }
          }
        } else {
          $(datagrid.selector).html($(this.options.templates.noData).html());        
        }
      } else {
        $(datagrid.selector).html('');
        if (data && (data.length > 0)) {
          for (i in data) {
            if (data[i]) {
              $(datagrid.selector).append(datagrid.renderRow(data[i]));
            }
          }
        } else {
          $(datagrid.selector).html($(this.options.templates.noData).html());
        }
      }
    } else {
      $(datagrid.selector).html($(this.options.templates.noData).html());
    }
    callEvent('change', data);
  }

}

function BrDataCombo(selector, dataSource, options) {

  var $ = jQuery;
  var _this = this;

  _this.selector = selector;
  _this.dataSource = dataSource;
  _this.options = options;
  _this.fields = _this.options.fields || {};
  _this.saveSelection = _this.options.saveSelection || false;

  _this.cb = {};

  _this.on = function(event, callback) {
    _this.cb[event] = this.cb[event] || new Array();
    _this.cb[event][this.cb[event].length] = callback;
  }

  function callEvent(event, data) {
    _this.cb[event] = _this.cb[event] || new Array();
    for (i in _this.cb[event]) {
      _this.cb[event][i].call($(_this.selector), data);
    }
  }

  function storageTag(c) {

    return document.location.toString() + ':filter-value:' + $(c).attr('name');
  }

  function render(data) {
    var options = _this.options;
    if (_this.saveSelection) {
      options.selectedValue = br.storage.get(storageTag(_this.selector));
    }
    _this.dataSource.fillCombo(_this.selector, data, options);
    callEvent('load', data);
  }

  _this.reload = function(callback) {
    _this.dataSource.select({}, function() {
      if (callback) {
        callback.call($(_this.selector));
      }
    }, { fields: _this.fields });
  }

  _this.dataSource.on('select', function(data) {
    render(data);
  });

  _this.dataSource.on('insert', function(data) {
//    insert(data, true);
  });

  _this.dataSource.on('update', function(data) { 
//    $(_this.selector).find('option[value=' + rowid +']').remove();
  });

  _this.dataSource.on('remove', function(rowid) {
    $(_this.selector).find('option[value=' + rowid +']').remove();
    callEvent('change');
  });

  $(_this.selector).change(function() {    
    if (_this.saveSelection) {
      br.storage.set(storageTag(this), $(this).val());
    }
    callEvent('change');
  });

}

// jQuery extension

jQuery.fn.extend({
  log: function(data) {
    return this.each(function() {
      var html = '<table>';
      html = html + "<tr><th style='border-bottom:1px solid black;'>Field</th><th style='border-bottom:1px solid black;'>Value</th></tr>";
      if ((typeof(data) == 'object') && (typeof(data.length) != 'undefined')) {
        for(var i = 0; i < data.length; i++) {
          for(name in data.item(i)) {
            html = html + '<tr><td>' + name + '</td><td>' + data.item(i)[name] + '</td></tr>';
          }
        }
      } else {
        for(name in data) {
          html = html + '<tr><td>' + name + '</td><td>' + data[name] + '</td></tr>';
        }
      }
      html = html + '</table>';
      $(this).html(html);
    });
  }
  , modified: function(callback) {
    return this.each(function() {
      $(this).live('change', callback);
      $(this).live('keyup', function(e) {
        if ((e.keyCode == 8) || ((e.keyCode >= 32) && (e.keyCode <= 128))) {
          callback.call();
        }
      });
    });
  }
});

(function($) {

  $(document).ready(function() {

    $('body').ajaxStart(function() { br.showProgress(); });
    $('body').ajaxStop(function() { br.hideProgress(); });
    $('body').ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
      if (jqXHR.status == 401) {
        document.location = br.baseUrl + 'login.html?caller=' + encodeURIComponent(document.location.toString()); 
      } 
    });

    $('input[data-click-on-enter]').live('keypress', function(e) {
      if (e.keyCode == 13) { $($(this).attr('data-click-on-enter')).trigger('click'); }
    });

    if ($('.focused').length > 0) {
      $('.focused')[0].focus();
    }  

    var users = 
      new BrDataSource( 'users'
                      , { restServiceUrl: br.baseUrl + 'api/users/'
                        } 
                      );

    users.on('error', function(operation, error) {
      br.growlError(error);
    });

    $('.action-signup').click(function() {

      var form = $(this).closest('form');
      var data = {};
      $(form).find('input').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
      $(form).find('select').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
      users.invoke('signup', data, function(result) {
        if (result) {
          br.redirect('?from=signup');
        }
      });

    });

    $('.action-login').click(function() {

      var form = $(this).closest('form');
      var data = {};
      $(form).find('input').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
      $(form).find('select').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });

      users.invoke( 'login'
                  , data
                  , function(result) {
                      if (result) {
                        br.redirect(br.request.get('caller', '?from=login'));
                      }
                    }
                  );

    });

    $('.action-forgot-password').click(function() {

      var form = $(this).closest('form');
      var data = {};
      $(form).find('input').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
      $(form).find('select').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });

      users.invoke( 'remindPassword'
                  , data
                  , function(result) {
                      if (result) {
                        br.redirect('login.html?from=forgot');
                      }
                    }
                  );

    });

    $('.action-logout').live('click', function() {

      users.invoke( 'logout'
                  , { }
                  , function(result) {
                      if (result) {
                        document.location = br.baseUrl;
                      }
                    }
                  );

    });  
      
  });
})(jQuery);

/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */

var dateFormat = function () {
  var token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
    timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
    timezoneClip = /[^-+\dA-Z]/g,
    pad = function (val, len) {
      val = String(val);
      len = len || 2;
      while (val.length < len) val = "0" + val;
      return val;
    };

  // Regexes and supporting functions are cached through closure
  return function (date, mask, utc) {
    var dF = dateFormat;

    // You can't provide utc if you skip other args (use the "UTC:" mask prefix)
    if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
      mask = date;
      date = undefined;
    }

    // Passing date through Date applies Date.parse, if necessary
    date = date ? new Date(date) : new Date;
    if (isNaN(date)) throw SyntaxError("invalid date");

    mask = String(dF.masks[mask] || mask || dF.masks["default"]);

    // Allow setting the utc argument via the mask
    if (mask.slice(0, 4) == "UTC:") {
      mask = mask.slice(4);
      utc = true;
    }

    var _ = utc ? "getUTC" : "get",
      d = date[_ + "Date"](),
      D = date[_ + "Day"](),
      m = date[_ + "Month"](),
      y = date[_ + "FullYear"](),
      H = date[_ + "Hours"](),
      M = date[_ + "Minutes"](),
      s = date[_ + "Seconds"](),
      L = date[_ + "Milliseconds"](),
      o = utc ? 0 : date.getTimezoneOffset(),
      flags = {
        d:    d,
        dd:   pad(d),
        ddd:  dF.i18n.dayNames[D],
        dddd: dF.i18n.dayNames[D + 7],
        m:    m + 1,
        mm:   pad(m + 1),
        mmm:  dF.i18n.monthNames[m],
        mmmm: dF.i18n.monthNames[m + 12],
        yy:   String(y).slice(2),
        yyyy: y,
        h:    H % 12 || 12,
        hh:   pad(H % 12 || 12),
        H:    H,
        HH:   pad(H),
        M:    M,
        MM:   pad(M),
        s:    s,
        ss:   pad(s),
        l:    pad(L, 3),
        L:    pad(L > 99 ? Math.round(L / 10) : L),
        t:    H < 12 ? "a"  : "p",
        tt:   H < 12 ? "am" : "pm",
        T:    H < 12 ? "A"  : "P",
        TT:   H < 12 ? "AM" : "PM",
        Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
        o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
        S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
      };

    return mask.replace(token, function ($0) {
      return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
    });
  };
}();

// Some common format strings
dateFormat.masks = {
  "default":      "ddd mmm dd yyyy HH:MM:ss",
  shortDate:      "m/d/yy",
  mediumDate:     "mmm d, yyyy",
  longDate:       "mmmm d, yyyy",
  fullDate:       "dddd, mmmm d, yyyy",
  shortTime:      "h:MM TT",
  mediumTime:     "h:MM:ss TT",
  longTime:       "h:MM:ss TT Z",
  isoDate:        "yyyy-mm-dd",
  isoTime:        "HH:MM:ss",
  isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
  isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
  dayNames: [
    "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
    "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
  ],
  monthNames: [
    "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
    "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
  ]
};

// For convenience...
Date.prototype.format = function (mask, utc) {
  return dateFormat(this, mask, utc);
};

!function(a,b){a.br=a.br||{};a.br.isNumber=function(c){return(!isNaN(parseFloat(c))&&isFinite(c))};a.br.isArray=function(c){return(!br.isNull(c)&&(Object.prototype.toString.call(c)==="[object Array]"))};a.br.isObject=function(c){return(!br.isEmpty(c)&&(typeof c=="object"))};a.br.isBoolean=function(c){return(typeof c=="boolean")};a.br.isString=function(c){return(typeof c=="string")};a.br.isNull=function(c){return((c===b)||(c===null))};a.br.isEmpty=function(c){return(br.isNull(c)||((typeof c.length!="undefined")&&(c.length==0)))}}(window);!function(a,b){a.br=a.br||{};var d={pack:function(e){return JSON.stringify(e)},unpack:function(f){try{return JSON.parse(f)}catch(e){return null}}};var c=function(j){var f=j;var h=this;this.get=function(m,l){if(br.isArray(m)){var k={};for(i in m){k[m[i]]=this.get(m[i])}}else{var k=d.unpack(f.getItem(m))}return br.isEmpty(k)?(br.isNull(l)?k:l):k};this.set=function(k,l){if(br.isObject(k)){for(name in k){this.set(name,k[name])}}else{f.setItem(k,d.pack(l))}return this};this.inc=function(l,k,n){var m=this.get(l);if(br.isNumber(m)){var k=(br.isNumber(k)?k:1);this.set(l,m+k)}else{if(br.isString(m)){if(!br.isEmpty(k)){if(n===b){n=", "}if(!br.isEmpty(m)){m=m+n+k}else{m=k}this.set(l,m)}}else{var k=(br.isNumber(k)?k:1);this.set(l,k)}}return this};this.dec=function(l,k){var k=(br.isNumber(k)?k:1);var m=this.get(l);this.set(l,br.isNumber(m)?(m-k):k);return this};this.append=function(l,n,k){if(!br.isEmpty(n)){var m=this.get(l);if(!br.isArray(m)){m=[]}if(br.isArray(n)){for(i in n){this.append(l,n[i],k)}}else{if(br.isNumber(k)){while(m.length>=k){m.shift()}}m.push(n);this.set(l,m)}}return this};this.appendUnique=function(l,m,k){if(!br.isEmpty(m)){this.remove(l,m);this.append(l,m,k)}return this};this.prepend=function(l,n,k){if(!br.isEmpty(n)){var m=this.get(l);if(!br.isArray(m)){m=[]}if(br.isArray(n)){for(i in n){this.prepend(l,n[i],k)}}else{if(br.isNumber(k)){while(m.length>=k){m.pop()}}m.unshift(n);this.set(l,m)}}return this};this.prependUnique=function(l,m,k){if(!br.isEmpty(m)){this.remove(l,m);this.prepend(l,m,k)}return this};this.each=function(k,l){var m=this.get(k);if(!br.isArray(m)){m=[]}for(i=0;i<m.length;i++){l.call(this,m[i])}return this};function g(n,m,l){var k=null;var o=h.get(n,m);if(br.isArray(o)){if(o.length>0){var k=o.pop();if(l){h.set(n,o)}}}return br.isEmpty(k)?(br.isNull(m)?k:m):k}this.getLast=function(l,k){return g(l,k,false)};this.takeLast=function(l,k){return g(l,k,true)};function e(n,m,l){var k=null;var o=h.get(n,m);if(br.isArray(o)){if(o.length>0){var k=o.shift();if(l){h.set(n,o)}}}return br.isEmpty(k)?(br.isNull(m)?k:m):k}this.getFirst=function(l,k){return e(l,k,false)};this.takeFirst=function(l,k){return e(l,k,true)};this.extend=function(k,m){if(!br.isEmpty(m)){var l=this.get(k);if(!br.isObject(l)){l={}}if(br.isObject(m)){for(i in m){l[i]=m[i]}this.set(k,l)}}return this};this.not=function(k){var l=this.get(k);if(!br.isBoolean(l)){l=false}this.set(k,!l);return this};this.clear=function(){f.clear();return this};this.all=function(){var k={};for(name in f){k[name]=this.get(name)}return k};this.remove=function(l,m){var n=this.get(l);if(!br.isEmpty(m)&&br.isArray(n)){var k=n.indexOf(m);if(k!=-1){n.splice(k,1)}this.set(l,n)}else{f.removeItem(l)}return this}};a.br.storage=new c(a.localStorage);a.br.session=new c(a.sessionStorage)}(window);