// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

!function ($, window, undefined) {

  window.br = window.br || {};

  window.br.showError = function(s) {
    alert(s);
  };

  window.br.growlError = function(s, image) {
    if (!br.isEmpty(s)) {
      if (typeof $.gritter != 'undefined') {
        $.gritter.add({
            title: ' '
          , text: s
          , class_name: 'gritter-red'
          , image: image
        });
      } else
      if (typeof window.humane != 'undefined') {
        humane.log(s, { addnCls     : 'humane-jackedup-error humane-original-error'
                      //, clickToClose: true
                      , timeout     : 5000
                      });
      } else {
        alert(s);
      }
    }
  };

  window.br.showMessage = function(s) {
    if (!br.isEmpty(s)) {
      alert(s);
    }
  };

  window.br.growlMessage = function(s, image) {
    if (!br.isEmpty(s)) {
      if (typeof $.gritter != 'undefined') {
        $.gritter.add({
            title: ' '
          , text: s
          , class_name: 'gritter-light'
          , image: image
        });
      } else
      if (typeof window.humane != 'undefined') {
        humane.log(s);
      } else {
        alert(s);
      }
    }
  };

  window.br.confirm = function(title, message, buttons, callback) {
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

  window.br.prompt = function(title, fields, callback, options) {
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
              '<input type="text" class="span4" value="' + inputs[i] + '" data-click-on-enter=".action-confirm-close" />';
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
          var results = [];
          $(this).closest('div.modal').find('input[type=text]').each(function() {
            results.push($(this).val());
          });
          callback.call(this, results);
        });
      })
      .on('hide', function(e) {
        dialog.remove();
      });
    $(dialog).modal();
  }

  var noTemplateEngine = false;

  window.br.fetch = function(template, data, tags) {
    data = data || {};
    if (template) {
      if (typeof window.Mustache == 'undefined') {
        if (typeof window.Handlebars == 'undefined') {
          if (!noTemplateEngine) {
            noTemplateEngine = true;
            this.showError('Template engine not found. Please link breeze/3rdparty/mustache.js or breeze/3rdparty/handlebars.js.');
          }
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

  var progressCounter = 0;

  window.br.showProgress = function() {
    progressCounter++;
    $('.ajax-in-progress').css('visibility', 'visible');
  }

  window.br.hideProgress = function() {
    progressCounter--;
    if (progressCounter <= 0) {
      $('.ajax-in-progress').css('visibility', 'hidden');
      progressCounter = 0;
    }
  }

  $(document).ready(function() { 

    var notAuthorized = false;

    $('body').ajaxStart(function() { br.showProgress(); });

    $('body').ajaxStop(function() { br.hideProgress(); });
    
    $('body').ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
      if (jqXHR.status == 401) {
        if (!notAuthorized) {
          notAuthorized = true;
          br.growlError('You are trying to run operation which require authorization.');
        }
      }
    });

    $('input[data-click-on-enter]').live('keypress', function(e) {
      if (e.keyCode == 13) { $($(this).attr('data-click-on-enter')).trigger('click'); }
    });

    if ($('.focused').length > 0) {
      $('.focused')[0].focus();
    }

  });

}(jQuery, window);
