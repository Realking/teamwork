<?php
/**
 * Página de configuración de grupos de trabajo de la actividad
 *
 * Esta pagina visible sólo por el profesor permite crear, editar y borrar los grupos
 * de alumnos para esta actividad
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

//la accion a realizar
$action =  optional_param('action', 'list', PARAM_ALPHA);


//
/// obtener datos del contexto donde se ejecuta el modulo
//

//el objeto $cm contiene los datos del contexto de la instancia del modulo
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

//y ademas es necesario que tenga permisos de manager
require_capability('mod/teamwork:manage', $cm->context);


//
/// header
//

$navigation = build_navigation(get_string('groupseditor', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('groupseditor', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, '', navmenu($course, $cm));

echo '<div class="clearer"></div><br />';

//selección de la acción a realizar
switch($action)
{
    //muestra la lista de grupos actualmente existentes en esta actividad
    case 'list':
        
    break;

    //añade un grupo vacio
    case 'groupadd':

    break;

    //edita la información sobre un grupo ya creado (no edita los usuarios)
    case 'groupedit':

    break;

    //elimina un grupo existente
    case 'groupdelete':

    break;

    //muestra la lista de usuarios disponibles y le asigna el especificado al grupo
    case 'useradd':

    break;

    //muestra la lista de miembros del grupo y elimina el especificado
    case 'userdelete':

    break;

    //establece un nuevo lider en el grupo
    case 'leaderset':

    break;

    //mensaje de error al no existir la acción especificada
    default:
        print_error('actionnotexist', 'teamwork');
}

//
/// footer
//

print_footer($course);
?>
