<?php

// ----------------------------------------------------------------------
// PREAMBLE
//
// Sets up some well-known values for the configuration environment.
//
// Most likely do not need to edit anything in this section.
// ----------------------------------------------------------------------

include_once 'install-funcs.inc.php';

// set default timezone
date_default_timezone_set('UTC');

$OLD_PWD = $_SERVER['PWD'];

// work from lib directory
chdir(dirname($argv[0]));

if ($argv[0] === './pre-install.php' || $_SERVER['PWD'] !== $OLD_PWD) {
  // pwd doesn't resolve symlinks
  $LIB_DIR = $_SERVER['PWD'];
} else {
  // windows doesn't update $_SERVER['PWD']...
  $LIB_DIR = getcwd();
}

$APP_DIR = dirname($LIB_DIR);
$CONF_DIR = $APP_DIR . DIRECTORY_SEPARATOR . 'conf';

$CONFIG_FILE = $CONF_DIR . DIRECTORY_SEPARATOR . 'config.ini';
$APACHE_CONFIG_FILE = $CONF_DIR . DIRECTORY_SEPARATOR . 'httpd.conf';


// ----------------------------------------------------------------------
// CONFIGURATION
//
// Define the configuration parameters necessary in order
// to install/run this application. Some basic parameters are provided
// by default. Ensure that you add matching keys to both the $DEFAULTS
// and $HELP_TEXT arrays so the install process goes smoothly.
//
// This is the most common section to edit.
// ----------------------------------------------------------------------

$DEFAULTS = array(
  'APP_DIR' => $APP_DIR,
  'DATA_DIR' => str_replace('/apps/', '/data/', $APP_DIR),
  'MOUNT_PATH' => '',
  'DATA_HOST' => '',

  'DB_DSN' => 'mysql:host=127.0.0.1;port=3306;dbname=web',
  'DB_USER' => 'web',
  'DB_PASS' => ''
);

$HELP_TEXT = array(
  'APP_DIR' => 'Absolute path to application root directory',
  'DATA_DIR' => 'Absolute path to application data directory',
  'MOUNT_PATH' => 'Url path to application',
  'DATA_HOST' => 'Host where data files are served',

  'DB_DSN' => 'Database connection DSN string',
  'DB_USER' => 'Read-only username for database connections',
  'DB_PASS' => 'Password for database user'
);

// for travis integration
foreach ($argv as $arg) {
  if ($arg === '--non-interactive') {
    define('NON_INTERACTIVE', true);
  }
}
if (!defined('NON_INTERACTIVE')) {
  define('NON_INTERACTIVE', false);
}


// ----------------------------------------------------------------------
// MAIN
//
// Run the interactive configuration and write configuration files to
// to file system (httpd.conf and config.ini).
//
// Edit this section if this application requires additional installation
// steps such as setting up a database schema etc... When editing this
// section, note the helpful install-funcs.inc.php functions that are
// available to you.
// ----------------------------------------------------------------------

include_once 'configure.php';
$MOUNT_PATH = $CONFIG['MOUNT_PATH'];

// output apache configuration
file_put_contents($APACHE_CONFIG_FILE, '
  # auto generated by ' . __FILE__ . ' at ' . date('r') . '
  Alias ' . $MOUNT_PATH . '/data ' . $CONFIG['DATA_DIR'] . '
  Alias ' . $MOUNT_PATH . ' ' . $CONFIG['APP_DIR'] . '/htdocs

  RewriteEngine On

  # Strip trailing slash
  RewriteRule ^' . $MOUNT_PATH . '(.*)/+$ ' . $MOUNT_PATH . '$1 [L,R=301]

  # Prevent apache from adding trailing slash on "real" directories by explicitly requesting index.php
  RewriteRule ^' . $MOUNT_PATH . '$ ' . $MOUNT_PATH . '/index.php [L,PT]

  # Pretty URLs
  RewriteRule ^' . $MOUNT_PATH . '/stations/?([a-z0-9]+)?$ ' .
    $MOUNT_PATH . '/stationlist.php?filter=$1 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)$ ' .
    $MOUNT_PATH . '/network.php?network=$1 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/(([a-zA-Z0-9_-]+)/)?kml(/(last|timespan|years))?$ ' .
    $MOUNT_PATH . '/kml.php?network=$2&sortBy=$4 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/notupdated$ ' .
    $MOUNT_PATH . '/notupdated.php?network=$1 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/velocities$ ' .
    $MOUNT_PATH . '/velocities.php?network=$1 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/waypoints$ ' .
    $MOUNT_PATH . '/waypoints.php?network=$1 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})$ ' .
    $MOUNT_PATH . '/station.php?network=$1&station=$2 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})/kinematic$ ' .
    $MOUNT_PATH . '/kinematic.php?network=$1&station=$2 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})/kinematic/data$ ' .
    $MOUNT_PATH . '/_getKinematic.csv.php?station=$2 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})/logs$ ' .
    $MOUNT_PATH . '/logsheets.php?network=$1&station=$2 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})/offsets$ ' .
    $MOUNT_PATH . '/offsets.php?network=$1&station=$2 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})/photos$ ' .
    $MOUNT_PATH . '/photos.php?network=$1&station=$2 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})/qc$ ' .
    $MOUNT_PATH . '/qc.php?network=$1&station=$2 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})/qc/table$ ' .
    $MOUNT_PATH . '/qctable.php?network=$1&station=$2 [L,PT]
  RewriteRule ^' . $MOUNT_PATH . '/([a-zA-Z0-9_-]+)/([a-zA-Z0-9]{4})/qc/data$ ' .
    $MOUNT_PATH . '/_getQcData.csv.php?network=$1&station=$2 [L,PT]

  <Location ' . $MOUNT_PATH . '>
    Order allow,deny
    Allow from all

    <LimitExcept GET>
      deny from all
    </LimitExcept>

    ExpiresActive on
    ExpiresDefault "access plus 1 days"
  </Location>
');
