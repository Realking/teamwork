<?php
/**
 * Página de asignación de trabajos a los equipos para su evaluación
 *
 * Esta página, con secciones visibles sólo por el profesorm permite asignar
 * trabajos a los equipos para que estos los evalúen. Esta asignación puede
 * hacerse tanto de forma manual como automática.
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



//
/// header
//

//iniciamos el bufer de salida (es posible que tengamos que modificar las cabeceras http y si imprimimos aqui algo no podremos hacerlo)
ob_start();

$navigation = build_navigation(get_string('assignseditor', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('assignseditor', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, '', navmenu($course, $cm));

echo '<div class="clearer"></div><br />';

//
/// cuerpo
//

switch($action)
{
  case 'list':

    //mostrar la tabla con la lista de trabajos enviados
    $table = new stdClass;
    $table->width = '70%';
    $table->tablealign = 'center';
    $table->id = 'sentworkstable';
    $table->head = array(get_string('teamname', 'teamwork'), get_string('teamsthatevalthiswork', 'teamwork'), get_string('actions', 'teamwork'));
    $table->size = array('25%', '65%', '10%');
    $table->align = array('center', 'center', 'center');

    print_heading(get_string('sentworkslist', 'teamwork'));

    // Si hay trabajos enviados
    if($works = get_records_sql('select * from '.$CFG->prefix.'teamwork_teams t where t.teamworkid = '.$teamwork->id.' and t.worktime != 0 order by t.teamname'))
    {
      foreach($works as $work)
      {
        $stractions = teamwork_sent_works_table_options($work);

        //listar todos los trabajos que tiene asignado
        $evaluators = get_records_sql('select t.id, t.teamname from '.$CFG->prefix.'teamwork_teams t, '.$CFG->prefix.'teamwork_evals e, '.$CFG->prefix.'teamwork_users_teams ut where
                                 e.teamevaluated = '.$work->id.' AND ut.userid = e.evaluator AND t.id = ut.teamid');

        var_dump($evaluators);

        $table->data[] = array($work->teamname, '', $stractions);
      }

      //disponibles: imprimir la tabla y el boton de añadir
      print_table($table);
    }
    // Si no hay trabajos
    else
    {
      echo '<br /><div align="center">';
      echo get_string('donothavesentworks', 'teamwork');
      echo '</div>';
    }

    //imprimir opciones inferiores
    echo '<br /><div align="center"><br />';
    if(count_records_sql('select count(*) from '.$CFG->prefix.'teamwork_teams t where t.teamworkid = '.$teamwork->id.' and t.worktime != 0 order by t.teamname') == 0)
    {
      echo '<img src="images/asterisk.png" alt="'.get_string('symbolicwork', 'teamwork').'" title="'.get_string('symbolicwork', 'teamwork').'"/> <a href="assign.php?id='.$cm->id.'&action=symbolicworks">'.get_string('symbolicwork', 'teamwork').'</a> | ';
    }
    echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="view.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
    echo '</div>';

  break;

  // Muestra la lista de evaluadores del equipo
  case 'editevaluators':

    // Parametros requeridos
    $tid = required_param('tid', PARAM_INT);

    // Obtenemos el nombre del equipo
    $team = get_record('teamwork_teams', 'id', $tid);

    // Obtenemos la lista de equipos que evaluan al equipo $tid
    $sql = 'select distinct t.id, t.teamname from '.$CFG->prefix.'teamwork_teams t, '.$CFG->prefix.'teamwork_evals e, '.$CFG->prefix.'teamwork_users_teams u
            where e.teamevaluated = "'.$tid.'" and u.userid = e.evaluator and t.id = u.teamid';

    print_heading(get_string('teamevaluators', 'teamwork', $team->teamname));

    if( !$evaluators = get_records_sql($sql) )
    {
      // No hay nadie que evalúe a este equipo
      echo '<p align="center">'.get_string('donothaveanyevaluatorforthisteam', 'teamwork').'</p>';
    }
    else
    {
      // Mostramos los equipos que lo evalúan
      $table = new stdClass;
      $table->width = '40%';
      $table->tablealign = 'center';
      $table->id = 'usersteamstable';
      $table->head = array(get_string('teamname', 'teamwork'), get_string('actions', 'teamwork'));
      $table->align = array('center', 'center');
      $table->size = array('80%', '20%');

      foreach($evaluators as $evaluator)
      {
        $stractions = '<a href="assign.php?id='.$cm->id.'&action=deleteevaluator&tid='.$tid.'&eid='.$evaluator->id.'"><img src="images/delete.png" alt="'.get_string('removeteamforeval', 'teamwork').'" title="'.get_string('removeteamforeval', 'teamwork').'" /></a>&nbsp;&nbsp;';
        $name = '<a href="team.php?id='.$cm->id.'&action=userlist&tid='.$evaluator->id.'" target="_blank">'.$evaluator->teamname.'</a>';

        $table->data[] = array($name, $stractions);
      }

      // Imprimir tabla
      print_table($table);
    }

    //imprimir opciones inferiores
    echo '<br /><div align="center"><br />';
    echo '<img src="images/add.png" alt="'.get_string('addevaluators', 'teamwork').'" title="'.get_string('addevaluators', 'teamwork').'"/> <a href="assign.php?id='.$cm->id.'&action=addevaluators">'.get_string('addevaluators', 'teamwork').'</a> | ';
    echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="assign.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
    echo '</div>';

  break;

  //añade evaluadores a un equipo
  case 'addevaluators':



  break;

  //elimina un evaluador de un equipo
  case 'deleteevaluator':



  break;

  // Elimina un trabajo enviado
  case 'deletework':

    //verificar que se pueda realmente editar este teamwork
    if(!teamwork_is_editable($teamwork))
    {
        print_error('teamworkisnoeditable', 'teamwork');
    }

    //parametros requeridos
    $tid = required_param('tid', PARAM_INT);

    //si el grupo puede ser borrado, pedir confirmación
    //si no ha sido enviada, mostrar la confirmacion
    if(!isset($_POST['tid']))
    {
      notice_yesno(get_string('confirmationfordeletework', 'teamwork'), 'assign.php', 'assign.php', array('id'=>$cm->id, 'action'=>'deletework', 'tid'=>$tid), array('id'=>$cm->id), 'post', 'get');
    }
    //si se ha enviado, procesamos
    else
    {
      // Borrar el contenido de la base de datos
      $data = new stdClass;
      $data->id = $tid;
      $data->worktime = 0;
      $data->workdescription = '';
      update_record('teamwork_teams', $data);

      // Borrar el archivo subido si existiere
      remove_dir( $CFG->dataroot.'/'.$course->id.'/'.$CFG->moddata.'/teamwork/'.$teamwork->id.'/'.$tid );

      // Redireccionar a la pagina de asignaciones
      header('Location: assign.php?id='.$cm->id);
    }

  break;

  // Crea trabajos simbólicos para todos los equipos existentes
  case 'symbolicworks':

    //verificar que se pueda realmente editar este teamwork
    if(!teamwork_is_editable($teamwork))
    {
        print_error('teamworkisnoeditable', 'teamwork');
    }    

    // Obtener la lista de trabajos enviados
    $works = count_records_sql('select count(*) from '.$CFG->prefix.'teamwork_teams t where t.teamworkid = '.$teamwork->id.' and t.worktime != 0 order by t.teamname');

    // Para que se puedan crear trabajos simbolicos es necesario que ningún grupo haya subido nada y que esté cerrado el plazo de envio
    if($works == 0 AND $teamwork->endsends < time())
    {
      // Actualizar los equipos de esta instancia con los datos por defecto
      execute_sql('update '.$CFG->prefix."teamwork_teams set workdescription = '" . get_string('symbolycworktext', 'teamwork') . "', worktime = ".time()." where teamworkid = ".$teamwork->id);

      // Redireccionar a la lista de trabajos enviados
      header('Location: assign.php?id='.$cm->id);
    }
    else
    {
      // No se puede crear trabajos simbólicos si ya existen o aun está abierto el plazo de envíos...
      print_error('youcannotcreatesymbolicworks', 'teamwork');
    }
    
  break;
}


//
/// footer
//

print_footer($course);
?>
