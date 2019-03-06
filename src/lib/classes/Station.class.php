<?php

include_once '../conf/config.inc.php'; // app config

/**
 * Model for GPS station
 * - populates fields from db query using magic __set method
 * - also sets up other properites (e.g. links, networkList, and velocities)
 *
 * Station Object (
 *   [links] => Array
 *   [networkList] => Array
 *   [numLogs] => Int
 *   [numPhotos] => Int
 *   [noise] => Array
 *   [offsets] => Array
 *   [postSeismic] => Array
 *   [seasonal] => Array
 *   [stationPath] => String
 *   [velocities] => Array
 *   ... (+ all properties returned from db query)
 * )
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class Station {
  private $_data = array();

  public function __construct (
    $networkList=NULL,
    $rsNoise=NULL,
    $rsOffsets=NULL,
    $rsPostSeismic=NULL,
    $rsSeasonal=NULL,
    $rsVelocities=NULL
  ) {

    $this->_data['stationPath'] = $GLOBALS['MOUNT_PATH'] . '/' . $this->network
      . '/' . $this->station;

    $this->_data['links'] = $this->_getLinkList();
    $this->_data['networkList'] = $networkList;
    $this->_data['numLogs'] = $this->_getNumLogs();
    $this->_data['numPhotos'] = $this->_getNumPhotos();
    $this->_data['noise'] = $rsNoise->fetchAll(PDO::FETCH_ASSOC);
    $this->_data['offsets'] = $rsOffsets->fetchAll(PDO::FETCH_ASSOC);
    $this->_data['postSeismic'] = $rsPostSeismic->fetchAll(PDO::FETCH_ASSOC);
    $this->_data['seasonal'] = $rsSeasonal->fetchAll(PDO::FETCH_ASSOC);
    $this->_data['velocities'] = $rsVelocities->fetchAll(PDO::FETCH_ASSOC);
  }

  public function __get ($name) {
    return $this->_data[$name];
  }

  public function __set ($name, $value) {
    if ($name === 'elevation'
      || $name === 'lat'
      || $name === 'lon'
      || $name === 'showcoords'
      || $name === 'x'
      || $name === 'y'
      || $name === 'z'
    ) {
      $value = floatval($value);
    }
    $this->_data[$name] = $value;
  }

  private function _getLinkList () {
    $kinematic = $this->stationPath . '/kinematic';
    $logs = $this->stationPath . '/logs';
    $ngs = $this->_getNgsLink();
    $photos = $this->stationPath . '/photos';
    $qc = $this->stationPath . '/qc';
    $weather = $this->_getWeatherLink();

    $links = array(
      'Photos' => $photos,
      'Field Logs' => $logs,
      'Quality Control Data' => $qc,
      'Kinematic Data' => $kinematic,
      'Weather' => $weather,
      '<abbr title="National Geodetic Survey">NGS</abbr> Datasheets' => $ngs
    );

    // campaign stations don't have kinematic data; continous don't have photos
    if ($this->stationtype === 'campaign') {
      unset($links['Kinematic Data']);
    } else if ($this->stationtype === 'continuous') {
      unset($links['Photos']);
    }

    return $links;
  }

  private function _getNgsLink () {
    return sprintf(
      'http://www.ngs.noaa.gov/cgi-bin/ds_radius.prl?selectedFormat=Decimal+Degrees&DLatBox=%f&DLonBox=%f&RadBox=0.5&StabilSelected=0&TypeSelected=X-0&SubmitBtn=Submit',
      $this->lat,
      $this->lon * -1 // ngs server 'balks' at negative values for W longitude
    );
  }

  private function _getNumLogs () {
    global $DATA_DIR;

    $dir = sprintf('%s/stations/%s.dir/%s/logsheets',
      $DATA_DIR,
      substr($this->station, 0, 1),
      $this->station
    );
    $files = getDirContents($dir);

    $logSheets = [];
    foreach ($files as $file) {
      preg_match('/^\w{4}(\d{8})[^\d]+/', $file, $matches);
      if (isSet($matches[1])) {
        $logSheets[] = $matches[1];
      }
    }

    return count(array_unique($logSheets));
  }

  private function _getNumPhotos () {
    global $DATA_DIR;

    $dir = sprintf('%s/stations/%s.dir/%s/photos/screen',
      $DATA_DIR,
      substr($this->station, 0, 1),
      $this->station
    );
    $files = getDirContents($dir);

    return count($files);
  }

  private function _getWeatherLink () {
    return sprintf(
      'http://forecast.weather.gov/MapClick.php?textField1=%.4f&textField2=%.4f',
      $this->lat,
      $this->lon
    );
  }
}
