<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * 
 * Settings for Jitsi instances
 * @package   block_validador
 * @copyright  2024 Sergio Comerón (info@sergiocomeron.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('block_validador', get_string('pluginname', 'block_validador'));

    // Obtener todas las categorías de cursos
    $categories = core_course_category::make_categories_list();

    $settings->add(new admin_setting_configmultiselect(
        'block_validador/showcategories',
        get_string('showcategories', 'block_validador'),
        get_string('showcategories_desc', 'block_validador'),
        [],
        $categories
    ));

    $ADMIN->add('blocksettings', $settings);
}