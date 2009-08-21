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
 * Comprueba si se puede borrar una plantilla (que no la esté usando ningún módulo)
 *
 * @param integer $tplid id del template a comprobar
 * @return boolean si se puede borrar
 */
function teamwork_tpl_is_erasable($tplid)
{
    return !(bool) count_records('teamwork_tplinstances', 'templateid', $tplid);
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

/**
 * Genera el código XML necesario a partir de un array con los datos
 *
 * Ej.
 * <root>
 *  <name></name>
 *  <values>
 *      <value prop1="a" prop2="b">contenido</value>
 *      <value prop1="c" prop2="d">contenido2</value>
 *  </values>
 * </root>
 *
 * $xml = array('root', null, array(
 *                  array('name', null, ''),
 *                  array('values', null, array(
 *                              array('value', array('prop'1=>'a', 'prop2'=>'b'), 'contenido'),
 *                              array('value', array('prop1'=>'c', 'prop2'=>'d'), 'contenido2')
 *                                             )
 *                                 )
 *                    )
 *        )
 * 
 * @param array $array matriz con los datos a formatear en xml
 * siguiendo la estructura nombre del nodo, array de parametros, contenido (este puede ser otro array de elemento)
 * @param integer $depth profundidad en el arbol (usado en la recursion)
 * @return string cadena de texto con el contenido en xml
 * o boolean false en caso de fallo
 */
function teamwork_array2xml($array, $depth = 0)
{
    $xml = '';
    
    //si la profundidad es 0, generar encabezado
    if(!$depth)
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    }

    //si no es array o si está vacío... devolver false
    if(!is_array($array) OR empty($array))
    {
        return false;
    }
   
    //si $array es un array de arrays
    if(count($array) > 0 AND is_array($array[0]))
    {
        //repetir esta tarea por cada subarray
        foreach($array as $element)
        {
            $xml .= teamwork_array2xml_extract($element, $depth);
        }
    }
    //si solo tiene uno
    else
    {
        $xml .= teamwork_array2xml_extract($array, $depth);
    }
    
    return $xml;
}

/**
 * Funcion auxiliar para extraer datos para la funcion teamwork_array2xml
 *
 * @param array $array matriz de datos
 * @param integer $depth nivel de profundidad en el arbol
 * @return string código xml obtenido de parsear los datos
 */
function teamwork_array2xml_extract($array, $depth)
{
    $xml = '';
    
    //extraer datos del elemento
    list($name, $params, $content) = $array;

    $xml .= str_repeat("\t", $depth).'<'.$name;

    //si contiene argumentos
    if(!empty($params))
    {
        foreach($params as $key => $value)
        {
            $xml .= ' '.$key.'="'.$value.'"';
        }
    }

    $xml .= ">";

    //si el contenido es otro array es que hay sublementos, obtener contenido
    if(is_array($content))
    {
        $xml .= "\n" . teamwork_array2xml($content, $depth + 1);
    }
    else
    {
        $xml .= $content;
    }

    $xml .= str_repeat("\t", $depth) . '</'.$name.">\n";

    return $xml;
}

/**
 * Convert a phrase to a URL-safe title. Note that non-ASCII characters
 * should be transliterated before using this function.
 *
 * @link http://github.com/shadowhand/kohana-core/blob/2a87f0383bdfb7c099333ab3ee7fb4855a797073/classes/kohana/url.php
 *
 * @param string phrase to convert
 * @param string word separator (- or _)
 * @return string
 */
function teamwork_url_safe($title, $separator = '-')
{
    $separator = ($separator === '-') ? '-' : '_';

    // Remove all characters that are not the separator, a-z, 0-9, or whitespace
    $title = preg_replace('/[^'.$separator.'a-z0-9\s]+/', '', strtolower($title));

    // Replace all separator characters and whitespace by a single separator
    $title = preg_replace('/['.$separator.'\s]+/', $separator, $title);

    // Trim separators from the beginning and end
    return trim($title, $separator);
}
?>
