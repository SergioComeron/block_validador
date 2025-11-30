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
 * Settings for validador block
 * @package   block_validador
 * @copyright  2024 Sergio Comerón (info@sergiocomeron.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Obtener todas las categorías de cursos
    $categories = core_course_category::make_categories_list();

    $settings->add(new admin_setting_configmultiselect(
        'block_validador/showcategories',
        get_string('showcategories', 'block_validador'),
        get_string('showcategories_desc', 'block_validador'),
        [],
        $categories
    ));

    // Configuración de fecha mínima de creación de grupos
    $settings->add(new admin_setting_configdate(
        'block_validador/min_group_timecreated',
        get_string('min_group_timecreated', 'block_validador'),
        get_string('min_group_timecreated_desc', 'block_validador'),
        1738337214
    ));

    // Añadir enlace a la página list_invalid_courses.php
    $settings->add(new admin_setting_heading(
        'block_validador/linktoinvalidcourses',
        get_string('linktoinvalidcourses', 'block_validador'),
        html_writer::link(new moodle_url('/blocks/validador/list_invalid_courses.php'), get_string('linktoinvalidcourses_desc', 'block_validador'))
    ));
}