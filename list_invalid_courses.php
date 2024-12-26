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

/**
 * Clase de la tabla donde se personalizan columnas y cómo se muestran.
 */
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
     * Genera el enlace para el nombre del curso.
     */
    public function col_coursename($values) {
        global $OUTPUT;
        $courseurl = new moodle_url('/course/view.php', ['id' => $values->courseid]);
        return $OUTPUT->action_link($courseurl, $values->coursename);
    }

    /**
     * Genera el enlace para la actividad (si existe).
     */
    public function col_activity($values) {
        global $OUTPUT;
        // Si no hay cmid, simplemente mostramos el texto de la actividad.
        if (empty($values->cmid)) {
            return $values->activity;
        }
        // Si hay cmid, enlazamos a la página de configuración de la actividad.
        $activityurl = new moodle_url('/course/modedit.php', [
            'update' => $values->cmid,
            'return' => 1
        ]);
        return $OUTPUT->action_link($activityurl, $values->activity);
    }

    public function col_timecreated($values) {
        return userdate($values->timecreated);
    }

    public function col_timemodified($values) {
        return userdate($values->timemodified);
    }
}

// ========================================
// 1. OBTENEMOS TODAS LAS VALIDACIONES
// ========================================
global $DB;

// Obtenemos un array de nombres de validación (distintos)
$sqlvalidations = "SELECT DISTINCT validationname
                   FROM {block_validador_results}
                   WHERE validationname <> ''
                   ORDER BY validationname";
$validations = $DB->get_records_sql($sqlvalidations);

// Creamos un array para el <select> (clave => texto)
$selectoptions = [];
$selectoptions[''] = get_string('all'); // O "Todas" si prefieres
foreach ($validations as $val) {
    $selectoptions[$val->validationname] = $val->validationname;
}

// ========================================
// 2. PROCESAMOS EL PARÁMETRO DE BUSQUEDA
// ========================================
$selectedvalidation = optional_param('validation', '', PARAM_TEXT);

// ========================================
// 3. MOSTRAMOS EL FORMULARIO DE FILTRO
// ========================================
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url]);
echo html_writer::start_tag('div');

// Selector de validaciones
echo html_writer::select(
    $selectoptions,       // opciones de <select>
    'validation',         // nombre del campo
    $selectedvalidation,  // valor seleccionado actual
    false                 // opción nula, la omitimos porque ya añadimos la '' = ALL
);

// Botón de envío
echo html_writer::empty_tag('input', [
    'type'  => 'submit',
    'value' => get_string('search')
]);

echo html_writer::end_tag('div');
echo html_writer::end_tag('form');

// ========================================
// 4. CONFIGURAMOS LA TABLA Y EL FILTRO
// ========================================
$table = new invalid_courses_table('invalid-courses-table');
$table->define_baseurl($PAGE->url);

// Definimos los campos, from y where de la consulta principal.
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

// Empezamos el WHERE con la condición de "passed=0"
$where = "v.passed = 0";
$params = ['contextlevel' => CONTEXT_MODULE];

// Si el usuario ha seleccionado una validación, filtramos por ella.
if (!empty($selectedvalidation)) {
    $where .= " AND v.validationname = :val";
    $params['val'] = $selectedvalidation;
}

// Ahora establecemos la consulta final en la tabla
$table->set_sql($fields, $from, $where, $params);

// ========================================
// 5. MOSTRAMOS LA TABLA
// ========================================
$table->out(10, true);

echo $OUTPUT->footer();