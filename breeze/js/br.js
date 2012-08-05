// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

!function ($, window, undefined) {

  window.br = window.br || {};

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

  window.br.baseUrl = baseUrl;

  window.br.log = function(msg) {
    if (typeof(console) != 'undefined') {
      console.log(msg);
    }
  };

  window.br.isTouchScreen = function() {
    var ua = navigator.userAgent;
    return /iPad/i.test(ua) || /iPhone/i.test(ua) || /Android/i.test(ua);
  };

  window.br.isiOS = function() {
    var ua = navigator.userAgent;
    return /iPad/i.test(ua) || /iPhone/i.test(ua);
  };

  window.br.redirect = function(url) {
    if ((url.search(/^\//) == -1) && (url.search(/^http[s]?:\/\//) == -1)) {
      url = this.baseUrl + url;
    }
    document.location = url;
  };

  window.br.refresh = function() {
    document.location = document.location;
  };

  window.br.preloadImages = function(images) {
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

  window.br.randomInt = function(min, max) {
    if (max == undefined) {
      max = min;
      min = 0;
    }
    return Math.floor(Math.random() * (max - min + 1)) + min;
  };

  window.br.forHtml = function(text) {
    if (text) {
      text = text.split('<').join('&lt;').split('>').join('&gt;');
    }
    return text;
  };

  window.br.extend = function(Child, Parent) {
    var F = function() { };
    F.prototype = Parent.prototype;
    Child.prototype = new F();
    Child.prototype.constructor = Child;
    Child.superclass = Parent.prototype;
  };

  window.br.toInt = function(value) {
    if (typeof value == 'string') {
      if (value.length > 0) {
        return parseInt(value);
      }
    }
    //return null;
  };

  window.br.toReal = function(value) {
    if (typeof value == 'string') {
      if (value.length > 0) {
        return parseFloat(value);
      }
    }
    //return null;
  };

  window.br.openPopup = function(url, w, h) {

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

  var modifiedTimeout;

  window.br.modifiedDeferred = function(selector, callback) {
    $(selector).live('change', function() {
      var $this = $(this);
      window.clearTimeout(modifiedTimeout);
      modifiedTimeout = window.setTimeout(function() {
        if ($this.data('br-last-change') != $this.val()) {
          $this.data('br-last-change', $this.val());
          callback.call($this);
        }
      }, 500);
    });
    $(selector).live('keyup', function(e) {
      if ((e.keyCode == 8) || (e.keyCode == 32) || ((e.keyCode >= 48) && (e.keyCode <= 90)) || ((e.keyCode >= 96) && (e.keyCode <= 111)) || ((e.keyCode >= 186) && (e.keyCode <= 222))) {
        var $this = $(this);
        window.clearTimeout(modifiedTimeout);
        modifiedTimeout = window.setTimeout(function() {
          if ($this.data('br-last-change') != $this.val()) {
            $this.data('br-last-change', $this.val());
            callback.call($this);
          }
          // callback.call($this);
        }, 500);
      }
    });
  }

  window.br.modified = function(selector, callback) {
    $(selector).live('change', function() {
      if ($(this).data('br-last-change') != $(this).val()) {
        $(this).data('br-last-change', $(this).val());
        callback.call(this);
      }
    });
    $(selector).live('keyup', function(e) {
      if ((e.keyCode == 8) || (e.keyCode == 32) || ((e.keyCode >= 48) && (e.keyCode <= 90)) || ((e.keyCode >= 96) && (e.keyCode <= 111)) || ((e.keyCode >= 186) && (e.keyCode <= 222))) {
        if ($(this).data('br-last-change') != $(this).val()) {
          $(this).data('br-last-change', $(this).val());
          callback.call(this);
        }
      }
    });
  }

  window.br.closeConfirmationMessage = 'Some changes have been made. Are you sure you want to close current window?';

  function breezeConfirmClose() {
    return closeConfirmationMessage;
  }

  window.br.confirmClose = function(message) {
    if (message) {
      closeConfirmationMessage = message;
    }
    window.onbeforeunload = breezeConfirmClose;
  }

  window.br.resetCloseConfirmation = function(message) {
    window.onbeforeunload = null;
  }

}(jQuery, window);
