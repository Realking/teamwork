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

  $return = insert_record('teamwork', $teamwork);
  
  if($return)
  {
    // Añadimos las fechas como eventos del calendario
    $event = new stdClass;
    $event->description = $teamwork->description;
    $event->courseid    = $teamwork->course;
    $event->groupid     = 0;
    $event->userid      = 0;
    $event->modulename  = 'teamwork';
    $event->instance    = $return;
    $event->eventtype   = 'due';
    $event->timeduration = 0;
    
    // Fecha de inicio de envíos
    $event->name        = $teamwork->name.' - '.get_string('startsends', 'teamwork');
    $event->timestart   = $teamwork->startsends;
    add_event($event);
    
    // Fecha de finalización de envíos
    $event->name        = $teamwork->name.' - '.get_string('endsends', 'teamwork');
    $event->timestart   = $teamwork->endsends;
    add_event($event);
    
    // Fecha de inicio de evaluaciones
    $event->name        = $teamwork->name.' - '.get_string('startevals', 'teamwork');
    $event->timestart   = $teamwork->startevals;
    add_event($event);
    
    // Fecha de finalización de evaluaciones
    $event->name        = $teamwork->name.' - '.get_string('endevals', 'teamwork');
    $event->timestart   = $teamwork->endevals;
    add_event($event);
  }

  return $return;
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

  // Añadir los valores por defecto
	
  $teamwork->id = $teamwork->instance;

  $return = update_record('teamwork', $teamwork);

  if($return)
  {
    // Eliminamos los datos del calendario antiguo
    delete_records('event', 'modulename', 'teamwork', 'instance', $teamwork->id);

    // Añadimos las fechas como eventos del calendario
    $event = new stdClass;
    $event->description = $teamwork->description;
    $event->courseid    = $teamwork->course;
    $event->groupid     = 0;
    $event->userid      = 0;
    $event->modulename  = 'teamwork';
    $event->instance    = $teamwork->id;
    $event->eventtype   = 'due';
    $event->timeduration = 0;

    // Fecha de inicio de envíos
    $event->name        = $teamwork->name.' - '.get_string('startsends', 'teamwork');
    $event->timestart   = $teamwork->startsends;
    add_event($event);

    // Fecha de finalización de envíos
    $event->name        = $teamwork->name.' - '.get_string('endsends', 'teamwork');
    $event->timestart   = $teamwork->endsends;
    add_event($event);

    // Fecha de inicio de evaluaciones
    $event->name        = $teamwork->name.' - '.get_string('startevals', 'teamwork');
    $event->timestart   = $teamwork->startevals;
    add_event($event);

    // Fecha de finalización de evaluaciones
    $event->name        = $teamwork->name.' - '.get_string('endevals', 'teamwork');
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
 * - Realizar el cálculo de las notas cuando el profesor lo decide
 * - Enviar recordatorios por email a los alumnos
 * 
 * @return void
 */
function teamwork_cron()
{
  global $CFG;
  
  mtrace('... Starting...');

  //
  /// Cálculo de las calificaciones
  //

  // Obtener las instancias de teamwork en las que se pida calcular las notas
  $instances = get_records('teamwork', 'doassessment', '1');

  // Si existen instancias...
  if($instances)
  {
    mtrace('... iniciando el calculo de calificaciones');

    foreach($instances as $instance)
    {
      mtrace('## Calificaciones de la instancia ID: '.$instance->id.' | '.$instance->name.' ##');
      
      // Equipos que participan en la calificación
      $teams = get_records('teamwork_teams', 'teamworkid', $instance->id);

      // Si no hay equipos pasamos a la siguiente instancia
      if(empty($teams))
      {
        mtrace('... Esta instancia no tiene equipos definidos.');
        continue;
      }

      // Profesores del curso
      $teachers = get_course_teachers($instance->course);
      $teachers_keys = array_keys($teachers);

      // Alumnos participantes
      foreach($teams as $team)
      {
        $result = get_records('teamwork_users_teams', 'teamid', $team->id);
        foreach($result as $r)
        {
          $students[$team->id][] = $r->userid;
        }
      }


      //
      /// Cálculo de las notas de un equipo
      //

      // Si está activa la evaluación del profesor
      if($instance->wgteacher)
      {
        // Para cada equipo...
        foreach($teams as $team)
        {
          // Obtener la calificación de los profesores hacia ese equipo
          $sql = 'select id, grade from '.$CFG->prefix.'teamwork_evals where teamevaluated = '.$team->id.'
                  and evaluator in('.implode(',', $teachers_keys).') and grade is not null and teamworkid = '.$instance->id;
          $result = get_records_sql($sql);

          if(!empty($result))
          {
            $sum = 0;

            // Para cada evaluación de un profesor...
            foreach($result as $g)
            {
              $sum += $g->grade;
            }

            // Calif. Profesores hacia este equipo. Realizar la media aritmética con las notas
            $teamsgrades[$team->id]['teachers'] = ($sum / count($result)) * ($instance->wgteacher / ($instance->wgteacher + $instance->wgteam));
          }
        }
      }

      // Si está activa la evaluación del equipo (por el resto de los alumnos)
      if($instance->wgteam)
      {
        // Para cada equipo...
        foreach($teams as $team)
        {
          // Obtener la calificación de los alumnos hacia ese equipo
          $sql = 'select id, grade from '.$CFG->prefix.'teamwork_evals where teamevaluated = '.$team->id.'
                  and evaluator not in('.implode(',', $teachers_keys).') and grade is not null and teamworkid = '.$instance->id;
          $result = get_records_sql($sql);

          if(!empty($result))
          {
            $sum = 0;

            // Para cada evaluación de un alumno... media artimética
            foreach($result as $g)
            {
              $sum += $g->grade;
            }
            
            $mean = ($sum / count($result));

            // Si hay que eliminar los extremos...
            if($instance->bgteam)
            {
              $sum = 0;

              // Hay que calcular la desviación típica de la muestra
              foreach($result as $r)
              {
                $sum += pow($r->grade - $mean, 2);
              }

              $desviation = sqrt($sum / (count($result)-1));

              // Establecemos los margenes de confianza superior e inferior
              $margin_top     = $mean + 2 * $desviation;
              $margin_bottom  = $mean - 2 * $desviation;

              // Volvemos a calcular la media pero eliminando aquellos elementos fuera de los margenes

              $sum = 0;

              foreach($result as $r)
              {
                // Comprobamos que se encuentra dentro del margen permitido
                if($r->grade >= $margin_bottom AND $r->grade <= $margin_top)
                {
                  $sum += $g->grade;
                }
              }

              $mean = ($sum / count($result));
            }

            // Calif. Alumnos hacia este equipo. Realizar la media aritmética con las notas
            $teamsgrades[$team->id]['students'] = $mean * ($instance->wgteam / ($instance->wgteacher + $instance->wgteam));
          }
        }
      }

      //
      /// Calificación del Alumno
      //

      // Al menos una de las dos calificaciones anteriores debe estar activa (profesor o alumnos)
      // si no no tiene sentido corregir una nota que no existe, por tanto no se califica esta instancia
      if($instance->wgteacher OR  $instance->wgteam)
      {
        $studentsgrades = array();
        
        // Recorremos los estudiantes que estan agrupados por equipos
        foreach($students as $team => $stds)
        {
          $teamgrade = (isset($teamsgrades[$team]['teachers'])) ? $teamsgrades[$team]['teachers'] : 0;
          $teamgrade = (isset($teamsgrades[$team]['students'])) ? $teamgrade + $teamsgrades[$team]['students'] : $teamgrade;

          foreach($stds as $student)
          {
            // La calificación del alumno en este punto es la obtenida por su equipo
            $studentsgrades[$student] = $teamgrade;
            
            // Si está activa la Calificación de Participación (Intra)
            if($instance->wgintra)
            {
              // Obtener la calificación de los compañeros de equipo hacia este alumno
              $sql = 'select id, grade from '.$CFG->prefix.'teamwork_evals where userevaluated = '.$student.'
                      and grade is not null and teamworkid = '.$instance->id;
              $result = get_records_sql($sql);

              if(!empty($result))
              {
                $sum = 0;

                // Para cada evaluación de un alumno...
                foreach($result as $g)
                {
                  $sum += $g->grade;
                }

                // Si hay que eliminar los extremos...
                if($instance->bgintra)
                {
                  $sum = 0;

                  // Hay que calcular la desviación típica de la muestra
                  foreach($result as $r)
                  {
                    $sum += pow($r->grade - $mean, 2);
                  }

                  $desviation = sqrt($sum / (count($result)-1));

                  // Establecemos los margenes de confianza superior e inferior
                  $margin_top     = $mean + 2 * $desviation;
                  $margin_bottom  = $mean - 2 * $desviation;

                  // Volvemos a calcular la media pero eliminando aquellos elementos fuera de los margenes

                  $sum = 0;

                  foreach($result as $r)
                  {
                    // Comprobamos que se encuentra dentro del margen permitido
                    if($r->grade >= $margin_bottom AND $r->grade <= $margin_top)
                    {
                      $sum += $g->grade;
                    }
                  }

                  $mean = ($sum / count($result));
                }

                // Calif. Compañeros hacia este alumno. Realizar la media aritmética con las notas
                $studentsgrades[$student] = ($studentsgrades[$student] * $instance->wgintra * $mean) + ($studentsgrades[$student] * (1 - $instance->wgintra));
              }
            }
          }
        }
      }

      mtrace(print_r($teamsgrades, true));
      mtrace(print_r($studentsgrades, true));

    } // Final bucle instancias
  } // Final si existen instancias

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