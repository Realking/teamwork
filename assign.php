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

//y ademas es necesario que tenga permisos de manager
require_capability('mod/teamwork:manage', $cm->context);


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
    if($works = get_records_sql('select * from '.$CFG->prefix.'teamwork_teams as t where t.teamworkid = '.$teamwork->id.' and t.worktime != 0 order by t.teamname'))
    {
      foreach($works as $work)
      {
        $stractions = teamwork_sent_works_table_options($work);
        $teams = teamwork_get_team_evaluators($work);
        
        $table->data[] = array($work->teamname, $teams, $stractions);
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
    if(count_records_sql('select count(*) from '.$CFG->prefix.'teamwork_teams as t where t.teamworkid = '.$teamwork->id.' and t.worktime != 0 order by t.teamname') == 0)
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
    $sql = 'select distinct t.id, t.teamname from '.$CFG->prefix.'teamwork_teams as t, '.$CFG->prefix.'teamwork_evals as e, '.$CFG->prefix.'teamwork_users_teams as u
            where e.teamevaluated = "'.$tid.'" and u.userid = e.evaluator and t.id = u.teamid and t.teamworkid = "'.$teamwork->id.'"';

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

        $table->data[] = array($evaluator->teamname, $stractions);
      }

      // Imprimir tabla
      print_table($table);
    }

    //imprimir opciones inferiores
    echo '<br /><div align="center"><br />';
    echo '<img src="images/add.png" alt="'.get_string('addevaluators', 'teamwork').'" title="'.get_string('addevaluators', 'teamwork').'"/> <a href="assign.php?id='.$cm->id.'&action=addevaluators&tid='.$tid.'">'.get_string('addevaluators', 'teamwork').'</a> | ';
    echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="assign.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
    echo '</div>';

  break;

  //añade evaluadores a un equipo
  case 'addevaluators':

    //verificar que se pueda realmente editar este teamwork
    if(!teamwork_is_editable($teamwork))
    {
        print_error('teamworkisnoeditable', 'teamwork');
    }

    //parametros requeridos
    $tid = required_param('tid', PARAM_INT);

    //si se envia la lista de los miembros a incorporar...
    if(isset($_POST['submit']))
    {
        //usuarios a añadir
        $selection = optional_param('selection', array());

        // Descartar por si se enviase, el propio grupo de la lista
        while(($key = array_search($tid, $selection)) !== FALSE)
        {
          unset($selection[$key]);
        }

        // Obtener la lista de equipos de la instancia
        $teams = get_records('teamwork_teams', 'teamworkid', $teamwork->id);

        if($teams !== false)
        {
            foreach($teams as $item)
            {
                $aux[] = $item->id;
            }

            $teams = $aux;
            unset($aux);
        }
        else
        {
            $teams = array();
        }

        $data = new stdClass;
        $data->teamevaluated = $tid;
        $data->timecreated = time();
        $data->teamworkid = $teamwork->id;

        // Comprobar que los grupos escogidos pertenezcan a esta instancia del teamwork
        // Si pertenecen, insertar sus miembros como evaluadores
        foreach($selection as $s)
        {
          if(in_array($s, $teams))
          {
            //Obtener los usuarios pertenecientes a ese equipo
            $users = get_records('teamwork_users_teams', 'teamid', $s);

            // Insertamos cada usuario en la bbdd
            foreach($users as $user)
            {
              $data->evaluator = $user->userid;

              insert_record('teamwork_evals', $data);
            }
          }
        }

        header('Location: assign.php?id='.$cm->id.'&action=editevaluators&tid='.$tid);
    }
    // Si no, mostramos la lista de los equipos de la instancia
    else
    {
        // Cargar la lista de equipos asignados actualmente para eliminarlos de los disponibles
        $sql = 'select distinct t.id from '.$CFG->prefix.'teamwork_teams as t, '.$CFG->prefix.'teamwork_evals as e, '.$CFG->prefix.'teamwork_users_teams as u
            where e.teamevaluated = "'.$tid.'" and u.userid = e.evaluator and t.id = u.teamid and t.teamworkid = "'.$teamwork->id.'"';
        $current_teams = get_records_sql($sql);

        if($current_teams !== false)
        {
            foreach($current_teams as $item)
            {
                $aux[] = $item->id;
            }

            $current_teams = $aux;
            unset($aux);
        }
        else
        {
            $current_teams = array();
        }

        // Cargar la lista de grupos de la instancia
        $teams = get_records('teamwork_teams', 'teamworkid', $teamwork->id);

        // Eliminar de la lista nuestro equipo, pues un equipo no puede evaluarse a si mismo
        if(isset($teams[$tid]))
        {
          unset($teams[$tid]);
        }

        //titulo
        $team = get_record('teamwork_teams', 'id', $tid);
        print_heading(get_string('teamevaluators', 'teamwork', $team->teamname));

        // Inicio del formulario
        echo '<form method="post" action="'.teamwork_create_url('assign.php').'">';

        // Cabecera de la tabla
        echo '<table width="40%" cellspacing="1" cellpadding="5" id="userstable" class="generaltable boxaligncenter">';
        echo '<tbody><tr>';
        echo '<th scope="col" class="header c0" style="vertical-align: top; text-align: center; width: 80%; white-space: nowrap;">'.get_string('teamname', 'teamwork').'</th>';
        echo '<th scope="col" class="header c1 lastcol" style="vertical-align: top; text-align: center; width: 20%; white-space: nowrap;">'.get_string('actions', 'teamwork').'</th>';
        echo '</tr>';

        foreach($teams as $team)
        {
          $css = '';

          // Si el equipo ya está como evaluador...
          if(in_array($team->id, $current_teams))
          {
              //el equipo ya está como evaluador, marcamos el fondo de verde
              $css = ' teamwork_highlight_green';
          }

          echo '<tr class="r0">';
          echo '<td class="cell c0'.$css.'" style="text-align: center; width: 80%;">';
              echo $team->teamname;
          echo '</td>';
          echo '<td class="cell c1 lastcol'.$css.'" style="text-align: center; width: 20%;">';

          //si css esta vacio (no pertenece a ningun grupo), mostramos el marcador para seleccionar el usuario
          if(empty($css))
          {
              echo '<input type="checkbox" name="selection[]" value="'.$team->id.'" />';
          }
          echo '</td>';
          echo '</tr>';
        }

        //pie de la tabla
        echo '</tbody></table>';

        //fin de formulario
        echo '<p align="center"><input type="submit" name="submit" value="'.get_string('addnewevaluators', 'teamwork').'" /></p>';
        echo '</form>';

        //leyenda
        $var = '<span class="teamwork_highlight_green">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
        echo '<br /><p align="center">'.get_string('asignteamforevalleyend', 'teamwork', $var).'</p>';

        //imprimir opciones inferiores
        echo '<br /><div align="center">';
        echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="assign.php?id='.$cm->id.'&action=editevaluators&tid='.$tid.'">'.get_string('goback', 'teamwork').'</a>';
        echo '</div>';
    }

  break;

  //elimina un evaluador de un equipo
  case 'deleteevaluator':

    //verificar que se pueda realmente editar este teamwork
    if(!teamwork_is_editable($teamwork))
    {
        print_error('teamworkisnoeditable', 'teamwork');
    }

    //parametros requeridos
    $tid = required_param('tid', PARAM_INT);
    $eid = required_param('eid', PARAM_INT);

    // Pedir confirmación
    if(!isset($_POST['tid']))
    {
      notice_yesno(get_string('confirmationforremovefromevaluators', 'teamwork'), 'assign.php', 'assign.php', array('id'=>$cm->id, 'action'=>'deleteevaluator', 'tid'=>$tid, 'eid'=>$eid), array('id'=>$cm->id, 'action'=>'editevaluators', 'tid'=>$tid), 'post', 'get');
    }
    // Si se ha enviado, procesamos
    else
    {
      // Obtenemos todos los miembros del equipo que evalua
      $eval_members = get_records('teamwork_users_teams', 'teamid', $eid);

      foreach($eval_members as $member)
      {
        // Eliminamos su evaluacion y cada elemento evaluado si lo hubiere

        // Obtenemos la evaluación
        $e = get_record('teamwork_evals', 'evaluator', $member->userid, 'teamevaluated', $tid);

        // Eliminamos los items de evaluacion que existieran
        delete_records('teamwork_eval_items', 'evalid', $e->id);

        // Eliminamos el elemento de evaluación
        delete_records('teamwork_evals', 'id', $e->id);
      }

      // Redireccionar a la pagina de asignaciones
      header('Location: assign.php?id='.$cm->id.'&action=editevaluators&tid='.$tid);
    }

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
      // Si hay algun usuario que evalue a este equipo no se puede borrar el trabajo
      $teachers = array_keys(get_course_teachers($course->id));
      $sql = "select count(*) from ".$CFG->prefix."teamwork_evals where teamevaluated = ".$tid." and evaluator NOT IN(".implode(',', $teachers).")";
      
      if( count_records_sql($sql))
      {
        print_error('cannotdeletethisworkbecausethishaveevaluators', 'teamwork');
      }

      // Borrar el contenido de la base de datos
      $data = new stdClass;
      $data->id = $tid;
      $data->worktime = 0;
      $data->workdescription = '';
      update_record('teamwork_teams', $data);

      // Borrar el archivo subido si existiere
      remove_dir( $CFG->dataroot.'/'.$course->id.'/'.$CFG->moddata.'/teamwork/'.$teamwork->id.'/'.$tid );

      // Borrar a los profesores como evaluadores, ya que en este punto tenemos la certeza que los unicos que quedan son los profesores
      delete_records('teamwork_evals', 'teamworkid', $teamwork->id, 'teamevaluated', $tid);

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

    // Pedir confirmación para crear los trabajos simbólicos
    if(!isset($_POST['do']))
    {
      notice_yesno(get_string('confirmationforcreatesymbolicsworks', 'teamwork'), 'assign.php', 'assign.php', array('id'=>$cm->id, 'action'=>'symbolicworks', 'do'=>'true'), array('id'=>$cm->id), 'post', 'get');
    }
    else
    {
      // Obtener la lista de trabajos enviados
      $works = count_records_sql('select count(*) from '.$CFG->prefix.'teamwork_teams as t where t.teamworkid = '.$teamwork->id.' and t.worktime != 0 order by t.teamname');

      // Para que se puedan crear trabajos simbolicos es necesario que ningún grupo haya subido nada y que esté cerrado el plazo de envio
      if($works == 0 AND $teamwork->endsends < time())
      {
        // Actualizar los equipos de esta instancia con los datos por defecto
        execute_sql('update '.$CFG->prefix."teamwork_teams set workdescription = '" . get_string('symbolycworktext', 'teamwork') . "', worktime = ".time()." where teamworkid = ".$teamwork->id);

        // Permitir a los profesores del curso evaluar a todos los equipos si esta activa esa opcion
        if($teamwork->wgteacher)
        {
          // Obtener la lista de equipos de la instancia
          $teams = get_records('teamwork_teams', 'teamworkid', $teamwork->id);

          $teachers = get_course_teachers($course->id);
          $insert = new stdClass;
          $insert->teamworkid = $teamwork->id;
          $insert->timecreated = time();

          foreach($teams as $team)
          { 
            $insert->teamevaluated = $team->id;

            foreach($teachers as $teacher)
            {
              $insert->evaluator = $teacher->id;
              insert_record('teamwork_evals', $insert);
            }
          }
        }

        // Redireccionar a la lista de trabajos enviados
        header('Location: assign.php?id='.$cm->id);
      }
      else
      {
        // No se puede crear trabajos simbólicos si ya existen o aun está abierto el plazo de envíos...
        print_error('youcannotcreatesymbolicworks', 'teamwork');
      }
    }
    
  break;
}


//
/// footer
//

print_footer($course);
?>