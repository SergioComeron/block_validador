<?php

require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_login();

// Validar que el usuario tenga la capacidad de gestionar.
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// URL de la página
$PAGE->set_url('/blocks/validador/list_invalid_courses.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('invalidcourses', 'block_validador'));
$PAGE->set_heading(get_string('invalidcourses', 'block_validador'));

// Recuperar parámetros para la acción de eliminar.
$resultid = optional_param('resultid', 0, PARAM_INT);
$confirm  = optional_param('confirm', 0, PARAM_BOOL);

// Procesar eliminación si se solicita.
if ($resultid && $confirm && confirm_sesskey()) {
    $DB->delete_records('block_validador_results', ['id' => $resultid]);
    redirect($PAGE->url, get_string('delete_success', 'block_validador'), 2);
}

echo $OUTPUT->header();

/**
 * Clase de la tabla personalizada.
 */
class invalid_courses_table extends table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'coursename',
            'validationname',
            'activity',
            'timecreated',
            'timemodified',
            'editingteachers', // Nueva columna
            'deleteaction'
        ]);

        $this->define_headers([
            get_string('course', 'block_validador'),
            get_string('validation', 'block_validador'),
            get_string('activity', 'block_validador'),
            get_string('timecreated', 'block_validador'),
            get_string('timemodified', 'block_validador'),
            get_string('editingteachers', 'block_validador'), // Encabezado de la nueva columna
            get_string('delete')
        ]);

        $this->sortable(true, 'timemodified', SORT_DESC);
        $this->collapsible(false);
        $this->pageable(true);
    }

    public function col_coursename($values) {
        global $OUTPUT;
        $courseurl = new moodle_url('/course/view.php', ['id' => $values->courseid]);
        return $OUTPUT->action_link($courseurl, $values->coursename);
    }

    public function col_activity($values) {
        global $OUTPUT;
        if (empty($values->cmid)) {
            return $values->activity;
        }
        $activityurl = new moodle_url('/course/modedit.php', ['update' => $values->cmid, 'return' => 1]);
        return $OUTPUT->action_link($activityurl, $values->activity);
    }

    public function col_timecreated($values) {
        return userdate($values->timecreated);
    }

    public function col_timemodified($values) {
        return userdate($values->timemodified);
    }

    public function col_deleteaction($values) {
        global $OUTPUT, $PAGE;
        $deleteurl = new moodle_url($PAGE->url, [
            'resultid' => $values->resultid,
            'confirm' => 1,
            'sesskey' => sesskey()
        ]);
        return $OUTPUT->single_button($deleteurl, get_string('delete'), 'post');
    }

    public function col_editingteachers($values) {
        global $DB;

        $sql = "
            SELECT u.firstname, u.lastname
            FROM {role_assignments} ra
            JOIN {user} u ON ra.userid = u.id
            JOIN {context} ctx ON ra.contextid = ctx.id
            WHERE ctx.contextlevel = :contextlevel
              AND ctx.instanceid = :courseid
              AND ra.roleid = :roleid
        ";
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'courseid' => $values->courseid,
            'roleid' => 3 // ID por defecto del rol editingteacher
        ];
        $teachers = $DB->get_records_sql($sql, $params);

        if (empty($teachers)) {
            return get_string('noteachers', 'block_validador');
        }

        $teacher_names = array_map(function($t) {
            return $t->firstname . ' ' . $t->lastname;
        }, $teachers);

        return implode(', ', $teacher_names);
    }
}

// ========================================
// Configuración de la tabla y consulta SQL
// ========================================
global $DB;

$table = new invalid_courses_table('invalid-courses-table');
$table->define_baseurl($PAGE->url);

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

$where = "v.passed = 0";
$params = ['contextlevel' => CONTEXT_MODULE];

$table->set_sql($fields, $from, $where, $params);

// Renderizar la tabla.
$table->out(10, true);

echo $OUTPUT->footer();