<?php
/**
 * Procedimiento de actualización
 *
 * Este archivo contiene los procedimientos de actualización de la base de datos
 * al cambiar de versión del modulo (si fuera necesario).
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

/**
 * Actualiza la base de datos si procede
 * 
 * @global object $CFG información de la configuración
 * @global object $THEME
 * @global object $db clase de abstracción de la base de datos
 * @param integer $oldversion versión instalada del modulo
 * @return boolean resultado de la actualización 
 */
function xmldb_teamwork_upgrade($oldversion=0)
{
    global $CFG, $THEME, $db;

    $result = true;

    /*
    And upgrade begins here. For each one, you'll need one
    block of code similar to the next one. Please, delete
    this comment lines once this file start handling proper
    upgrade code.

    if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
        $result = result of "/lib/ddllib.php" function calls
    }
    */

    if($result && $oldversion < 2010040201)
    {
      // Actualizacion de la tabla teamwork_evals para añadir el campo teamworkid

      $table = new XMLDBTable('teamwork_evals');
      $field = new XMLDBField('teamworkid');
      $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'id');

      $result = $result && add_field($table, $field);

      // Añadir el nuevo campo creado como clave foranea
      $key = new XMLDBKey('teamworkid');
      $key->setAttributes(XMLDB_KEY_FOREIGN, array('teamworkid'), 'teamwork', array('id'));

      $result = $result && add_key($table, $key);
    }

    return $result;
}

?>