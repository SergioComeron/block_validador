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

// Consulta para obtener cursos y actividades con validaciones erróneas.
$sql = "SELECT v.id AS resultid, c.id AS courseid, c.fullname AS coursename, v.validationname, v.timecreated, v.timemodified, 
               CASE 
                   WHEN ctx.contextlevel = 50 THEN c.fullname
                   WHEN ctx.contextlevel = 70 THEN (SELECT c2.fullname FROM {course} c2 JOIN {quiz} q ON q.course = c2.id WHERE q.id = ctx.instanceid)
                   ELSE 'Unknown'
               END AS coursename
        FROM {block_validador_results} v
        JOIN {context} ctx ON v.contextid = ctx.id
        LEFT JOIN {course} c ON (ctx.contextlevel = 50 AND ctx.instanceid = c.id)
        WHERE (ctx.contextlevel = 50 OR ctx.contextlevel = 70) AND v.passed = 0";

$results = $DB->get_records_sql($sql);

if (!$results) {
    echo $OUTPUT->notification(get_string('nocoursesfound', 'block_validador'), 'notifymessage');
} else {
    // Agrupar resultados por curso
    $courses = [];
    foreach ($results as $result) {
        $courses[$result->courseid]['coursename'] = $result->coursename;
        $courses[$result->courseid]['validations'][] = $result;
    }

    // Contar el número total de cursos con validaciones no válidas
    $total_courses = count($courses);
    echo html_writer::tag('p', get_string('totalinvalidcourses', 'block_validador', $total_courses));

    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('course', 'block_validador'));
    echo html_writer::tag('th', get_string('validation', 'block_validador'));
    echo html_writer::tag('th', get_string('timecreated', 'block_validador'));
    echo html_writer::tag('th', get_string('timemodified', 'block_validador'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($courses as $courseid => $course) {
        $rowspan = count($course['validations']);
        $first = true;
        foreach ($course['validations'] as $validation) {
            echo html_writer::start_tag('tr');
            if ($first) {
                echo html_writer::tag('td', html_writer::link(new moodle_url('/course/view.php', ['id' => $courseid]), $course['coursename']), ['rowspan' => $rowspan]);
                $first = false;
            }
            echo html_writer::tag('td', $validation->validationname);
            echo html_writer::tag('td', userdate($validation->timecreated));
            echo html_writer::tag('td', userdate($validation->timemodified));
            echo html_writer::end_tag('tr');
        }
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

echo $OUTPUT->footer();