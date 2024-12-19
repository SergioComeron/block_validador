<?php
require_once('../../config.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/blocks/validador/list_invalid_courses.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('invalidcourses', 'block_validador'));
$PAGE->set_heading(get_string('invalidcourses', 'block_validador'));

echo $OUTPUT->header();

global $DB;

// Asegurarse de que CONTEXT_MODULE está definido
// if (!defined('CONTEXT_MODULE')) {
//     define('CONTEXT_MODULE', 70);
// }

// Consulta para obtener cursos y actividades con validaciones erróneas.
$sql = "SELECT 
            v.id AS resultid, 
            c.id AS courseid, 
            c.fullname AS coursename, 
            v.validationname, 
            v.timecreated, 
            v.timemodified, 
            cm.id AS cmid, 
            m.name AS modulename
        FROM {block_validador_results} v
        JOIN {course} c ON v.courseid = c.id
        LEFT JOIN {context} ctx ON ctx.id = v.contextid AND ctx.contextlevel = :contextlevel
        LEFT JOIN {course_modules} cm ON cm.id = ctx.instanceid
        LEFT JOIN {modules} m ON cm.module = m.id
        WHERE v.passed = 0";

$params = ['contextlevel' => CONTEXT_MODULE];
$results = $DB->get_records_sql($sql, $params);

if (!$results) {
    echo $OUTPUT->notification(get_string('nocoursesfound', 'block_validador'), 'notifymessage');
} else {
    // Agrupar resultados por curso
    $courses = [];
    foreach ($results as $result) {
        $courses[$result->courseid]['coursename'] = $result->coursename;
        $courses[$result->courseid]['validations'][] = $result;
    }

    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('course', 'block_validador'));
    echo html_writer::tag('th', get_string('validation', 'block_validador'));
    echo html_writer::tag('th', get_string('activity', 'block_validador'));
    echo html_writer::tag('th', get_string('timecreated', 'block_validador'));
    echo html_writer::tag('th', get_string('timemodified', 'block_validador'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($courses as $courseid => $course) {
        foreach ($course['validations'] as $validation) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', html_writer::link(new moodle_url('/course/view.php', ['id' => $courseid]), $course['coursename']));
            echo html_writer::tag('td', $validation->validationname);
            if (!empty($validation->cmid)) {
                $activity_url = new moodle_url('/mod/'.$validation->modulename.'/view.php', ['id' => $validation->cmid]);
                echo html_writer::tag('td', html_writer::link($activity_url, $validation->modulename));
            } else {
                echo html_writer::tag('td', '-');
            }
            echo html_writer::tag('td', userdate($validation->timecreated));
            echo html_writer::tag('td', userdate($validation->timemodified));
            echo html_writer::end_tag('tr');
        }
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

echo $OUTPUT->footer();