<?php
/**
 * Listado de instancias de teamwork en un curso
 *
 * Este archivo muestra una lista de las instancias de la actividad teamwork
 * que existen en el ámbito de un curso
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

//incluir archivos basicos para el funcionamiento del modulo
require_once('../../config.php');
require_once('locallib.php');

//obtener la id del curso sobre el que mostrar la lista de instancias
$id = required_param('id', PARAM_INT);

//select * from course where id = $id // obtener los datos del curso
if (! $course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

//asegurar que se cuenta con los permisos de acceso a este curso
require_course_login($course);

//añade al log que se ha visto esta pagina
add_to_log($course->id, 'teamwork', 'view all', "index.php?id=$course->id", "");

//conocer si el usuario posee permisos de gestión (admin, profesor, y profesor-editor)
$isteacher = isteacher($course->id, $USER->id);

//construir la barra de navegacion superior
$navlinks = array();
$navlinks[] = array('name' => get_string('modulenameplural', 'teamwork'), 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple(get_string('modulenameplural', 'teamwork'), '', $navigation, '', '', true, '', navmenu($course));
echo '<div class="clearer"></div><br />';

$modinfo = get_fast_modinfo($course);

if(!isset($modinfo->instances['teamwork']))
{
    notice(get_string('noinstances', 'teamwork'), "../../course/view.php?id=$course->id");
    die;
}

$teamworks = get_records('teamwork', 'course', $course->id);

$table = new stdClass;

if ($course->format == 'weeks')
{
    // Si es profesor mostrar una información más breve
    if($isteacher)
    {
      $table->head  = array (get_string('week'), get_string('name'), get_string('status'));
      $table->align = array ('center', 'left', 'left');
    }
    else
    {
      $table->head  = array (get_string('week'), get_string('name'), get_string('areyouleader?', 'teamwork'), get_string('status'), get_string('duedate', 'teamwork'), get_string('grade'));
      $table->align = array ('center', 'left', 'center', 'left', 'left', 'left');
    }
}
else if ($course->format == 'topics')
{
    // Si es profesor mostrar una información más breve
    if($isteacher)
    {
      $table->head  = array (get_string('topic'), get_string('name'), get_string('status'));
      $table->align = array ('center', 'left', 'left');
    }
    else
    {
      $table->head  = array (get_string('topic'), get_string('name'), get_string('areyouleader?', 'teamwork'), get_string('status'), get_string('duedate', 'teamwork'), get_string('grade'));
      $table->align = array ('center', 'left', 'center', 'left', 'left', 'left');
    }
}
else
{
    // Si es profesor mostrar una información más breve
    if($isteacher)
    {
      $table->head  = array (get_string('name'), get_string('status'));
      $table->align = array ('left', 'left');
    }
    else
    {
      $table->head  = array (get_string('name'), get_string('areyouleader?', 'teamwork'), get_string('status'), get_string('duedate', 'teamwork'), get_string('grade'));
      $table->align = array ('left', 'center', 'left', 'left', 'left');
    }
}

$currentsection = '';

foreach($modinfo->instances['teamwork'] as $cm)
{
    if (!$cm->uservisible)
    {
        continue;
    }

    //Show dimmed if the mod is hidden
    $class = $cm->visible ? '' : 'class="dimmed"';

    $link = "<a $class href=\"view.php?id=$cm->id\">".format_string($cm->name)."</a>";

    $printsection = '';
    
    if ($cm->sectionnum !== $currentsection)
    {
        if ($cm->sectionnum)
        {
            $printsection = $cm->sectionnum;
        }
        if ($currentsection !== '')
        {
            $table->data[] = 'hr';
        }
        
        $currentsection = $cm->sectionnum;
    }

    //comprueba si soy el lider del equipo
    $teams = count_records('teamwork_teams', 'teamworkid', $cm->instance, 'teamleader', $USER->id);
    $leader = ($teams) ? get_string('yes') : get_string('no');
    $leader = strtoupper($leader);

    //obtiene la fase en la que se encuentra la actividad
    $status = teamwork_phase($teamworks[$cm->instance]);

    //fechas limite
    switch(teamwork_phase($teamworks[$cm->instance], true))
    {        
        case 3:
            $due = userdate($teamworks[$cm->instance]->endsends);
        break;

        case 5:
            $due = userdate($teamworks[$cm->instance]->endevals);
        break;
        
        default:
        $due = '-';
    }

    $grade = teamwork_check_student_evaluated();
    $grade = ($grade) ? round($grade['grade'], 2).' / '.round($grade['grademax'], 2) : '-';

    if ($course->format == "weeks" or $course->format == "topics")
    {
        // Si es profesor mostrar una información más breve
        if($isteacher)
        {
          $table->data[] = array ($printsection, $link, $status);
        }
        else
        {
          $table->data[] = array ($printsection, $link, $leader, $status, $due, $grade);
        }
    }
    else
    {
        // Si es profesor mostrar una información más breve
        if($isteacher)
        {
          $table->data[] = array ($link, $status);
        }
        else
        {
          $table->data[] = array ($link, $leader, $status, $due, $grade);
        }
    }
}

print_table($table);
print_footer($course);

?>
