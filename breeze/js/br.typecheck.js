// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

!function (window, undefined) {

  window.br = window.br || {};

  window.br.isNumber = function(value) {
    return (!isNaN(parseFloat(value)) && isFinite(value));
  }

  window.br.isArray = function (value) {
    return (!br.isNull(value) && (Object.prototype.toString.call(value) === '[object Array]'));
  }

  window.br.isObject = function (value) {
    return (!br.isEmpty(value) && (typeof value == 'object'));
  }

  window.br.isBoolean = function (value) {
    return (typeof value == 'boolean');
  }

  window.br.isString = function (value) {
    return (typeof value == 'string');
  }

  window.br.isNull = function(value) {
    return (
             (value === undefined) || 
             (value === null) 
           );
  }

  window.br.isEmpty = function(value) {
    return ( 
             br.isNull(value) || 
             ((typeof value.length != 'undefined') && (value.length == 0)) // Array, String
           );
  }

}(window);