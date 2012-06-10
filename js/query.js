$(document).ready(function() {

  // activation navigation item
  $('a[data-toggle=tab]').on('shown', function (e) {
    br.storage.set('work-mode', $(this).attr('href'));
  })  

  $('.navbar-inner a[href=' + br.storage.get('work-mode', '#query') +']').tab('show');

  // pager parameters
  var config = { recentQueriesAmount: 40 }
  var pager = { skip: 0, limit: 20 }

  // syntax highlighter
  var editor = CodeMirror.fromTextArea( $('textarea.query-editor')[0]
                                      , { mode: "text/x-mysql"
                                        , tabMode: "indent"
                                        , matchBrackets: true
                                        //, theme: 'ambiance'
                                        }
                                      );

  var recentQueriesTag = 'recentQueries';

  // load previous queries into "Run query" button
  function refreshRecentQueries() {

    // remove current list - sorry, not elegant and slow, but I suppose ok for 20 items :(
    $('.action-run-recent').remove();

    // iterate recent queries and create drop down items
    br.storage.each(recentQueriesTag, function(sql) {
      // create items
      var name = sql.substring(0, 50);
      var item = $(br.fetch($('#recentQueryDropDownItem').html(), { name: name } ));
      // storage query definition
      item.find('a').data('sql', sql);
      // append drop down item
      $('.recent-queries').append(item);
    });

    // update queries amount
    var l = $('.action-run-recent').length;
    if (l > 0) {
      $('.queries-amount').text(l);
    } else {
      $('.queries-amount').text('');
    }
  }

  // refresh saved queries
  function refreshSavedQueriesBadge() {

    savedQueriesDataSource.select({}, function(result, response) {
      if (result) {
        $('.saved-queries-badge').text(response.length);
      }
    }, { disableEvents: true });

  }

  // refresh library queries
  function refreshLibraryQueries() {

    libraryQueriesDataSource.select(function(result, response) {
      if (result) {
        $('.library-queries-badge').text(response.length);
      }
    });

  }

  // data source
  var dataSource = new BrDataSource( br.baseUrl + 'api/query/' );

  dataSource.on('error', function(operation, error) {
    $('span.query-error').text(error);
    $('div.query-error').show();
  });

  // data source for pager
  var countDataSource = new BrDataSource( br.baseUrl + 'api/query/' );

  // recent queries data source
  var savedQueriesDataSource = new BrDataSource( br.baseUrl + 'api/savedQueries/' );

  savedQueriesDataSource.on('error', function(operation, error) {
    $('span.query-error').text(error);
    $('div.query-error').show();
  });

  var libraryQueriesDataSource = new BrDataSource( br.baseUrl + 'api/libraryQueries/' );

  // data grid
  var dataGrid = new BrDataGrid( '#query tbody'
                               , { templates: { row:    '#row-template'                                              
                                              , header: '#header-template'                                              
                                              , footer: '#footer-template'
                                              , noData: '#no-data' 
                                              }
                                 , dataSource: dataSource
                                 , headersSelector: '#query thead'
                                 , footersSelector: '#query tfoot'
                                 , freeGrid: true 
                                 }
                               );

  var savedQueriesDataGrid = new BrDataGrid( '#saved tbody'
                                           , { templates: { row:    '#savedQueryRowTemplate'                                              
                                                          , noData: '#savedQueryNoDataTemplate' 
                                                          }
                                             , dataSource: savedQueriesDataSource
                                             }
                                           );

  savedQueriesDataGrid.on('change', function() {
    refreshSavedQueriesBadge();
  });

  var libraryQueriesDataGrid = new BrDataGrid( '#libraryContent'
                                             , { templates: { row:    '#libraryQueryRowTemplate'                                              
                                                            , noData: '#libraryQueryNoDataTemplate' 
                                                            }
                                               , dataSource: libraryQueriesDataSource
                                               }
                                             );

  // run query
  function runQuery(sql) {

    var filter = {};

    filter.__skip  = pager.skip;
    filter.__limit = pager.limit;

    filter.sql = sql;

    $('div.query-error').hide();
    $('.action-cancel-run').show();

    br.storage.prependUnique(recentQueriesTag, sql, config.recentQueriesAmount);

    dataSource.select(filter, function(result, response) {
      $('.action-cancel-run').hide();
      if (result) {
        updatePager(filter);
        refreshRecentQueries();
        autoRefresh();
        $('.query-results').show();
      } else {
        $('.query-results').hide();
      }
    });

  }

  // pager
  function updatePager(filter, recordsAmount) {

    countDataSource.selectCount(filter, function(success, result) {
      // if (recordsAmount >= pager.limit) {
      //   var min = 1;
      //   var max = recordsAmount;
      // } else {
        var min = (pager.skip + 1);
        var max = Math.min(pager.skip + pager.limit, result);
        if (result > 0) {
          $('.pager').show();
          if (result > max) {
            $('.action-next').show();
          } else {
            $('.action-next').hide();
          }
          if (pager.skip > 0) {
            $('.action-prior').show();
          } else {
            $('.action-prior').hide();
          }
        } else {
          $('.pager').hide();        
        }
      //}
      $('.pager-stat').text('Records ' + min + '-' + max + ' of ' + result);
    });

  }    

  // Automatic refresh
  var autoRefreshTimer;

  function resetAutoRefresh() {
    $('.autorun-field[name=active]').removeClass('active');
  }

  function activateQueryMode() {
    $('.navbar-inner a[href=#query]').tab('show');  
  }

  function autoRefresh() {    
    window.clearTimeout(autoRefreshTimer);
    if ($('.autorun-field[name=active]').hasClass('active')) {
      var period = $('.autorun-field[name=period]').val();
      if (!br.isNumber(period)) {
        period = 5;
      }
      period = Math.max(period, 3);
      autoRefreshTimer = window.setTimeout(function() {
        runQuery(br.storage.getFirst(recentQueriesTag));
      }, period * 1000);
    }
  }

  $('.action-autorefresh').click(function() {
    window.setTimeout(function() {
      autoRefresh();
    }, 500);
  });

  br.modified('.autorun-field[name=period]', function() {
    autoRefresh();
  });

  // UI
  $('.action-next').click(function() {
     
    resetAutoRefresh();
    pager.skip += pager.limit;
    runQuery(br.storage.getFirst(recentQueriesTag));

  });

  $('.action-prior').click(function() {
     
    resetAutoRefresh();
    pager.skip = Math.max(pager.skip - pager.limit, 0);
    runQuery(br.storage.getFirst(recentQueriesTag));

  });

  $('.action-run').click(function() {

    resetAutoRefresh();
    pager.skip = 0;
    runQuery(editor.getValue());

  });

  $('.action-run-recent').live('click', function() {

    resetAutoRefresh();
    pager.skip = 0;
    var sql = $(this).data('sql')
    
    // $('.CodeMirror').fadeOut(function() {
    editor.setValue(sql);
      // $(this).fadeIn();
    // });

  });

  function runHelper(sql) {

    resetAutoRefresh();
    pager.skip = 0;
    activateQueryMode();
    editor.setValue(sql);
    runQuery(sql);

  }

  $('.action-run-saved,.action-run-library').live('click', function() {

    var data = $(this).closest('[data-rowid]').data('data-row');

    var regexp = /%(.+)%/g;
    var result = regexp.exec(data.sql);
    if (result != null) {
      br.prompt('Please enter', result[1], function(value) {
        br.storage.set('lastParamValue', value);
        var sql = data.sql.replace('%' + result[1] + '%', value);
        runHelper(sql);
      }, { defaultValue: br.storage.get('lastParamValue', '') });
    } else {
      runHelper(data.sql);
    }

  });

  $('.action-delete-saved').live('click', function() {

    var data = $(this).closest('[data-rowid]').data('data-row');
    savedQueriesDataSource.remove(data.rowid);

  });

  $('.action-refresh').click(function() {

    resetAutoRefresh();
    pager.skip = 0;
    runQuery(br.storage.getFirst(recentQueriesTag));

  });

  $('.action-cancel-run').click(function() {

    dataSource.abortRequest();

  });

  $('.action-save-query').click(function() {

    $('div.query-error').hide();

    var name = $('.query-field[name=name]').val();
    if (!name) {
      br.growlError('Please enter name for this query');
    } else {
      savedQueriesDataSource.insert( { name: name, sql: br.storage.getFirst(recentQueriesTag) }
                                   , function(result, response) {
                                       if (result) {
                                         br.growlMessage('Saved');
                                         refreshSavedQueriesBadge();
                                         $('.query-field[name=name]').val('');
                                       }
                                     }
                                   );
    }

  });

  // $('html').keyup(function(e) {
  //   if ((e.keyCode == 91) || (e.keyCode == 93)) {
  //     resetAutoRefresh();
  //     pager.skip = 0;
  //     runQuery(editor.getValue());      
  //   }
  // });

  // run

  // create queries drop down
  refreshRecentQueries();

  // show saved queris badge
  savedQueriesDataSource.select();

  // load library queries
  refreshLibraryQueries();

  // load last query into query editor
  editor.setValue(br.storage.getFirst(recentQueriesTag, 'SHOW TABLES'));  

});

