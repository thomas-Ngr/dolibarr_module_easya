<?php

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
	// Try to detect any lockfile in the default documents path
	$lockfile = '../../../documents/install.lock';
}
if (@file_exists($lockfile)) {
	print "The install.lock file is set.\n";
    print "For security reasons, executing this script is forbidden.\n";
    print "Please remove install.lock file before continuing.\n";
	exit(1);
}

print __DIR__ . "\n";
print DOL_DOCUMENT_ROOT . "\n";

/*
 * Load constants file
 */
$constants_file_path = realpath(__DIR__ . "/../constants_preset.csv");
var_dump ($constants_file_path);

$constants_file = new ConstantsCSVInput($constants_file_path);
$constants = $constants_file->getConstants();
var_dump($constants);

// dolibarr_set_const($db, $name, $value, $type = 'chaine', $visible = 0, $note = '', $entity = 1)

/*
 * 
 */

