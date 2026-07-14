<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin page listing courses with failed validations.
 *
 * @package   block_validador
 * @copyright 2024, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// El plugin puede estar symlinkeado: __DIR__ y '..' resolverían el symlink y
// caerían fuera del árbol de Moodle, así que se recorta SCRIPT_FILENAME.
require_once(dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php');
require_once($CFG->libdir . '/tablelib.php');
require_login();

// Validar que el usuario tenga la capacidad de gestionar.
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$editingteacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

// URL de la página
$PAGE->set_url('/blocks/validador/list_invalid_courses.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('invalidcourses', 'block_validador'));
$PAGE->set_heading(get_string('invalidcourses', 'block_validador'));

// Recuperar parámetros para la acción de eliminar.
$resultid = optional_param('resultid', 0, PARAM_INT);
$confirm  = optional_param('confirm', 0, PARAM_BOOL);

// Verificar si se solicita exportar a CSV.
$exportcsv = optional_param('exportcsv', 0, PARAM_BOOL);
$exportsummarycsv = optional_param('exportsummarycsv', 0, PARAM_BOOL);

// Recuperar parámetro para filtrar por curso.
$filtercourse = optional_param('filtercourse', 0, PARAM_INT);

// Recuperar parámetro para eliminar registros de un curso.
$deletecourse = optional_param('deletecourse', 0, PARAM_INT);

// Recuperar parámetro para ignorar registros de un curso.
$ignorecourse = optional_param('ignorecourse', 0, PARAM_INT);

$page             = optional_param('page', 0, PARAM_INT);
$perpage          = 20;
$filtervalidation = optional_param('filtervalidation', '', PARAM_ALPHANUMEXT);

// Procesar limpieza completa si se solicita.
$cleanall = optional_param('cleanall', 0, PARAM_BOOL);
if ($cleanall && confirm_sesskey()) {
    $DB->delete_records_select('block_validador_results', 'passed = 0');
    redirect($PAGE->url, get_string('delete_success', 'block_validador'), 2);
}

// Procesar eliminación si se solicita.
if ($resultid && $confirm && confirm_sesskey()) {
    $DB->delete_records('block_validador_results', ['id' => $resultid]);
    redirect($PAGE->url, get_string('delete_success', 'block_validador'), 2);
}

if ($deletecourse && confirm_sesskey()) {
    // Eliminar todos los registros del curso especificado.
    $DB->delete_records('block_validador_results', ['courseid' => $deletecourse]);
    redirect($PAGE->url, get_string('delete_success', 'block_validador'), 2);
}

if ($ignorecourse && confirm_sesskey()) {
    // Actualizar todos los registros del curso especificado, asignando passed = 2.
    $DB->set_field('block_validador_results', 'passed', 2, ['courseid' => $ignorecourse]);
    redirect($PAGE->url, get_string('delete_success', 'block_validador'), 2);
}

// Configuración de campos y consulta SQL para la tabla.
$fields = "
    v.id AS resultid,
    c.id AS courseid,
    c.fullname AS coursename,
    v.validationname,
    v.timecreated,
    v.timemodified,
    cm.id AS cmid,
    m.name AS activity
";

$from = "
    {block_validador_results} v
    JOIN {course} c ON v.courseid = c.id
    LEFT JOIN {context} ctx ON ctx.id = v.contextid AND ctx.contextlevel = :contextlevel
    LEFT JOIN {course_modules} cm ON cm.id = ctx.instanceid
    LEFT JOIN {modules} m ON cm.module = m.id
";

$where = "v.passed = 0 AND c.visible = 1";
$params = ['contextlevel' => CONTEXT_MODULE];

if (!empty($filtercourse)) {
    $where .= " AND v.courseid = :filtercourse";
    $params['filtercourse'] = $filtercourse;
}
if (!empty($filtervalidation)) {
    $where .= " AND v.validationname = :filtervalidation";
    $params['filtervalidation'] = $filtervalidation;
}

// Consulta para agrupar por curso.
$groupwhere  = "v.passed = 0 AND c.visible = 1";
$groupparams = [];
if (!empty($filtervalidation)) {
    $groupwhere .= " AND v.validationname = :filtervalidation";
    $groupparams['filtervalidation'] = $filtervalidation;
}

if ($CFG->dbtype == 'pgsql') {
    $sql = "
        SELECT
            c.id AS courseid,
            c.fullname AS coursename,
            string_agg(v.validationname, ', ') AS errors,
            COUNT(v.id) AS errorcount
        FROM {block_validador_results} v
        JOIN {course} c ON v.courseid = c.id
        WHERE $groupwhere
        GROUP BY c.id, c.fullname
        ORDER BY errorcount DESC
    ";
} else {
    $sql = "
        SELECT
            c.id AS courseid,
            c.fullname AS coursename,
            GROUP_CONCAT(v.validationname SEPARATOR ', ') AS errors,
            COUNT(v.id) AS errorcount
        FROM {block_validador_results} v
        JOIN {course} c ON v.courseid = c.id
        WHERE $groupwhere
        GROUP BY c.id, c.fullname
        ORDER BY errorcount DESC
    ";
}
$courses     = $DB->get_records_sql($sql, $groupparams);
$totalcourses = count($courses);
$pagedcourses = array_slice($courses, $page * $perpage, $perpage, true);

// Pre-cargar profesores de todos los cursos en una sola query.
$teachersbycourse = [];
if (!empty($courses)) {
    [$insql, $inparams] = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED, 'cid');
    $inparams['contextlevel'] = CONTEXT_COURSE;
    $inparams['roleid']       = $editingteacherroleid;
    $teacherrows = $DB->get_records_sql("
        SELECT ra.id, ctx.instanceid AS courseid, u.firstname, u.lastname
          FROM {role_assignments} ra
          JOIN {user} u ON ra.userid = u.id
          JOIN {context} ctx ON ra.contextid = ctx.id
         WHERE ctx.contextlevel = :contextlevel
           AND ra.roleid = :roleid
           AND ctx.instanceid $insql
    ", $inparams);
    foreach ($teacherrows as $row) {
        $teachersbycourse[$row->courseid][] = $row->firstname . ' ' . $row->lastname;
    }
}

// Exportar datos a CSV si se solicita.
if ($exportcsv) {
    $filename = clean_filename(get_string('invalidcourses', 'block_validador') . '_' . date('Ymd_His') . '.csv');

    // Obtener los datos usando la consulta definida.
    $rs = $DB->get_recordset_sql("
        SELECT $fields
        FROM $from
        WHERE $where
    ", $params);

    // Generar el archivo CSV.
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Encabezados del CSV.
    fputcsv($output, [
        get_string('course', 'block_validador'),
        get_string('courselink', 'block_validador'), // Nueva columna para el enlace
        get_string('validation', 'block_validador'),
        get_string('activity', 'block_validador'),
        get_string('timecreated', 'block_validador'),
        get_string('timemodified', 'block_validador'),
    ]);

    // Agregar filas de datos.
    foreach ($rs as $record) {
        $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $record->courseid;
        fputcsv($output, [
            $record->coursename,
            $courseurl,
            $record->validationname,
            $record->activity,
            userdate($record->timecreated),
            userdate($record->timemodified),
        ]);
    }
    $rs->close();

    fclose($output);
    exit;
}

// Exportar resumen a CSV si se solicita.
if ($exportsummarycsv) {
    $filename = clean_filename(get_string('invalidcourses_summary', 'block_validador') . '_' . date('Ymd_His') . '.csv');

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Encabezados del CSV.
    fputcsv($output, [
        get_string('course', 'block_validador'),
        get_string('courselink', 'block_validador'), // Nueva columna para el enlace
        get_string('invalidcount', 'block_validador'),
        get_string('editingteachers', 'block_validador'),
    ]);

    foreach ($courses as $course) {
        $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $course->courseid;
        $teachersstr = !empty($teachersbycourse[$course->courseid])
            ? implode(', ', $teachersbycourse[$course->courseid])
            : get_string('noteachers', 'block_validador');

        fputcsv($output, [
            $course->coursename,
            $courseurl,
            $course->errorcount,
            $teachersstr ?: get_string('noteachers', 'block_validador'),
        ]);
    }
    fclose($output);
    exit;
}

// Obtener el número total de validaciones erróneas (solo cursos visibles).
$totalinvalidations = $DB->count_records_sql(
    "SELECT COUNT(v.id)
     FROM {block_validador_results} v
     JOIN {course} c ON v.courseid = c.id
     WHERE v.passed = 0 AND c.visible = 1"
);

echo $OUTPUT->header();

// Mostrar totales.
echo $OUTPUT->heading(get_string('totalcoursesinvalid', 'block_validador') . ': ' . $totalcourses, 4);
echo $OUTPUT->heading(get_string('totalinvalidations', 'block_validador') . ': ' . $totalinvalidations, 4);

// Botones de acción
$exportcsvurl = new moodle_url($PAGE->url, ['exportcsv' => 1]);
echo $OUTPUT->single_button($exportcsvurl, get_string('exportcsv', 'block_validador'));

$exportsummarycsvurl = new moodle_url($PAGE->url, ['exportsummarycsv' => 1]);
echo $OUTPUT->single_button($exportsummarycsvurl, get_string('exportsummarycsv', 'block_validador'));

// Botón para limpiar todos los registros.
$cleanallurl = new moodle_url($PAGE->url, ['cleanall' => 1, 'sesskey' => sesskey()]);
echo $OUTPUT->single_button($cleanallurl, 'Limpiar todos los registros', 'post');

/**
 * Renderiza una lista de nombres de profesores como celda HTML compacta.
 * Con 1 nombre lo muestra directamente; con más, colapsa el resto bajo un <details>.
 * @package block_validador
 */
function format_teachers_html(array $names): string {
    if (empty($names)) {
        return get_string('noteachers', 'block_validador');
    }
    if (count($names) === 1) {
        return s($names[0]);
    }
    $first = s(array_shift($names));
    $rest  = implode('<br>', array_map('s', $names));
    return $first . '<br><details><summary>+' . count($names) . ' más</summary>' . $rest . '</details>';
}

// Cargar tipos de error distintos para el selector.
$validationnames = $DB->get_fieldset_sql(
    "SELECT DISTINCT validationname FROM {block_validador_results} WHERE passed = 0 ORDER BY validationname"
);

// Formulario de filtro por tipo de error.
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url, 'class' => 'mb-3']);
echo html_writer::start_tag('div', ['class' => 'd-flex align-items-center gap-2']);
echo html_writer::tag('label', 'Filtrar por error:', ['for' => 'filtervalidation', 'class' => 'mb-0']);
$options = ['' => 'Todos'];
foreach ($validationnames as $vname) {
    $options[$vname] = $vname;
}
echo html_writer::select($options, 'filtervalidation', $filtervalidation, false, ['id' => 'filtervalidation']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Filtrar', 'class' => 'btn btn-primary btn-sm']);
if (!empty($filtervalidation)) {
    echo html_writer::link($PAGE->url, 'Quitar filtro', ['class' => 'btn btn-secondary btn-sm']);
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('form');

// Mostrar tabla resumen.
echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('course', 'block_validador'));
echo html_writer::tag('th', get_string('invalidcount', 'block_validador'));
echo html_writer::tag('th', 'Errores');
echo html_writer::tag('th', get_string('editingteachers', 'block_validador'));
echo html_writer::tag('th', get_string('details'));
echo html_writer::tag('th', 'Borrar registros'); // Botón existente para eliminar registros
echo html_writer::tag('th', 'Obviar');           // Nueva cabecera para obviar el curso
echo html_writer::end_tag('tr');

foreach ($pagedcourses as $course) {
    $teachersstr = format_teachers_html($teachersbycourse[$course->courseid] ?? []);

    $detailurl    = new moodle_url($PAGE->url, ['filtercourse' => $course->courseid, 'filtervalidation' => $filtervalidation]);
    $deleteurl    = new moodle_url($PAGE->url, ['deletecourse' => $course->courseid, 'sesskey' => sesskey()]);
    $ignoreurl    = new moodle_url($PAGE->url, ['ignorecourse' => $course->courseid, 'sesskey' => sesskey()]);
    $deletebutton = $OUTPUT->single_button($deleteurl, get_string('delete'), 'post');
    $ignorebutton = $OUTPUT->single_button($ignoreurl, 'Obviar', 'post');

    echo html_writer::start_tag('tr');
    $courseurl = new moodle_url('/course/view.php', ['id' => $course->courseid]);
    echo html_writer::tag('td', html_writer::link($courseurl, $course->coursename));
    echo html_writer::tag('td', $course->errorcount);
    echo html_writer::tag('td', $course->errors);
    echo html_writer::tag('td', $teachersstr);
    echo html_writer::tag('td', html_writer::link($detailurl, get_string('details')));
    echo html_writer::tag('td', $deletebutton);
    echo html_writer::tag('td', $ignorebutton);
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('table');

$pagingurl = new moodle_url($PAGE->url, ['filtervalidation' => $filtervalidation]);
echo $OUTPUT->paging_bar($totalcourses, $page, $perpage, $pagingurl);
echo html_writer::empty_tag('br');

/**
 * Clase de la tabla personalizada.
 * @package block_validador
 */
class invalid_courses_table extends table_sql {
    /**
     * Initialises the table columns and headers.
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'coursename',
            'validationname',
            'activity',
            'timecreated',
            'timemodified',
            'editingteachers',
            'deleteaction',
        ]);

        $this->define_headers([
            get_string('course', 'block_validador'),
            get_string('validation', 'block_validador'),
            get_string('activity', 'block_validador'),
            get_string('timecreated', 'block_validador'),
            get_string('timemodified', 'block_validador'),
            get_string('editingteachers', 'block_validador'),
            get_string('delete'),
        ]);

        $this->sortable(true, 'timemodified', SORT_DESC);
        $this->collapsible(false);
        $this->pageable(true);
    }

    /**
     * Renders the course name column as a link.
     */
    public function col_coursename($values) {
        global $OUTPUT;
        $courseurl = new moodle_url('/course/view.php', ['id' => $values->courseid]);
        return $OUTPUT->action_link($courseurl, $values->coursename);
    }

    /**
     * Renders the activity column as a link to the course module edit page.
     */
    public function col_activity($values) {
        global $OUTPUT;
        if (empty($values->cmid)) {
            return $values->activity;
        }
        $activityurl = new moodle_url('/course/modedit.php', ['update' => $values->cmid, 'return' => 1]);
        return $OUTPUT->action_link($activityurl, $values->activity);
    }

    /**
     * Renders the creation date column.
     */
    public function col_timecreated($values) {
        return userdate($values->timecreated);
    }

    /**
     * Renders the last modified date column.
     */
    public function col_timemodified($values) {
        return userdate($values->timemodified);
    }

    /**
     * Renders the delete action button column.
     */
    public function col_deleteaction($values) {
        global $OUTPUT, $PAGE;
        $deleteurl = new moodle_url($PAGE->url, [
            'resultid' => $values->resultid,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]);
        return $OUTPUT->single_button($deleteurl, get_string('delete'), 'post');
    }

    /**
     * Renders the editing teachers column.
     */
    public function col_editingteachers($values) {
        global $DB;
        static $roleid = null;
        static $cache = [];

        if ($roleid === null) {
            $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        }

        if (!array_key_exists($values->courseid, $cache)) {
            $teachers = $DB->get_records_sql("
                SELECT u.id, u.firstname, u.lastname
                  FROM {role_assignments} ra
                  JOIN {user} u ON ra.userid = u.id
                  JOIN {context} ctx ON ra.contextid = ctx.id
                 WHERE ctx.contextlevel = :contextlevel
                   AND ctx.instanceid = :courseid
                   AND ra.roleid = :roleid
            ", ['contextlevel' => CONTEXT_COURSE, 'courseid' => $values->courseid, 'roleid' => $roleid]);

            $names = empty($teachers)
                ? []
                : array_map(fn($t) => $t->firstname . ' ' . $t->lastname, $teachers);
            $cache[$values->courseid] = format_teachers_html($names);
        }

        return $cache[$values->courseid];
    }
}

// Tabla de detalle: solo se muestra cuando se filtra por un curso concreto.
if (!empty($filtercourse)) {
    $coursename = $DB->get_field('course', 'fullname', ['id' => $filtercourse]);
    echo $OUTPUT->heading($coursename, 3);
    echo html_writer::link($PAGE->url, '← ' . get_string('back'), ['class' => 'btn btn-secondary btn-sm mb-2']);

    $table = new invalid_courses_table('invalid-courses-table');
    $table->define_baseurl($PAGE->url);
    $table->set_sql($fields, $from, $where, $params);
    $table->out(10, true);
}

echo $OUTPUT->footer();
