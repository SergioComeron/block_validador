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

class block_validador extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_validador');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $COURSE, $DB, $CFG;

        // Contenido del bloque
        $this->content = new stdClass();
        
        // Realizar las validaciones
        $validations = $this->perform_validations();
        $validations_passed = array_reduce($validations, function($carry, $item) {
            return $carry && $item['passed'];
        }, true);

        // Mostrar el emoji y el mensaje correspondiente según las validaciones
        $emoji = $validations_passed ? '✅' : '❌';
        $message = $validations_passed ? 'Curso Validado' : 'Curso No Validado';
        $message = "<span style='font-size: 2em;'>$message</span>";
        $this->content->text = "$emoji $message<br>";
        $this->content->text .= "<h4>Grupos</h4>";
        // Listado de validaciones
        foreach ($validations as $validation) {
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
        }
        
        $this->content->text .= "<h4>Libro de Calificaciones</h4>";
        $validationsgradebook = $this->performs_validations_gradebook();

        // Listado de validaciones
        foreach ($validationsgradebook as $validation) {
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
        }

        $this->content->text .= "<h4>Cuestionarios</h4>";
        $groups = groups_get_all_groups($COURSE->id);

        $valid_group_count = 0;
        $valid_groups = [];
        foreach ($groups as $group) {
            if (preg_match('/^#\d{5}#$/', $group->name)) {
                $valid_group_count++;
                $valid_groups[] = $group;
            }
        }
        // print_r($valid_groups);
        foreach ($valid_groups as $group) {
            // $this->content->text .= "<br>Grupo: {$group->name}<br>";
            $quiz = $DB->get_record_sql('SELECT * FROM {quiz} WHERE course = ? AND name LIKE ?', [$COURSE->id, $group->name . '%']);
            $this->content->text .= "<strong>Cuestionario: {$quiz->name}</strong><br>";
            
            // Validación del límite de tiempo
            $validationstimelimit = $this->timelimitvalidation($quiz);
            foreach ($validationstimelimit as $validation) {
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            }

            // Validación: verificar que todas las preguntas estén en una misma página
            $validationsquestionperpage = $this->questionperpagevalidation($quiz);
            foreach ($validationsquestionperpage as $validation) {
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            }

            // Validación: verificar que el cuestionario tenga restricciones de grupo
            $validationsgrouprestiction = $this->grouprestictionvalidation($quiz, $group);
            foreach ($validationsgrouprestiction as $validation) {
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            }

            // Validacion: verificar label
            $validationslabel = $this->labelvalidation($quiz);
            foreach ($validationslabel as $validation) {
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            }

            // Validacion: verificar nota de aprobado
            $validationsgradetopass = $this->gradetopass($quiz);
            foreach ($validationsgradetopass as $validation) {
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            }

            // Validacion: verificar categoria de calificación
            $validationsquizgradecategory = $this->validate_quiz_grade_category($quiz);
            foreach ($validationsquizgradecategory as $validation) {
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            }

            // Validacion: verificar envio automatico
            $validationsquizautosubmit = $this->validate_quiz_auto_submit($quiz);
            foreach ($validationsquizautosubmit as $validation) {
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            }

            // Validacion: verificar opciones de revisión
            $validationsquizreviewoptions = $this->validate_quiz_review_options($quiz);
            foreach ($validationsquizreviewoptions as $validation) {
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            }
        }
        $this->content->footer = '';

        return $this->content;
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
        if ($review_options_immediately->attempt != \mod_quiz\question\display_options::VISIBLE ||
            $review_options_immediately->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->marks != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_immediately->overallfeedback != \mod_quiz\question\display_options::HIDDEN) {
            $review_options_valid = false;
        }

        // Verificar las opciones de revisión mientras el cuestionario está abierto
        if ($review_options_open->attempt != \mod_quiz\question\display_options::VISIBLE ||
            $review_options_open->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->marks != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_open->overallfeedback != \mod_quiz\question\display_options::HIDDEN) {
            $review_options_valid = false;
        }

        // Verificar las opciones de revisión después de cerrar el cuestionario
        if ($review_options_closed->attempt != \mod_quiz\question\display_options::VISIBLE ||
            $review_options_closed->correctness != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->marks != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->generalfeedback != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->rightanswer != \mod_quiz\question\display_options::HIDDEN ||
            $review_options_closed->overallfeedback != \mod_quiz\question\display_options::HIDDEN) {
            $review_options_valid = false;
        }
    
        $validations[] = [
            'name' => 'Opciones de Revisión del Cuestionario',
            'passed' => $review_options_valid
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
            'name' => 'Envío Automático al Terminar el Tiempo',
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
            $category = $DB->get_record('grade_categories', ['id' => $grade_item->categoryid]);
            if ($category && $category->fullname == 'Examen online') {
                $category_valid = true;
            }
        }

        $validations[] = [
            'name' => 'Categoría de Calificación Examen online',
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
                'name' => 'Nota de Aprobado',
                'passed' => true
            ];
        } else {
            $validations[] = [
                'name' => 'Nota de Aprobado',
                'passed' => false
            ];
        }
    
        return $validations;
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
        $label_found = false;
        foreach ($sequence as $cmid) {
            $cm = get_coursemodule_from_id(null, $cmid);
            if ($cm && $cm->modname == 'label') {
                $label = $DB->get_record('label', ['id' => $cm->instance]);
                if ($label) {
                    // Eliminar etiquetas HTML del texto del label.
                    $intro_text = strip_tags($label->intro);

                    // Texto a buscar, sin formato.
                    $expected_text = 'Si tiene problemas técnicos para acceder al examen, contacte por correo electrónico a la siguiente dirección: innovación@udima.es.';

                    // Comparar ignorando formato.
                    if (strpos($intro_text, $expected_text) !== false) {
                        $labels_valid = true;
                    }
                }
            }
        }

        $validations[] = [
            'name' => 'Recursos de Texto y Medios',
            'passed' => $labels_valid
        ];
        return $validations;
    }

    private function questionperpagevalidation($quiz) {
        $validations = [];
        // Validación: verificar que todas las preguntas estén en una misma página
        if ($quiz->questionsperpage != 0) {
            $validations[] = [
                'name' => 'Preguntas en una sola página',
                'passed' => false
            ];
        } else {
            $validations[] = [
                'name' => 'Preguntas en una sola página',
                'passed' => true
            ];
        }
        return $validations;
    }

    private function timelimitvalidation($quiz) {
        $validations = [];
        if ($quiz->timelimit == 5400) { // 5400 segundos = 90 minutos
            $validations[] = [
                'name' => 'Límite de Tiempo del Cuestionario',
                'passed' => true
            ];
        } else {
            $validations[] = [
                'name' => 'Límite de Tiempo del Cuestionario',
                'passed' => false
            ];
        }
        return $validations;
    }

    private function perform_validations() {
        global $COURSE, $DB, $CFG;

        require_once($CFG->libdir.'/gradelib.php'); // Necesario para usar GRADE_AGGREGATE_MAX

        $validations = [];

        // Validación: verificar que existan grupos en el curso
        $groups = groups_get_all_groups($COURSE->id);
        $groups_exist = !empty($groups);
        $validations[] = [
            'name' => 'Grupos',
            'passed' => $groups_exist
        ];

        // Validación: verificar que haya al menos dos grupos con nombres válidos
        $valid_group_count = 0;
        $valid_groups = [];
        foreach ($groups as $group) {
            if (preg_match('/^#\d{5}#$/', $group->name)) {
                $valid_group_count++;
                $valid_groups[] = $group;
            }
        }
        $valid_group_names = $valid_group_count >= 2;
        $validations[] = [
            'name' => 'Nombres de Grupos Válidos',
            'passed' => $valid_group_names
        ];

        // Validación: verificar que cada grupo válido tenga un cuestionario correspondiente
        $quizzes_valid = true;
        // Vamos a validar todos los cuestionarios.
        foreach ($valid_groups as $group) {
            // Buscar el cuestionario cuyo nombre es igual al nombre del grupo
            $quiz = $DB->get_record_sql('SELECT * FROM {quiz} WHERE course = ? AND name LIKE ?', [$COURSE->id, $group->name . '%']);
            if ($quiz) {
                
                // $this->validate_quiz($quiz, $group);

            } else {
                // No se encontró un cuestionario con el nombre del grupo
                $quizzes_valid = false;
                break;
            }
        }
        $validations[] = [
            // 'name' => 'Cuestionarios por Grupo',
            'name' => 'Todos los grupos tienen cuestionarios',
            'passed' => $quizzes_valid
        ];

        // Validación: verificar que cada cuestionario tenga un área de texto y medios en la misma semana
        $resources_valid = true;
        foreach ($valid_groups as $group) {
            // Buscar el cuestionario cuyo nombre es igual al nombre del grupo
            $quiz = $DB->get_record('quiz', ['course' => $COURSE->id, 'name' => $group->name]);
            if ($quiz) {
                $cm = get_coursemodule_from_instance('quiz', $quiz->id, $COURSE->id);
                $sectionquiz = $DB->get_record_sql('SELECT section FROM {course_modules} WHERE id = ?', [$cm->id]);
                $section = $DB->get_record('course_sections', ['id' => $sectionquiz->section]);
                $sequence = explode(',', $section->sequence);
                $label_found = false;

                foreach ($sequence as $cmid) {
                    $cm = get_coursemodule_from_id(null, $cmid);
                    if ($cm && $cm->modname == 'label') {
                        $label = $DB->get_record('label', ['id' => $cm->instance]);
                        if ($label && strpos($label->intro, 'Si tiene problemas técnicos para acceder al examen, contacte por correo electrónico a la siguiente dirección:') !== false) {
                            $label_found = true;
                            break;
                        }
                    }
                }

                if (!$label_found) {
                    $quizzes_valid = false;
                    break;
                }
            } else {
                $labels_valid = false;
                break;
            }
        }
        return $validations;
    }


    private function performs_validations_gradebook() {
        global $COURSE, $DB, $CFG;
        // Validación: verificar la estructura del libro de calificaciones
        $gradebook_valid = true;
        // Obtener la categoría "Exámen final"
        $exam_final_category = $DB->get_record('grade_categories', ['courseid' => $COURSE->id, 'fullname' => 'Examen final']);
        if ($exam_final_category == null) {
            // Verificar que el método de calificación sea "Calificación más alta" (GRADE_AGGREGATE_MAX)

            /// >>>>>>>>>>>>>> AQUI VA LA VALIDACIÓN DEL LIBRO DE CALIFICACIONES <<<<<<<<<<<<<<
        } else {
            $gradebook_valid = false;
        }

        $validationsgradebook[] = [
            'name' => 'Hay categoría Examen final',
            'passed' => $gradebook_valid
        ];

        return $validationsgradebook;
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
            'name' => 'Restricciones de Grupo',
            'passed' => $conrestriccion,
        ];  
        return $validations;
    }

    private function has_group_restriction($availability, $groupid) {
        if (isset($availability->type) && $availability->type == 'group' && isset($availability->id) && $availability->id == $groupid) {
            // echo "<br>Restricción de grupo encontrada<br>";
            return true;
        } elseif (isset($availability->op) && isset($availability->c)) {
            foreach ($availability->c as $condition) {
                if ($this->has_group_restriction($condition, $groupid)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function check_showc($availability) {
        if (!isset($availability->showc) || (isset($availability->showc[0]) && !$availability->showc[0] == 1)) {
            return true;
        }
        return false;
    }

    private function validate_quiz_time_limit($valid_groups) {
        global $COURSE, $DB;

        foreach ($valid_groups as $group) {
            // Buscar el cuestionario cuyo nombre es igual al nombre del grupo
            $quiz = $DB->get_record('quiz', ['course' => $COURSE->id, 'name' => $group->name]);
            if ($quiz) {
                // Validación del límite de tiempo
                if ($quiz->timelimit != 5400) { // 5400 segundos = 90 minutos
                    return false;
                }
            } else {
                // Si no se encuentra el cuestionario, la validación ya falló anteriormente
                return false;
            }
        }
        return true;
    }
}