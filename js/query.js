$(document).ready(function() {

  // pager parameters
  var config = { recentQueriesAmount: 40
               , pagerLimit: 20 
               , tabsAmount: 8
               , recentQueriesTag: 'recentQueries'
               }

  // editors
  var editors = [];

  // find current editor
  function getCurrentEditor() {

    var idx = $('li.query-tab.active').attr('rel');
    return editors[idx];

  }

  // load previous queries into "Run query" button
  function refreshRecentQueries() {

    $('table#recent tbody').html('');

    // iterate recent queries and create drop down items
    br.storage.each(config.recentQueriesTag, function(sql) {
      // create items
      var item = $(br.fetch($('#recentQueryRowTemplate').html(), { sql: sql } ));
      // storage query definition
      item.find('a').data('sql', sql);
      // append drop down item
      $('table#recent tbody').append(item);
    });

    // update queries amount
    $('.recent-queries-badge').text($('table#recent').find('tr').length);
  }

  // data source

  // recent queries data source
  var savedQueriesDataSource = new BrDataSource( br.baseUrl + 'api/savedQueries/' );

  savedQueriesDataSource.on('error', function(operation, error) {
    $('span.query-error').text(error);
    $('div.query-error').show();
  });

  function refreshSavedQueriesBadge() {

    savedQueriesDataSource.select({}, function(result, response) {
      if (result) {
        $('.saved-queries-badge').text(response.length);
      }
    }, { disableEvents: true });

  }

  savedQueriesDataSource.after('insert', function(operation, error) {
    refreshSavedQueriesBadge();
  });

  savedQueriesDataSource.after('remove', function(operation, error) {
    refreshSavedQueriesBadge();
  });

  savedQueriesDataSource.after('select', function(result, response) {
    if (result) {
      $('.saved-queries-badge').text(response.length);
    }
  });

  var savedQueriesDataGrid = new BrDataGrid( '#saved tbody'
                                           , { templates: { row:    '#savedQueryRowTemplate'                                              
                                                          , noData: '#savedQueryNoDataTemplate' 
                                                          }
                                             , dataSource: savedQueriesDataSource
                                             }
                                           );

  // library queries
  var libraryQueriesDataSource = new BrDataSource( br.baseUrl + 'api/libraryQueries/' );

  libraryQueriesDataSource.after('select', function(result, response) {
    if (result) {
      $('.library-queries-badge').text(response.length);
    }
  });

  var libraryQueriesDataGrid = new BrDataGrid( '#libraryContent'
                                             , { templates: { row:    '#libraryQueryRowTemplate'                                              
                                                            , noData: '#libraryQueryNoDataTemplate' 
                                                            }
                                               , dataSource: libraryQueriesDataSource
                                               }
                                             );

  function activateQueryMode() {
    $('.navbar-inner a[href=#query]').tab('show');  
  }

  $('.action-run-saved,.action-run-library').live('click', function() {

    var data = $(this).closest('[data-rowid]').data('data-row');
    activateQueryMode();
    getCurrentEditor().runQuery(data.sql);

  });

  $('.action-run-recent').live('click', function() {

    var sql = $(this).data('sql');
    activateQueryMode();
    getCurrentEditor().runQuery(sql);

  });

  $('.action-edit-saved,.action-edit-library').live('click', function() {

    var data = $(this).closest('[data-rowid]').data('data-row');
    activateQueryMode();
    getCurrentEditor().setQuery(data.sql);

  });

  $('.action-edit-recent').live('click', function() {

    var sql = $(this).data('sql');
    activateQueryMode();
    getCurrentEditor().setQuery(sql);

  });

  $('.action-delete-saved').live('click', function() {

    var data = $(this).closest('[data-rowid]').data('data-row');
    savedQueriesDataSource.remove(data.rowid);

  });

  // create tabs
  for (i = 0; i < config.tabsAmount; i++) {
    editors.push(new QueryEditor( { labelsSelector: '#queryTabLabels'
                                  , tabsSelector: '#queryTabs'
                                  , pagerLimit: config.pagerLimit
                                  , recentQueriesAmount: config.recentQueriesAmount
                                  , recentQueriesTag: config.recentQueriesTag
                                  , onRun: function() {
                                      refreshRecentQueries();
                                    }
                                  }
                                , savedQueriesDataSource
                                ));
  }

  editors[br.storage.get('activeQueryTab', 0)].activate();

  // activate navigation item
  $('a.section[data-toggle=tab]').on('shown', function (e) {
    var workMode = $(this).attr('href');
    br.storage.set('section', workMode);
    if (workMode == '#query') {
      getCurrentEditor().activate();
    }
  })  

  $('.navbar-inner a.section[href=' + br.storage.get('section', 'query') +']').tab('show');

  // create queries drop down
  refreshRecentQueries();

  // show saved queris badge
  savedQueriesDataSource.select();

  // load library queries
  libraryQueriesDataSource.select();

});

