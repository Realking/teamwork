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
	//añadir los valores por defecto
	if($teamwork->allowselecteval == '0')
	{
		$teamwork->selectevalmin = 0;
		$teamwork->selectevalmax = 0;
		$teamwork->selectteammax = 0;
	}
	
	return insert_record('teamwork', $teamwork);
}

/**
 * Actualiza una instancia de teamwork en la base de datos
 *
 * @param object $teamwork datos enviados por el formulario
 * @return boolean status code de la operación
 */
function teamwork_update_instance($teamwork)
{	
	//añadir los valores por defecto
	if($teamwork->allowselecteval == '0')
	{
		$teamwork->selectevalmin = 0;
		$teamwork->selectevalmax = 0;
		$teamwork->selectteammax = 0;
	}
	
	$teamwork->id = $teamwork->instance;
	
	return update_record('teamwork', $teamwork);
}

/**
 * Elimina una instancia de teamwork y todos sus datos
 * 
 * @param integer $id id de la instancia a eliminar
 * @return boolean status code de la operación
 */
function teamwork_delete_instance($id)
{
	//obtenemos los datos de la instancia de teamwork
	if (! $teamwork = get_record("teamwork", "id", "$id"))
	{
        return false;
    }
	
	//por defecto el resultado de la eliminación es positivo
    $result = true;
	
	//borrar tablas con datos referentes a esta instancia
	// TODO
	
	//en ultima instancia borramos los datos del teamwork
	if (! delete_records("teamwork", "id", "$teamwork->id"))
	{
        $result = false;
    }
	
	//devolver el resultado de la operación
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
?>
