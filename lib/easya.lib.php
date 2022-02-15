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
    private $lines;
    private $const_array = [];

    public function __construct($path) {
        $this->file_path = $path;
        $this->read();

        // TODO filter lines input
        //$this->checkLinesAreFine();
    }

    private function read() {
        $this->lines = [];

        if (($file = fopen($this->file_path, "r")) !== false) {
            while (($line = fgetcsv($file)) !== false) {
                $this->lines[] = $line;
            }
        }
    }

    public function getConstants() {
        return $this->lines;
        //return $this->const_array;
    }

    private function check_first_line() {}

    private function line_fields_are_fine($line) {}
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

