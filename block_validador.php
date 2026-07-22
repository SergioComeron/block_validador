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
 * Plugin capabilities for the block_pluginname plugin.
 *
 * @package   block_validador
 * @copyright 2024, Sergio Comerón <info@sergiocomeron.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

/**
 * Block that validates course configuration before an online exam.
 */
class block_validador extends block_base {
    /**
     * Initialises the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_validador');
    }

    /**
     * Returns the block content.
     */
    public function get_content() {
        global $COURSE, $DB, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        if (!has_capability('block/validador:view', context_course::instance($COURSE->id))) {
            $this->content = new stdClass();
            $this->content->text = '';
            return $this->content;
        }

        if ($COURSE->visible == 0) {
            $this->content = new stdClass();
            $this->content->text = '';
            return $this->content;
        }

        $hasbatchgroups = $DB->count_records_sql("
            SELECT COUNT(*)
              FROM {local_creaexamen_quiz_log} ql
              JOIN {local_creaexamen_batches} b ON b.id = ql.batchid
             WHERE ql.courseid = :courseid
               AND b.active = 1
        ", ['courseid' => $COURSE->id]);
        if (!$hasbatchgroups) {
            $this->content = new stdClass();
            $this->content->text = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $validationspassed = true;

        $this->content->text .= "<h4>Grupos</h4>";
        $validationsgroups = $this->perform_validations_groups();
        foreach ($validationsgroups as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validationspassed = $validationspassed && $validation['passed'];
        }

        $groupspassed = !empty(array_filter($validationsgroups, fn($v) => $v['id'] === 'groups' && $v['passed']));
        if ($groupspassed) {
            foreach ($this->perform_validations_groupwithquizzes() as $validation) {
                $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
                $validationspassed = $validationspassed && $validation['passed'];
            }
        }

        $this->content->text .= "<h4>Libro de Calificaciones</h4>";
        foreach ($this->performs_validations_gradebook_final() as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validationspassed = $validationspassed && $validation['passed'];
        }
        foreach ($this->performs_validations_gradebook_online() as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validationspassed = $validationspassed && $validation['passed'];
        }
        foreach ($this->validate_examen_online_weight() as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validationspassed = $validationspassed && $validation['passed'];
        }

        $this->content->text .= "<h4>Smowl</h4>";
        foreach ($this->perform_validations_smowl() as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validationspassed = $validationspassed && $validation['passed'];
        }

        $groupspassed = !empty(array_filter($validationsgroups, fn($v) => $v['id'] === 'groups' && $v['passed']));
        if ($groupspassed) {
            $this->content->text .= "<h4>Cuestionarios</h4>";
            $validgroups = [];
            foreach (groups_get_all_groups($COURSE->id) as $group) {
                if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes') {
                    $validgroups[] = $group;
                }
            }
            foreach ($validgroups as $group) {
                $groupquizzes = $DB->get_records_sql(
                    'SELECT * FROM {quiz} WHERE course = ? AND name LIKE ? ORDER BY id ASC',
                    [$COURSE->id, $group->name . '%']
                );
                if (empty($groupquizzes)) {
                    continue;
                }
                $quiz = reset($groupquizzes);
                $cm = get_coursemodule_from_instance('quiz', $quiz->id);
                $contextid = context_module::instance($cm->id)->id;

                $this->content->text .= "<strong>Cuestionario: {$quiz->name}</strong><br>";

                $multipleval = [
                    'id'     => 'quizmultipleforgroup',
                    'name'   => get_string('quizmultipleforgroup', 'block_validador'),
                    'passed' => count($groupquizzes) === 1,
                ];
                $this->content->text .= $this->save_and_render_validation($multipleval, $contextid);
                $validationspassed = $validationspassed && $multipleval['passed'];
                if (!$multipleval['passed']) {
                    continue;
                }

                $datesvalidation = $this->validate_quiz_has_dates($quiz)[0];
                $this->content->text .= $this->save_and_render_validation($datesvalidation, $contextid);
                $validationspassed = $validationspassed && $datesvalidation['passed'];
                if (!$datesvalidation['passed']) {
                    continue;
                }

                foreach ($this->validate_quiz_pledge_access($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->timelimitvalidation($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, 'El tiempo deben ser 90 minutos');
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->questionperpagevalidation($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, 'Todas las preguntas en una única página');
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->validate_quiz_single_page($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, 'No debe haber saltos de página entre las preguntas');
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->validate_quiz_has_questions($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, 'El cuestionario debe tener al menos una pregunta');
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->validate_quiz_random_questions($quiz) as $validation) {
                    $randomtitle = 'Las preguntas aleatorias no pueden superar las disponibles en la categoría del banco de preguntas';
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, $randomtitle);
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->grouprestictionvalidation($quiz, $group) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, 'El cuestionario tiene restricción por grupo');
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->labelvalidation($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validationspassed = $validationspassed && $validation['passed'];
                }

                $validationspledge = $this->check_quiz_has_pledge_above($quiz, $group);
                if (!is_array($validationspledge)) {
                    $validationspledge = [[
                        'id'     => 'quizhaspledgeabove',
                        'name'   => get_string('quizhaspledgeabove', 'block_validador'),
                        'passed' => $validationspledge,
                    ]];
                }
                foreach ($validationspledge as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validationspassed = $validationspassed && $validation['passed'];
                }

                foreach ($this->gradetopass($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->validate_quiz_grade_category($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->validate_quiz_auto_submit($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validationspassed = $validationspassed && $validation['passed'];
                }
                foreach ($this->validate_quiz_review_options($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validationspassed = $validationspassed && $validation['passed'];
                }
            }
        }

        $emoji = $validationspassed ? '✅' : '❌';
        $message = $validationspassed ? 'Curso Validado' : 'Curso No Validado';
        $message = "<span style='font-size: 2em;'>$message</span>";
        $this->content->text = "$emoji $message<br>" . $this->content->text;

        return $this->content;
    }

    /**
     * Persists a validation result and returns its HTML rendering.
     */
    private function save_and_render_validation(array $validation, int $contextid, string $title = ''): string {
        global $DB, $COURSE;

        $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
        $existing = $DB->get_record('block_validador_results', $params);
        $newpassed = $validation['passed'] ? 1 : 0;

        if (!$existing) {
            $record = new stdClass();
            $record->contextid = $contextid;
            $record->validationname = $validation['id'];
            $record->courseid = $COURSE->id;
            $record->passed = $newpassed;
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('block_validador_results', $record);
        } else if ($existing->passed != 2 && $existing->passed != $newpassed) {
            $existing->passed = $newpassed;
            $existing->timemodified = time();
            $DB->update_record('block_validador_results', $existing);
        }

        $status = $validation['passed'] ? '🟢' : '🔴';
        $color = $validation['passed'] ? 'black' : 'red';
        $tooltip = $validation['title'] ?? $title;
        $titleattr = $tooltip ? " title='$tooltip'" : '';
        return "<span style='color: $color;'$titleattr>$status {$validation['name']}</span><br>";
    }

    /**
     * Validates quiz review options configuration.
     */
    private function validate_quiz_review_options($quiz) {
        $validations = [];
        $reviewoptionsvalid = true;

        // Obtener las opciones de revisión del cuestionario
        $reviewoptionsduring = \mod_quiz\question\display_options::make_from_quiz($quiz, \mod_quiz\question\display_options::DURING);
        $reviewoptionsimmediately = \mod_quiz\question\display_options::make_from_quiz($quiz, \mod_quiz\question\display_options::IMMEDIATELY_AFTER);
        $reviewoptionsopen = \mod_quiz\question\display_options::make_from_quiz($quiz, \mod_quiz\question\display_options::LATER_WHILE_OPEN);
        $reviewoptionsclosed = \mod_quiz\question\display_options::make_from_quiz($quiz, \mod_quiz\question\display_options::AFTER_CLOSE);

        // Verificar que solo la opción de ver el intento esté activada durante el intento
        if (
            $reviewoptionsduring->attempt != \mod_quiz\question\display_options::VISIBLE ||
            $reviewoptionsduring->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsduring->marks != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsduring->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsduring->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsduring->overallfeedback != \mod_quiz\question\display_options::HIDDEN
        ) {
            $reviewoptionsvalid = false;
        }

        // Verificar las opciones de revisión inmediatamente después del intento
        if (
            $reviewoptionsimmediately->attempt != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsimmediately->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsimmediately->marks != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsimmediately->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsimmediately->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsimmediately->overallfeedback != \mod_quiz\question\display_options::HIDDEN
        ) {
            $reviewoptionsvalid = false;
        }

        // Verificar las opciones de revisión mientras el cuestionario está abierto
        if (
            $reviewoptionsopen->attempt != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsopen->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsopen->marks != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsopen->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsopen->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsopen->overallfeedback != \mod_quiz\question\display_options::HIDDEN
        ) {
            $reviewoptionsvalid = false;
        }

        // Verificar las opciones de revisión después de cerrar el cuestionario
        if (
            $reviewoptionsclosed->attempt != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsclosed->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsclosed->marks != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsclosed->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsclosed->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $reviewoptionsclosed->overallfeedback != \mod_quiz\question\display_options::HIDDEN
        ) {
            $reviewoptionsvalid = false;
        }

        $validations[] = [
            'id' => 'quizreviewoptions',
            'name' => get_string('quizreviewoptions', 'block_validador'),
            'passed' => $reviewoptionsvalid,
        ];

        return $validations;
    }

    /**
     * Validates that a SMOWL block exists in the course.
     */
    private function perform_validations_smowl() {
        global $COURSE, $DB;

        $validations = [];
        $smowlblockexists = false;

        // Obtener todos los bloques del curso
        $blocks = $DB->get_records('block_instances', ['parentcontextid' => context_course::instance($COURSE->id)->id]);

        // Verificar si existe un bloque Smowl en el curso
        foreach ($blocks as $block) {
            if ($block->blockname == 'smowl') {
                $smowlblockexists = true;
                break;
            }
        }

        $validations[] = [
            'id' => 'smowl',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('smowl', 'block_validador'),
            'passed' => $smowlblockexists,
        ];

        return $validations;
    }

    /**
     * Validates that the quiz is set to auto-submit on timeout.
     */
    private function validate_quiz_auto_submit($quiz) {
        $validations = [];
        $autosubmit = false;

        // Validación: verificar que el cuestionario esté configurado para enviar automáticamente al finalizar el tiempo
        if ($quiz->overduehandling == 'autosubmit') {
            $autosubmit = true;
        }

        $validations[] = [
            'id' => 'quizautosubmit',
            'name' => get_string('quizautosubmit', 'block_validador'),
            'passed' => $autosubmit,
        ];

        return $validations;
    }

    /**
     * Validates that the quiz has at least one question.
     */
    private function validate_quiz_has_questions($quiz) {
        global $DB;

        $validations = [];

        // Validación: verificar que el cuestionario tenga al menos una pregunta.
        $hasquestions = $DB->count_records('quiz_slots', ['quizid' => $quiz->id]) > 0;

        $validations[] = [
            'id' => 'quizhasquestions',
            'name' => get_string('quizhasquestions', 'block_validador'),
            'passed' => $hasquestions,
        ];

        return $validations;
    }

    /**
     * Validates that the quiz has no page breaks between questions.
     *
     * Complements questionperpagevalidation: the questionsperpage setting can be 0
     * but manual page breaks persist per slot in quiz_slots.page, so every slot
     * must actually be on page 1.
     */
    private function validate_quiz_single_page($quiz) {
        global $DB;

        $validations = [];

        // Validación: ningún slot puede estar en una página distinta de la primera.
        $singlepage = $DB->count_records_select('quiz_slots', 'quizid = ? AND page > 1', [$quiz->id]) == 0;

        $validations[] = [
            'id' => 'quizsinglepage',
            'name' => get_string('quizsinglepage', 'block_validador'),
            'passed' => $singlepage,
        ];

        return $validations;
    }

    /**
     * Validates that random questions do not exceed the questions available in their bank category.
     *
     * Groups the random slots of the quiz by filter condition (category, subcategories, tags)
     * and checks that each group does not request more questions than the ones available,
     * to avoid the "Not enough questions in category" error during the attempt.
     */
    private function validate_quiz_random_questions($quiz) {
        global $CFG;

        require_once($CFG->dirroot . '/question/engine/lib.php');

        $validations = [];
        $randomvalid = true;

        $cm = get_coursemodule_from_instance('quiz', $quiz->id);
        $context = context_module::instance($cm->id);
        $slots = \mod_quiz\question\bank\qbank_helper::get_question_structure($quiz->id, $context);

        // Agrupar los slots aleatorios por condición de filtro: los que comparten
        // categoría/etiquetas compiten por el mismo conjunto de preguntas.
        // Un slot es aleatorio si tiene filtercondition (en 4.5 qtype='random',
        // en 5.2 qtype=null con random=true, así que qtype no sirve).
        $groups = [];
        foreach ($slots as $slot) {
            if (empty($slot->filtercondition)) {
                continue;
            }
            $filter = $slot->filtercondition['filter'] ?? [];
            $key = json_encode($filter);
            if (!isset($groups[$key])) {
                $groups[$key] = ['filter' => $filter, 'count' => 0];
            }
            $groups[$key]['count']++;
        }

        if ($groups) {
            $loader = new \core_question\local\bank\random_question_loader(new \qubaid_list([]));
            foreach ($groups as $group) {
                $available = $loader->count_filtered_questions($group['filter']);
                if ($group['count'] > $available) {
                    $randomvalid = false;
                    break;
                }
            }
        }

        $validations[] = [
            'id' => 'quizrandomquestions',
            'name' => get_string('quizrandomquestions', 'block_validador'),
            'passed' => $randomvalid,
        ];

        return $validations;
    }

    /**
     * Validates the quiz grade category name.
     */
    private function validate_quiz_grade_category($quiz) {
        global $DB;

        $validations = [];
        $categoryvalid = false;

        // Obtener la categoría de calificación del cuestionario
        $gradeitem = $DB->get_record('grade_items', [
            'iteminstance' => $quiz->id,
            'itemmodule' => 'quiz',
        ]);

        if ($gradeitem) {
            // Obtener la categoría de calificación asociada al cuestionario
            $category = $DB->get_record('grade_categories', ['id' => $gradeitem->categoryid]);

            // Validar insensibilidad a mayúsculas/minúsculas en el nombre de la categoría
            if ($category && (strcasecmp(trim($category->fullname), 'Examen online') === 0 || strcasecmp(trim($category->fullname), 'Examen final online') === 0)) {
                $categoryvalid = true;
            }
        }

        $validations[] = [
            'id' => 'quizgradecategory',
            'name' => get_string('quizgradecategory', 'block_validador'),
            'passed' => $categoryvalid,
        ];

        return $validations;
    }

    /**
     * Validates the quiz grade to pass value.
     */
    private function gradetopass($quiz) {
        global $DB;

        $validations = [];

        // Obtener la nota de aprobado desde la tabla grade_items.
        $gradeitem = $DB->get_record('grade_items', [
            'iteminstance' => $quiz->id,
            'itemmodule' => 'quiz',
        ]);

        if ($gradeitem && $gradeitem->gradepass == 5) {
            $validations[] = [
                'id' => 'gradetopass',
                'name' => get_string('gradetopass', 'block_validador'),
                'passed' => true,
            ];
        } else {
            $validations[] = [
                'id' => 'gradetopass',
                'name' => get_string('gradetopass', 'block_validador'),
                'passed' => false,
            ];
        }

        return $validations;
    }

    /**
     * Checks that a pledge activity exists just above the quiz.
     */
    private function check_quiz_has_pledge_above($quiz, $group) {
        global $COURSE, $DB;

        $validations = [];
        $haspledgeabove = false;
        $pledgedatesvalid = false;

        // Obtener el course module del cuestionario.
        $quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $COURSE->id);
        if (!$quizcm) {
            return $this->return_pledge_validations(false, false);
        }

        // Obtener la sección en la que se encuentra el cuestionario.
        $section = $DB->get_record('course_sections', ['id' => $quizcm->section, 'course' => $COURSE->id]);
        if (!$section || empty($section->sequence)) {
            return $this->return_pledge_validations(false, false);
        }

        // Separar la secuencia de course modules y buscar la posición del cuestionario.
        $cmids = explode(',', $section->sequence);
        $currentindex = array_search($quizcm->id, $cmids);
        if ($currentindex === false || $currentindex === 0) {
            return $this->return_pledge_validations(false, false);
        }

        // Obtener el course module que está justo antes del cuestionario.
        $prevcmid = $cmids[$currentindex - 1];
        $prevmodule = get_coursemodule_from_id(null, $prevcmid, 0, false, IGNORE_MISSING);
        if (!$prevmodule) {
            return $this->return_pledge_validations(false, false);
        }

        if ($prevmodule && $prevmodule->modname === 'pledge') {
            // Obtener el registro del pledge
            $pledge = $DB->get_record('pledge', ['id' => $prevmodule->instance]);

            // Verificar que el pledge esté configurado para completarse al ser visto.
            if (isset($prevmodule->completionview) && $prevmodule->completionview == 1) {
                // Verificar que el pledge tenga una restricción de grupo y que se pertenezca al grupo $group.
                if (!empty($prevmodule->availability)) {
                    $availability = json_decode($prevmodule->availability);
                    if (isset($availability->c) && is_array($availability->c)) {
                        foreach ($availability->c as $condition) {
                            if (isset($condition->type) && $condition->type == 'group' && isset($condition->id) && $condition->id == $group->id) {
                                $haspledgeabove = true;

                                // Validar fechas del pledge en relación con el cuestionario
                                if ($pledge && $quiz->timeopen && $quiz->timeclose) {
                                    // El pledge abre 15 min antes que el cuestionario
                                    $expectedpledgestart = $quiz->timeopen - 900;
                                    // El pledge cierra 5 min antes que el cuestionario
                                    $expectedpledgeend = $quiz->timeclose - 300;

                                    if (
                                        $pledge->timeopen == $expectedpledgestart &&
                                        $pledge->timeclosed == $expectedpledgeend
                                    ) {
                                        $pledgedatesvalid = true;
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $this->return_pledge_validations($haspledgeabove, $pledgedatesvalid);
    }

    /**
     * Returns pledge validation results array.
     */
    private function return_pledge_validations($haspledgeabove, $pledgedatesvalid) {
        return [
            [
                'id' => 'quizhaspledgeabove',
                'name' => get_string('quizhaspledgeabove', 'block_validador'),
                'passed' => $haspledgeabove,
            ],
            [
                'id' => 'pledgedates',
                'name' => get_string('pledgedates', 'block_validador'),
                'passed' => $pledgedatesvalid,
            ],
        ];
    }

    /**
     * Validates that the quiz has a pledge completion restriction.
     */
    private function validate_quiz_pledge_access($quiz) {
        global $COURSE;

        // Obtener el course module del cuestionario.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $COURSE->id);
        $pledgerestrictionvalid = false;

        // Verificar que el cuestionario tenga restricciones de acceso configuradas.
        if ($cm && !empty($cm->availability)) {
            $availability = json_decode($cm->availability);

            // Buscar recursivamente una condición de tipo 'completion' marcada como completada.
            $pledgerestrictionvalid = $this->check_completion_condition($availability);
        }

        return [
            [
                'id'      => 'quizpledgeaccess',
                'name'    => get_string('quizpledgeaccess', 'block_validador'),
                'passed'  => $pledgerestrictionvalid,
            ],
        ];
    }

    /**
     * Recursively checks availability tree for a pledge completion condition.
     */
    private function check_completion_condition($availability) {
            global $DB;
            // Si la condición es de tipo "completion"
        if (isset($availability->type) && $availability->type === 'completion') {
            if (isset($availability->cm)) {
                $cmid = $availability->cm;
                $pledgecm = get_coursemodule_from_id('pledge', $cmid, 0, false);
                if ($pledgecm) {
                    $pledge = $DB->get_record('pledge', ['id' => $pledgecm->instance]);
                    if ($pledge && preg_match('/\#\d{6}\#/', $pledge->name)) {
                        return true;
                    }
                }
            }
        }
            // Recorrer recursivamente las condiciones hijas
        if (isset($availability->c) && is_array($availability->c)) {
            foreach ($availability->c as $condition) {
                if ($this->check_completion_condition($condition)) {
                    return true;
                }
            }
        }
            return false;
    }




    /**
     * Validates that a support label exists in the same section as the quiz.
     */
    private function labelvalidation($quiz) {
        global $COURSE, $DB;

        $validations = [];
        $labelsvalid = false;

        // Validación: verificar que cada cuestionario tenga un área de texto y medios en la misma semana
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $COURSE->id);
        $sectionquiz = $DB->get_record_sql('SELECT section FROM {course_modules} WHERE id = ?', [$cm->id]);
        $section = $DB->get_record('course_sections', ['id' => $sectionquiz->section]);
        $sequence = explode(',', $section->sequence);

        foreach ($sequence as $cmid) {
            $cm = get_coursemodule_from_id(null, $cmid);
            if ($cm && $cm->modname == 'label') {
                $label = $DB->get_record('label', ['id' => $cm->instance]);

                if ($label) {
                    // Eliminar etiquetas HTML del texto del label.
                    $introtext = strip_tags($label->intro);

                    // Palabras clave esperadas en el texto del label
                    $keywords = [
                        'problemas técnicos',
                        'correo electrónico',
                        'soporte.alumno@udima.es',
                    ];

                    // Verificar si todas las palabras clave están presentes en el texto
                    $allkeywordsfound = true;
                    foreach ($keywords as $keyword) {
                        if (stripos($introtext, $keyword) === false) {
                            $allkeywordsfound = false;
                            break;
                        }
                    }

                    if ($allkeywordsfound) {
                        $labelsvalid = true;
                        break; // No es necesario seguir buscando
                    }
                }
            }
        }

        $validations[] = [
            'id' => 'label',
            'name' => get_string('label', 'block_validador'),
            'passed' => $labelsvalid,
        ];
        return $validations;
    }

    /**
     * Validates that all questions are on a single page.
     */
    private function questionperpagevalidation($quiz) {
        $validations = [];
        // Validación: verificar que todas las preguntas estén en una misma página
        if ($quiz->questionsperpage != 0) {
            $validations[] = [
                'id' => 'quizquestionsperpage',
                'name' => get_string('quizquestionsperpage', 'block_validador'),
                'passed' => false,
            ];
        } else {
            $validations[] = [
                'id' => 'quizquestionsperpage',
                'name' => get_string('quizquestionsperpage', 'block_validador'),
                'passed' => true,
            ];
        }
        return $validations;
    }

    /**
     * Validates that the quiz has open and close dates configured.
     */
    private function validate_quiz_has_dates($quiz): array {
        $passed = !empty($quiz->timeopen) && !empty($quiz->timeclose);
        return [[
            'id'     => 'quizhasdates',
            'name'   => get_string('quizhasdates', 'block_validador'),
            'passed' => $passed,
        ]];
    }

    /**
     * Validates the quiz time limit is 90 or 45 minutes.
     */
    private function timelimitvalidation($quiz) {
        $validations = [];
        if ($quiz->timelimit == 5400 || $quiz->timelimit == 2700) { // 5400 segundos = 90 minutos, 2700 segundos = 45 minutos
            $validations[] = [
                'id' => 'quiztimelimit',
                'name' => get_string('quiztimelimit', 'block_validador'),
                'passed' => true,
            ];
        } else {
            $validations[] = [
                'id' => 'quiztimelimit',
                'name' => get_string('quiztimelimit', 'block_validador'),
                'passed' => false,
            ];
        }

        return $validations;
    }

    /**
     * Validates that each valid group has a corresponding quiz.
     */
    private function perform_validations_groupwithquizzes() {
        global $COURSE, $DB;
        // Validación: verificar que cada grupo válido tenga un cuestionario correspondiente
        $validgroupcount = 0;
        $validgroups = [];
        $groups = groups_get_all_groups($COURSE->id);
        $mintimecreated = get_config('block_validador', 'min_group_timecreated');
        foreach ($groups as $group) {
            if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes' && $group->timecreated > $mintimecreated) {
                $validgroupcount++;
                $validgroups[] = $group;
            }
        }

        $quizzesvalid = true;
        $hasquizzes = false;

        // Vamos a validar todos los cuestionarios.
        foreach ($validgroups as $group) {
            // Buscar el cuestionario cuyo nombre es igual al nombre del grupo
            $quiz = $DB->get_record_sql('SELECT * FROM {quiz} WHERE course = ? AND name LIKE ?', [$COURSE->id, $group->name . '%']);
            if ($quiz) {
                $hasquizzes = true;
                // $this->validate_quiz($quiz, $group);
            } else {
                // No se encontró un cuestionario con el nombre del grupo
                $quizzesvalid = false;
                break;
            }
        }

        // Si no hay cuestionarios, no se puede dar por válido
        if (!$hasquizzes) {
            $quizzesvalid = false;
        }

        $validations[] = [
            'id' => 'groupwithquizzes',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('groupwithquizzes', 'block_validador'),
            'passed' => $quizzesvalid,
        ];

        return $validations;
    }

    /**
     * Validates that valid exam groups exist in the course.
     */
    private function perform_validations_groups() {
        global $COURSE, $DB, $CFG;

        $validations = [];

        $groups = groups_get_all_groups($COURSE->id);
        $mintimecreated = get_config('block_validador', 'min_group_timecreated');

        // Validación: verificar que haya al menos dos grupos con nombres válidos
        $validgroupcount = 0;
        $validgroups = [];
        foreach ($groups as $group) {
            if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes' && $group->timecreated > $mintimecreated) {
                $validgroupcount++;
                $validgroups[] = $group;
            }
        }
        $validgroupnames = $validgroupcount >= 1;
        $validations[] = [
            'id' => 'groups',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('groups', 'block_validador'),
            'passed' => $validgroupnames,
        ];

        return $validations;
    }

    /**
     * Validates that the Examen final gradebook category is visible.
     */
    private function performs_validations_gradebook_final() {
        global $COURSE, $DB, $CFG;

        $gradebookvalid = true;
        $reason = '';

        $examfinalcategory = $DB->get_record_sql(
            "SELECT *
             FROM {grade_categories}
             WHERE courseid = :courseid
             AND LOWER(TRIM(fullname)) = LOWER(:fullname)",
            ['courseid' => $COURSE->id, 'fullname' => 'Examen final']
        );

        if ($examfinalcategory) {
            if (isset($examfinalcategory->hidden) && $examfinalcategory->hidden != 0) {
                $gradebookvalid = false;
                $reason = 'La categoría no está visible';
            }
        } else {
            $gradebookvalid = false;
            $reason = 'La categoría no existe';
        }

        $validationsgradebook[] = [
            'id'        => 'gradebook',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name'      => get_string('gradebook', 'block_validador'),
            'passed'    => $gradebookvalid,
            'title'     => $reason,
        ];

        return $validationsgradebook;
    }

    /**
     * Returns the Examen online grade category record.
     */
    private function get_exam_online_category() {
        global $COURSE, $DB;
        return $DB->get_record_sql(
            "SELECT * FROM {grade_categories}
              WHERE courseid = :courseid
                AND LOWER(TRIM(fullname)) = LOWER(:fullname)",
            ['courseid' => $COURSE->id, 'fullname' => 'Examen online']
        );
    }

    /**
     * Validates that the Examen online gradebook category is hidden.
     */
    private function performs_validations_gradebook_online() {
        global $COURSE;

        $category = $this->get_exam_online_category();
        $passed = false;
        $reason = '';

        if (!$category) {
            $reason = 'La categoría no existe';
        } else if (!isset($category->hidden) || $category->hidden != 1) {
            $reason = 'La categoría no está oculta';
        } else {
            $passed = true;
        }

        return [[
            'id'        => 'gradebook_subcategorie_examenonline',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name'      => get_string('gradebook_online', 'block_validador'),
            'passed'    => $passed,
            'title'     => $reason,
        ]];
    }

    /**
     * Validates that the Examen online category weight is zero.
     */
    private function validate_examen_online_weight() {
        global $COURSE, $DB;

        $passed = false;
        $reason = '';
        $category = $this->get_exam_online_category();

        if (!$category) {
            $reason = 'La categoría no existe';
        } else {
            $item = $DB->get_record('grade_items', [
                'itemtype'     => 'category',
                'iteminstance' => $category->id,
                'courseid'     => $COURSE->id,
            ]);
            if (!$item) {
                $reason = 'No se encontró el ítem de calificación';
            } else if ((float)$item->aggregationcoef !== 0.0) {
                $reason = 'El peso es ' . $item->aggregationcoef . ' (debe ser 0)';
            } else {
                $passed = true;
            }
        }

        return [[
            'id'        => 'gradebook_online_weight',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name'      => get_string('gradebook_online_weight', 'block_validador'),
            'passed'    => $passed,
            'title'     => $reason,
        ]];
    }

    /**
     * Validates that the quiz has an invisible group restriction.
     */
    private function grouprestictionvalidation($quiz, $group) {
        global $DB, $COURSE;
        // $validations = [];
        $conrestriccion = false;
        // Validación: verificar que el cuestionario tenga restricciones de grupo
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $COURSE->id);
        if ($cm && $cm->availability) {
            $availability = json_decode($cm->availability);
            // print_r($availability);
            if ($this->has_group_restriction($availability, $group->id)) {
                // echo "Restricción de grupo encontrada";
                if ($this->check_showc($availability)) {
                    // echo "Invisible";
                    $conrestriccion = true;
                }
            } else {
                $conrestriccion = false;
            }
        } else {
            // El cuestionario no tiene restricciones de acceso
            $conrestriccion = false;
        }
        $validations[] = [
            'id' => 'grouprestriccion',
            'name' => get_string('grouprestriccion', 'block_validador'),
            'passed' => $conrestriccion,
        ];
        return $validations;
    }

    /**
     * Recursively checks availability tree for a group restriction.
     */
    private function has_group_restriction($availability, $groupid) {
        if (isset($availability->type) && $availability->type == 'group' && isset($availability->id) && $availability->id == $groupid) {
            return true;
        } else if (isset($availability->op) && isset($availability->c)) {
            foreach ($availability->c as $condition) {
                if ($this->has_group_restriction($condition, $groupid)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns true if the availability condition is set to hide the activity.
     */
    private function check_showc($availability) {
        if (!isset($availability->showc)) {
            return true;
        }
        return !$availability->showc[0];
    }

    /**
     * Returns applicable course formats.
     */
    public function applicable_formats() {
        return ['site' => true, 'course-view' => true];
    }

    /**
     * Only site admins can remove this block.
     */
    public function instance_can_be_removed() {
        return is_siteadmin();
    }

    /**
     * Only site admins can hide this block.
     */
    public function instance_can_be_hidden() {
        return is_siteadmin();
    }

    /**
     * Only site admins can edit this block.
     */
    public function user_can_edit() {
        return is_siteadmin();
    }

    /**
     * Only site admins can add this block.
     */
    public function user_can_addto($page) {
        return is_siteadmin();
    }

    /**
     * Returns true as this block has a settings page.
     */
    public function has_config() {
        return true;
    }
}
