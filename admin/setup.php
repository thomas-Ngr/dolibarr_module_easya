<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/easya/admin/setup.php
 *		\ingroup    easya
 *		\brief      Page to setup easya module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
dol_include_once('/easya/lib/easya.lib.php');

$langs->load("admin");
$langs->load("easya@easya");
$langs->load("opendsi@easya");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$backup_file_choice = GETPOST('csv_backup', 'int');

/*
 * Constant values
 */
$max_file_size = 3000;


/*
 *	Actions
 */

 /*
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    $value=(GETPOST($code) ? GETPOST($code) : 1);
    if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}
*/

// abort if two values are sent
if (!empty($backup_file_choice) && !empty($_FILES['csv_input']["name"])) {
    $url_to_redirect = $_SERVER['REQUEST_URI'];
    setEventMessage($langs->trans('ManyValuesSent'), 'errors');
    header('Location: '.$url_to_redirect);
    exit(1);
}

// Get backup files 
$backup_files_path = scandir(Constants::$backup_dir);
// remove . and .. dirs
array_shift($backup_files_path);
$backup_files_path[0] = '';

// If user has chosen a backup file
if (!empty($backup_file_choice)) {
    if (!empty($backup_files_path[$backup_file_choice])) {
        $file_to_load = Constants::$backup_dir .'/'. $backup_files_path[$backup_file_choice];
    } else {
        setEventMessage($langs->trans('BackupIndexDoesNotexist'), 'errors');
        $err++;
    }
}

// Deal with uploaded CSV constant files
if (!empty($_FILES['csv_input']["name"])) {
    $csv_input = $_FILES['csv_input'];
    $err = 0;
    if ($csv_input['size'] > $max_file_size) {
        setEventMessage($langs->trans('TooLargeFile'), 'errors');
        $err++;
    }
    if ($csv_input['type'] != "text/csv" ) {
        setEventMessage($langs->trans('PleaseProvideCSV'), 'errors');
        $err++;
    }
    // forbid any php file
    if (preg_match("/<\?php/mi", file_get_contents($csv_input['tmp_name']))) {
        setEventMessage($langs->trans('NoPhpFile'), 'errors');
        $err++;
    }
    //set constants file
    if ($err == 0) $file_to_load = $csv_input['tmp_name'];
}

// Load constants
if ($err == 0 && !empty($file_to_load)) {
    // get content as array
    try {
        $constants_file = new ConstantsCSVInput($file_to_load);
        $constants_values = $constants_file->getConstants();

        if (analyseVarsForSqlAndScriptsInjection($constants_values, 0)) {
            $constants = new Constants($db, $constants_values);
            $constants->backupAndApply();
            setEventMessage($langs->trans('ConstantsApplied'), 'mesgs');
        }
    } catch (Exception $e) {
        setEventMessage($e->getMessage(), 'errors');
    }
}


/*
 *	View
 */


llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("EasyaSetup"),$linkback,'title_setup');
print "<br>\n";

$head=easya_prepare_head();
$form = new Form($db);

dol_fiche_head($head, 'settings', $langs->trans("Module501000Name"), 0, 'action');


print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
//print '<input type="hidden" name="action" value="set">';

//$var=true; // What is this used for !?

print '<table class="noborder centpercent"><tbody>';

print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Configuration").'</td>';
print "</tr>\n";

print '<tr class="oddeven"> <td>';
print '<label for="csv_input" >'.$langs->trans("LoadConfigurationFile").'</label>';
print '</td><td class="right">';
print '<input type="hidden" name="MAX_FILE_SIZE" value="'.$max_file_size.'" />';
print '<input id="csv_input" name="csv_input" type="file" accept=".csv"></input>';
print '</td></tr>';

print '<tr class="oddeven"> <td>';
print '<label for="csv_backup" >'.$langs->trans("LoadBackupFile").'</label>';
print '</td><td class="right">';
print $form->selectarray('csv_backup', $backup_files_path);
print '</td></tr>';

print '<tbody><table>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Load").'">';
print '</div>';

dol_fiche_end();

/*
print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';
*/

print '</form>';

llxFooter();

$db->close();
