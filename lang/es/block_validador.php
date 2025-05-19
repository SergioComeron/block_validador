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

 // General strings
$string['pluginname'] = 'Validador';
$string['message'] = 'Hola, estás en el curso: {$a}.';
$string['nogroups'] = 'No hay grupos en este curso';
$string['groups'] = 'Hay grupos válidos';
$string['groupwithquizzes'] = 'Todos los grupos tienen cuestionarios';
$string['gradebook'] = 'Hay una categoría de Examen Final';
$string['smowl'] = 'Hay un bloque SMOWL';
$string['quiztimelimit'] = 'Límite de tiempo del cuestionario';
$string['quizquestionsperpage'] = 'Preguntas por página';
$string['label'] = 'Recursos de texto y medios';
$string['gradetopass'] = 'Calificación para aprobar';
$string['quizgradecategory'] = 'Categoría de calificación del cuestionario';
$string['quizautosubmit'] = 'Envío automático al finalizar el tiempo';
$string['quizreviewoptions'] = 'Opciones de revisión del cuestionario';
$string['grouprestriccion'] = 'Restricción de grupo';
$string['invalidcourses'] = 'Cursos con errores de validación';
$string['course'] = 'Curso';
$string['validation'] = 'Validación';
$string['timecreated'] = 'Hora de creación';
$string['timemodified'] = 'Hora de modificación';
$string['nocoursesfound'] = 'No se encontraron cursos con errores de validación.';
$string['totalinvalidcourses'] = 'Total de cursos con validaciones no válidas: {$a}';
$string['activity'] = 'Actividad';
$string['gradebook_subcategorie_examenonline'] = 'Examen final tiene subcategoría Examen Online';
$string['gradebook_subcategorie_examenpresencial'] = 'Examen final tiene la subcategoría Examen Presencial';
$string['gradebook_examenfinal_aggregation'] = 'Configuración calculo total de la categoría Examen final';
$string['gradebook_examenonline_aggregation'] = 'Configuración calculo total de la categoría Examen Online';
$string['gradebook_examenpresencial_aggregation'] = 'Configuración calculo total de la categoría Examen Presencial';
$string['gradebook_online'] = 'Hay una categoría de Examen Online';
$string['showcategories'] = 'Mostrar categorías';
$string['showcategories_desc'] = 'Seleccione las categorías del curso que desea mostrar.';
$string['linktoinvalidcourses'] = 'Enlace a cursos inválidos';
$string['linktoinvalidcourses_desc'] = 'Haga clic aquí para ver la lista de cursos inválidos.';
$string['delete_success'] = 'La validación ha sido eliminada correctamente.';
$string['editingteachers'] = 'Editando profesores';
$string['validador:addinstance'] = 'Agregar un nuevo bloque validador';
$string['validador:view'] = 'Ver el bloque validador';
$string['validador:myaddinstance'] = 'Agregar un nuevo bloque validador a la página Mi Moodle';
$string['exportcsv'] = 'Exportar a CSV';
$string['totalinvalidations'] = 'Total de validaciones no válidas';
$string['noteachers'] = 'No hay profesores en este curso';
$string['quizhaspledgeabove'] = 'El cuestionario tiene código de honor arriba';
$string['quizpledgeaccess'] = 'El cuestionario tiene restricción de código de honor';