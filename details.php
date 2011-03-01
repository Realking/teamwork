<?php
/**
 * Página de información detallada de la actividad
 *
 * Esta pagina visible sólo por el profesor muestra información detallada de
 * las evaluaciones efectuadas por los alumnos en una actividad
 *
 * @author Javier Aranda <internet@javierav.com>
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

// Y ademas es necesario que tenga permisos de manager
require_capability('mod/teamwork:manage', $cm->context);


//
/// header
//

// Iniciamos el bufer de salida (es posible que tengamos que modificar las cabeceras http y si imprimimos aqui algo no podremos hacerlo)
ob_start();

$navigation = build_navigation(get_string('evalsdetails', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('teamseditor', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, '', navmenu($course, $cm));

echo '<div class="clearer"></div><br />';

// La fecha actual tiene que ser mayor o igual que la fecha de inicio de las evaluaciones
if( time() < $teamwork->startevals )
{
	print_error('detailsnotavailable', 'teamwork');
}

//
/// Información sobre las calificaciones a equipos
//
print_heading(get_string('teamsevals', 'teamwork'));

// Profesores del curso
$teachers = array_keys(get_course_teachers($teamwork->course));

$sql = 'select e.id, u.id as userid, u.firstname, u.lastname, t.id as teamid, e.grade from '.$CFG->prefix.'teamwork_evals e, '.$CFG->prefix.'user u, 
			 '.$CFG->prefix.'teamwork_teams t where t.id = e.teamevaluated and u.id = e.evaluator and e.teamevaluated is not null 
			 order by u.lastname ASC';

if(! $evals = get_records_sql($sql) )
{
	echo '<p align="center">'.get_string('notexistanyteam', 'teamwork').'</p>';
}
else
{
	$table = new stdClass;
	$table->width = '95%';
	$table->tablealign = 'center';
	$table->id = 'evalsdetailtable';
	
	// Obtenemos la lista de equipos de este teamwork
	if(!$teams = get_records('teamwork_teams', 'teamworkid', $teamwork->id))
	{
		print_error('notexistanyteam', 'teamwork');
	}
	
	$table->head = array(get_string('evaluator', 'teamwork'));
	$table->align = array('center');
	
	foreach($teams as $team)
	{
		$table->head[] = $team->teamname;
		$table->align[] = 'center';
	}

	$evaluations_s = array();
	$evaluations_t = array();
	
	foreach($evals as $eval)
	{
		$name = $eval->firstname.' '.$eval->lastname;
		
		// Si la evaluación es de un profesor, separar del resto
		if(in_array($eval->userid, $teachers))
		{
			$evaluations_t[$name][$eval->teamid] = $eval->grade;
		}
		else
		{
			$evaluations_s[$name][$eval->teamid] = $eval->grade;
		}
	}
	
	// Evaluaciones de los profesores
	foreach($evaluations_t as $k => $v)
	{
		$data = array('<span class="redfont">'.$k.'</span>');
		
		foreach($teams as $team)
		{
			if(isset($v[$team->id]))
			{
				$data[] = '<span class="redfont">'.$v[$team->id].'</span>';
			}
			else
			{
				$data[] = '<span class="redfont">-</span>';
			}
		}
		
		$table->data[] = $data;
	}
	
	$table->data[] = 'hr';
	
	// Evaluaciones de los alumnos
	foreach($evaluations_s as $k => $v)
	{
		$data = array($k);
		
		foreach($teams as $team)
		{
			if(isset($v[$team->id]))
			{
				$data[] = $v[$team->id];
			}
			else
			{
				$data[] = '-';
			}
		}
		
		$table->data[] = $data;
	}

	// Imprimir la tabla
	print_table($table);
}


?>
