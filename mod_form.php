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

//incluir clase padre de gestión de formularios
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

        //añadir un fieldset (marco), de nombre 'general' y cuyo titulo se obtiene de i18n
        $mform->addElement('header', 'general', get_string('general', 'form'));

        //añadir un campo de texto, de nombre 'name', el titulo mediante i18n y de tamaño 64 caracteres
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT); //funcion definida en moodle/lib/formslib.php
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        //añadir una regla de validación +info: http://pear.php.net/package/HTML_QuickForm/docs/latest/HTML_QuickForm/HTML_QuickForm.html#methodaddRule
        //sobre el campo 'name', sin mensaje de error, validación de tipo requerido, no se envia ningun formato y ¡¡¿¿la validación se hace en el cliente??!!
        $mform->addRule('name', null, 'required', null, 'client');

        if (!$options = get_records_menu("survey", "template", 0, "name", "id, name")) {
            error('No survey templates found!');
        }

        foreach ($options as $id => $name) {
            $options[$id] = get_string($name, "survey");
        }
        $options = array(''=>get_string('choose').'...') + $options;
        $mform->addElement('select', 'template', get_string("surveytype", "survey"), $options);
        $mform->addRule('template', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('template', array('surveys', get_string('helpsurveys', 'survey')));


        $mform->addElement('textarea', 'intro', get_string('customintro', 'survey'), 'wrap="virtual" rows="20" cols="75"');
        $mform->setType('intro', PARAM_RAW);

        $features = new stdClass;
        $features->groups = true;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }
}
?>
