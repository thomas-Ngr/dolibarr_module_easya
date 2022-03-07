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
        if ($first_line == ['name', 'entity', 'value', 'type', 'visible', 'note']) {
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
            try {
                // TODO real filters to prevent SQL and XSS
                $line['name'] = $this->checkNoSpace($line[0]);             // name
                $line['entity'] = $this->checkAndFormatBoolInt($line[1]);     // entity
                $line['value'] = $line[2];                      // value
                $line['type'] = $this->checkNoSpace($line[3]);                           // type -> should check that type exists
                $line['visible'] = $this->checkAndFormatBoolInt($line[4]);     // visible
                $line['note'] = $line[5];                       // note
            } catch (Exception $e) {
                $err_message  = $e->getMessage();
                $err_message .= ' on line '.$key;
                throw new Exception($err_message);
            }
            $this->lines[$key] = $line;
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
    private static $backup_path = '/easya/const_backup';
    private static $bak_file_prefix = 'backup_';
    private $backup_file;
    private $db;
    private $const_list;

    public function __construct($db, $const_list) {
        $this->db = $db;
        $this->const_list = $const_list;

        $date = dol_print_date(dol_now(), 'dayhourxcard');
        //$file_path = self::$backup_path .'/'.self::$bak_file_prefix . $date . '.csv.bak';
        $this->backup_dir = DOL_DATA_ROOT . self::$backup_path;
        $this->backup_file = $this->backup_dir .'/'.self::$bak_file_prefix . $date . '.csv.bak';

        return $this;
    }

    public function backupAndApply() {
        //$this->verifyAndWarn(); // Afficher un warning si entity ou visibility sont différentes !!
        $this->backup();
        $this->apply();
    }

    private function backup() {
        print $this->backup_dir . "\n"; //self::$backup_path; //debug

        // create dir if not exist
        if (!is_dir($this->backup_dir)){
            if (!mkdir($this->backup_dir, '0640', true )) {
                throw new Exception ('Error module Easya: backup dir could not be created.');
            }
        }

        if (($file = fopen($this->backup_file, "x")) !== false) {
            foreach($this->const_list as $const) {
                $line_length = fputcsv($file, array_values($const));
                if (!$line_length) {
                    throw new Exception("Error module Easya: line could not be written in file ".$this->backup_file." : ". array_values($const));
                }
                var_dump($const);
            }
        } else {
            throw new Exception("Error module Easya: file ". $this->backup_file . " already exists or could not be created.");
        }
    }



    private function apply() {
        foreach($this->const_list as $const) {
            var_dump($const);
            $res = dolibarr_set_const($this->db, $const['name'], $const['value'], $const['type'], $const['visible'], $const['note'], $const['entity']);
            if ($res !== 1) {
                throw new Exception("Error module Easya: Constant could not be saved : " . $const);
            }
        }
    }
}

function includeRoot($dir, $search_file) {
    if (is_file($dir . $search_file)) {
        return $dir . $search_file;
    } else {
        $dir = dirname($dir);
        return includeRoot($dir, $search_file);
    }

    //return is_file($search_file) ? $search_file : includeRoot("../" . $search_file);
}

