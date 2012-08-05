// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

!function (window, undefined) {

  window.br = window.br || {};

  window.br.request = new BrRequest();

  function BrRequest() {

    this.continueRoute = true;
    this.get = function(name, defaultValue) {
      var vars = document.location.search.replace('?', '').split('&');
      for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        if (pair[0] == name) {
          return unescape(pair[1]);
        }
      }
      return defaultValue;
    };
    this.anchor = function(defaultValue) {
      var value = document.location.hash.replace('#', '');
      if (value.length == 0) {
        value = defaultValue;
      }
      return value;
    };
    this.route = function(path, func) {
      if (this.continueRoute) {
        var l = document.location.toString();
        l = l.replace(/[?].*/, '');
        if (l.search(path) != -1) {
          this.continueRoute = false;
          func.call();
        }
      }
      return this;
    }

  }

}(window);
