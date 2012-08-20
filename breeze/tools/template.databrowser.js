$(document).ready(function() {

  $('.nav-item[rel="[[entityName]]"]').addClass('active');

  var grid = br.dataBrowser('[[entityName]]');

  grid.before('select', function(request) {
    /*
    [[#foreignKeys]]
    var val = $('select.data-filter[name=[[fieldName]]').val();
    if (!br.isEmpty(val)) { request.[[fieldName]] = val; }
    [[/foreignKeys]]
     */
  });

  /*
  [[#foreignKeys]]      
  var [[referencedEntity]]DataSource = br.dataSource(br.baseUrl + 'api/[[referencedEntity]]/');

  var [[referencedEntity]]FilterCombo = br.dataCombo('select.data-filter[name=[[fieldName]]]', [[referencedEntity]]DataSource, saveSelection: true});
  [[referencedEntity]]FilterCombo.on('load', function() { grid.refresh(); });
  [[referencedEntity]]FilterCombo.on('change', function() { grid.refresh(); });
  [[referencedEntity]]FilterCombo.reload();

  var [[referencedEntity]]EditorCombo = br.dataCombo('select.data-field[name=[[fieldName]]]', [[referencedEntity]]DataSource);
  [[referencedEntity]]EditorCombo.reload();
  [[/foreignKeys]]
  */

  grid.refresh();

});
