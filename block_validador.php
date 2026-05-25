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

require_once($CFG->libdir . '/gradelib.php');

class block_validador extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_validador');
    }

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
        $validations_passed = true;

        $this->content->text .= "<h4>Grupos</h4>";
        $validationsgroups = $this->perform_validations_groups();
        foreach ($validationsgroups as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $groups_passed = !empty(array_filter($validationsgroups, fn($v) => $v['id'] === 'groups' && $v['passed']));
        if ($groups_passed) {
            foreach ($this->perform_validations_groupwithquizzes() as $validation) {
                $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
                $validations_passed = $validations_passed && $validation['passed'];
            }
        }

        $this->content->text .= "<h4>Libro de Calificaciones</h4>";
        foreach ($this->performs_validations_gradebook_final() as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validations_passed = $validations_passed && $validation['passed'];
        }
        foreach ($this->performs_validations_gradebook_online() as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validations_passed = $validations_passed && $validation['passed'];
        }
        foreach ($this->validate_examen_online_weight() as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $this->content->text .= "<h4>Smowl</h4>";
        foreach ($this->perform_validations_smowl() as $validation) {
            $this->content->text .= $this->save_and_render_validation($validation, $validation['contextid']);
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $groups_passed = !empty(array_filter($validationsgroups, fn($v) => $v['id'] === 'groups' && $v['passed']));
        if ($groups_passed) {
            $this->content->text .= "<h4>Cuestionarios</h4>";
            $valid_groups = [];
            foreach (groups_get_all_groups($COURSE->id) as $group) {
                if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes') {
                    $valid_groups[] = $group;
                }
            }
            foreach ($valid_groups as $group) {
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
                $validations_passed = $validations_passed && $multipleval['passed'];
                if (!$multipleval['passed']) {
                    continue;
                }

                $datesvalidation = $this->validate_quiz_has_dates($quiz)[0];
                $this->content->text .= $this->save_and_render_validation($datesvalidation, $contextid);
                $validations_passed = $validations_passed && $datesvalidation['passed'];
                if (!$datesvalidation['passed']) {
                    continue;
                }

                foreach ($this->validate_quiz_pledge_access($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validations_passed = $validations_passed && $validation['passed'];
                }
                foreach ($this->timelimitvalidation($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, 'El tiempo deben ser 90 minutos');
                    $validations_passed = $validations_passed && $validation['passed'];
                }
                foreach ($this->questionperpagevalidation($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, 'Todas las preguntas en una única página');
                    $validations_passed = $validations_passed && $validation['passed'];
                }
                foreach ($this->grouprestictionvalidation($quiz, $group) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid, 'El cuestionario tiene restricción por grupo');
                    $validations_passed = $validations_passed && $validation['passed'];
                }
                foreach ($this->labelvalidation($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validations_passed = $validations_passed && $validation['passed'];
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
                    $validations_passed = $validations_passed && $validation['passed'];
                }

                foreach ($this->gradetopass($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validations_passed = $validations_passed && $validation['passed'];
                }
                foreach ($this->validate_quiz_grade_category($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validations_passed = $validations_passed && $validation['passed'];
                }
                foreach ($this->validate_quiz_auto_submit($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validations_passed = $validations_passed && $validation['passed'];
                }
                foreach ($this->validate_quiz_review_options($quiz) as $validation) {
                    $this->content->text .= $this->save_and_render_validation($validation, $contextid);
                    $validations_passed = $validations_passed && $validation['passed'];
                }
            }
        }

        $emoji = $validations_passed ? '✅' : '❌';
        $message = $validations_passed ? 'Curso Validado' : 'Curso No Validado';
        $message = "<span style='font-size: 2em;'>$message</span>";
        $this->content->text = "$emoji $message<br>" . $this->content->text;

        return $this->content;
    }

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

    private function validate_quiz_review_options($quiz) {
        $validations = [];
        $review_options_valid = true;
    
        // Obtener las opciones de revisión del cuestionario
        $review_options_during = \mod_quiz\question\display_options::make_from_quiz($quiz, \mod_quiz\question\display_options::DURING);
        $review_options_immediately = \mod_quiz\question\display_options::make_from_quiz($quiz, \mod_quiz\question\display_options::IMMEDIATELY_AFTER);
        $review_options_open = \mod_quiz\question\display_options::make_from_quiz($quiz, \mod_quiz\question\display_options::LATER_WHILE_OPEN);
        $review_options_closed = \mod_quiz\question\display_options::make_from_quiz($quiz, \mod_quiz\question\display_options::AFTER_CLOSE);

        // Verificar que solo la opción de ver el intento esté activada durante el intento
        if ($review_options_during->attempt != \mod_quiz\question\display_options::VISIBLE ||
            $review_options_during->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_during->marks != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_during->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_during->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_during->overallfeedback != \mod_quiz\question\display_options::HIDDEN) {
            $review_options_valid = false;
        }

        // Verificar las opciones de revisión inmediatamente después del intento
        if ($review_options_immediately->attempt != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->marks != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->overallfeedback != \mod_quiz\question\display_options::HIDDEN) {
            $review_options_valid = false;
        }

        // Verificar las opciones de revisión mientras el cuestionario está abierto
        if ($review_options_open->attempt != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->marks != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->overallfeedback != \mod_quiz\question\display_options::HIDDEN) {
            $review_options_valid = false;
        }

        // Verificar las opciones de revisión después de cerrar el cuestionario
        if ($review_options_closed->attempt != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->marks != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->overallfeedback != \mod_quiz\question\display_options::HIDDEN) {
            $review_options_valid = false;
        }
    
        $validations[] = [
            'id' => 'quizreviewoptions',
            'name' => get_string('quizreviewoptions', 'block_validador'),
            'passed' => $review_options_valid
        ];
    
        return $validations;
    }

    private function perform_validations_smowl() {
        global $COURSE, $DB;

        $validations = [];
        $smowl_block_exists = false;

        // Obtener todos los bloques del curso
        $blocks = $DB->get_records('block_instances', ['parentcontextid' => context_course::instance($COURSE->id)->id]);

        // Verificar si existe un bloque Smowl en el curso
        foreach ($blocks as $block) {
            if ($block->blockname == 'smowl') {
                $smowl_block_exists = true;
                break;
            }
        }

        $validations[] = [
            'id' => 'smowl',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('smowl', 'block_validador'),
            'passed' => $smowl_block_exists
        ];

        return $validations;
    }

    private function validate_quiz_auto_submit($quiz) {
        $validations = [];
        $auto_submit = false;

        // Validación: verificar que el cuestionario esté configurado para enviar automáticamente al finalizar el tiempo
        if ($quiz->overduehandling == 'autosubmit') {
            $auto_submit = true;
        }

        $validations[] = [
            'id' => 'quizautosubmit',
            'name' => get_string('quizautosubmit', 'block_validador'),
            'passed' => $auto_submit
        ];

        return $validations;
    }

    private function validate_quiz_grade_category($quiz) {
        global $DB;
    
        $validations = [];
        $category_valid = false;
    
        // Obtener la categoría de calificación del cuestionario
        $grade_item = $DB->get_record('grade_items', [
            'iteminstance' => $quiz->id,
            'itemmodule' => 'quiz'
        ]);
    
        if ($grade_item) {
            // Obtener la categoría de calificación asociada al cuestionario
            $category = $DB->get_record('grade_categories', ['id' => $grade_item->categoryid]);
    
            // Validar insensibilidad a mayúsculas/minúsculas en el nombre de la categoría
            if ($category && (strcasecmp(trim($category->fullname), 'Examen online') === 0 || strcasecmp(trim($category->fullname), 'Examen final online') === 0)) {
                $category_valid = true;
            }
        }
    
        $validations[] = [
            'id' => 'quizgradecategory',
            'name' => get_string('quizgradecategory', 'block_validador'),
            'passed' => $category_valid
        ];
    
        return $validations;
    }

    private function gradetopass($quiz) {
        global $DB;
    
        $validations = [];
    
        // Obtener la nota de aprobado desde la tabla grade_items.
        $gradeitem = $DB->get_record('grade_items', [
            'iteminstance' => $quiz->id,
            'itemmodule' => 'quiz'
        ]);
    
        if ($gradeitem && $gradeitem->gradepass == 5) {
            $validations[] = [
                'id' => 'gradetopass',
                'name' => get_string('gradetopass', 'block_validador'),
                'passed' => true
            ];
        } else {
            $validations[] = [
                'id' => 'gradetopass',
                'name' => get_string('gradetopass', 'block_validador'),
                'passed' => false
            ];
        }
    
        return $validations;
    }

    private function check_quiz_has_pledge_above($quiz, $group) {
        global $COURSE, $DB;

        $validations = [];
        $has_pledge_above = false;
        $pledge_dates_valid = false;

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
        $currentIndex = array_search($quizcm->id, $cmids);
        if ($currentIndex === false || $currentIndex === 0) {
            return $this->return_pledge_validations(false, false);
        }

        // Obtener el course module que está justo antes del cuestionario.
        $prevCmid = $cmids[$currentIndex - 1];
        $prevModule = get_coursemodule_from_id(null, $prevCmid, 0, false, IGNORE_MISSING);
        if (!$prevModule) {
            return $this->return_pledge_validations(false, false);
        }

        if ($prevModule && $prevModule->modname === 'pledge') {
            // Obtener el registro del pledge
            $pledge = $DB->get_record('pledge', ['id' => $prevModule->instance]);
            
            // Verificar que el pledge esté configurado para completarse al ser visto.
            if (isset($prevModule->completionview) && $prevModule->completionview == 1) {
                // Verificar que el pledge tenga una restricción de grupo y que se pertenezca al grupo $group.
                if (!empty($prevModule->availability)) {
                    $availability = json_decode($prevModule->availability);
                    if (isset($availability->c) && is_array($availability->c)) {
                        foreach ($availability->c as $condition) {
                            if (isset($condition->type) && $condition->type == 'group' && isset($condition->id) && $condition->id == $group->id) {
                                $has_pledge_above = true;
                                
                                // Validar fechas del pledge en relación con el cuestionario
                                if ($pledge && $quiz->timeopen && $quiz->timeclose) {
                                    // El pledge abre 15 min antes que el cuestionario
                                    $expected_pledge_start = $quiz->timeopen - 900;
                                    // El pledge cierra 5 min antes que el cuestionario
                                    $expected_pledge_end = $quiz->timeclose - 300;

                                    if ($pledge->timeopen == $expected_pledge_start &&
                                        $pledge->timeclosed == $expected_pledge_end) {
                                        $pledge_dates_valid = true;
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $this->return_pledge_validations($has_pledge_above, $pledge_dates_valid);
    }

    private function return_pledge_validations($has_pledge_above, $pledge_dates_valid) {
        return [
            [
                'id' => 'quizhaspledgeabove',
                'name' => get_string('quizhaspledgeabove', 'block_validador'),
                'passed' => $has_pledge_above
            ],
            [
                'id' => 'pledgedates',
                'name' => get_string('pledgedates', 'block_validador'),
                'passed' => $pledge_dates_valid
            ]
        ];
    }

    private function validate_quiz_pledge_access($quiz) {
        global $COURSE;
    
        // Obtener el course module del cuestionario.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $COURSE->id);
        $pledgeRestrictionValid = false;
    
        // Verificar que el cuestionario tenga restricciones de acceso configuradas.
        if ($cm && !empty($cm->availability)) {
            $availability = json_decode($cm->availability);

            // Buscar recursivamente una condición de tipo 'completion' marcada como completada.
            $pledgeRestrictionValid = $this->check_completion_condition($availability);
        }
    
        return [
            [
                'id'      => 'quizpledgeaccess',
                'name'    => get_string('quizpledgeaccess', 'block_validador'),
                'passed'  => $pledgeRestrictionValid
            ]
        ];
    }

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
    

    

    private function labelvalidation($quiz) {
        global $COURSE, $DB;
    
        $validations = [];
        $labels_valid = false;
    
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
                    $intro_text = strip_tags($label->intro);

                    // Palabras clave esperadas en el texto del label
                    $keywords = [
                        'problemas técnicos',
                        'correo electrónico',
                        'soporte.alumno@udima.es',
                    ];
    
                    // Verificar si todas las palabras clave están presentes en el texto
                    $all_keywords_found = true;
                    foreach ($keywords as $keyword) {
                        if (stripos($intro_text, $keyword) === false) {
                            $all_keywords_found = false;
                            break;
                        }
                    }

                    if ($all_keywords_found) {
                        $labels_valid = true;
                        break; // No es necesario seguir buscando
                    }
                }
            }
        }
    
        $validations[] = [
            'id' => 'label',
            'name' => get_string('label', 'block_validador'),
            'passed' => $labels_valid
        ];
        return $validations;
    }

    private function questionperpagevalidation($quiz) {
        $validations = [];
        // Validación: verificar que todas las preguntas estén en una misma página
        if ($quiz->questionsperpage != 0) {
            $validations[] = [
                'id' => 'quizquestionsperpage',
                'name' => get_string('quizquestionsperpage', 'block_validador'),
                'passed' => false
            ];
        } else {
            $validations[] = [
                'id' => 'quizquestionsperpage',
                'name' => get_string('quizquestionsperpage', 'block_validador'),
                'passed' => true
            ];
        }
        return $validations;
    }

    private function validate_quiz_has_dates($quiz): array {
        $passed = !empty($quiz->timeopen) && !empty($quiz->timeclose);
        return [[
            'id'     => 'quizhasdates',
            'name'   => get_string('quizhasdates', 'block_validador'),
            'passed' => $passed,
        ]];
    }

    private function timelimitvalidation($quiz) {
        $validations = [];
        if ($quiz->timelimit == 5400 || $quiz->timelimit == 2700) { // 5400 segundos = 90 minutos, 2700 segundos = 45 minutos
            $validations[] = [
                'id' => 'quiztimelimit',
                'name' => get_string('quiztimelimit', 'block_validador'),
                'passed' => true
            ];
        } else {
            $validations[] = [
                'id' => 'quiztimelimit',
                'name' => get_string('quiztimelimit', 'block_validador'),
                'passed' => false
            ];
        }
        
        return $validations;
    }

    private function perform_validations_groupwithquizzes() {
        global $COURSE, $DB;
        // Validación: verificar que cada grupo válido tenga un cuestionario correspondiente
        $valid_group_count = 0;
        $valid_groups = [];
        $groups = groups_get_all_groups($COURSE->id);
        $min_timecreated = get_config('block_validador', 'min_group_timecreated');
        foreach ($groups as $group) {
            if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes' && $group->timecreated > $min_timecreated) {
                $valid_group_count++;
                $valid_groups[] = $group;
            }
        }

        $quizzes_valid = true;
        $has_quizzes = false;

        // Vamos a validar todos los cuestionarios.
        foreach ($valid_groups as $group) {
            // Buscar el cuestionario cuyo nombre es igual al nombre del grupo
            $quiz = $DB->get_record_sql('SELECT * FROM {quiz} WHERE course = ? AND name LIKE ?', [$COURSE->id, $group->name . '%']);
            if ($quiz) {
                $has_quizzes = true;
                // $this->validate_quiz($quiz, $group);
            } else {
                // No se encontró un cuestionario con el nombre del grupo
                $quizzes_valid = false;
                break;
            }
        }

        // Si no hay cuestionarios, no se puede dar por válido
        if (!$has_quizzes) {
            $quizzes_valid = false;
        }

        $validations[] = [
            'id' => 'groupwithquizzes',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('groupwithquizzes', 'block_validador'),
            'passed' => $quizzes_valid
        ];

        return $validations;
    }

    private function perform_validations_groups() {
        global $COURSE, $DB, $CFG;

        $validations = [];

        $groups = groups_get_all_groups($COURSE->id);
        $min_timecreated = get_config('block_validador', 'min_group_timecreated');

        // Validación: verificar que haya al menos dos grupos con nombres válidos
        $valid_group_count = 0;
        $valid_groups = [];
        foreach ($groups as $group) {
            if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes' && $group->timecreated > $min_timecreated) {
                $valid_group_count++;
                $valid_groups[] = $group;
            }
        }
        $valid_group_names = $valid_group_count >= 1;
        $validations[] = [
            'id' => 'groups',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('groups', 'block_validador'),
            'passed' => $valid_group_names
        ];

        return $validations;
    }

    private function performs_validations_gradebook_final() {
        global $COURSE, $DB, $CFG;

        $gradebook_valid = true;
        $reason = '';

        $exam_final_category = $DB->get_record_sql(
            "SELECT *
             FROM {grade_categories}
             WHERE courseid = :courseid
             AND LOWER(TRIM(fullname)) = LOWER(:fullname)",
            ['courseid' => $COURSE->id, 'fullname' => 'Examen final']
        );

        if ($exam_final_category) {
            if (isset($exam_final_category->hidden) && $exam_final_category->hidden != 0) {
                $gradebook_valid = false;
                $reason = 'La categoría no está visible';
            }
        } else {
            $gradebook_valid = false;
            $reason = 'La categoría no existe';
        }

        $validationsgradebook[] = [
            'id'        => 'gradebook',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name'      => get_string('gradebook', 'block_validador'),
            'passed'    => $gradebook_valid,
            'title'     => $reason,
        ];

        return $validationsgradebook;
    }

    private function get_exam_online_category() {
        global $COURSE, $DB;
        return $DB->get_record_sql(
            "SELECT * FROM {grade_categories}
              WHERE courseid = :courseid
                AND LOWER(TRIM(fullname)) = LOWER(:fullname)",
            ['courseid' => $COURSE->id, 'fullname' => 'Examen online']
        );
    }

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

    private function check_showc($availability) {
        if (!isset($availability->showc)) {
            return true;
        }
        return !$availability->showc[0];
    }

    public function applicable_formats() {
        return ['site' => true, 'course-view' => true];
    }

    public function instance_can_be_removed() {
        return is_siteadmin();
    }

    public function instance_can_be_hidden() {
        return is_siteadmin();
    }

    public function user_can_edit() {
        return is_siteadmin();
    }

    public function user_can_addto($page) {
        return is_siteadmin();
    }

    public function has_config() {
        return true;
    }
}