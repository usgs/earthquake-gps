/* global network */ // passed via var embedded in html page

'use strict';

var Icon = require('leaflet/Icon'),
    L = require('leaflet'),
    Util = require('util/Util');

require('leaflet.label');

var _DEFAULTS = {
  alt: 'GPS station'
};

var _SHAPES = {
  campaign: 'triangle',
  continuous: 'square'
};

var _LAYERNAMES = {
  blue: 'Past 3 days',
  yellow: '4&ndash;7 days ago',
  orange: '8&ndash;14 days ago',
  red: 'Over 14 days ago'
};

/**
 * Factory for Stations overlay
 *
 * @param data {String}
 *        contents of geojson file containing stations
 * @param options {Object}
 *        Leaflet Marker options
 *
 * @return {Object}
 *         Leaflet featureGroup {
 *           layers: {Object}
 *           getBounds: {Function}
 *           openPopup: {Function}
 *         }
 */
var StationsLayer = function (data, options) {
  var _this,
      _initialize,

      _bounds,
      _icons,
      _points,

      _getColor,
      _initLayers,
      _onEachFeature,
      _pointToLayer;

  _this = L.featureGroup();

  _initialize = function () {
    options = Util.extend({}, _DEFAULTS, options);

    _bounds = new L.LatLngBounds();
    _icons = {};
    _points = {};

    _initLayers();

    L.geoJson(data, {
      onEachFeature: _onEachFeature,
      pointToLayer: _pointToLayer
    });
  };

  /**
   * Create a layerGroup for each group of stations (classed by age)
   * (also set up a count to keep track of how many stations are in each group)
   */
  _initLayers = function () {
    _this.count = {};
    _this.layers = {};
    _this.names = _LAYERNAMES;
    Object.keys(_LAYERNAMES).forEach(function(key) {
      _this.count[key] = 0;
      _this.layers[key] = L.layerGroup();
      _this.addLayer(_this.layers[key]); // add to featureGroup
    });
  };

  /**
   * Get icon color
   *
   * @param days {Integer}
   *        days since station last updated
   *
   * @return color {String}
   */
  _getColor = function (days) {
    var color = 'red'; //default

    if (days > 14) {
      color = 'red';
    } else if (days > 7) {
      color = 'orange';
    } else if (days > 3) {
      color = 'yellow';
    } else if (days >= 0) {
      color = 'blue';
    }

    return color;
  };

  /**
   * Leaflet GeoJSON option: called on each created feature layer. Useful for
   * attaching events and popups to features.
   */
  _onEachFeature = function (feature, layer) {
    var data,
        label,
        popup,
        popupTemplate;

    data = {
      lat: Math.round(feature.geometry.coordinates[1] * 1000) / 1000,
      lon: Math.round(feature.geometry.coordinates[0] * 1000) / 1000,
      network: network,
      station: feature.properties.station.toUpperCase()
    };
    popupTemplate = '<div class="popup station">' +
        '<h1>Station {station}</h1>' +
        '<span>({lat}, {lon})</span>' +
        '<p>{network}</p>' +
      '</div>';
    popup = L.Util.template(popupTemplate, data);
    label = feature.properties.station.toUpperCase();

    layer.bindPopup(popup).bindLabel(label, {
      pane: 'popupPane'
    });

    // Store point so its popup can be accessed by openPopup()
    _points[data.station] = layer;
  };

  /**
   * Leaflet GeoJSON option: used for creating layers for GeoJSON points
   *
   * @return marker {Object}
   *         Leaflet marker
   */
  _pointToLayer = function (feature, latlng) {
    var color,
        key,
        marker,
        shape;

    color = _getColor(feature.properties.days);
    shape = _SHAPES[feature.properties.type];
    key = shape + '+' + color;

    options.icon = Icon.getIcon(key);
    marker = L.marker(latlng, options);

    // Group stations in separate layers by type
    _this.layers[color].addLayer(marker);
    _this.count[color] ++;

    _bounds.extend(latlng);

    return marker;
  };

  /**
   * Get bounds for station layers
   *
   * @return {Object}
   *         Leaflet latLngBounds
   */
  _this.getBounds = function () {
    return _bounds;
  };

  /**
   * Open popup for a given station
   */
  _this.openPopup = function (station) {
    _points[station].openPopup();
  };

  _initialize();

  return _this;
};

L.stationsLayer = StationsLayer;

module.exports = StationsLayer;
