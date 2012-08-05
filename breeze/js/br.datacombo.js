// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

!function ($, window, undefined) {

  window.br = window.br || {};

  window.br.dataCombo = function (selector, dataSource, options) {
    return new BrDataCombo(selector, dataSource, options);
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
      _this.dataSource.select({}, function(result) {
        if (result) {
          if (callback) {
            callback.call($(_this.selector), result);
          }
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

}(jQuery, window);


