<?php
require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/blocks/validador/list_invalid_courses.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('invalidcourses', 'block_validador'));
$PAGE->set_heading(get_string('invalidcourses', 'block_validador'));

echo $OUTPUT->header();

class invalid_courses_table extends table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Define las columnas y sus cabeceras
        $this->define_columns([
            'coursename',
            'validationname',
            'activity',
            'timecreated',
            'timemodified'
        ]);
        $this->define_headers([
            get_string('course', 'block_validador'),
            get_string('validation', 'block_validador'),
            get_string('activity', 'block_validador'),
            get_string('timecreated', 'block_validador'),
            get_string('timemodified', 'block_validador')
        ]);

        $this->sortable(true, 'timemodified', SORT_DESC);
        $this->collapsible(false);
        $this->pageable(true);
    }

    /**
     * Genera el enlace para el nombre del curso
     */
    public function col_coursename($values) {
        global $OUTPUT;
        $courseurl = new moodle_url('/course/view.php', ['id' => $values->courseid]);
        return $OUTPUT->action_link($courseurl, $values->coursename);
    }

    /**
     * Genera el enlace para la actividad (si existe)
     */
    public function col_activity($values) {
        global $OUTPUT;

        // Si no hay cmid, simplemente mostramos el texto
        if (empty($values->cmid)) {
            // Podríamos devolver un texto vacío o el nombre del módulo
            // según convenga. Aquí devolvemos el nombre.
            return $values->activity;
        }

        // Si hay cmid, enlazamos a la página de configuración de la actividad
        $activityurl = new moodle_url('/course/modedit.php', [
            'update' => $values->cmid,
            'return' => 1
        ]);
        return $OUTPUT->action_link($activityurl, $values->activity);
    }

    // Formatear la columna timecreated
    public function col_timecreated($values) {
        return userdate($values->timecreated);
    }

    // Formatear la columna timemodified
    public function col_timemodified($values) {
        return userdate($values->timemodified);
    }
}

// Instanciamos nuestra tabla
$table = new invalid_courses_table('invalid-courses-table');

// Establecer la URL base para la tabla
$table->define_baseurl($PAGE->url);

// Definir las partes de la consulta SQL
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

// Definir los parámetros de la consulta
$params = ['contextlevel' => CONTEXT_MODULE];

// Configurar la consulta para la tabla
$table->set_sql($fields, $from, $where, $params);

// Mostrar la tabla
$table->out(10, true);

echo $OUTPUT->footer();