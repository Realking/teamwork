<?php
/**
 * Configuración de los permisos de acceso
 *
 * Este archivo contiene las directivas de configuración de los permisos de
 * acceso (capacidades en el lenguaje de moodle) que tendremos disponibles en
 * nuestro modulo.
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 * 
 * @link http://docs.moodle.org/en/Development:NEWMODULE_Adding_capabilities
 * @link http://docs.moodle.org/en/Development:Roles
 * @link http://docs.moodle.org/en/Development:Hardening_new_Roles_system
 */

$mod_teamwork_capabilities = array(

    //capacidad de participar en el teamwork
    'mod/teamwork:participate' => array(

        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW
        )
    ),

    //capacidad de gestionar el teamwork
    'mod/teamwork:manage' => array(

        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    )
);

?>
