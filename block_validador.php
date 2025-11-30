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
        if (!has_capability('block/validador:view', context_course::instance($COURSE->id))) {
            // Si el usuario no tiene la capacidad, no mostrar nada.
            // Podrías retornar vacío o un mensaje, según prefieras.
            $this->content = new stdClass();
            $this->content->text = '';
            return $this->content;
        }

        // Obtener la configuración de categorías permitidas
        $allowed_categories = get_config('block_validador', 'showcategories');

        // Verificar si la categoría del curso actual está en la lista de categorías permitidas
        if (!in_array($COURSE->category, explode(',', $allowed_categories))) {
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
                // Inserción normal.
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Si ya existe y tiene passed = 2, no se vuelve a validar.
                if ($existing->passed == 2) {
                    // No hacer nada.
                } else if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
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
                    // Inserción normal.
                    $record = new stdClass();
                    $record->contextid = $contextid;
                    $record->validationname = $validation['id'];
                    $record->courseid = $COURSE->id; // Agregar el ID del curso
                    $record->passed = $newpassed;
                    $record->timecreated = time();
                    $record->timemodified = time();
                    $DB->insert_record('block_validador_results', $record);
                } else {
                    // Si ya existe y tiene passed = 2, no se vuelve a validar.
                    if ($existing->passed == 2) {
                        // No hacer nada.
                    } else if ($existing->passed != $newpassed) {
                        $existing->passed = $newpassed;
                        $existing->timemodified = time();
                        $DB->update_record('block_validador_results', $existing);
                    }
                }
                $status = $validation['passed'] ? '🟢' : '🔴';
                $color = $validation['passed'] ? 'black' : 'red';
                $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
                $validations_passed = $validations_passed && $validation['passed'];
            }
        }
       
        
        $this->content->text .= "<h4>Libro de Calificaciones</h4>";

        $validationsgradebook = $this->performs_validations_gradebook_final();
        foreach ($validationsgradebook as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);
        
            $newpassed = $validation['passed'] ? 1 : 0;
        
            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                // Inserción normal.
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Si ya existe y tiene passed = 2, no se vuelve a validar.
                if ($existing->passed == 2) {
                    // No hacer nada.
                } else if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        $validationsgradebooksubcategorieexamenonline = $this->performs_validations_gradebook_online();
        foreach ($validationsgradebooksubcategorieexamenonline as $validation) {
            $contextid = $validation['contextid']; // Asegúrate de que 'contextid' esté disponible en tu validación
            $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
            $existing = $DB->get_record('block_validador_results', $params);
        
            $newpassed = $validation['passed'] ? 1 : 0;
        
            // Si no existe el registro, lo insertamos
            // Si existe, solo actualizamos si hubo un cambio en passed
            if (!$existing) {
                // Inserción normal.
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Si ya existe y tiene passed = 2, no se vuelve a validar.
                if ($existing->passed == 2) {
                    // No hacer nada.
                } else if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
            }
            $status = $validation['passed'] ? '🟢' : '🔴';
            $color = $validation['passed'] ? 'black' : 'red';
            $this->content->text .= "<span style='color: $color;'>$status{$validation['name']}</span><br>";
            $validations_passed = $validations_passed && $validation['passed'];
        }

        /* $validationsgradebooksubcategorieexamenpresencial = $this->performs_validations_gradebook_subcategorie_examenpresencial();
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
        } */

        /* $validationsgradebookexamenfinalaggregation = $this->validate_examen_final_aggregation();
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
        } */

        /* $validationsgradebookexamenonlineaggregation = $this->validate_examen_online_aggregation();
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
        } */

        /* $validationsgradebookexamenpresencialaggregation = $this->validate_examen_presencial_aggregation();
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
        } */

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
                // Inserción normal.
                $record = new stdClass();
                $record->contextid = $contextid;
                $record->validationname = $validation['id'];
                $record->courseid = $COURSE->id; // Agregar el ID del curso
                $record->passed = $newpassed;
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('block_validador_results', $record);
            } else {
                // Si ya existe y tiene passed = 2, no se vuelve a validar.
                if ($existing->passed == 2) {
                    // No hacer nada.
                } else if ($existing->passed != $newpassed) {
                    $existing->passed = $newpassed;
                    $existing->timemodified = time();
                    $DB->update_record('block_validador_results', $existing);
                }
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
                if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes') {
                    $valid_group_count++;
                    $valid_groups[] = $group;
                }
            }
            foreach ($valid_groups as $group) {
                $quiz = $DB->get_record_sql('SELECT * FROM {quiz} WHERE course = ? AND name LIKE ?', [$COURSE->id, $group->name . '%']);
                if ($quiz) {
                    $this->content->text .= "<strong>Cuestionario: {$quiz->name}</strong><br>";
                
                // Validación del límite de tiempo
    
                $cm = get_coursemodule_from_instance('quiz', $quiz->id);
                $context = context_module::instance($cm->id);
                $contextid = $context->id;
    
                $validationspledgeaccess = $this->validate_quiz_pledge_access($quiz);
                foreach ($validationspledgeaccess as $validation) {
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
                    $newpassed = $validation['passed'] ? 1 : 0;
                    if (!$existing) {
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id;
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    $this->content->text .= "<span style='color: $color;'>$status {$validation['name']}</span><br>";
                    $validations_passed = $validations_passed && $validation['passed'];
                }


                $validationstimelimit = $this->timelimitvalidation($quiz);
                foreach ($validationstimelimit as $validation) {
                    // $contextid = context_module::instance($quiz->cmid)->id;
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
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
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
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
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
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
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
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

                // Validacion: verificar cuestionario con pledge encima
                $validationspledge = $this->check_quiz_has_pledge_above($quiz, $group);
                // Forzar que siempre sea un array para iterar, incluso si se retorna bool
                if (!is_array($validationspledge)) {
                    $validationspledge = [[
                        'id'      => 'quizhaspledgeabove',
                        'name'    => get_string('quizhaspledgeabove', 'block_validador'),
                        'passed'  => $validationspledge
                    ]];
                }
                foreach ($validationspledge as $validation) {
                    $params = ['contextid' => $contextid, 'validationname' => $validation['id']];
                    $existing = $DB->get_record('block_validador_results', $params);
    
                    $newpassed = $validation['passed'] ? 1 : 0;
    
                    // Si no existe el registro, lo insertamos
                    // Si existe, solo actualizamos si hubo un cambio en passed
                    if (!$existing) {
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
                            $existing->passed = $newpassed;
                            $existing->timemodified = time();
                            $DB->update_record('block_validador_results', $existing);
                        }
                    }
                    $status = $validation['passed'] ? '🟢' : '🔴';
                    $color = $validation['passed'] ? 'black' : 'red';
                    // Mostrar el resultado en el listado, tanto si se valida como si no
                    $this->content->text .= "<span style='color: $color;'>$status {$validation['name']}</span><br>";
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
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
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
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
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
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
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
                        // Inserción normal.
                        $record = new stdClass();
                        $record->contextid = $contextid;
                        $record->validationname = $validation['id'];
                        $record->courseid = $COURSE->id; // Agregar el ID del curso
                        $record->passed = $newpassed;
                        $record->timecreated = time();
                        $record->timemodified = time();
                        $DB->insert_record('block_validador_results', $record);
                    } else {
                        // Si ya existe y tiene passed = 2, no se vuelve a validar.
                        if ($existing->passed == 2) {
                            // No hacer nada.
                        } else if ($existing->passed != $newpassed) {
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

        // Obtener el course module del cuestionario.
        $quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $COURSE->id);
        if (!$quizcm) {
            return false;
        }

        // Obtener la sección en la que se encuentra el cuestionario.
        $section = $DB->get_record('course_sections', ['id' => $quizcm->section, 'course' => $COURSE->id]);
        if (!$section || empty($section->sequence)) {
            return false;
        }

        // Separar la secuencia de course modules y buscar la posición del cuestionario.
        $cmids = explode(',', $section->sequence);
        $currentIndex = array_search($quizcm->id, $cmids);
        if ($currentIndex === false || $currentIndex === 0) {
            return false;
        }

        // Obtener el course module que está justo antes del cuestionario.
        $prevCmid = $cmids[$currentIndex - 1];
        $prevModule = get_coursemodule_from_id(null, $prevCmid, 0, false, IGNORE_MISSING);
        if (!$prevModule) {
            return false;
        }

        if ($prevModule && $prevModule->modname === 'pledge') {
            // Verificar que el pledge esté configurado para completarse al ser visto.
            // Suponemos que esta configuración se almacena en el campo 'completionview'
            // y que el valor 1 indica que se marca como completado al verse.
            if (isset($prevModule->completionview) && $prevModule->completionview == 1) {
                // Además, verificar que el pledge tenga una restricción de grupo y que se pertenezca al grupo $group.
                if (!empty($prevModule->availability)) {
                    $availability = json_decode($prevModule->availability);
                    if (isset($availability->c) && is_array($availability->c)) {
                        foreach ($availability->c as $condition) {
                            if (isset($condition->type) && $condition->type == 'group' && isset($condition->id) && $condition->id == $group->id) {
                                $has_pledge_above = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        $validations[] = [
            'id' => 'quizhaspledgeabove',
            'name' => get_string('quizhaspledgeabove', 'block_validador'),
            'passed' => $has_pledge_above
        ];

        return $validations;
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
                        'innovacion@udima.es',
                        'examenes@udima.es',
                        'académica'
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
        foreach ($groups as $group) {
            if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes' && $group->timecreated > 1738337214) {
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


        // Validación: verificar que haya al menos dos grupos con nombres válidos
        $valid_group_count = 0;
        $valid_groups = [];
        foreach ($groups as $group) {
            if (preg_match('/^#\d{6}#$/', $group->name) && $group->idnumber == 'planiexamenes') {
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
    
        // Validación: verificar la estructura del libro de calificaciones
        $gradebook_valid = true;
    
        // Obtener la categoría "Examen final" (insensible a mayúsculas/minúsculas)
        $exam_final_category = $DB->get_record_sql(
            "SELECT * 
             FROM {grade_categories} 
             WHERE courseid = :courseid 
             AND LOWER(TRIM(fullname)) = LOWER(:fullname)",
            ['courseid' => $COURSE->id, 'fullname' => 'Examen final']
        );
    
        if ($exam_final_category) {
            // La categoría debe estar visible (hidden = 0)
            if (isset($exam_final_category->hidden) && $exam_final_category->hidden != 0) {
                $gradebook_valid = false;
            }
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

    private function performs_validations_gradebook_online() {
    global $COURSE, $DB, $CFG;

    $gradebook_valid = true;

    // Obtener la categoría "Examen online" (nombre insensible a mayúsculas/minúsculas)
    $exam_online_category = $DB->get_record_sql(
        "SELECT * 
         FROM {grade_categories} 
         WHERE courseid = :courseid 
         AND LOWER(TRIM(fullname)) = LOWER(:fullname)",
        ['courseid' => $COURSE->id, 'fullname' => 'Examen online']
    );

    if (!$exam_online_category || !isset($exam_online_category->hidden) || $exam_online_category->hidden != 1) {
        $gradebook_valid = false;
    } else {
        // Buscar el ítem asociado a la categoría
        $category_grade_item = $DB->get_record('grade_items', [
            'itemtype' => 'category',
            'iteminstance' => $exam_online_category->id,
            'courseid' => $COURSE->id
        ]);

        // Validar que el peso (aggregationcoef) es 0
        if (!$category_grade_item || $category_grade_item->aggregationcoef2 != 0) {
            $gradebook_valid = false;
        }
    }

    $validationsgradebook[] = [
        'id' => 'gradebook_subcategorie_examenonline',
        'contextid' => context_course::instance($COURSE->id)->id,
        'name' => get_string('gradebook_online', 'block_validador'),
        'passed' => $gradebook_valid
    ];

    return $validationsgradebook;
}

    /* private function performs_validations_gradebook_subcategorie_examenpresencial() {
        global $COURSE, $DB, $CFG;
    
        // Validación: verificar la estructura del libro de calificaciones
        $gradebook_valid = true;
    
        // Obtener la categoría "Examen final" (insensible a mayúsculas/minúsculas)
        $exam_final_category = $DB->get_record_sql(
            "SELECT * 
             FROM {grade_categories} 
             WHERE courseid = :courseid 
             AND LOWER(TRIM(fullname)) = LOWER(:fullname)",
            ['courseid' => $COURSE->id, 'fullname' => 'Examen final']
        );
    
        if ($exam_final_category) {
            // Obtener las subcategorías de la categoría "Examen final"
            $subcategories = $DB->get_records('grade_categories', ['parent' => $exam_final_category->id]);
    
            // Verificar que exista al menos una subcategoría llamada "Examen presencial" (insensible a mayúsculas/minúsculas)
            $exam_online_subcategory_exists = false;
            foreach ($subcategories as $subcategory) {
                if (strcasecmp(trim($subcategory->fullname), 'Examen presencial') === 0 || strcasecmp(trim($subcategory->fullname), 'Examen final presencial') === 0) {
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
    } */

    /* private function validate_examen_final_aggregation() {
        global $DB, $COURSE;
    
        $aggregation_correcta = true;
    
        // Obtener la categoría "Examen final" insensible a mayúsculas/minúsculas
        $exam_final_category = $DB->get_record_sql(
            "SELECT * 
             FROM {grade_categories} 
             WHERE courseid = :courseid 
             AND LOWER(TRIM(fullname)) = LOWER(:fullname)",
            ['courseid' => $COURSE->id, 'fullname' => 'Examen final']
        );
    
        if ($exam_final_category) {
            // Verificar si el método de calificación es "Calificación más alta" (GRADE_AGGREGATE_MAX)
            if ($exam_final_category->aggregation != GRADE_AGGREGATE_MAX) {
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
    } */

    /* private function validate_examen_presencial_aggregation() {
        global $DB, $COURSE;
        $aggregation_correcta = true;
    
        // Buscar categoría insensible a mayúsculas/minúsculas
        $exam_presencial_category = $DB->get_record_sql(
            "SELECT * 
             FROM {grade_categories} 
             WHERE courseid = :courseid 
             AND (LOWER(TRIM(fullname)) = LOWER(:fullname1) OR LOWER(TRIM(fullname)) = LOWER(:fullname2))",
            ['courseid' => $COURSE->id, 'fullname1' => 'Examen presencial', 'fullname2' => 'Examen final presencial']
        );
    
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
    } */

    /* private function validate_examen_online_aggregation() {
        global $DB, $COURSE;
        $aggregation_correcta = true;
    
        // Buscar categoría insensible a mayúsculas/minúsculas y con posibles espacios al final
        $exam_online_category = $DB->get_record_sql(
            "SELECT * 
             FROM {grade_categories} 
             WHERE courseid = :courseid 
             AND (LOWER(TRIM(fullname)) = LOWER(:fullname1) OR LOWER(TRIM(fullname)) = LOWER(:fullname2))",
            ['courseid' => $COURSE->id, 'fullname1' => 'Examen online', 'fullname2' => 'Examen final online']
        );
    
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
    } */

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
        if (!isset($availability->showc) || (isset($availability->showc[0]) && !$availability->showc[0] == 1)) {
            return true;
        }
        return false;
    }

    public function has_config() {
        return true;
    }
}