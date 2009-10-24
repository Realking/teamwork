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

//si es manager y no se tiene asociado alguno de los 2 templates de items necesarios...
if($ismanager AND count_records('teamwork_tplinstances', 'teamworkid', $teamwork->id) < 2)
{
	//redirigimos a la página de edición de templates
	//redirect("template.php?id=$cm->id");
}

//popup con la lista de miembros del equipo para la vision del alumno
$teamcomponents = optional_param('teamcomponents', null);

if($teamcomponents !== null)
{
    //imprimimos la lista de miembros
    print_header();
    echo '<div class="clearer"></div>';

    //obtenemos el equipo al que pertenece el usuario
    $team = get_record_sql('select t.id, t.teamname from '.$CFG->prefix.'teamwork_users_teams ut, '.$CFG->prefix.'teamwork_teams t where ut.userid = '.$USER->id.' AND t.id = ut.teamid AND t.teamworkid = '.$teamwork->id);

    //obtenemos los miembros del grupo
    if($members = get_records_sql('select u.id, u.firstname, u.lastname, u.picture, u.imagealt from '.$CFG->prefix.'user u, '.$CFG->prefix.'teamwork_users_teams ut where ut.teamid = '.$team->id.' AND u.id = ut.userid'))
    {
        echo print_heading(get_string('teammembers', 'teamwork', $team->teamname));
        echo '<br />';

        $table = new stdClass;
        $table->width = '95%';
        $table->tablealign = 'center';
        $table->id = 'usersteamstable';
        $table->head = array('', get_string('studentname', 'teamwork'));
        $table->align = array('center','center');
        $table->size = array('10%','90%');

        foreach($members as $member)
        {
            $name = '<a href="../../user/view.php?id='.$member->id.'&course='.$course->id.'" target="_blank">'.$member->firstname.' '.$member->lastname.'</a>';
            $table->data[] = array(print_user_picture($member, $course->id, null, 0, true),$name);
        }

        print_table($table);
    }

    print_footer('empty');
    exit();
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

//enunciado de la actividad
echo '<br />';
print_heading(get_string('activity'));
print_simple_box($teamwork->description, 'center', '', '', 0, 'generalbox', 'intro');



//
/// pie de pagina
//

print_footer($course);
?>
