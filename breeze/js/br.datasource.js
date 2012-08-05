// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

!function ($, window, undefined) {

  window.br = window.br || {};

  window.br.dataSource = function (restServiceUrl, options) {
    return new BrDataSource(restServiceUrl, options);
  }

  function BrDataSource(restServiceUrl, options) {

    var $ = jQuery;
    var datasource = this;
    var ajaxRequest = null;

    this.cb = {};
    this.refreshTimeout;
    this.name = '-';
    this.options = options || {};
    this.options.restServiceUrl = restServiceUrl;
    this.options.refreshDelay = this.options.refreshDelay || 500;
    if (this.options.restServiceUrl.charAt(this.options.restServiceUrl.length-1) != '/') {
      this.options.restServiceUrl = this.options.restServiceUrl + '/';
    }

    if (this.options.offlineMode) {
      this.db = TAFFY();
      this.db.store('taffy-db-' + name);
    }

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

      options = options || { };

      var url = this.options.restServiceUrl;
      if (requestRowid) {
        url = url + requestRowid;
      }

      var proceed = true;

      if (!disableEvents) {
        try {
          callEvent('before:select', request, options);
        } catch(e) {
          proceed = false;
        }
      }

      if (proceed) {
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

        if (options && options.order) {
          request.__order = options.order;
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
      } else {
        
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
          val = selectedValue;
        }
        if (!br.isEmpty(val)) {
          $(this).find('option[value=' + val +']').attr('selected', 'selected');
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

}(jQuery, window);
