<?php
/**
 * Visor de los trabajos enviados por los alumnos
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

//la id del equipo sobre el que queremos ver su trabajo
$tid =  required_param('tid', PARAM_INT);


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

$navigation = build_navigation(get_string('worksviewer', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('worksviewer', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, '', navmenu($course, $cm));

echo '<div class="clearer"></div><br />';


//
/// cuerpo
//

// Ver si se trata de un profesor o no
$cap = has_capability('mod/teamwork:manage', $cm->context);

// Si no es un profesor, nos aseguramos que el alumno puede ver este trabajo (porque lo corrige)
if( !$cap )
{
  if( !count_records('teamwork_evals', 'evaluator', $USER->id, 'teamevaluated', $tid))
  {
    // No tienes permiso de ver este trabajo
    print_error('youarentallowedtoseethiswork', 'teamwork');
  }
}

// Obtenemos los datos del equipo del cual queremos ver su trabajo
if($team = get_record('teamwork_teams', 'id', $tid, 'teamworkid', $teamwork->id))
{
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

  //imprimir opciones inferiores
  echo '<br /><div align="center">';
  echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="javascript: history.go(-1);">'.get_string('goback', 'teamwork').'</a>';
  echo '</div>';
}
else
{
  // Mostramos error al no existir el equipo o no ser este equipo de esta instancia
  print_error('thisteamnotexistornotisfromthisinstance', 'teamwork');
}


//
/// footer
//

print_footer($course);

?>
