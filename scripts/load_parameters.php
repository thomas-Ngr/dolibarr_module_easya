<?php
if (php_sapi_name() != "cli") {
	throw new Error("This script should be executed in CLI. Have a good day.", 400);
	die;
}

// find master.inc.php
$master_inc = 'master.inc.php';
function findRootDir($dir, $master_inc) {
    return is_file($dir . '/' . $master_inc) ? $dir : findRootDir(dirname($dir), $master_inc);
}
$dir = findRootDir(__DIR__, $master_inc);
require_once $dir . '/' . $master_inc;

require_once __DIR__ . '/../lib/easya.lib.php';


// don't execute script if install.lock is set
$conf = new Conf();
$lockfile = DOL_DATA_ROOT.'/install.lock';
if (constant('DOL_DATA_ROOT') === null) {
	// We don't have a configuration file yet
	print "ERROR: DOL_DATA_ROOT is not set. The configuration file does not exist. Please install Dolibarr.";
	exit(1);
}
if (@file_exists($lockfile)) {
	print "The install.lock file is set.\n";
    print "For security reasons, executing this script is forbidden.\n";
    print "Please remove install.lock file before continuing.\n";
	exit(1);
}

/*
 * Load constants file
 */

if (count($argv) !== 2) {
	print("module Easya: ERROR:\nThis script expects a file for constant input.\nPlease provide a csv file.\n");
	exit(1);
}

$constants_file_path = realpath($argv[1]);
if (!$constants_file_path) {
	print "module Easya: ERROR:\nThe provided file does not exist. Please check the file exists.\nProvided file path: " . $argv[1] . "\n";
	exit(1);
}

$constants_file = new ConstantsCSVInput($constants_file_path);
$constants_values = $constants_file->getConstants();

/*
 * Apply constants
 */

$constants = new Constants($db, $constants_values);
$constants->backupAndApply();
print "module Easya: Constants successfuly applied.\n";
