<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 *	\file       htdocs/easya/lib/easya.lib.php
 * 	\ingroup	easya
 *	\brief      Functions for the module easya
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function easya_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/easya/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/easya/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/easya/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'easya_admin');

    return $head;
}

function setConstants($db, $const_array, $backup) {}

class ConstantsCSVInput
{
    public static $LINE_CELL_NUMBER = 5;
    private $file_path;
    private $lines = [];

    public function __construct($path) {
        $this->file_path = $path;
        $this->read();
        $this->checkAndRemoveFirstLine();
        $this->line_fields_are_fine();

        return $this;
    }

    private function read() {
        if (($file = fopen($this->file_path, "r")) !== false) {
            while (($line = fgetcsv($file)) !== false) {
                $this->lines[] = $line;
            }
        }
    }

    public function getConstants() {
        return $this->lines;
    }

    private function checkAndRemoveFirstLine() {
        $first_line = $this->trim_values($this->lines[0]);
        if ($first_line == ['name', 'value', 'type', 'visible', 'note']) {
            array_shift($this->lines);
        }
    }

    private function trim_values($string_array) {
        $new_arr = [];
        foreach($string_array as $string) {
            $new_arr[] = trim($string);
        }
        return $new_arr;
    }

    private function line_fields_are_fine() {
        foreach($this->lines as $key => $line) {
            $line = $this->trim_values($line);
            $line_with_keys = array();
            if (count($line) !== self::$LINE_CELL_NUMBER) {
                throw new Exception("Error: module Easya: Constant line does not have ".self::$LINE_CELL_NUMBER." cells: " .$line[0]);
            }
            try {
                // TODO real filters to prevent SQL and XSS
                $line_with_keys['name'] = $this->checkNoSpace($line[0]);
                $line_with_keys['value'] = ($line[1] === "NULL") ? null : $line[1];
                $line_with_keys['type'] = $this->checkNoSpace($line[2]);
                $line_with_keys['visible'] = $this->checkAndFormatBoolInt($line[3]);
                $line_with_keys['note'] = $line[4];
            } catch (Exception $e) {
                $err_message  = $e->getMessage();
                $err_message .= ' on line '.$key;
                throw new Exception($err_message);
            }
            $this->lines[$key] = $line_with_keys;
        }
    }

    private function checkNoSpace($string) {
        $string = trim($string);
        if (strpos($string, " ") !== false) {
            throw new Exception('moduleEasya: value "'. $string.'" contains a space');
        }
        return $string;
    }

    private function checkAndFormatBoolInt($value) {
        if ($value !== 1 && $value !== 0 && $value !== '1' && $value !== '0') {
            throw new Exception('moduleEasya: "'.$value.'" should be 0 or 1');
        }
        return (int) $value;
    }
}

class Constants
{
    public static $backup_dir = DOL_DATA_ROOT . '/easya/const_backup';
    private static $bak_file_prefix = 'backup_';
    private $backup_file;
    private $db;
    private $const_list;

    public function __construct($db, $const_list) {
        $this->db = $db;
        $this->const_list = $const_list;
        $entity = $this->getConstEntity();

        $date = dol_print_date(dol_now(), "%Y-%m-%d_%H-%M");//'dayhourxcard');
        //$file_path = self::$backup_path .'/'.self::$bak_file_prefix . $date . '.csv.bak';
        //$this->backup_dir = DOL_DATA_ROOT . self::$backup_path;
        $this->backup_file = self::$backup_dir .'/'.self::$bak_file_prefix . "entity-" . $entity ."_" . $date . '.csv.bak';

        // create backup dir if not exist
        if (!is_dir(self::$backup_dir)){
            if (!mkdir(self::$backup_dir, '0640', true )) {
                throw new Exception ('Error module Easya: backup dir could not be created.');
            }
        }

        return $this;
    }

    public function backupAndApply() {
        if (($backup_file = fopen($this->backup_file, "x")) !== false) {
            foreach($this->const_list as $const) {
                $this->checkAndBackupLine($const, $backup_file);
                $this->applyLine($const);
            }
        } else {
            throw new Exception("Error module Easya: file ". $this->backup_file . " already exists or could not be created.");
        }
    }

    private function checkAndBackupLine($const, $backup_file) {
        $entity = $this->getConstEntity();

        // fetch original const
        $sql  = "SELECT *";
        $sql .= " FROM ".MAIN_DB_PREFIX."const";
        $sql .= " WHERE name = '".$this->db->sanitize($const['name'])."'";
        $sql .= " AND entity = " . $entity;

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result) > 1) {
                fclose($backup_file);
                throw new Exception("Error: module Easya: There are two constants named ".$const['name'].". Please fix it.");
            }

            // No need to do a while loop since there is 0 to 1 rows in the result
            if ($obj = $this->db->fetch_object($result)) {
                // compare visible
                if ($obj->visible != $const['visible']) {
                    trigger_error("Warning: module Easya: New constant ".$const['name']."has a different visibility '".$const['visible']."' than original one '".$obj->visible."'.", E_USER_WARNING);
                }

                // backup original const
                $backup_line_arr = [
                    $obj->name,
                    $obj->value,
                    $obj->type,
                    $obj->visible,
                    $obj->note
                ];
            } else {
                // backup a const with current name and NULL value
                $backup_line_arr = array_values($const);
                $backup_line_arr[1] = "NULL";               //value
                $backup_line_arr[2] = "chaine";             //type
            }
            $line_length = fputcsv($backup_file, $backup_line_arr);
            if (!$line_length) {
                fclose($backup_file);
                throw new Exception("Error module Easya: line could not be written in file ".$this->backup_file." : ". $backup_line_arr);
            }
        }
    }

    private function applyLine($const) {
        $entity = $this->getConstEntity();

        $res = dolibarr_set_const($this->db, $const['name'], $const['value'], $const['type'], $const['visible'], $const['note'], $entity);
        if ($res !== 1) {
            throw new Exception("Error module Easya: Constant could not be saved : " . $const);
        }
    }

    private function getConstEntity() {
        global $conf;
        return (php_sapi_name() == "cli") ? 0 : (int) $conf->entity;
    }
}
