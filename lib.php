<?php
/**
 * Funciones de integración con moodle
 *
 * Este archivo contiene las funciones básicas requeridas por moodle para su
 * correcto funcionamiento e interacción
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

/**
 * Añade una nueva instancia de teamwork en la base de datos
 *
 * @param object $teamwork datos enviados por el formulario
 * @return integer id de la nueva instancia del teamwork
 */
function teamwork_add_instance($teamwork)
{	
	$return = false;

  //añadir los valores por defecto
	if($teamwork->allowselecteval == '0')
	{
		$teamwork->selectevalmin = 0;
		$teamwork->selectevalmax = 0;
		$teamwork->selectteammax = 0;
	}
	
	$return = insert_record('teamwork', $teamwork);
  
  if($return)
  {
    // Añadimos las fechas como eventos del calendario
    $event = new stdClass;
    $event->name        = $teamwork->name;
    $event->description = $teamwork->description;
    $event->courseid    = $teamwork->course;
    $event->groupid     = 0;
    $event->userid      = 0;
    $event->modulename  = 'teamwork';
    $event->instance    = $return;
    $event->eventtype   = 'due';
    $event->timeduration = 0;
    
    // Fecha de inicio de envíos
    $event->timestart   = $teamwork->startsends;
    add_event($event);
    
    // Fecha de finalización de envíos
    $event->timestart   = $teamwork->endsends;
    add_event($event);
    
    // Fecha de inicio de evaluaciones
    $event->timestart   = $teamwork->startevals;
    add_event($event);
    
    // Fecha de finalización de evaluaciones
    $event->timestart   = $teamwork->endevals;
    add_event($event);
  }

  return (bool) $return;
}

/**
 * Actualiza una instancia de teamwork en la base de datos
 *
 * @param object $teamwork datos enviados por el formulario
 * @return boolean status code de la operación
 */
function teamwork_update_instance($teamwork)
{	
	$return = false;

  //añadir los valores por defecto
	if($teamwork->allowselecteval == '0')
	{
		$teamwork->selectevalmin = 0;
		$teamwork->selectevalmax = 0;
		$teamwork->selectteammax = 0;
	}
	
	$teamwork->id = $teamwork->instance;
	
	$return = update_record('teamwork', $teamwork);

  if($return)
  {
    // Eliminamos los datos del calendario antiguo
    delete_records('event', 'modulename', 'teamwork', 'instance', $teamwork->id);

    // Añadimos las fechas como eventos del calendario
    $event = new stdClass;
    $event->name        = $teamwork->name;
    $event->description = $teamwork->description;
    $event->courseid    = $teamwork->course;
    $event->groupid     = 0;
    $event->userid      = 0;
    $event->modulename  = 'teamwork';
    $event->instance    = $teamwork->id;
    $event->eventtype   = 'due';
    $event->timeduration = 0;

    // Fecha de inicio de envíos
    $event->timestart   = $teamwork->startsends;
    add_event($event);

    // Fecha de finalización de envíos
    $event->timestart   = $teamwork->endsends;
    add_event($event);

    // Fecha de inicio de evaluaciones
    $event->timestart   = $teamwork->startevals;
    add_event($event);

    // Fecha de finalización de evaluaciones
    $event->timestart   = $teamwork->endevals;
    add_event($event);
  }

  return $return;
}

/**
 * Elimina una instancia de teamwork y todos sus datos
 * 
 * @param integer $id id de la instancia a eliminar
 * @return boolean status code de la operación
 */
function teamwork_delete_instance($id)
{
	// Obtenemos los datos de la instancia de teamwork
	if(! $teamwork = get_record('teamwork', 'id', $id))
	{
    return false;
  }
	
	// Por defecto el resultado de la eliminación es positivo
  $result = true;

  //
  // Plantillas
  //

  // Si las plantillas que usa no son usadas en otra instancia de este curso, las borramos
  if($tpls = get_records('teamwork_tplinstances', 'teamworkid', $teamwork->id))
  {
    foreach($tpls as $tpl)
    {
      // Comprobamos si esta plantilla se está usando en otra instancia
      if( !count_records('teamwork_tplinstances', 'templateid', $tpl->templateid))
      {
        // No se está usando, la podemos borrar
        // Borramos los items de esa plantilla
        $result = $result && delete_records('teamwork_items', 'templateid', $tpl->templateid);

        // Borramos la plantilla en si
        $result =  $result && delete_records('teamwork_templates', 'id', $tpl->templateid);
      }
    }
  }

  // Borramos las intancias de plantillas a este teamwork
  $result = $result && delete_records('teamwork_tplinstances', 'teamworkid', $teamwork->id);

  //
  /// Equipos
  //

  // Obtenemos la lista de equipos de esta instancia de teamwork
  if($teams =  get_records('teamwork_teams', 'teamworkid', $teamwork->id))
  {
    foreach($teams as $team)
    {
      // Eliminamos los usuarios del equipo
      $result = $result && delete_records('teamwork_users_teams', 'teamid', $team->id);
    }
  }

  // Borramos los equipos
  $result = $result && delete_records('teamwork_teams', 'teamworkid', $teamwork->id);

  //
  /// Evaluaciones
  //

  // Obtenemos la lista de evaluaciones
  if($evals =  get_records('teamwork_evals', 'teamworkid', $teamwork->id))
  {
    foreach($evals as $eval)
    {
      // Eliminar las evaluaciones
      $result = $result && delete_records('teamwork_eval_items', 'evalid', $eval->id);
    }
  }

  // Borramos las evaluaciones
  $result = $result && delete_records('teamwork_evals', 'teamworkid', $teamwork->id);

  //
  /// Calendario
  //

  // Borramos los eventos asociados a esta instancia
  $result = $result && delete_records('event', 'modulename', 'teamwork', 'instance', $teamwork->id);

  //
  /// Instancia
  //

  // Borramos la instancia del teamwork
  $result = $result && delete_records('teamwork', 'id', $teamwork->id);
	
	// Devolver el resultado de la operación
	return $result;
}

/**
 * Realiza comprobaciones periodicas acorde al cron de moodle
 * 
 * - Enviar recordatorios por email a los alumnos
 * 
 * @return void
 */
function teamwork_cron()
{
	return true;
}

/**
 * Indica si una escala está siendo usada por una determinada instancia de teamwork
 *
 * @param int $teamworkid
 * @param int $scaleid numero negativo
 * @return bool
 */
function teamwork_scale_used($teamworkid, $scaleid)
{
    global $CFG;
    $return = false;

    // Obtenemos los templates instanciados en este teamwork
    $sql = 'select count(*) from '.$CFG->prefix.'teamwork_tplinstances as tpl, '.$CFG->prefix.'teamwork_items as i
            where tpl.teamworkid = '.$teamworkid.' and i.templateid = tpl.templateid and i.scale = -'.$scaleid;

    $rec = count_records_sql($sql);

    if ($rec && !empty($scaleid))
    {
      $return = true;
    }

    return $return;
}

/**
 * Comprueba si una escala está siendo usada en cualquier instancia de teamwork
 *
 * @param $scaleid int
 * @return boolean true si la escala está siendo usada por cualquier teamwork
 */
function teamwork_scale_used_anywhere($scaleid)
{
    global $CFG;
    $return = false;

    // Obtenemos los templates instanciados en este teamwork
    $sql = 'select count(*) from '.$CFG->prefix.'teamwork_tplinstances as tpl, '.$CFG->prefix.'teamwork_items as i
            where i.templateid = tpl.templateid and i.scale = -'.$scaleid;

    $rec = count_records_sql($sql);

    if ($rec && !empty($scaleid))
    {
      $return = true;
    }

    return $return;
}
?>