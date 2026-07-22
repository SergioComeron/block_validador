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
$string['activity'] = 'Actividad';
$string['course'] = 'Curso';
$string['courselink'] = 'Enlace al curso';
$string['delete_success'] = 'La validación ha sido eliminada correctamente.';
$string['editingteachers'] = 'Editando profesores';
$string['exportcsv'] = 'Exportar a CSV';
$string['exportsummarycsv'] = 'Exportar resumen a CSV';
$string['gradebook'] = 'Hay una categoría de Examen Final';
$string['gradebook_examenfinal_aggregation'] = 'Configuración calculo total de la categoría Examen final';
$string['gradebook_examenonline_aggregation'] = 'Configuración calculo total de la categoría Examen Online';
$string['gradebook_examenpresencial_aggregation'] = 'Configuración calculo total de la categoría Examen Presencial';
$string['gradebook_online'] = 'La categoría Examen Online existe y está oculta';
$string['gradebook_online_weight'] = 'El peso de la categoría Examen Online debe ser 0';
$string['gradebook_subcategorie_examenonline'] = 'Examen final tiene subcategoría Examen Online';
$string['gradebook_subcategorie_examenpresencial'] = 'Examen final tiene la subcategoría Examen Presencial';
$string['gradetopass'] = 'Calificación para aprobar';
$string['grouprestriccion'] = 'Restricción de grupo';
$string['groups'] = 'Hay grupos válidos';
$string['groupwithquizzes'] = 'Todos los grupos tienen cuestionarios';
$string['invalidcount'] = 'Nº de errores';
$string['invalidcourses'] = 'Cursos con errores de validación';
$string['invalidcourses_summary'] = 'Resumen de cursos con errores';
$string['label'] = 'Recursos de texto y medios';
$string['linktoinvalidcourses'] = 'Enlace a cursos inválidos';
$string['linktoinvalidcourses_desc'] = 'Haga clic aquí para ver la lista de cursos inválidos.';
$string['message'] = 'Hola, estás en el curso: {$a}.';
$string['min_group_timecreated'] = 'Fecha mínima de creación de grupos';
$string['min_group_timecreated_desc'] = 'Fecha mínima para validar grupos. Los grupos creados antes de esta fecha no serán validados.';
$string['nocoursesfound'] = 'No se encontraron cursos con errores de validación.';
$string['nogroups'] = 'No hay grupos en este curso';
$string['noteachers'] = 'No hay profesores en este curso';
$string['pledgedates'] = 'Fechas del Pledge (inicio 15 min antes, fin 5 min antes)';
$string['pluginname'] = 'Validador';
$string['quizautosubmit'] = 'Envío automático al finalizar el tiempo';
$string['quizgradecategory'] = 'Categoría de calificación del cuestionario';
$string['quizhasdates'] = 'El cuestionario tiene fechas de inicio y cierre configuradas';
$string['quizhaspledgeabove'] = 'El cuestionario tiene código de honor arriba';
$string['quizhasquestions'] = 'El cuestionario tiene al menos una pregunta';
$string['quizmultipleforgroup'] = 'El grupo tiene un único cuestionario asociado';
$string['quizpledgeaccess'] = 'El cuestionario tiene restricción de código de honor';
$string['quizquestionsperpage'] = 'Preguntas por página';
$string['quizrandomquestions'] = 'Las preguntas aleatorias no superan las disponibles en la categoría';
$string['quizreviewoptions'] = 'Opciones de revisión del cuestionario';
$string['quizsinglepage'] = 'Todas las preguntas están en la misma página (sin saltos de página)';
$string['quiztimelimit'] = 'Límite de tiempo del cuestionario';
$string['showcategories'] = 'Mostrar categorías';
$string['showcategories_desc'] = 'Seleccione las categorías del curso que desea mostrar.';
$string['smowl'] = 'Hay un bloque SMOWL';
$string['timecreated'] = 'Hora de creación';
$string['timemodified'] = 'Hora de modificación';
$string['totalcoursesinvalid'] = 'Cursos con errores';
$string['totalinvalidations'] = 'Total de validaciones no válidas';
$string['totalinvalidcourses'] = 'Total de cursos con validaciones no válidas: {$a}';
$string['validador:addinstance'] = 'Agregar un nuevo bloque validador';
$string['validador:myaddinstance'] = 'Agregar un nuevo bloque validador a la página Mi Moodle';
$string['validador:view'] = 'Ver el bloque validador';
$string['validation'] = 'Validación';
