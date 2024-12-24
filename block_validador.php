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

        // Verificar la capacidad:
        if (!has_capability('block/validador:view', $this->context)) {
            // Si el usuario no tiene la capacidad, no mostrar nada.
            // Podrías retornar vacío o un mensaje, según prefieras.
            $this->content = new stdClass();
            $this->content->text = '';
            return $this->content;
        }

        // Contenido del bloque
        $this->content = new stdClass();
        $this->content->text = '';

        $validations_passed = true;

        $this->content->text .= "<h4>Grupos</h4>";
        $validationsgroups = $this->perform_validations_groups();
        foreach ($validationsgroups as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);

            $newpassed = $validation['passed'] ? 1 : 0;

            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Existe un resultado previo, verificar si cambió
                if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
                // Si no cambió, no hacemos nada.
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }
        if ($validationsgroups[0]['passed'] == 1) {
            $validations_gropupswithquizzes = $this->perform_validations_groupwithquizzes();
            foreach ($validations_gropupswithquizzes as $validation) {
                $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
                $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                $existing = $DB->get_record('block_validador_results', $params);
            
                $newpassed = $validation['passed'] ? 1 : 0;
            
                // Si no existe el registro, lo insertamos
                // Si existe, solo actualizamos si hubo un cambio en passed
                if (!$existing) {
                    $record = new stdClass();
                    $record->contextid = $contextid;
                    $record->validationname = $validation['id'];
                    $record->courseid = $COURSE->id; // Agregar el ID del curso
                    $record->passed = $newpassed;
                    $record->timecreated = time();
                    $record->timemodified = time();
                    $DB->insert_record('block_validador_results', $record);
                } else {
                    // Existe un resultado previo, verificar si cambió
                    if ($existing->passed != $newpassed) {
                        $existing->passed = $newpassed;
                        $existing->timemodified = time();
                        $DB->update_record('block_validador_results', $existing);
                    }
                    // Si no cambió, no hacemos nada.
                }
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
                $validations_passed = $validations_passed && $validation['passed'];
            }
        }
       
        
        $this->content->text .= "<h4>Libro de Calificaciones</h4>";

        $validationsgradebook = $this->performs_validations_gradebook();
        foreach ($validationsgradebook as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);
        
            $newpassed = $validation['passed'] ? 1 : 0;
        
            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Existe un resultado previo, verificar si cambió
                if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
                // Si no cambió, no hacemos nada.
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $validationsgradebooksubcategorieexamenonline = $this->performs_validations_gradebook_subcategorie_examenonline();
        foreach ($validationsgradebooksubcategorieexamenonline as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);
        
            $newpassed = $validation['passed'] ? 1 : 0;
        
            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Existe un resultado previo, verificar si cambió
                if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
                // Si no cambió, no hacemos nada.
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $validationsgradebooksubcategorieexamenpresencial = $this->performs_validations_gradebook_subcategorie_examenpresencial();
        foreach($validationsgradebooksubcategorieexamenpresencial as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);
        
            $newpassed = $validation['passed'] ? 1 : 0;
        
            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Existe un resultado previo, verificar si cambió
                if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
                // Si no cambió, no hacemos nada.
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $validationsgradebookexamenfinalaggregation = $this->validate_examen_final_aggregation();
        foreach($validationsgradebookexamenfinalaggregation as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);
        
            $newpassed = $validation['passed'] ? 1 : 0;
        
            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Existe un resultado previo, verificar si cambió
                if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
                // Si no cambió, no hacemos nada.
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $validationsgradebookexamenonlineaggregation = $this->validate_examen_online_aggregation();
        foreach($validationsgradebookexamenonlineaggregation as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);
        
            $newpassed = $validation['passed'] ? 1 : 0;
        
            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Existe un resultado previo, verificar si cambió
                if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
                // Si no cambió, no hacemos nada.
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $validationsgradebookexamenpresencialaggregation = $this->validate_examen_presencial_aggregation();
        foreach($validationsgradebookexamenpresencialaggregation as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);
        
            $newpassed = $validation['passed'] ? 1 : 0;
        
            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Existe un resultado previo, verificar si cambió
                if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
                // Si no cambió, no hacemos nada.
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $this->content->text .= "<h4>Smowl</h4>";

        // Listado de validaciones
        $validationssmowl = $this->perform_validations_smowl();
        foreach ($validationssmowl as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);

            $newpassed = $validation['passed'] ? 1 : 0;

            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Existe un resultado previo, verificar si cambió
                if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
                // Si no cambió, no hacemos nada.
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        if ($validationsgroups[0]['passed'] == 1) {
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
            foreach ($valid_groups as $group) {
                $quiz = $DB->get_record_sql('SELECT * FROM {quiz} WHERE course = ? AND name LIKE ?', [$COURSE->id, $group->name . '%']);
                
                $this->content->text .= "<strong>Cuestionario: {$quiz->name}</strong><br>";
                
                // Validación del límite de tiempo
    
                $cm = get_coursemodule_from_instance('quiz', $quiz->id);
                $context = context_module::instance($cm->id);
                $contextid = $context->id;
    
                $validationstimelimit = $this->timelimitvalidation($quiz);
                foreach ($validationstimelimit as $validation) {
                    // $contextid = context_module::instance($quiz->cmid)->id;
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Existe un resultado previo, verificar si cambió
                        if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                        // Si no cambió, no hacemos nada.
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;' title='El tiempo deben ser 90 minutos'>$status{$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
                }
    
                // Validación: verificar que todas las preguntas estén en una misma página
                $validationsquestionperpage = $this->questionperpagevalidation($quiz);
                foreach ($validationsquestionperpage as $validation) {
                    // $contextid = context_module::instance($quiz->cmid)->id;
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Existe un resultado previo, verificar si cambió
                        if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                        // Si no cambió, no hacemos nada.
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;' title='Todas las preguntas en una única página'>$status{$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
                }
    
                // Validación: verificar que el cuestionario tenga restricciones de grupo
                $validationsgrouprestiction = $this->grouprestictionvalidation($quiz, $group);
                foreach ($validationsgrouprestiction as $validation) {
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Existe un resultado previo, verificar si cambió
                        if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                        // Si no cambió, no hacemos nada.
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;' title='El cuestionario tiene restricción por grupo'>$status{$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
                }
    
                // Validacion: verificar label
                $validationslabel = $this->labelvalidation($quiz);
                foreach ($validationslabel as $validation) {
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Existe un resultado previo, verificar si cambió
                        if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                        // Si no cambió, no hacemos nada.
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
    
                }
    
                // Validacion: verificar nota de aprobado
                $validationsgradetopass = $this->gradetopass($quiz);
                foreach ($validationsgradetopass as $validation) {
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Existe un resultado previo, verificar si cambió
                        if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                        // Si no cambió, no hacemos nada.
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
                }
    
                // Validacion: verificar categoria de calificación
                $validationsquizgradecategory = $this->validate_quiz_grade_category($quiz);
                foreach ($validationsquizgradecategory as $validation) {
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Existe un resultado previo, verificar si cambió
                        if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                        // Si no cambió, no hacemos nada.
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
                }
    
                // Validacion: verificar envio automatico
                $validationsquizautosubmit = $this->validate_quiz_auto_submit($quiz);
                foreach ($validationsquizautosubmit as $validation) {
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Existe un resultado previo, verificar si cambió
                        if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                        // Si no cambió, no hacemos nada.
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
                }
    
                // Validacion: verificar opciones de revisión
                $validationsquizreviewoptions = $this->validate_quiz_review_options($quiz);
                foreach ($validationsquizreviewoptions as $validation) {
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Existe un resultado previo, verificar si cambió
                        if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                        // Si no cambió, no hacemos nada.
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
                }
            }
        }
        

        // Finalmente, mostrar el estado global
        $emoji = $validations_passed ? '✅' : '❌';
        $message = $validations_passed ? 'Curso Validado' : 'Curso No Validado';
        $message = "<span style='font-size: 2em;'>$message</span>";

        // Añadir el mensaje al inicio (o al final)
        $this->content->text = "$emoji $message<br>" . $this->content->text;

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
            $category = $DB->get_record('grade_categories', ['id' => $grade_item->categoryid]);
            if ($category && $category->fullname == 'Examen online') {
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
                    $expected_text = 'Si tiene problemas técnicos para acceder al examen, contacte por correo electrónico a la siguiente dirección: innovacion@udima.es';
                    // Comparar ignorando formato.
                    if (strpos($intro_text, $expected_text) !== false) {
                        $labels_valid = true;
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

    private function timelimitvalidation($quiz) {
        $validations = [];
        if ($quiz->timelimit == 5400) { // 5400 segundos = 90 minutos
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
        foreach ($groups as $group) {
            if (preg_match('/^#\d{5}#$/', $group->name)) {
                $valid_group_count++;
                $valid_groups[] = $group;
            }
        }
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
            'id' => 'groups',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('groups', 'block_validador'),
            'passed' => $valid_group_names
        ];

        return $validations;
    }


    private function performs_validations_gradebook() {
        global $COURSE, $DB, $CFG;
        // Validación: verificar la estructura del libro de calificaciones
        $gradebook_valid = true;
        // Obtener la categoría "Exámen final"
        $exam_final_category = $DB->get_record('grade_categories', ['courseid' => $COURSE->id, 'fullname' => 'Examen final']);
        if ($exam_final_category) {
            // Verificar que el método de calificación sea "Calificación más alta" (GRADE_AGGREGATE_MAX)

            /// >>>>>>>>>>>>>> AQUI VA LA VALIDACIÓN DEL LIBRO DE CALIFICACIONES <<<<<<<<<<<<<<
        } else {
            $gradebook_valid = false;
        }

        $validationsgradebook[] = [
            'id' => 'gradebook',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('gradebook', 'block_validador'),
            'passed' => $gradebook_valid
        ];

        return $validationsgradebook;
    }

    private function performs_validations_gradebook_subcategorie_examenonline() {
        global $COURSE, $DB, $CFG;
        // Validación: verificar la estructura del libro de calificaciones
        $gradebook_valid = true;
        // Obtener la categoría "Exámen final"
        $exam_final_category = $DB->get_record('grade_categories', ['courseid' => $COURSE->id, 'fullname' => 'Examen final']);
        if ($exam_final_category) {
            // Obtener las subcategorías de la categoría "Examen final"
            $subcategories = $DB->get_records('grade_categories', ['parent' => $exam_final_category->id]);

            // Verificar que exista al menos una subcategoría llamada "Examen online"
            $exam_online_subcategory_exists = false;
            foreach ($subcategories as $subcategory) {
                if ($subcategory->fullname == 'Examen online') {
                    $exam_online_subcategory_exists = true;
                    break;
                }
            }

            if (!$exam_online_subcategory_exists) {
                $gradebook_valid = false;
            }
        } else {
            $gradebook_valid = false;
        }

        $validationsgradebook[] = [
            'id' => 'gradebook_subcategorie_examenonline',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('gradebook_subcategorie_examenonline', 'block_validador'),
            'passed' => $gradebook_valid
        ];

        return $validationsgradebook;
    }

    private function performs_validations_gradebook_subcategorie_examenpresencial() {
        global $COURSE, $DB, $CFG;
        // Validación: verificar la estructura del libro de calificaciones
        $gradebook_valid = true;
        // Obtener la categoría "Exámen final"
        $exam_final_category = $DB->get_record('grade_categories', ['courseid' => $COURSE->id, 'fullname' => 'Examen final']);
        if ($exam_final_category) {
            // Obtener las subcategorías de la categoría "Examen final"
            $subcategories = $DB->get_records('grade_categories', ['parent' => $exam_final_category->id]);

            // Verificar que exista al menos una subcategoría llamada "Examen online"
            $exam_online_subcategory_exists = false;
            foreach ($subcategories as $subcategory) {
                if ($subcategory->fullname == 'Examen presencial') {
                    $exam_online_subcategory_exists = true;
                    break;
                }
            }

            if (!$exam_online_subcategory_exists) {
                $gradebook_valid = false;
            }
        } else {
            $gradebook_valid = false;
        }

        $validationsgradebook[] = [
            'id' => 'gradebook_subcategorie_examenpresencial',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('gradebook_subcategorie_examenpresencial', 'block_validador'),
            'passed' => $gradebook_valid
        ];

        return $validationsgradebook;
    }

    private function validate_examen_final_aggregation() {
        global $DB, $COURSE;
        $aggregation_correcta = true;
    
        $exam_final_category = $DB->get_record('grade_categories', ['courseid' => $COURSE->id, 'fullname' => 'Examen final']);
        if ($exam_final_category) {
            if ($exam_final_category->aggregation != GRADE_AGGREGATE_MAX) { // Usar GRADE_AGGREGATE_MAX
                $aggregation_correcta = false;
            }
        } else {
            $aggregation_correcta = false;
        }
    
        $validationsgradebook[] = [
            'id' => 'gradebook_examenfinal_aggregation',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('gradebook_examenfinal_aggregation', 'block_validador'),
            'passed' => $aggregation_correcta
        ];
    
        return $validationsgradebook;
    }

    private function validate_examen_presencial_aggregation() {
        global $DB, $COURSE;
        $aggregation_correcta = true;
    
        $exam_presencial_category = $DB->get_record('grade_categories', ['courseid' => $COURSE->id, 'fullname' => 'Examen presencial']);
        if ($exam_presencial_category) {
            if ($exam_presencial_category->aggregation != GRADE_AGGREGATE_MAX) { // Usar GRADE_AGGREGATE_MAX
                $aggregation_correcta = false;
            }
        } else {
            $aggregation_correcta = false;
        }
    
        $validationsgradebook[] = [
            'id' => 'gradebook_examenpresencial_aggregation',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('gradebook_examenpresencial_aggregation', 'block_validador'),
            'passed' => $aggregation_correcta
        ];
    
        return $validationsgradebook;
    }



    private function validate_examen_online_aggregation() {
        global $DB, $COURSE;
        $aggregation_correcta = true;
    
        $exam_online_category = $DB->get_record('grade_categories', ['courseid' => $COURSE->id, 'fullname' => 'Examen online']);
        if ($exam_online_category) {
            if ($exam_online_category->aggregation != GRADE_AGGREGATE_MEAN) { // Usar GRADE_AGGREGATE_MEAN
                $aggregation_correcta = false;
            }
        } else {
            $aggregation_correcta = false;
        }
    
        $validationsgradebook[] = [
            'id' => 'gradebook_examenonline_aggregation',
            'contextid' => context_course::instance($COURSE->id)->id,
            'name' => get_string('gradebook_examenonline_aggregation', 'block_validador'),
            'passed' => $aggregation_correcta
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
            'id' => 'grouprestriccion',
            'name' => get_string('grouprestriccion', 'block_validador'),
            'passed' => $conrestriccion,
        ];  
        return $validations;
    }

    private function has_group_restriction($availability, $groupid) {
        if (isset($availability->type) && $availability->type == 'group' && isset($availability->id) && $availability->id == $groupid) {
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

    public function has_config() {
        return true;
    }

    // public function applicable_formats() {
    //     return [
    //         'admin' => false,
    //         'site-index' => false,
    //         'course-view' => true,
    //         'mod' => false,
    //         'my' => false
    //     ];
    // }
}