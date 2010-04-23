<?php
/**
 * Página de recogida de las evaluaciones
 *
 * Esta pagina permite recoger las evaluaciones de alumnos y profesores y guardarlas en la base de datos
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

require_once('../../config.php');
require_once('locallib.php');

//
/// Obtener parametros requeridos y opcionales
//

// El id del recurso (no es la instancia que esta guardada en la tabla teamwork)
$id = required_param('id', PARAM_INT);

// ID de Evaluación
$eid = required_param('eid', PARAM_INT);


//
/// Obtener datos del contexto donde se ejecuta el modulo
//

// El objeto $cm contiene los datos del contexto de la instancia del modulo
if(!$cm = get_coursemodule_from_id('teamwork', $id))
{
	error('Course Module ID was incorrect');
}

// El objeto $course contiene los datos del curso en el que está el modulo instanciado
if(!$course = get_record('course', 'id', $cm->course))
{
	error('Course is misconfigured');
}

// El objeto $teamwork contiene los datos de la instancia del modulo
if(!$teamwork = get_record('teamwork', 'id', $cm->instance))
{
	error('Course module is incorrect');
}

// Es necesario estar logueado en el curso
require_login($course->id, false, $cm);


//
/// Header
//

// Iniciamos el bufer de salida (es posible que tengamos que modificar las cabeceras http y si imprimimos aqui algo no podremos hacerlo)
ob_start();

$navigation = build_navigation(get_string('assessmentscollection', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('assessmentscollection', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, '', navmenu($course, $cm));

echo '<div class="clearer"></div><br />';


//
/// Body
//

// Obtener los datos de la evaluación
if( !$eval = get_record('teamwork_evals', 'id', $eid) )
{
  print_error('thisevaluationidnotexist', 'teamwork');
}

// Comprobar que la evaluación le pertenece al alumno que accede a esta página
if( $eval->evaluator != $USER->id )
{
  print_error('thisevaluationnotisforyou', 'teamwork');
}

// Comprobar que esta evaluación no ha sido realizada ya
if( ! is_null($eval->timegraded) )
{
  print_error('thisevaluationalreadyhasbeenundertaken', 'teamwork');
}

// Titulo de la página
print_heading(get_string('evaluationform', 'teamwork'));

// Obtener a quién estamos evaluando
if( ! is_null($eval->userevaluated) )
{
  // Obtener los datos del usuario
  $usereval = get_record('user', 'id', $eval->userevaluated);

  echo '<p align="center">';
  print_string('youareevaluatingtheuser', 'teamwork', $usereval->firstname.$usereval->lastname);
  echo '</p>';
}
else
{
  // Obtener los datos del equipo
  $teameval = get_record('teamwork_teams', 'id', $eval->teamevaluated);

  echo '<p align="center">';
  print_string('youareevaluatingtheteam', 'teamwork', $teameval->teamname);
  echo '</p>';
}

// Cargamos el formulario
$form = new teamwork_evaluation_form('eval.php?id='.$cm->id.'&eid='.$eid, array('eid'=>$eid,'type'=>(! is_null($eval->userevaluated)) ? 'user' : 'team'));
// 1 evaluacion de usuario, 0 evaluacion de equipo

// Si no se ha enviado, se muestra
if(!$form->is_submitted())
{
  $form->display();
}
// Si se ha enviado pero se ha cancelado, redirigir a página principal
elseif($form->is_cancelled())
{
  header('Location: view.php?id='.$cm->id);
}
// Si se ha enviado y no valida el formulario...
elseif(!$form->is_validated())
{
  $form->display();
}
// Si se ha enviado y es válido, se procesa
else
{
  // Obtenemos los datos del formulario
  $data = $form->get_data();

  var_dump($data);
  

  //actualizar los datos en la base de datos
  //update_record('teamwork_templates', $data);

  // Mostramos mensaje
  echo '<br /><p align="center">';
  print_string('evaluationsavedok', 'teamwork', $teameval->teamname);
  echo '</p>';
  print_continue('view.php?id='.$cm->id);
}


//
/// Footer
//

print_footer($course);

?>