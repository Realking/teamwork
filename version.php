<?php
/**
 * Versión del módulo
 *
 * Configuración de la versión del módulo, la versión requerida de moodle y la
 * configuración de CRON. Son procesadas cuando se instala el módulo.
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

//versión del módulo
$module->version  = 2010051401;

//versión de moodle mínima para instalarse
$module->requires = 2007021599;

//tiempo cada cual debe ser llamada la función de cron del módulo
$module->cron     = 60;
?>
