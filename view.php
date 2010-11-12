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

//añadir al log que se ha visto esta página
add_to_log($course->id, 'teamwork', 'view', "view.php?id={$cm->id}", $teamwork->id, $cm->id);

// Si existe $cm->context lo usamos, si no, lo obtenemos
$mod_context = (isset($cm->context)) ? $cm->context : get_context_instance(CONTEXT_MODULE, $cm->id);

//conocer si el usuario posee permisos de gestión (admin, profesor, y profesor-editor)
$ismanager = has_capability('mod/teamwork:manage', $mod_context);

//popup con la lista de miembros del equipo para la vision del alumno
$teamcomponents = optional_param('teamcomponents', null);

if($teamcomponents !== null)
{
    //imprimimos la lista de miembros
    print_header();
    echo '<div class="clearer"></div>';

    //obtenemos el equipo al que pertenece el usuario
    $team = get_record_sql('select t.id, t.teamname, t.teamleader from '.$CFG->prefix.'teamwork_users_teams as ut, '.$CFG->prefix.'teamwork_teams as t where ut.userid = '.$USER->id.' AND t.id = ut.teamid AND t.teamworkid = '.$teamwork->id);

    //obtenemos los miembros del grupo
    if($members = get_records_sql('select u.id, u.firstname, u.lastname, u.picture, u.imagealt from '.$CFG->prefix.'user as u, '.$CFG->prefix.'teamwork_users_teams as ut where ut.teamid = '.$team->id.' AND u.id = ut.userid order by u.lastname ASC'))
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
            $leader = ($team->teamleader == $member->id) ? '&nbsp; <img src="images/leader.png" alt="'.get_string('thisuserisleader', 'teamwork').'" title="'.get_string('thisuserisleader', 'teamwork').'" />' : '';
            $name = '<a href="../../user/view.php?id='.$member->id.'&course='.$course->id.'" target="_blank">'.$member->firstname.' '.$member->lastname.'</a>'.$leader;
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

//iniciamos el bufer de salida (es posible que tengamos que modificar las cabeceras http y si imprimimos aqui algo no podremos hacerlo)
ob_start();

$navigation = (function_exists('build_navigation')) ? build_navigation('', $cm) : teamwork_build_navigation('', $cm); // Compatibilidad con Moodle 1.8.x
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, update_module_button($cm->id, $course->id, get_string('modulename', 'teamwork')), navmenu($course, $cm));

echo '<div class="clearer"></div><br />';



//
/// contenido
//

//mostrar el cuadro de información
teamwork_show_status_info();

//obtener los datos del equipo al que pertenezco
$team = get_record_sql('select t.id, t.teamname, t.teamleader, t.workdescription from '.$CFG->prefix.'teamwork_users_teams as ut, '.$CFG->prefix.'teamwork_teams as t where ut.userid = '.$USER->id.' AND t.id = ut.teamid AND t.teamworkid = '.$teamwork->id);

// Si el alumno/profesor debe evaluar a equipos/compañeros, mostramos la lista de evaluaciones pendientes. Siempre dentro del periodo de eval
// y siempre que pertenezca a un equipo
if($teamwork->startevals < time() AND $teamwork->endevals > time() AND ($team !== false OR $ismanager))
{ 
  // Obtenemos la lista de trabajos pendientes de evaluar
  $works = get_records_sql('select t.id, t.teamname, e.id as eid from '.$CFG->prefix.'teamwork_teams as t, '.$CFG->prefix.'teamwork_evals as e
                            where e.timegraded IS NULL and e.evaluator = '.$USER->id.' and e.teamevaluated IS NOT NULL and t.id = e.teamevaluated and e.teamworkid = '.$teamwork->id);

  // Si hay resultados...
  if( !empty($works))
  {
    $table = new stdClass;
    $table->width = '40%';
    $table->tablealign = 'center';
    $table->id = 'evalsteamstable';
    $table->head = array(get_string('teamname', 'teamwork'), get_string('actions', 'teamwork'));
    $table->align = array('center', 'center');
    $table->size = array('80%','20%');

    foreach($works as $work)
    {
      //boton de visualizar trabajo
      $stractions = '<a href="viewer.php?id='.$cm->id.'&tid='.$work->id.'"><img src="images/viewer.png" alt="'.get_string('viewwork', 'teamwork').'" title="'.get_string('viewwork', 'teamwork').'" /></a>&nbsp;&nbsp;';

      //boton de realizar evaluación
      $stractions .= '<a href="eval.php?id='.$cm->id.'&eid='.$work->eid.'"><img src="images/accept.png" alt="'.get_string('evaluateteam', 'teamwork').'" title="'.get_string('evaluateteam', 'teamwork').'" /></a>&nbsp;&nbsp;';

      $table->data[] = array($work->teamname, $stractions);
    }

    //disponibles: imprimir la tabla y el boton de añadir
    echo '<br />';
    print_heading(get_string(isteacher($course->id) ? 'waitingworksforevaluateteacher' : 'waitingworksforevaluate', 'teamwork'));
    print_table($table);
  }

  // Obtenemos la lista de compañeros pendientes de evaluar
  $coworkers = get_records_sql('select u.id, u.firstname, u.lastname, u.picture, u.imagealt, e.id as eid from '.$CFG->prefix.'user as u, '.$CFG->prefix.'teamwork_evals as e
                            where e.grade IS NULL and e.evaluator = '.$USER->id.' and e.userevaluated IS NOT NULL and u.id = e.userevaluated and e.teamworkid = '.$teamwork->id.' order by u.lastname asc');

  // Si hay resultados...
  if( !empty($coworkers))
  {
    $table = new stdClass;
    $table->width = '40%';
    $table->tablealign = 'center';
    $table->id = 'evalscoworkerstable';
    $table->head = array('', get_string('studentname', 'teamwork'), get_string('actions', 'teamwork'));
    $table->align = array('center', 'center', 'center');
    $table->size = array('10%','80%','10%');

    foreach($coworkers as $co)
    {
      //boton de realizar evaluación
      $stractions = '<a href="eval.php?id='.$cm->id.'&eid='.$co->eid.'"><img src="images/accept.png" alt="'.get_string('evaluatemember', 'teamwork').'" title="'.get_string('evaluatemember', 'teamwork').'" /></a>&nbsp;&nbsp;';

      // Nombre del compañero
      $name = '<a href="../../user/view.php?id='.$co->id.'&course='.$course->id.'" target="_blank">'.$co->firstname.' '.$co->lastname.'</a>';

      $table->data[] = array(print_user_picture($co, $course->id, null, 0, true) ,$name, $stractions);
    }

    //disponibles: imprimir la tabla y el boton de añadir
    echo '<br />';
    print_heading(get_string(isteacher($course->id) ? 'waitingcoworkersforevaluateteacher' : 'waitingcoworkersforevaluate', 'teamwork'));
    print_table($table);
  }
}

//enunciado de la actividad
echo '<br />';
print_heading(get_string('activity'));
print_simple_box($teamwork->description, 'center', '', '', 0, 'generalbox', 'intro');

//si pertenezco a un grupo
if($team !== false)
{
    //mi envio
    echo '<br />';
    print_heading(get_string('teamsubmission', 'teamwork'));

    // Buscamos si se ha enviado algún archivo
    $file = teamwork_get_team_submit_file($team);

    //si existe en la bbdd algun texto del trabajo enviado, lo mostramos
    if(!empty($team->workdescription))
    {
        print_simple_box($team->workdescription, 'center', '', '', 0, 'generalbox', 'intro');
    }
    //si no mostramos mensaje
    else
    {
        // Si ya ha pasado la fecha de envios, es que no se ha enviado nada
        if(time() > $teamwork->endsends AND $file === false)
        {
          print_simple_box(get_string('submissionnotsent','teamwork'), 'center', '', '', 0, 'generalbox', 'intro');
        }
        // Si aun no ha pasado...
        else
        {
          print_simple_box(get_string('submissionnothavetext','teamwork'), 'center', '', '', 0, 'generalbox', 'intro');
        }
    }

    //si existe algún archivo adjuntado, lo mostramos
    if( $file !== false)
    {
        $text = '<b>'.get_string('attachedfiles', 'teamwork').'</b><br /><br />'.teamwork_print_team_file($file, true);
        print_simple_box($text, 'center', '', '', 0, 'generalbox', 'intro');
    }

    //si soy el lider del equipo y estamos en el plazo, puedo editar el envío
    if($team->teamleader == $USER->id AND $teamwork->startsends < time() AND time() < $teamwork->endsends)
    {
        //si se quiere editar el envio
        if(optional_param('edit', 0))
        {
            //cargamos el formulario
            $form = new teamwork_edit_submission_form('view.php?id='.$cm->id.'&edit=1');

            //no se ha enviado, se muestra
            if(!$form->is_submitted())
            {
                if(!empty($team->workdescription))
                {
                    $form->set_data(array('description' => $team->workdescription));
                }
                
                $form->display();
            }
            //se ha enviado pero se ha cancelado, redirigir a página principal
            elseif($form->is_cancelled())
            {
                header('Location: view.php?id='.$cm->id);
            }
            //se ha enviado y no valida el formulario...
            elseif(!$form->is_validated())
            {
                $form->display();
            }
            //se ha enviado y es válido, se procesa
            else
            {
                //obtenemos los datos del formulario
                $data = $form->get_data();

                //actualizar los datos del trabajo
                $d = new stdClass;
                $d->workdescription = $data->description;
                $d->id = $team->id;
                $d->worktime = time();

                update_record('teamwork_teams', $d);

                //procesamos la subida de archivos si se produce
                $filepath = $CFG->dataroot.'/'.$course->id.'/'.$CFG->moddata.'/teamwork/'.$teamwork->id.'/'.$team->id;

                require_once($CFG->dirroot.'/lib/uploadlib.php');
                $um = new upload_manager('attachedfile', true, false, null, false, 0, false, true, true);
                $um->process_file_uploads($filepath);

                // Permitir a los profesores del curso evaluar a este equipo si esta activa esa opcion
                if($teamwork->wgteacher)
                {
                  $teachers = get_course_teachers($course->id);
                  $insert = new stdClass;
                  $insert->teamworkid = $teamwork->id;
                  $insert->timecreated = time();
                  $insert->teamevaluated = $team->id;

                  foreach($teachers as $teacher)
                  {
                    $insert->evaluator = $teacher->id;
                    insert_record('teamwork_evals', $insert);
                  }
                }

                //redireccionar
                header('Location: view.php?id='.$cm->id);
            }
        }
        else
        {
            echo '<div align="center">';
            print_single_button('view.php', array('id'=>$cm->id, 'edit'=>1), get_string('editsubmission', 'teamwork'));
            echo '</div>';
        }
    }
}

// Iniciar el proceso del cálculo de notas
if($ismanager AND time() > $teamwork->endevals AND !$teamwork->doassessment AND isset($_GET['dograde']))
{
  // Actualizar la bbdd
  $update = new stdClass;
  $update->id = $teamwork->id;
  $update->doassessment = 1;
  update_record('teamwork', $update);

  // Redireccionar
  header('Location: view.php?id='.$cm->id);
}


//
/// pie de pagina
//

print_footer($course);
?>