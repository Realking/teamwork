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

// Obtener los datos de la evaluación asegurandonos que pertenece a esta instancia
if( !$eval = get_record('teamwork_evals', 'id', $eid, 'teamworkid', $teamwork->id) )
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

// Comprobar que el plazo de evaluación siga abierto
if(time() > $teamwork->endevals)
{
  print_error('evaluationshavebeenclosed', 'teamwork');
}

// Titulo de la página
print_heading(get_string('evaluationform', 'teamwork'));

// Obtener a quién estamos evaluando
if( ! is_null($eval->userevaluated) )
{
  // Obtener los datos del usuario
  $usereval = get_record('user', 'id', $eval->userevaluated);

  echo '<p align="center">';
  print_string('youareevaluatingtheuser', 'teamwork', $usereval->firstname.' '.$usereval->lastname);
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
  $data = $form->get_data()->item;
  
  // Obtener la ID de la plantilla que debemos usar
  if( !$tplid = get_record('teamwork_tplinstances', 'teamworkid', $teamwork->id, 'evaltype', (! is_null($eval->userevaluated)) ? 'user' : 'team' ) )
  {
    print_error('notemplateasigned', 'teamwork');
  }

  // Obtenemos los items de evaluación
  if( !$items = get_records('teamwork_items', 'templateid', $tplid->templateid) )
  {
    print_error('thisevaluationidnotexist', 'teamwork');
  }
  $items_keys = array_keys($items);

  // Comprobar que el numero de elementos sea el mismo
  if( count($items) != count($data))
  {
    print_error('thenumberofsubmititemnotisequaltonumberofthisevaluationitems', 'teamwork');
  }

  $weightsum = 0;

  // Para cada uno de los item enviados...
  foreach($data as $key => $value)
  {
    // Comprobar que realmente pertenece a los items de esta evaluación
    if( ! in_array($key, $items_keys) )
    {
      print_error('itemnotinthisevaluation', 'teamwork');
    }

    // De paso, almacenamos la suma total de los pesos
    $weightsum += $items[$key]->weight;
  }

  $globalgrade = 0;
  
  // Ahora que el envio es seguro, se puede proceder a insertarlo en la bbdd
  foreach($data as $key => $value)
  {
    $insert = new stdClass;
    $insert->evalid = $eid;
    $insert->itemid = $key;

    // Si se trata de una evaluación 0..100
    if($items[$key]->scale > 0)
    {
      $value = (int) $value;
      
      // Comprobar que el valor que se envia esta dentro del rango permitido
      if($value < 0)
      {
        $value = 0;
      }
      else if($value > 100)
      {
        $value = 100;
      }

      $insert->grade = $value / $items[$key]->scale; // se almacena en forma de porcentaje, es decir, de 0 a 1
    }
    // Si se trata de una escala
    else if($items[$key]->scale < 0)
    {
      // Obtenemos la escala
      if( !$scale = get_record('scale', 'id', abs($items[$key]->scale)) )
      {
        print_error('thisevaluseanonexistscale', 'teamwork');
      }

      // El primer elemento de la escala vale 0, el resto se reparte 100% entre los elementos de la escala menos el primero
      $insert->grade = ($value == 0) ? 0 : $value / (count(make_menu_from_list($scale->scale)) - 1);
    }

    // Insertar la evaluacion del elemento
    insert_record('teamwork_eval_items', $insert);

    //Almacenamos la nota de este item en la nota global
    $globalgrade += $insert->grade * ($items[$key]->weight / $weightsum);
  }

  // Actualizar la evaluación
  $update = new stdClass;
  $update->id = $eid;
  $update->timegraded = time();
  $update->grade = $globalgrade;
  update_record('teamwork_evals', $update);

  // Mostramos mensaje
  echo '<br /><p align="center">';
  print_string('evaluationsavedok', 'teamwork');
  echo '</p>';
  print_continue('view.php?id='.$cm->id);
}


//
/// Footer
//

print_footer($course);

?>
