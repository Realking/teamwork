<?php
/**
 * Funciones específicas del modulo teamwork
 *
 * Este archivo contiene las funciones específicas usadas en el módulo
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

/**
 * Imprime la información de estado de la actividad que aparece en la vista general del módulo
 * 
 * @param object $teamwork datos de la instancia
 * @return void
 */
function teamwork_show_status_info($teamwork)
{
	//imprimir nombre
	print_heading(format_string($teamwork->name));
	
	//abrir caja 
	print_box_start();
	
	//imprimir fase actual
	echo '<b>'.get_string('currentphase', 'teamwork').'</b>: '.teamwork_phase($teamwork).'<br />';
	
	//fechas
	$dates = array(
        'startsends' => $teamwork->startsends,
        'endsends' => $teamwork->endsends,
        'startevals' => $teamwork->startevals,
        'endevals' => $teamwork->endevals
    );
	
    foreach($dates as $type => $date)
	{
        if($date)
		{
            $strdifference = format_time($date - time());
			
            if (($date - time()) < 0)
			{
                $strdifference = '<span class="redfont">'.get_string('timeafter', 'teamwork', $strdifference).'</span>';
            }
			else
			{
				$strdifference = get_string('timebefore', 'teamwork', $strdifference);
			}
			
            echo '<b>'.get_string($type, 'teamwork').'</b>: '.userdate($date)." ($strdifference)<br />";
        }
    }
	
	//cerrar caja
	print_box_end();
}

/**
 * Fase en la que se encuentra la actividad
 * 
 * @param object $teamwork datos de la instancia
 * @param boolean $numeric si debe devolverse un número y no una cadena que represente la fase
 * @return string fase en la que se encuentra
 */
function teamwork_phase($teamwork, $numeric = false)
{
	$time = time();
	
	if($time < $teamwork->startsends)
	{
		$status = 1;
		$message = get_string('phase1', 'teamwork');
	}
	else if($time < $teamwork->endsends)
	{
		$status = 2;
		$message = get_string('phase2', 'teamwork');
	}
	else if($time < $teamwork->startevals)
	{
		$status = 3;
		$message = get_string('phase3', 'teamwork');
	}
	else if($time < $teamwork->endevals)
	{
		$status = 4;
		$message = get_string('phase4', 'teamwork');
	}
	/*else if($sielalumnohasidocalificado)
	{
		$status = 5;
		$message = get_string('phase5', 'teamwork');
	}*/
	else
	{
		$status = 6;
		$message = get_string('phase68', 'teamwork');
	}
	
	//si $numeric es true devolvemos $status
	if($numeric)
	{
		return $status;
	}
	
	return $message;
}
?>
