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

require_once('../../lib/formslib.php');
/**
 * Imprime la información de estado de la actividad que aparece en la vista general del módulo
 * 
 * @global object $teamwork datos de la instancia
 * @global object $cm contexto del módulo
 * @return void
 */
function teamwork_show_status_info()
{
    global $ismanager, $teamwork, $cm;
	
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
    'endevals' => $teamwork->endevals);
	
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
			
            echo '<b>'.get_string($type, 'teamwork').'</b>: '.userdate($date)." ($strdifference)<br />\n";
        }
    }
	
    //si es manager imprimir aqui enlaces a la administración
    if($ismanager)
    {
        echo "<br />\n";
        echo '<span class="highlight2">'.get_string('youaremanager', 'teamwork').':</span> <a href="template.php?id='.$cm->id.'">'.get_string('templatesanditemseditor', 'teamwork').'</a>';
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
        $message = get_string('phase6', 'teamwork');
    }

    //si $numeric es true devolver $status
    if($numeric)
    {
        return $status;
    }

    return $message;
}

/**
 * Clase que muestra el formulario de editar (o añadir) un template
 */
class teamwork_templates_form extends moodleform
{
    /**
     * Define el formulario
     */
    function definition()
    {
        global $CFG;
        $mform =& $this->_form;

        //marco del formulario
        $mform->addElement('header', 'general', get_string('addtemplate', 'teamwork'));
        $mform->setHelpButton('general', array('addtemplate', get_string('addtemplate', 'teamwork'), 'teamwork'));

        //---> Nombre

        //nombre de la plantilla
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT); //funcion definida en moodle/lib/formslib.php
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }

        //regla de validacion (no puede estar vacio el campo)
        $mform->addRule('name', null, 'required', null, 'server');

        //---> Descripción

        //añadir un textarea para la descripción de la actividad
        $mform->addElement('htmleditor', 'description', get_string('description', 'teamwork'), 'wrap="virtual" rows="20" cols="75"');
        //tipo RAW para mantener el HTML
        $mform->setType('description', PARAM_RAW);
        //boton de ayuda unico con tres opciones relacionadas con el editor html
        $mform->setHelpButton('description', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');
		$mform->addRule('description', null, 'required', null, 'server');

        //---> Id del template (para la edicion)
        $mform->addElement('hidden', 'tplid', '');

        // botones de envío y cancelación
        $this->add_action_buttons();
    }
}

/**
 * Obtiene el número de items que tiene asignado un template por la id de este
 *
 * @param integer $tplid id del template
 * @return integer número de items
 */
function teamwork_get_items_by_template($tplid)
{
    return count_records('teamwork_items', 'templateid', $tplid);
}

/**
 * Comprueba si se puede borrar una plantilla (no esté en uso, es decir, que no haya teamworks con evaluaciones que usen esta plantilla)
 *
 * @param integer $tplid id del template a comprobar
 * @return boolean si se puede borrar
 */
//TODO realizar la implementación de la funcion tpl_is_erasable
function teamwork_tpl_is_erasable($tplid)
{
    return true;
}

/**
 * Comprueba si se puede editar una plantilla (no esté en uso, es decir, que no haya teamworks con evaluaciones que usen esta plantilla)
 *
 * @param integer $tplid id del template a comprobar
 * @return boolean si se puede editar
 */
//TODO realizar la implementación de la funcion tpl_is_editable
function teamwork_tpl_is_editable($tplid)
{
    return true;
}

/**
 * Comprueba si se puede asignar una plantilla del tipo x al teamwork actual
 *
 * @param string $type tipo del template a comprobar
 * @return boolean si se puede asignar
 */
//TODO realizar la implementación de la funcion tpl_is_assignable
function teamwork_tpl_is_assignable($type)
{
    return true;
}

/**
 * Obtiene la lista de teamworks que usan un determinado template
 * 
 * @param integer $tplid id del template
 * @return string lista de instancias
 */
//TODO realizar la implementación de la función get_instances_of_tpl
function teamwork_get_instances_of_tpl($tplid)
{
    return '-';
}

/**
 * Comprueba si la plantilla $tplid se encuentra instanciada en el teamwork actual
 *
 * @staticvar array $result cache del resultado de la consulta a la bbdd
 * @param integer $tplid id del template
 * @return mixed boolean false si no tiene instancias, el tipo de la instancia en el caso que solo tenga una
 * o boolean true si se encuentra instanciada en los dos tipos
 */
function teamwork_tpl_instanced_check($tplid)
{
    global $teamwork;
    static $result = null;

    //si no hemos realizado la consulta
    if(is_null($result))
    {
        $result = get_records('teamwork_tplinstances', 'teamworkid', $teamwork->id);
    }

    return false;
}
?>
