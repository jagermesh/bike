var idx = 0;

function regExpEditor(options, savedRegExpDataSource) {

  var _this = this;

  var index = idx;
  var tabQueriesTag = 'tabQueries' + index;
  var value = br.storage.get(tabQueriesTag);
  if (value) {
    var tabLabel = value.substring(0, 15);
  } else {
    var tabLabel = 'Query ' + (index  + 1);
  }

  idx++;
  var container = $(br.fetch($('#queryTabTemplate').html(), { tabIndex: index }));
  var label = $(br.fetch($('#queryTabLabelTemplate').html(), { tabIndex: index, tabLabel: tabLabel}));
  var lastQuery;

  $(options.labelsSelector).append(label);
  $(options.tabsSelector).append(container);

  var editor = CodeMirror( container.find( '.query-editor')[0]
                                         , { mode: "text/x-mysql"
                                           , tabMode: "indent"
                                           , matchBrackets: true
                                           , value: br.storage.get(tabQueriesTag)
                                           , onChange: function() {
                                               var value = editor.getValue();
                                               br.storage.set(tabQueriesTag, value);
                                               if (value) {
                                                 var tabLabel = value.substring(0, 15);
                                               } else {
                                                 var tabLabel = 'Query ' + (index  + 1);
                                               }
                                               label.find('a.query-tab').text(tabLabel);
                                             }
                                           }
                                         );
  var pager = { skip: 0, limit: options.pagerLimit };

  this.activate = function() {
    label.find('a').tab('show');
    editor.focus();
    // to trigger updateDisplay
    editor.setOption('tabSize', 4);
  }

  label.find('a').on('shown', function (e) {
    br.storage.set('activeQueryTab', index);
    editor.focus();
    // to trigger updateDisplay
    editor.setOption('tabSize', 4);
  })  

  // data source
  var dataSource = br.dataSource( br.baseUrl + 'api/query/' );
  var exportDataSource = br.dataSource( br.baseUrl + 'api/query/' );

  dataSource.on('error', function(operation, error) {
    container.find('.action-cancel-run').hide();
    container.find('span.query-error').text(error);
    container.find('div.query-error').show();
  });

  exportDataSource.on('error', function(operation, error) {
    container.find('.action-cancel-run').hide();
    container.find('span.query-error').text(error);
    container.find('div.query-error').show();
  });

  // data source for pager
  var countDataSource = br.dataSource( br.baseUrl + 'api/query/' );

  // data grid
  var dataGrid = br.dataGrid( container.find('table.query-results')
                            , '#row-template'
                            , dataSource
                            , { templates: { header: '#header-template'                                              
                                           , footer: '#footer-template'
                                           , noData: '#no-data' 
                                           }
                              , headersSelector: container.find('table.query-results')
                              , footersSelector: container.find('.query-results tfoot')
                              , freeGrid: true 
                              }
                            );

  // run query
  function setLastQuery(query) {
    br.storage.set(tabQueriesTag, query);
    lastQuery = query;
  }

  function getLastQuery() {
    return lastQuery;
  }

  function internalRunQuery(sql) {

    var filter = {};

    filter.__skip  = pager.skip;
    filter.__limit = pager.limit;

    filter.sql = sql;

    container.find('div.query-error').hide();
    container.find('.action-cancel-run').show();

    br.storage.prependUnique(options.recentQueriesTag, sql, options.recentQueriesAmount);

    options.onRun.call();

    dataSource.insert(filter, function(result, response) {
      if (result) {
        var queryDesc = response;
        dataSource.select(queryDesc, function(result, response) {
          container.find('.action-cancel-run').hide();
          if (result) {
            if (queryDesc.isSelect && !queryDesc.isLimited) {
              countDataSource.selectCount(queryDesc, function(success, result) {
                var min = (pager.skip + 1);
                var max = Math.min(pager.skip + pager.limit, result);
                if (result > 0) {
                  container.find('.pager-control').show();
                  if (result > max) {
                    container.find('.action-next').show();
                  } else {
                    container.find('.action-next').hide();
                  }
                  if (pager.skip > 0) {
                    container.find('.action-prior').show();
                  } else {
                    container.find('.action-prior').hide();
                  }
                } else {
                  container.find('.pager-control').hide();        
                }
                container.find('.pager-stat').text('Records ' + min + '-' + max + ' of ' + result);
              });
            } else {
              container.find('.pager-control').hide();                      
            }
            autoRefresh();
            container.find('.query-results').show();
          } else {
            container.find('.pager-control').hide();        
            container.find('.query-results').hide();
          }
        });
      }
    });

  }

  function exportQuery(sql) {

    var filter = {};

    filter.sql = sql;

    container.find('div.query-error').hide();
    container.find('.action-cancel-run').show();

    // br.storage.prependUnique(options.recentQueriesTag, sql, options.recentQueriesAmount);

    // options.onRun.call();

    exportDataSource.insert(filter, function(result, response) {
      if (result) {
        container.find('.action-cancel-run').hide();
        br.openPopup(br.baseUrl + 'export.html?hash=' + response.hash);
      }
    });

  }

  this.setQuery = function(sql) {

    editor.setValue(sql);

  }

  this.runCurrentQuery = function() {

    _this.runQuery(editor.getValue());

  }

  this.runQuery = function(sql) {

    var regexp = /[{][{](.+?)[}][}]/g;
    var match;
    var fields = {}; 
    var placeholders = false;

    while ((match = regexp.exec(sql)) != null) {
      placeholders = true;
      fields[match[1]] = br.storage.get('lastParamValue:' + match[1], '');
    }

    if (placeholders) {
      br.prompt('Please enter', fields, function(values) {
        for(i in values) {
          br.storage.set('lastParamValue:' + i, values[i]);
          if ((match = regexp.exec(sql)) != null) {
            br.storage.set('lastParamValue:' + match[1], values[i]);
            sql = sql.replace('{{' + match[1] + '}}', values[i]);
          }
        }
        resetAutoRefresh();
        pager.skip = 0;
        // editor.setValue(sql);
        setLastQuery(sql);
        internalRunQuery(sql);
      });
    } else {
      resetAutoRefresh();
      pager.skip = 0;
      editor.setValue(sql);
      setLastQuery(sql);
      internalRunQuery(sql);
    }

  }

  // UI
  container.find('.action-next').click(function() {
     
    resetAutoRefresh();
    pager.skip += pager.limit;
    internalRunQuery(getLastQuery());

  });

  container.find('.action-prior').click(function() {
     
    resetAutoRefresh();
    pager.skip = Math.max(pager.skip - pager.limit, 0);
    internalRunQuery(getLastQuery());

  });

  container.find('.action-refresh').click(function() {

    pager.skip = 0;
    internalRunQuery(getLastQuery());

  });

  container.find('.action-export').click(function() {

    pager.skip = 0;
    exportQuery(getLastQuery());

  });

  container.find('.action-cancel-run').click(function() {

    dataSource.abortRequest();

  });

  container.find('.action-save-query').click(function() {

    container.find('div.query-error').hide();

    br.prompt('Saving query', {'Please enter name': ''}, function(results) {
      savedQueriesDataSource.insert( { name: results[0]
                                     , sql: editor.getValue() 
                                     }
                                   , function(result, response) {
                                       if (result) {
                                         br.growlMessage('Saved');
                                       }
                                     }
                                   );
    });

  });

  container.find('.action-run').click(function() {

    _this.runCurrentQuery();

  });

  return this;

}
