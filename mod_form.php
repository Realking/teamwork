<?php
/**
 * Formulario de configuración de la actividad
 *
 * Este archivo contiene toda la lógica que muestra el formulario de
 * configuración de la actividad al crearla o al editarla
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

// Incluir clase padre de gestión de formularios
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Clase derivada de la clase de moodle para la gestión de formularios. Al
 * extenderla se define el formulario de configuración del módulo.
 *
 * Al final, moodle hace uso del paquete PEAR HTML_QuickForm cuya documentación
 * para variar es algo confusa.
 * 
 * @link http://pear.php.net/package/HTML_QuickForm/
 */
class mod_teamwork_mod_form extends moodleform_mod
{
    /**
     * Define el formulario
     */
    function definition()
    {
        global $CFG;
        $mform =& $this->_form;

        //------------------------- general -------------------------
        // Añadir un fieldset (marco), de nombre 'general' y cuyo titulo se obtiene de i18n
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Añadir un campo de texto, de nombre 'name', el titulo mediante i18n y de tamaño 64 caracteres
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT); //funcion definida en moodle/lib/formslib.php
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        // Añadir una regla de validación +info: http://pear.php.net/package/HTML_QuickForm/docs/latest/HTML_QuickForm/HTML_QuickForm.html#methodaddRule
        // sobre el campo 'name', sin mensaje de error, validación de tipo requerido, no se envia ningun formato y ¡¡¿¿la validación se hace en el cliente??!!
        $mform->addRule('name', null, 'required', null, 'client');

        // Añadir un textarea para la descripción de la actividad
        $mform->addElement('htmleditor', 'description', get_string('description', 'teamwork'), 'wrap="virtual" rows="20" cols="75"');
        // Tipo RAW para mantener el HTML
        $mform->setType('description', PARAM_RAW);
        // Boton de ayuda unico con tres opciones relacionadas con el editor html
        $mform->setHelpButton('description', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');
		$mform->addRule('description', null, 'required', null, 'client');
        //-----------------------------------------------------------

        //------------------------- temporización -------------------------
        // Marco
        $mform->addElement('header', 'temporizacion', get_string('timing', 'teamwork'));
		
        // Fechas
        $mform->addElement('date_time_selector', 'startsends', get_string('startsends', 'teamwork'), array('optional'=>false));
        $mform->setDefault('startsends', time());
        $mform->setHelpButton('startsends', array('startsends', get_string('startsends', 'teamwork'), 'teamwork'));
		
        $mform->addElement('date_time_selector', 'endsends', get_string('endsends', 'teamwork'), array('optional'=>false));
        $mform->setDefault('endsends', time()+7*24*3600);
        $mform->setHelpButton('endsends', array('endsends', get_string('endsends', 'teamwork'), 'teamwork'));
		
        $mform->addElement('date_time_selector', 'startevals', get_string('startevals', 'teamwork'), array('optional'=>false));
        $mform->setDefault('startevals', time()+8*24*3600);
        $mform->setHelpButton('startevals', array('startevals', get_string('startevals', 'teamwork'), 'teamwork'));
		
        $mform->addElement('date_time_selector', 'endevals', get_string('endevals', 'teamwork'), array('optional'=>false));
        $mform->setDefault('endevals', time()+14*24*3600);
        $mform->setHelpButton('endevals', array('endevals', get_string('endevals', 'teamwork'), 'teamwork'));
        //-----------------------------------------------------------------
		
        //------------------------- evaluación -------------------------
        $mform->addElement('header', 'evaluationweights', get_string('evaluationweights', 'teamwork'));
        $mform->setHelpButton('evaluationweights', array('evaluationweights', get_string('evaluationweights', 'teamwork'), 'teamwork'));

        $selectrange = array(0=>get_string('deactivateeval', 'teamwork')) + (array_combine(range(1, 100), range(1, 100)));

        $mform->addElement('select', 'wgteacher', get_string('wgteacher', 'teamwork'), $selectrange);
        $mform->setHelpButton('wgteacher', array('wgteacher', get_string('wgteacher', 'teamwork'), 'teamwork'));

        $mform->addElement('select', 'wgteam', get_string('wgteam', 'teamwork'), $selectrange);
        $mform->setHelpButton('wgteam', array('wgteam', get_string('wgteam', 'teamwork'), 'teamwork'));

        $values = array();
        $keys = range(0.01, 1, 0.01);
        foreach($keys as $k)
        {
          $values[] = ($k*100).' %';
        }

        $selectrange = array(0=>get_string('deactivateeval', 'teamwork')) + (array_combine($keys, $values));

        $mform->addElement('select', 'wgintra', get_string('wgintra', 'teamwork'), $selectrange);
        $mform->setHelpButton('wgintra', array('wgintra', get_string('wgintra', 'teamwork'), 'teamwork'));

        $mform->addElement('select', 'wggrading', get_string('wggrading', 'teamwork'), $selectrange);
        $mform->setHelpButton('wggrading', array('wggrading', get_string('wggrading', 'teamwork'), 'teamwork'));

        $selectrange = array_combine(range(1, 100), range(1, 100));

        $mform->addElement('select', 'maxgrade', get_string('maxgrade', 'teamwork'), $selectrange);
        $mform->setHelpButton('maxgrade', array('maxgrade', get_string('maxgrade', 'teamwork'), 'teamwork'));
        //--------------------------------------------------------------

        //------------------------- otrasopciones -------------------------
        $mform->addElement('header', 'otrasopciones', get_string('otheroptions', 'teamwork'));

        $selectrange = array(0=>get_string('no'), 1=>get_string('yes'));

        $mform->addElement('select', 'bgteam', get_string('bgteam', 'teamwork'), $selectrange);
        $mform->setHelpButton('bgteam', array('bgteam', get_string('bgteam', 'teamwork'), 'teamwork'));

        $mform->addElement('select', 'bgintra', get_string('bgintra', 'teamwork'), $selectrange);
        $mform->setHelpButton('bgintra', array('bgintra', get_string('bgintra', 'teamwork'), 'teamwork'));
        //-----------------------------------------------------------------

        //------------------------- commonmodulesettings -------------------------
        $features = new stdClass;
        $features->groups = false;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);
        //------------------------------------------------------------------------

        // Botones de envío y cancelación
        $this->add_action_buttons();
    }
}
?>
