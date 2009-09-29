<?php
/**
 * Página de inicio de la actividad/módulo
 *
 * Esta es la página inicial que se muestra cuando se accede a la actividad
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

require_once('../../config.php');
require_once('locallib.php');

//
///obtener parametros requeridos y opcionales
//

//el id del recurso (no es la instancia que esta guardada en la tabla teamwork)
$id = required_param('id', PARAM_INT);


//
/// obtener datos del contexto donde se ejecuta el modulo
//

//el objeto $cm contiene los datos dell contexto de la instancia del modulo
if(!$cm = get_coursemodule_from_id('teamwork', $id))
{
	error('Course Module ID was incorrect');
}

//el objeto $course contiene los datos del curso en el que está el modulo instanciado
if(!$course = get_record('course', 'id', $cm->course))
{
	error('Course is misconfigured');
}

//el objeto $teamwork contiene los datos de la instancia del modulo
if(!$teamwork = get_record('teamwork', 'id', $cm->instance))
{
	error('Course module is incorrect');
}

//es necesario estar logueado en el curso
require_login($course->id, false, $cm);

//$context = get_context_instance(CONTEXT_MODULE, $cm->id);
//es lo mismo que $context = $cm->context;
//require_capability('mod/assignment:view', $context);

//añadir al log que se ha visto esta página
add_to_log($course->id, 'teamwork', 'view', "view.php?id={$cm->id}", $teamwork->id, $cm->id);

//conocer si el usuario posee permisos de gestión (admin, profesor, y profesor-editor)
$ismanager = has_capability('mod/teamwork:manage', $cm->context);

//si no es profesor y la actividad se encuentra oculta...
//¡¡esta misma funcionalidad lo hace la funcion require_login
//http://phpdocs.moodle.org/19/moodlecore/_lib---moodlelib.php.html#functionrequire_login
/*if(!$ismanager AND !$cm->visible)
{
	notice(get_string("activityiscurrentlyhidden"));
}*/

//si es manager y no se tiene asociado alguno de los 2 templates de items necesarios...
if($ismanager AND count_records('teamwork_tplinstances', 'teamworkid', $teamwork->id) < 2)
{
	//redirigimos a la página de edición de templates
	//redirect("template.php?id=$cm->id");
}

//
/// cabecera
//

$navigation = build_navigation('', $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, update_module_button($cm->id, $course->id, get_string('modulename', 'teamwork')), navmenu($course, $cm));

echo '<div class="clearer"></div><br />';



//
/// contenido
//

//mostrar el cuadro de información
teamwork_show_status_info();



//
/// pie de pagina
//

print_footer($course);
?>
