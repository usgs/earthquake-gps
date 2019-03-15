/* global SimplBox */
'use strict';


var Util = require('util/Util');


var _DEFAULTS = {
  animationSpeed: 0,
  imageSize: 1,
  quitOnDocumentClick: true,
  quitOnImageClick: false
};

/**
 * Class for creating a lightbox using free SimplBox Library
 *
 * @param options {Object}
 *  {
 *    el: elem(s) to attach lightbox to
 *    SimplBox options...
 *  }
 */
var Lightbox = function (options) {
  var _this,
      _initialize,

      _addCaption,
      _addCloseButton,
      _addHandlers,
      _addOverlay,
      _addLoadingSpinner,
      _getPosition,
      _removeComponent,
      _showNavButtons,

      _simpleboxId;


  _this = {};

  _initialize = function () {
    var callbacks,
        el,
        simplbox;

    _simpleboxId = SimplBox.options.imageElementId;

    callbacks = {
      onImageLoadEnd: function () {
        document.getElementById(_simpleboxId).classList.add('material-icons');

        _removeComponent('simplbox-loading');
        _addHandlers(simplbox);
        _showNavButtons();
      },
      onImageLoadStart: function () {
        _addLoadingSpinner();
        _addCaption(simplbox);
      },
      onEnd: function () {
        _removeComponent('simplbox-caption');
        _removeComponent('simplbox-close');
        _removeComponent('simplbox-overlay');

        document.body.classList.remove('simplbox');
      },
      onStart: function () {
        _addOverlay();
        _addCloseButton(simplbox);

        document.body.classList.add('simplbox');
      }
    };
    el = options.el;
    options = Util.extend({}, _DEFAULTS, callbacks, options);

    simplbox = new SimplBox(el, options);
    simplbox.init();
  };


  /**
   * Add caption bar above photo
   *
   * @param base {Object}
   */
  _addCaption = function (base) {
    var div,
        fragment,
        title;

    fragment = document.createDocumentFragment();
    div = document.createElement('div');
    title = base.m_Alt.replace(/\(/, '<small>(').replace(/\)/, ')</small>');

    _removeComponent('simplbox-caption');

    div.setAttribute('id', 'simplbox-caption');
    div.innerHTML = title;
    fragment.appendChild(div);
    document.body.appendChild(fragment);
  };

  /**
   * Add close button to caption bar
   *
   * @param base {Object}
   */
  _addCloseButton = function (base) {
    var div;

    div = document.createElement('div');
    div.setAttribute('id', 'simplbox-close');
    div.innerHTML = '<i class="material-icons">&#xE5C9;</i>';
    document.body.appendChild(div);
    base.API_AddEvent(div, 'click touchend', function () {
      base.API_RemoveImageElement();
      return false;
    });
  };

  /**
   * Add handlers for prev / next photo navigation
   *
   * @param base {Object}
   */
  _addHandlers = function (base) {
    var div,
        position;

    div = document.getElementById(_simpleboxId);

    // Navigate to prev / next photo, depending on where user clicked
    base.API_AddEvent(div, 'click touchend', function (e) {
      position = _getPosition(div, e);
      if (position === 'left') {
        base.leftAnimationFunction();
      } else {
        base.rightAnimationFunction();
      }

      return false;
    });

    // Show navigation buttons on photo when user hovers
    div.addEventListener('mousemove', function (e) {
      position = _getPosition(div, e);
      if (position === 'left') {
        div.classList.add('left');
        div.classList.remove('right');
      } else {
        div.classList.add('right');
        div.classList.remove('left');
      }
    });
    div.addEventListener('mouseout', function () {
      div.classList.remove('left', 'right');
    });
  };

  /**
   * Add dark screen overlay
   */
  _addOverlay = function () {
    var div;

    div = document.createElement('div');
    div.setAttribute('id', 'simplbox-overlay');
    document.body.appendChild(div);
  };

  /**
   * Add loading spinner
   */
  _addLoadingSpinner = function () {
    var div1,
        div2;

    div1 = document.createElement('div');
    div2 = document.createElement('div');
    div1.setAttribute('id', 'simplbox-loading');
    div1.appendChild(div2);
    document.body.appendChild(div1);
  };

  /**
   * Get relative mouse position (right or left) over an element
   *
   * @param el {Element}
   * @param evt {Event}
   */
  _getPosition = function (el, evt) {
    var divPos = el.getBoundingClientRect(),
        divX = evt.clientX - divPos.left,
        position;

    if ((el.clientWidth / divX) > 2) {
      position = 'left';
    } else {
      position = 'right';
    }

    return position;
  };

  /**
  * Remove lightbox component from DOM
  *
  * @param id {String}
  *   id of component to remove
  */
  _removeComponent = function (id) {
    var el;

    el = document.getElementById(id);
    if (el) {
      el.parentNode.removeChild(el);
    }
  };

  /**
   * Briefly show navigation buttons on photo to alert user about photo navigation
   */
  _showNavButtons = function () {
    var div;

    div = document.getElementById(_simpleboxId);
    div.classList.add('left');
    div.classList.add('right');

    window.setTimeout(function () {
      div.classList.remove('left');
      div.classList.remove('right');
    }, 1500);
  };


  _initialize();
  options = null;
  return _this;
};

module.exports = Lightbox;
