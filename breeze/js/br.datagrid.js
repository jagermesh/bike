// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

!function ($, window, undefined) {

  window.br = window.br || {};

  window.br.dataGrid = function (selector, rowTemplate, dataSource, options) {
    return new BrDataGrid(selector, rowTemplate, dataSource, options);
  }

  function BrDataGrid(selector, rowTemplate, dataSource, options) {

    var $ = jQuery;
    var datagrid = this;

    this.cb = {};
    this.selector = selector;
    this.options = options || {};
    this.options.templates = this.options.templates || {};
    this.options.templates.row = rowTemplate;
    this.options.dataSource = dataSource;

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
                  if (data.rows[i].footer) {
                    $(datagrid.selector).append(datagrid.renderFooter(data.rows[i].footer));
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

}(jQuery, window);
