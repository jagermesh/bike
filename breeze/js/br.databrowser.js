!function ($, window, undefined) {

  window.br = window.br || {};

  window.br.dataBrowser = function (entity, options) {
    return new BrDataBrowser(entity, options);
  }

  function BrDataBrowser(entity, options) {

    this.options = options || {};
    this.options.entity = entity;
    this.options.noun = this.options.noun || '';
    this.storageTag = document.location.toString();
    this.limit = 20;
    this.skip = 0;
    this.dataSource = br.dataSource(br.baseUrl + 'api/' + this.options.entity + '/');
    this.countDataSource = br.dataSource( br.baseUrl + 'api/' + this.options.entity + '/');
    this.dataGrid = br.dataGrid( '.data-table'
                               , '.data-row-template'
                               , this.dataSource
                               , { templates: { noData: '.data-empty-template' }
                                 , deleteSelector: '.action-delete' 
                                 }
                               );
    this.before = function(operation, callback) {
      this.dataSource.before(operation, callback);
      this.countDataSource.before(operation, callback);
    }
    this.getOrder = function() {
      return br.storage.get(this.storageTag + 'order');
    }
    this.setOrder = function(order) {
      br.storage.set(this.storageTag + 'order', order);
    }
    this.init = function() {
      var _this = this;

      // nav
      $('.nav-item[rel=' + _this.options.nav + ']').addClass('active');

      _this.dataSource.on('error', function(operation, error) {
        br.growlError(error);//, br.baseUrl + 'images/person_default_box.gif');
      });

      _this.dataSource.before('select', function(request, options) {
        options.order = _this.getOrder();
        if ($('input.data-filter[name=keyword]').length > 0) {
          request.keyword = $('input.data-filter[name=keyword]').val();
        }
      });

      _this.dataSource.after('remove', function(request, options) {
        _this.updatePager();
      });

      _this.dataSource.after('insert', function(request, options) {
        _this.updatePager();
      });

      _this.countDataSource.before('select', function(request) {
        if ($('input.data-filter[name=keyword]').length > 0) {
          request.keyword = $('input.data-filter[name=keyword]').val();
        }
      });

      var order = _this.getOrder();
      if (order) {
        for (i in order) {
          $('.sortable[data-field="' + i + '"].' + (order[i] == -1 ? 'order-desc' : (order[i] == 1 ? 'order-asc' : 'dummy'))).addClass('icon-white');
        }
      }

      $('.sortable').on('mouseover', function() { $(this).css('cursor', 'pointer'); });

      $('.sortable').on('mouseout' , function() { $(this).css('cursor', 'auto'); });

      $('.sortable').on('click', function() {
        if ($(this).hasClass('icon-white')) {
          $(this).removeClass('icon-white');
        } else {
          $(this).siblings('i').removeClass('icon-white'); $(this).addClass('icon-white');
        }
        _this.setOrder({});
        $('.sortable').each(function() {
          if ($(this).hasClass('icon-white')) {
            var tmp = {};
            if ($(this).hasClass('order-asc')) {
              tmp[$(this).attr('data-field')] = 1;
            }
            if ($(this).hasClass('order-desc')) {
              tmp[$(this).attr('data-field')] = -1;
            }
            if (tmp) {
              _this.setOrder(tmp);
            }
          }
        });
        _this.refresh();
      });

      // search
      br.modified('input.data-filter[name=keyword]', function() { 
        var _val = $(this).val();
        $('input.data-filter[name=keyword]').each(function() {
          if ($(this).val() != _val) {
            $(this).val(_val);
          }
        }); 
        _this.skip = 0; 
        _this.refresh(true); 
      });
        
      // actions
      $('.data-edit-form').each(function() {
        $('.action-create').show();
        $('.action-edit,.action-create').live('click', function() {
          var rowid = $(this).closest('[data-rowid]').attr('data-rowid');
          $('.data-edit-form').each(function() {
            if (rowid) {
              $(this).find('.operation').text('Edit ' + _this.options.noun);
              $(this).data('rowid', rowid);
            } else {
              $(this).find('.operation').text('Create ' + _this.options.noun);
              $(this).removeData('rowid');
            }
            $(this).find('input.data-field,select.data-field,textarea.data-field').val('');
            var edit = $(this);
            $(edit).on('shown', function() {
              var firstInput = $(this).find('.data-field');
              if (firstInput.length > 0) {
                firstInput[0].focus();
              }
            });
            if (rowid) {
              _this.dataSource.selectOne(rowid, function(result, data) {
                if (result) {
                  for(i in data) {
                    $(edit).find('input.data-field[name=' + i + '],select.data-field[name=' + i + '],textarea.data-field[name=' + i + ']').val(data[i]);
                  }
                  if ($.datepicker) {
                    $(edit).find('.date').datepicker({ dateFormat: 'mm/dd/yy'});
                  }
                  $(edit).modal('show');
                }
              }, { disableEvents: true });
            } else {
              $(edit).modal('show');        
            }
          });
        });

        $('.action-save').live('click', function() {
          $('.data-edit-form').each(function() {
            var rowid = $(this).data('rowid');
            var data = { };
            var edit = $(this);
            var ok = true;
            $(this).find('input.data-field,select.data-field,textarea.data-field').each(function() {
              if (ok) {
                if ($(this).hasClass('required') && br.isEmpty($(this).val())) {
                  var title = $(this).attr('title');
                  if (br.isEmpty(title)) {
                    title = $(this).prev('label').text();
                  }
                  br.growlError(title + ' must be filled');//, br.baseUrl + 'images/person_default_box.gif');
                  this.focus();
                  ok = false;
                } else {
                  data[$(this).attr('name')] = $(this).val();
                }
              }
            });
            if (ok) {
              if (rowid) {
                _this.dataSource.update(rowid, data, function(result) {
                  if (result) {
                    $(edit).modal('hide');
                  }
                });
              } else {
                _this.dataSource.insert(data, function(result) {
                  if (result) {
                    $(edit).modal('hide');
                  }
                });
              }
            }
          });
        });
      });

      br.editable('.editable', function(content) {
        var $this = $(this); 
        var rowid = $this.closest('[data-rowid]').attr('data-rowid'); 
        var dataField = $this.attr('data-field');
        if (!br.isEmpty(rowid) && !br.isEmpty(dataField)) {
          var data = {};
          data[dataField] = content;
          _this.dataSource.update( rowid
                           , data
                           , function(result) {
                               if (result) {
                                 br.editable($this, 'apply', content);
                               }
                             }
                           );
        }
      });

      $('.richeditor').each(function() {
        //$(this).css('height', $(window).height() - 420);
        $(this).redactor({ autoresize: true, /*fixed: true, */autoformat: false/*, convertDivs: false*/ });
      });

      // pager
      $('.action-next').click(function() {
         
        _this.skip += _this.limit;
        _this.refresh();
        //$('.action-prior').show();

      });

      $('.action-prior').click(function() {
         
        _this.skip -= _this.limit;
        if (_this.skip < 0) {
          _this.skip = 0;
        }
        _this.refresh();

      });

      function showFiltersDesc() {

        if ($('.filters-panel').css('display') != 'none') {
          $('.filters-switcher').find('span').text('Hide filters');
          $('.filter-description').text('');
        } else {
          $('.filters-switcher').find('span').text('Show filters');
          var s = '';
          $('.data-filter').each(function() {
            var val = $(this).val();
            if (val) {
              s = s + '/ <strong>' + $(this).attr('title') + '</strong> ';
              if ($(this).is('select')) {
                s = s + $(this).find('option[value=' + val + ']').text() + ' ';
              } else {
                s = s + val + ' ';
              }

            }
          });
          $('.filter-description').html(s);
        }      

      }

      function setupFilters(initial) {

        function showHideFilters(initial) {

          if ($('.filters-panel').css('display') == 'none') {
            br.storage.set(_this.storageTag + ':filters-hidden', false);
            if (initial) {
              $('.filters-panel').show();
              showFiltersDesc();
            } else
            $('.filters-panel').slideDown(function() {
              showFiltersDesc();
            });
          } else {
            br.storage.set(document.location.toString() + ':filters-hidden', true);
            if (initial) {
              $('.filters-panel').hide();
              showFiltersDesc();
            } else
            $('.filters-panel').slideUp(function() {
              showFiltersDesc();
            });
          }      

        }

        $('.filters-switcher').click(function() {
          showHideFilters();
        });  

        $('.filters-reset').on('click', function () {
          $('select.data-filter option:selected').removeAttr('selected');
          $('select.data-filter').prop('selectedIndex',0);
          $('input.data-filter').val('');
          br.storage.clear();
          _this.refresh();
        });

        if (br.storage.get(document.location.toString() + ':filters-hidden')) {
          showFiltersDesc();
        } else {
          showHideFilters(initial);
        }

      }

      setupFilters(true);

      _this.dataSource.after('select', function() {
        showFiltersDesc();
      });

      return this;
    }

    this.updatePager = function(filter) {

      var _this = this;

      filter = filter || {};
      filter.__skip = _this.skip;
      filter.__limit = _this.limit;

      _this.countDataSource.selectCount(filter, function(success, result) {
        var min = (_this.skip + 1);
        var max = Math.min(_this.skip + _this.limit, result);
        if (result > 0) {
          $('.pager').show();
          if (result > max) {
            $('.action-next').show();
          } else {
            $('.action-next').hide();
          }
          if (_this.skip > 0) {
            $('.action-prior').show();
          } else {
            $('.action-prior').hide();
          }
        } else {
          $('.pager').hide();        
        }
        $('.pager-stat').text('Records ' + min + '-' + max + ' of ' + result);
      });

    }  
          
    
    this.refresh = function(deferred, filter, callback) {

      var _this = this;

      filter = filter || {};
      filter.__skip = _this.skip;
      filter.__limit = _this.limit;

      if (deferred) {
        _this.dataSource.deferredSelect(filter, function() {
          _this.updatePager();
          if (typeof callback != 'undefined') {
            callback.call(this);
          }
        });        
      } else {
        _this.dataSource.select(filter, function() {
          _this.updatePager();
          if (typeof callback != 'undefined') {
            callback.call(this);
          }
        });     
      }

    }

    return this.init();

  }

}(jQuery, window);
