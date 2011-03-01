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
	$max_cols = 10;
	
	$table = new stdClass;
	$table->width = '95%';
	$table->tablealign = 'center';
	$table->id = 'evalsdetailtable';
	
	// Obtenemos la lista de equipos de este teamwork
	if(!$teams = get_records('teamwork_teams', 'teamworkid', $teamwork->id))
	{
		print_error('notexistanyteam', 'teamwork');
	}
	
	for( $i=0; $i <= floor(count($teams)/$max_cols); $i++ )
	{
		$tables[$i]->head  = array(get_string('evaluator', 'teamwork'));
		$tables[$i]->align = array('center');
	}
	
	$j = 0;
	
	foreach($teams as $team)
	{
		$t = floor($j/$max_cols);
		
		$tables[$t]->head[] = $team->teamname;
		$tables[$t]->align[] = 'center';
		
		$j++;
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
		for( $i=0; $i <= floor(count($teams)/$max_cols); $i++ )
		{
			$data[$i] = array('<span class="redfont">'.$k.'</span>');
		}
		
		$j = 0;
		
		foreach($teams as $team)
		{
			$t = floor($j/$max_cols);
			
			if(isset($v[$team->id]))
			{
				$data[$t][] = '<span class="redfont">'.$v[$team->id].'</span>';
			}
			else
			{
				$data[$t][] = '<span class="redfont">-</span>';
			}
			
			$j++;
		}
		
		for($i = 0; $i < count($data); $i++)
		{
			$tables[$i]->data[] = $data[$i];
		}
	}
	
	for( $i=0; $i <= floor(count($teams)/$max_cols); $i++ )
	{
		$tables[$i]->data[] = 'hr';
	}
	
	// Evaluaciones de los alumnos
	foreach($evaluations_s as $k => $v)
	{
		for( $i=0; $i <= floor(count($teams)/$max_cols); $i++ )
		{
			$data[$i] = array($k);
		}
		
		$j = 0;
		
		foreach($teams as $team)
		{
			$t = floor($j/$max_cols);
			
			if(isset($v[$team->id]))
			{
				$data[$t][] = $v[$team->id];
			}
			else
			{
				$data[$t][] = '-';
			}
			
			$j++;
		}
		
		for($i = 0; $i < count($data); $i++)
		{
			$tables[$i]->data[] = $data[$i];
		}
	}

	// Imprimir las tablas
	foreach( $tables as $table )
	{
		print_table($table);
		echo '<br />';
	}
}


?>
