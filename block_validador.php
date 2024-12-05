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

        global $COURSE;

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
        $this->content->text = "$emoji $message<br>";

        // Listado de validaciones
        foreach ($validations as $validation) {
            $status = $validation['passed'] ? 'Sí' : 'No';
            $this->content->text .= "{$validation['name']}: $status<br>";
        }

        $this->content->footer = '';

        return $this->content;
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
        foreach ($valid_groups as $group) {
            // echo "<br><br><br><br><br><br><br><br><br><br><br><br>Grupo encontrado: {$group->name}<br>";
            // Buscar el cuestionario cuyo nombre es igual al nombre del grupo
            $quiz = $DB->get_record_sql('SELECT * FROM {quiz} WHERE course = ? AND name LIKE ?', [$COURSE->id, $group->name . '%']);
            if ($quiz) {

                



                // echo "Cuestionario encontrado: {$quiz->name}<br>";
                $cm = get_coursemodule_from_instance('quiz', $quiz->id, $COURSE->id);
                if ($cm && $cm->availability) {
                    $availability = json_decode($cm->availability);
                    // print_r($availability);
                    // echo '';
                    if ($this->has_group_restriction($availability, $group->id)) {
                        // echo '<br>visible para otros grupos: '.$this->check_showc($availability).'<br>';
                        if ($this->check_showc($availability)) {
                            // echo "No se puede mostrar el cuestionari a otros grupos<br>";
                            $quizzes_valid = false;
                            break;
                        }
                        continue;
                    } else {
                        $quizzes_valid = false;
                        break;
                    }
                } else {
                    // El cuestionario no tiene restricciones de acceso
                    // echo "No hay restricciones de acceso<br>";
                    $quizzes_valid = false;
                    break;
                }
            } else {
                // No se encontró un cuestionario con el nombre del grupo
                // echo "No se encontró un cuestionario con el nombre del grupo<br>";
                $quizzes_valid = false;
                break;
            }
        }
        $validations[] = [
            'name' => 'Cuestionarios por Grupo',
            'passed' => $quizzes_valid
        ];

        // Validación: verificar la estructura del libro de calificaciones
        $gradebook_valid = true;

        // Obtener la categoría "Exámen final"
        $exam_final_category = $DB->get_record('grade_categories', ['courseid' => $COURSE->id, 'fullname' => 'Exámen final']);
        if ($exam_final_category) {
            // Verificar que el método de calificación sea "Calificación más alta" (GRADE_AGGREGATE_MAX)
            // if ($exam_final_category->aggregation == GRADE_AGGREGATE_MAX) {
                // // Obtener las subcategorías de "Exámen final"
                // $subcategories = $DB->get_records('grade_categories', ['parent' => $exam_final_category->id]);
                // $subcategories_names = [];
                // foreach ($subcategories as $subcategory) {
                //     $subcategories_names[] = $subcategory->fullname;
                // }
                // if (in_array('Examen online', $subcategories_names) && in_array('Examen presencial', $subcategories_names)) {
                //     // La validación es exitosa
                // } else {
                //     $gradebook_valid = false;
                // }
            // } else {
                // $gradebook_valid = false;
            // }
        } else {
            $gradebook_valid = false;
        }

        $validations[] = [
            'name' => 'Estructura del Libro de Calificaciones',
            'passed' => $gradebook_valid
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
            return false;
        }
        return true;
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

    public function validate_specific_quiz($quizid) {
        global $DB;

        // Obtener el cuestionario por su ID
        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        if (!$quiz) {
            return [
                'name' => 'Cuestionario Específico',
                'passed' => false,
                'message' => 'El cuestionario no existe'
            ];
        }

        // Validación del límite de tiempo
        if ($quiz->timelimit != 5400) { // 5400 segundos = 90 minutos
            return [
                'name' => 'Cuestionario Específico',
                'passed' => false,
                'message' => 'El límite de tiempo no es de 90 minutos'
            ];
        }

        // Validación de restricciones de grupo
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
        if ($cm && $cm->availability) {
            $availability = json_decode($cm->availability);
            if (!$this->has_group_restriction($availability, $quiz->groupid)) {
                return [
                    'name' => 'Cuestionario Específico',
                    'passed' => false,
                    'message' => 'El cuestionario no tiene restricciones de grupo adecuadas'
                ];
            }
        } else {
            return [
                'name' => 'Cuestionario Específico',
                'passed' => false,
                'message' => 'El cuestionario no tiene restricciones de acceso'
            ];
        }

        return [
            'name' => 'Cuestionario Específico',
            'passed' => true,
            'message' => 'El cuestionario cumple con todas las validaciones'
        ];
    }
}