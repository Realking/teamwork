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
    global $ismanager, $teamwork, $cm, $CFG, $USER;
	
    //imprimir nombre
    print_heading(format_string($teamwork->name));

    //abrir caja
    print_simple_box_start('center', '', '', 0, 'generalbox', 'intro');

    //imprimir fase actual
    echo '<b>'.get_string('currentphase', 'teamwork').'</b>: '.teamwork_phase($teamwork).'<br /><br />';

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

    //grupo al que pertenece
    $result = get_record_sql('select t.teamname, t.teamleader from '.$CFG->prefix.'teamwork_users_teams as ut, '.$CFG->prefix.'teamwork_teams as t where ut.userid = '.$USER->id.' AND t.id = ut.teamid AND t.teamworkid = '.$teamwork->id);

    if($result)
    {
        echo "<br />";
        $leader = ($result->teamleader == $USER->id) ? '(<span class="redfont">'.get_string('youareleader', 'teamwork').'</span>)' : '';
        $link = link_to_popup_window($CFG->wwwroot.'/mod/teamwork/view.php?id='.$cm->id.'&teamcomponents=true', null, $result->teamname, 400, 500, get_string('teammembers', 'teamwork', $result->teamname), null, true);
        echo '<b>'.get_string('groupbelong', 'teamwork').'</b>: '.$link." $leader<br />\n";
    }

    // Si el alumno ha sido evaluado
    $grade = teamwork_check_student_evaluated();

    if($grade !== false)
    {
      // Mostrar evaluación al alumno
      echo '<br />';
      echo '<b>'.get_string('studentgrade', 'teamwork').'</b>: '.round($grade['grade'], 2).' / '.round($grade['grademax'], 2)."<br />\n";
    }
	
    //si es manager imprimir aqui enlaces a la administración
    if($ismanager)
    {
        echo "<br />\n";
        echo '<span class="highlight2">'.get_string('youaremanager', 'teamwork').':</span> <a href="template.php?id='.$cm->id.'">'.get_string('templatesanditemseditor', 'teamwork').'</a>';
        echo ' | <a href="team.php?id='.$cm->id.'">'.get_string('teamseditor', 'teamwork').'</a>';
        if(time() > $teamwork->endsends)
        {
            echo ' | <a href="assign.php?id='.$cm->id.'">'.get_string('assignseditor', 'teamwork').'</a>';
        }

        // Mostrar botón de calificar alumnos cuando proceda
        if(time() > $teamwork->endevals AND !$teamwork->doassessment)
        {
          echo '<br /><br />';
          echo '<div style="text-align: center">';
          print_single_button('view.php', array('id'=>$cm->id, 'dograde'=>true), get_string('dograde', 'teamwork'), 'get', '_self', false, '', false, get_string('dogradeask', 'teamwork'));
          echo '</div>';
        }

        // Mostrar mensaje de calificaciones en proceso cuando proceda
        if($teamwork->doassessment)
        {
          echo '<br /><br />';
          echo '<div style="text-align: center">'.get_string('gradeinprogress', 'teamwork');
          echo '</div>';
        }
    }

    //cerrar caja
    print_simple_box_end();
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
    global $CFG, $USER, $course;

    $time = time();

    if($time < $teamwork->startsends)
    {
        $status = 1;
        $message = get_string('phase1', 'teamwork');
    }
    else if( !count_records_sql('select count(*) from '.$CFG->prefix.'teamwork_users_teams as ut, '.$CFG->prefix.'teamwork_teams as t where t.teamworkid = '.$teamwork->id.' and ut.teamid = t.id and ut.userid = '.$USER->id) AND !isteacher($course->id, $USER->id))
    {
      $status = 2;
      $message = get_string('phase2', 'teamwork');
    }
    else if($time < $teamwork->endsends)
    {
        $status = 3;
        $message = get_string('phase3', 'teamwork');
    }
    else if($time < $teamwork->startevals)
    {
        $status = 4;
        $message = get_string('phase4', 'teamwork');
    }
    else if($time < $teamwork->endevals)
    {
        $status = 5;
        $message = get_string('phase5', 'teamwork');
    }
    else if(teamwork_check_student_evaluated() === false)
    {
        $status = 6;
        $message = get_string('phase6', 'teamwork');
    }
    else
    {
        $status = 7;
        $message = get_string('phase7', 'teamwork');
    }

    //si $numeric es true devolver $status
    if($numeric)
    {
        return $status;
    }

    return $message;
}

/**
 * Comprueba si el estudiante ha sido calificado por el profesor en esta actividad o no
 * 
 * @return mixed false si no ha sido calificado, array con los datos de la calificación
 */
//TODO implementar esta funcion
function teamwork_check_student_evaluated()
{
  global $cm, $USER, $CFG;

  if( !function_exists('grade_get_grades') )
  {
    require_once($CFG->libdir.'/gradelib.php');
  }

  // Obtenemos los datos del gradebook para este usuario
  $grade = grade_get_grades($cm->course, 'mod', 'teamwork', $cm->instance, $USER->id);
  
  // Los procesamos
  $grademax = $grade->items[0]->grademax;
  $grade = $grade->items[0]->grades[$USER->id]->grade;

  // Si no hay evaluación devolver false
  if($grade === null)
  {
    return false;
  }

  return array('grade' => $grade, 'grademax' => $grademax);
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
        $mform->addElement('header', 'general', get_string('edittemplate', 'teamwork'));
        $mform->setHelpButton('general', array('edittemplate', get_string('edittemplate', 'teamwork'), 'teamwork'));

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
 * Obtiene la lista de teamworks que usan un determinado template
 * 
 * @param integer $tplid id del template
 * @return string lista de instancias
 */
//TODO realizar la implementación de la función get_instances_of_tpl
function teamwork_get_instances_of_tpl($tplid)
{
    global $CFG;
    
    $result = get_records_sql('select i.id, t.name, i.evaltype, t.id as teamworkid from '.$CFG->prefix.'teamwork_tplinstances as i, '.$CFG->prefix.'teamwork as t where t.id = i.teamworkid AND i.templateid = '.$tplid);

    if($result === false)
    {
        return '-';
    }

    foreach($result as $item)
    {
        if(isset($data[$item->name]))
        {
            $data[$item->name][1][1] = $item->evaltype;
        }
        else
        {
            $data[$item->name] = array($item->teamworkid, array($item->evaltype));
        }
    }

    $strresult = '';

    foreach($data as $activity => $opt)
    {
        $strresult .= '<a href="view.php?id='.get_coursemodule_from_instance('teamwork', $opt[0])->id.'">'.$activity . '</a> (';
      
        foreach($opt[1] as $element)
        {
            if($element == 'team')
            {
                $strresult .= '<img src="images/group.png" alt="'.get_string('usedbygroupeval', 'teamwork').'" title="'.get_string('usedbygroupeval', 'teamwork').'" />, ';
            }
            else
            {
                $strresult .= '<img src="images/user.png" alt="'.get_string('usedbyusereval', 'teamwork').'" title="'.get_string('usedbyusereval', 'teamwork').'" />, ';
            }

        }

        $strresult = substr($strresult, 0, strlen($strresult)-2);

        $strresult .= '), ';
    }

    $strresult = substr($strresult, 0, strlen($strresult)-2);

    return $strresult;
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

    $count = 0;

    //si existe algun resultado...
    if(is_array($result))
    {
        //comprobar si la plantilla especificada se encuentra en el resultado
        foreach($result as $element)
        {
            if($element->templateid == $tplid)
            {
                $count++;
                $type = $element->evaltype;
            }
        }

        //si hay dos coincidencias, devolver true
        if($count == 2)
        {
            return true;
        }
        elseif($count == 1)
        {
            return $type;
        }
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
 *                              array('value', array('prop1'=>'a', 'prop2'=>'b'), 'contenido'),
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
            $xml .= ' '.$key.'="'.htmlentities($value).'"';
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
        $xml .= htmlentities($content);
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

/**
 * Genera los botones de acción necesarios para la tabla del listado de plantillas
 * 
 * @param object $tpl datos de la plantilla actual
 * @param object $cm contexto del módulo
 * @return string html con las acciones
 */
function teamwork_tpl_table_options($tpl, $cm)
{
    $stractions  = '<a href="template.php?id='.$cm->id.'&section=templates&action=modify&tplid='.$tpl->id.'"><img src="images/pencil.png" alt="'.get_string('edit', 'teamwork').'" title="'.get_string('edit', 'teamwork').'" /></a>&nbsp;&nbsp;';

    //si se puede editar el template mostrar botón
    if(teamwork_tpl_is_editable($tpl->id))
    {
        $stractions .= '<a href="template.php?id='.$cm->id.'&section=items&tplid='.$tpl->id.'"><img src="images/page_edit.png" alt="'.get_string('edititems', 'teamwork').'" title="'.get_string('edititems', 'teamwork').'" /></a>&nbsp;&nbsp;';
    }

    //si se puede borrar el template mostrar botón
    if(teamwork_tpl_is_erasable($tpl->id))
    {
        $stractions .= '<a href="template.php?id='.$cm->id.'&section=templates&action=delete&tplid='.$tpl->id.'"><img src="images/delete.png" alt="'.get_string('deletetpl', 'teamwork').'" title="'.get_string('deletetpl', 'teamwork').'" /></a>&nbsp;&nbsp;';
    }

    $stractions .= '<a href="template.php?id='.$cm->id.'&section=templates&action=copy&tplid='.$tpl->id.'"><img src="images/arrow_divide.png" alt="'.get_string('newtplfrom', 'teamwork').'" title="'.get_string('newtplfrom', 'teamwork').'" /></a>&nbsp;&nbsp;';

    $stractions .= '<a href="template.php?id='.$cm->id.'&section=templates&action=export&tplid='.$tpl->id.'"><img src="images/page_white_put.png" alt="'.get_string('exporttpl', 'teamwork').'" title="'.get_string('exporttpl', 'teamwork').'" /></a>';

    $inst = teamwork_tpl_instanced_check($tpl->id);

    //mostrar los botones de instanciar como evaluacion de grupos e intra
    if($inst === false)
    {
        if(!teamwork_check_tpl_type('user', $cm->instance))
        {
            $stractions .= '&nbsp;&nbsp;<a href="template.php?id='.$cm->id.'&section=instances&action=add&tplid='.$tpl->id.'&type=user"><img src="images/user_add.png" alt="'.get_string('usetemplateforintraeval', 'teamwork').'" title="'.get_string('usetemplateforintraeval', 'teamwork').'" /></a>';
        }

        if(!teamwork_check_tpl_type('team', $cm->instance))
        {
            $stractions .= '&nbsp;&nbsp;<a href="template.php?id='.$cm->id.'&section=instances&action=add&tplid='.$tpl->id.'&type=team"><img src="images/group_add.png" alt="'.get_string('usetemplateforgroupeval', 'teamwork').'" title="'.get_string('usetemplateforgroupeval', 'teamwork').'" /></a>';
        }
    }
    elseif($inst === 'team' AND !teamwork_check_tpl_type('user', $cm->instance))
    {
        $stractions .= '&nbsp;&nbsp;<a href="template.php?id='.$cm->id.'&section=instances&action=add&tplid='.$tpl->id.'&type=user"><img src="images/user_add.png" alt="'.get_string('usetemplateforintraeval', 'teamwork').'" title="'.get_string('usetemplateforintraeval', 'teamwork').'" /></a>';
    }
    elseif($inst === 'user' AND !teamwork_check_tpl_type('team', $cm->instance))
    {
        $stractions .= '&nbsp;&nbsp;<a href="template.php?id='.$cm->id.'&section=instances&action=add&tplid='.$tpl->id.'&type=team"><img src="images/group_add.png" alt="'.get_string('usetemplateforgroupeval', 'teamwork').'" title="'.get_string('usetemplateforgroupeval', 'teamwork').'" /></a>';
    }

    return $stractions;
}

/**
 * Clase que muestra el formulario de editar (o añadir) un criterio de evaluación o items
 */
class teamwork_items_form extends moodleform
{
    /**
     * Define el formulario
     */
    function definition()
    {
        global $CFG;
        $mform =& $this->_form;

        //marco del formulario
        $mform->addElement('header', 'general', get_string('edititem', 'teamwork'));
        $mform->setHelpButton('general', array('edititem', get_string('edititem', 'teamwork'), 'teamwork'));

        //---> Criterio a evaluar

        //añadir un textarea para la descripción de la actividad
        $mform->addElement('htmleditor', 'description', get_string('evalcriteria', 'teamwork'), 'wrap="virtual" rows="20" cols="75"');
        //tipo RAW para mantener el HTML
        $mform->setType('description', PARAM_RAW);
        //boton de ayuda unico con tres opciones relacionadas con el editor html
        $mform->setHelpButton('description', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');
        // no puede enviarse sin contenido
        $mform->addRule('description', null, 'required', null, 'server');

        //---> Escala de calificación

        $mform->addElement('modgrade', 'scale', get_string('grade'));
        $mform->setDefault('scale', 100);
        $mform->addRule('scale', null, 'required', null, 'server');

        //---> Nombre

        //nombre de la plantilla
        $mform->addElement('text', 'weight', get_string('elementweight', 'teamwork'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('weight', PARAM_TEXT); //funcion definida en moodle/lib/formslib.php
        } else {
            $mform->setType('weight', PARAM_CLEAN);
        }
        $mform->setHelpButton('weight', array('weight', get_string('elementweight', 'teamwork'), 'teamwork'));
        //regla de validacion (no puede estar vacio el campo)
        $mform->addRule('weight', null, 'required', null, 'server');

        //---> Campos ocultos

        //id del template (para la edicion)
        $mform->addElement('hidden', 'tplid', '');

        //id del elemento (para la edicion)
        $mform->addElement('hidden', 'itemid', '');

        
        // botones de envío y cancelación
        $this->add_action_buttons();
    }
}

/**
 * Genera los botones de acción necesarios para la tabla del listado de elementos de una plantilla
 *
 * @param object $item datos del elemento actual
 * @param object $cm contexto del módulo
 * @return string html con las acciones
 */
function teamwork_item_table_options($item, $cm, $tpl, $nitems, $current_item, $prev_item, $next_item)
{
    $stractions = '';
    
    //si se puede editar la plantilla, se pueden editar sus elementos, mostrar botón
    if(teamwork_tpl_is_editable($tpl->id))
    {
        $stractions .= '<a href="template.php?id='.$cm->id.'&section=items&action=modify&itemid='.$item->id.'&tplid='.$tpl->id.'"><img src="images/pencil.png" alt="'.get_string('edit', 'teamwork').'" title="'.get_string('edit', 'teamwork').'" /></a>&nbsp;&nbsp;';
        $stractions .= '<a href="template.php?id='.$cm->id.'&section=items&action=delete&itemid='.$item->id.'&tplid='.$tpl->id.'"><img src="images/delete.png" alt="'.get_string('deleteitem', 'teamwork').'" title="'.get_string('deleteitem', 'teamwork').'" /></a>&nbsp;&nbsp;';
    }

    //si el elemento no es el primero, mostrar flecha de subir el orden
    if($current_item != 1)
    {
        $stractions .= '<a href="template.php?id='.$cm->id.'&section=items&action=order&itemid='.$item->id.'&tplid='.$tpl->id.'&o='.$prev_item->id.'"><img src="images/arrow_up.png" alt="'.get_string('upitem', 'teamwork').'" title="'.get_string('upitem', 'teamwork').'" /></a>&nbsp;&nbsp;';
    }

    //si el elemento no es el último, mostrar flecha de bajar el orden
    if($current_item != $nitems)
    {
        $stractions .= '<a href="template.php?id='.$cm->id.'&section=items&action=order&itemid='.$item->id.'&tplid='.$tpl->id.'&o='.$next_item->id.'"><img src="images/arrow_down.png" alt="'.get_string('downitem', 'teamwork').'" title="'.get_string('downitem', 'teamwork').'" /></a>&nbsp;&nbsp;';
    }

    return $stractions;
}

/**
 * Comprueba si la actividad tiene asignada una plantilla para el tipo de evaluación especificado
 *
 * @param string $type tipo a comprobar
 * @param object $teamwork objeto que representa una actividad teamwork
 * @return bool true si tiene asignada una plantilla para ese tipo, false si no.
 */
function teamwork_check_tpl_type($type, $teamworkid)
{
    static $result = null;

    //si no hemos realizado la consulta
    if(!isset($result[$type]))
    {
        $result[$type] = count_records('teamwork_tplinstances', 'evaltype', $type, 'teamworkid', $teamworkid);
    }
    
    return ($result[$type]) ? true : false;
}

/**
 * Comprueba si una escala existe en el sistema y la tenemos disponible en el curso actual
 *
 * @global object $course objeto que hace referencia al curso actual
 * @staticvar array $result cache de resultados de las consultas
 * @param int $scale referencia a la id de la escala usada
 * @return bool true si existe, false si no.
 */
function teamwork_check_scale($scale)
{
    static $result = null;
    global $course, $CFG;

    //si no hemos realizado la consulta
    if(!isset($result[$scale]))
    {
        $result[$scale] = count_records_sql('select count(s.id) from '.$CFG->prefix.'scale as s where s.id = '.$scale.' and (s.courseid = '.$course->id.' or s.courseid = 0)');
    }

    return ($result[$scale]) ? true : false;
}

/**
 * Clase que muestra el formulario de editar (o añadir) un grupo de usuarios
 */
class teamwork_groups_form extends moodleform
{
    /**
     * Define el formulario
     */
    function definition()
    {
        global $CFG;
        $mform =& $this->_form;

        //marco del formulario
        $mform->addElement('header', 'general', get_string('editteam', 'teamwork'));
        $mform->setHelpButton('general', array('editteam', get_string('editteam', 'teamwork'), 'teamwork'));

        //---> Nombre

        //nombre de la plantilla
        $mform->addElement('text', 'teamname', get_string('teamname', 'teamwork'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('teamname', PARAM_TEXT); //funcion definida en moodle/lib/formslib.php
        } else {
            $mform->setType('teamname', PARAM_CLEAN);
        }
        $mform->setHelpButton('teamname', array('teamname', get_string('teamname', 'teamwork'), 'teamwork'));
        //regla de validacion (no puede estar vacio el campo)
        $mform->addRule('teamname', null, 'required', null, 'server');

        //---> Campos ocultos

        //id del grupo (para la edicion)
        $mform->addElement('hidden', 'tid', '');
        

        // botones de envío y cancelación
        $this->add_action_buttons();
    }
}

/**
 * Comprueba si se puede editar cualquier parte de esta instancia de actividad
 * 
 * @param object $teamwork referencia al teamwork actual
 * @return bool true si se puede editar, en otro caso false
 */
function teamwork_is_editable($teamwork)
{
    global $CFG;

    // Consideramos que se puede editar mientras que ningún alumno/profesor haya enviado alguna evaluación
    if(count_records_sql('select count(*) from '.$CFG->prefix.'teamwork_evals as e where e.teamworkid = '.$teamwork->id.' and timegraded IS NOT NULL'))
    {
      // Si hay al menos una evaluación...
      return false;
    }

    return true;
}

/**
 * Acciones de la tabla con la lista de equipos
 *
 * @global object $cm contexto del modulo
 * @global object $teamwork referencia al teamwork
 * @param object $team datos del equipo
 * @return string html de las acciones
 */
function teamwork_group_table_options($team)
{
    global $cm, $teamwork;

    $stractions = '';
    
    //opcion de editar el nombre del grupo (siempre se puede)
    $stractions .= '<a href="team.php?id='.$cm->id.'&action=editteam&tid='.$team->id.'"><img src="images/pencil.png" alt="'.get_string('editteam', 'teamwork').'" title="'.get_string('editteam', 'teamwork').'" /></a>&nbsp;&nbsp;';

    //solo si se puede editar el teamwork
    if(teamwork_is_editable($teamwork))
    {
        //boton de editar los miembros del grupo
        $stractions .= '<a href="team.php?id='.$cm->id.'&action=userlist&tid='.$team->id.'"><img src="images/page_edit.png" alt="'.get_string('editmembers', 'teamwork').'" title="'.get_string('editmembers', 'teamwork').'" /></a>&nbsp;&nbsp;';

        // Un equipo puede ser borrado si y solo si no tiene miembros asignados
        if( !count_records('teamwork_users_teams', 'teamid', $team->id))
        {
          //boton de eliminar grupo
          $stractions .= '<a href="team.php?id='.$cm->id.'&action=deleteteam&tid='.$team->id.'"><img src="images/delete.png" alt="'.get_string('deleteteam', 'teamwork').'" title="'.get_string('deleteteam', 'teamwork').'" /></a>&nbsp;&nbsp;';
        }
    }

    return $stractions;
}

/**
 * Obtiene la lista de los miembros de un equipo
 * 
 * @param object $team referfencia al equipo
 * @return string lista de miembros 
 */
function teamwork_get_team_members($team)
{
    global $CFG, $course;
    
    //obtenemos los nombres de los usuarios
    if(!$result = get_records_sql('select u.id, u.firstname, u.lastname from '.$CFG->prefix.'teamwork_users_teams as ut, '.$CFG->prefix.'user as u where u.id = ut.userid and ut.teamid = '.$team->id))
    {
        //no hay usuarios
        return '-';
    }
    //hay usuarios
    else
    {
        $output = '';

        foreach($result as $user)
        {
            $output .= ' <a href="../../user/view.php?id='.$user->id.'&course='.$course->id.'" target="_blank">'.$user->firstname.' '.$user->lastname.'</a>,';
        }

        return substr($output, 0, strlen($output)-1);
    }
}

/**
 * Acciones de la tabla con la lista de miembros de un equipo
 * 
 * @global object $cm contexto del modulo
 * @global object $teamwork referencia al teamwork
 * @param object $member datos del usuario
 * @param integer $tid id del equipo
 * @param object $team referencia al equipo
 * @return string html de las acciones
 */
function teamwork_usersteams_table_options($member, $tid, $team)
{
    global $cm, $teamwork;

    $output = '';

    //solo si se puede editar el teamwork
    if(teamwork_is_editable($teamwork))
    {
        //boton de quitar el miembro del grupo
        $output .= '<a href="team.php?id='.$cm->id.'&action=deleteuser&tid='.$tid.'&uid='.$member->id.'"><img src="images/delete.png" alt="'.get_string('removeuserfromteam', 'teamwork').'" title="'.get_string('removeuserfromteam', 'teamwork').'" /></a>&nbsp;&nbsp;';

        //boton de establecer como lider
        if($member->id != $team->teamleader)
        {
            $output .= '<a href="team.php?id='.$cm->id.'&action=setleader&tid='.$tid.'&uid='.$member->id.'"><img src="images/leader_set.png" alt="'.get_string('setthisuserasteamleader', 'teamwork').'" title="'.get_string('setthisuserasteamleader', 'teamwork').'" /></a>&nbsp;&nbsp;';
        }
    }

    return $output;
}

/**
 * Genera una lista del estilo de Nombre : Todos A B C D E F G H I J K L M N O P Q R S T U V W X Y Z con enlaces
 * 
 * @param string $urlbase url base para los enlaces
 * @param string $param_name nombre de la variable que indica la letra elegida
 * @return string html con la lista
 */
function teamwork_alphabetical_list($url_base, $param_name)
{
    $selected = optional_param($param_name, null);
    $output = '';

    $chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

    $output .= ($selected === null) ? '<b>'.get_string('all').'</b>&nbsp;' : '<a href="'.teamwork_create_url($url_base, array(), array($param_name)).'">'.get_string('all').'</a>&nbsp;';

    foreach($chars as $char)
    {
        //si es la variable pasada por get, la ponemos en negrita
        if(strtolower($char) == $selected)
        {
            $output .= '<b>'.$char.'</b>&nbsp;';
        }
        else
        {
            $output .= '<a href="'.teamwork_create_url($url_base, array($param_name=>strtolower($char))).'">'.$char.'</a>&nbsp;';
        }
    }

    return $output;
}

/**
 * Genera una url de raiz este módulo en base a una serie de parametros
 * 
 * @param string $url_base ruta al archivo a partir de ...moodle/mod/teamwork/
 * @param array $set array de nombre=>valor que le asigno a una variable
 * @param array $delete array de variables que se eliminarán de esta url. Si es true borra todos los existentes
 */
function teamwork_create_url($url_base = '', $set = array(), $delete = array())
{
    global $CFG;

    $active_params = array();

    //para cada parametro enviado...
    foreach($_GET as $param => $value)
    {    
        //el parametro se ha enviado
        $active_params[$param] = $value;
    }

    //para cada parametro que yo establezco
    foreach($set as $key => $value)
    {
        //establecemos el parametro
        $active_params[$key] = $value;
    }

    //para cada parametro que quiero quitar
    foreach($delete as $name)
    {
        //eliminamos el parametro
        unset($active_params[$name]);
    }

    $url = http_build_query($active_params);

    $url = (!empty($url)) ? '?' . $url : $url;

    $url = $CFG->wwwroot . '/mod/teamwork/' . $url_base . $url;

    return $url;
}

/**
 * Clase que muestra el formulario de la generación aleatoria de equipos
 */
class teamwork_randomteams_form extends moodleform
{
    /**
     * Define el formulario
     */
    function definition()
    {
        global $CFG;
        $mform =& $this->_form;

        //marco del formulario
        $mform->addElement('header', 'general', get_string('createrandomteams', 'teamwork'));
        $mform->setHelpButton('general', array('createrandomteams', get_string('createrandomteams', 'teamwork'), 'teamwork'));


        //---> Especifidad

        $options = array(get_string('numberofteams', 'teamwork'), get_string('membersperteam', 'teamwork'));
        $mform->addElement('select', 'specify', get_string('typeofspecify', 'teamwork'), $options);


        //---> Numero especificado

        $mform->addElement('text', 'number', get_string('numberofteams-members', 'teamwork'),'maxlength="4" size="4"');
        $mform->setType('number', PARAM_INT);
        $mform->addRule('number', null, 'numeric', null, 'server');
        $mform->addRule('number', get_string('required'), 'required', null, 'server');


        //---> Tipo de distribución

        $options = array('random'    => get_string('random', 'teamwork'),
                         'firstname' => get_string('byfirstname', 'teamwork'),
                         'lastname'  => get_string('bylastname', 'teamwork'));
        $mform->addElement('select', 'distribution', get_string('distribution', 'teamwork'), $options);
        $mform->setDefault('distribution', 'random');


        //---> Esquema de nombrado

        $mform->addElement('text', 'namingscheme', get_string('namingscheme', 'teamwork'));
        $mform->setHelpButton('namingscheme', array('namingscheme', get_string('namingscheme', 'teamwork'), 'teamwork'));
        $mform->addRule('namingscheme', get_string('required'), 'required', null, 'server');
        $mform->setType('namingscheme', PARAM_MULTILANG);
        $mform->setDefault('namingscheme', get_string('namingschemetpl', 'teamwork'));


        // botones de envío y cancelación
        //$this->add_action_buttons();
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'preview', get_string('preview'), 'xx');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('submit', 'teamwork'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    function validation($data, $files) {
    	global $CFG, $COURSE;
        $errors = parent::validation($data, $files);

        //el numero no puede ser menor que 1
        if($data['number'] < 1)
        {
            $errors['number'] = get_string('numberteammemberscannotbezero', 'teamwork');
        }

       /// Check the naming scheme
        $matchcnt = preg_match_all('/[#@]{1,1}/', $data['namingscheme'], $matches);

        if ($matchcnt != 1) {
            $errors['namingscheme'] = get_string('badnamingscheme', 'teamwork');
        }

        return $errors;
    }
}

/**
 * Obtiene la ruta al archivo enviado por el grupo
 *
 * @param object $team referencia al equipo
 * @return mixed string ruta al archivo, bool false si no se ha enviado ningún archivo
 */
function teamwork_get_team_submit_file($team)
{
    global $teamwork, $course, $CFG;

    //ruta al directorio con los archivos
    $filepath = $CFG->dataroot.'/'.$course->id.'/'.$CFG->moddata.'/teamwork/'.$teamwork->id.'/'.$team->id;

    if(is_dir($filepath))
    {
        //obtenemos el nombre del archivo
        if($files = get_directory_list($filepath))
        {
            if(count($files) == 1)
            {
                $o = new stdClass;
                $o->name = $files[0];
                $o->path = $filepath.'/'.$files[0];
                $o->download = '/'.$course->id.'/'.$CFG->moddata.'/teamwork/'.$teamwork->id.'/'.$team->id.'/'.$files[0];
                
                return $o;
            }   
        }
    }

    return false;
}

/**
 * Obtiene el html necesario para imprimir un archivo subido
 * 
 * @global object $CFG datos de configuracion
 * @param object $file datos del archivo a mostrar
 * @param bool $return true si debemos devolverlo en la función, false para imprimirlo directamente
 * @return mixed string o nada 
 */
function teamwork_print_team_file($file, $return = false)
{
    global $CFG;
    
    $output = '';

    require_once($CFG->libdir.'/filelib.php');

    $icon = mimeinfo('icon', $file->path);
    $ffurl = get_file_url($file->download);

    $output .= '<img src="'.$CFG->pixpath.'/f/'.$icon.'" class="icon" alt="'.$icon.'" />'.
            '<a href="'.$ffurl.'" >'.$file->name.'</a><br />';

    $output = '<div class="files">'.$output.'</div>';

    if ($return)
    {
        return $output;
    }
    
    echo $output;
}

/**
 * Clase que muestra el formulario de la generación aleatoria de equipos
 */
class teamwork_edit_submission_form extends moodleform
{
    /**
     * Define el formulario
     */
    function definition()
    {
        global $CFG, $course;
        $mform =& $this->_form;

        $this->set_upload_manager(new upload_manager('attachedfile', true, false, null, false, 0, false, true, true));

        //marco del formulario
        $mform->addElement('header', 'general', get_string('editsubmission', 'teamwork'));
        $mform->setHelpButton('general', array('editsubmission', get_string('editsubmission', 'teamwork'), 'teamwork'));

        //---> Contenido del trabajo

        //añadir un textarea para la descripción de la actividad
        $mform->addElement('htmleditor', 'description', get_string('submissioncontent', 'teamwork'), 'wrap="virtual" rows="20" cols="75"');
        //tipo RAW para mantener el HTML
        $mform->setType('description', PARAM_RAW);
        //boton de ayuda unico con tres opciones relacionadas con el editor html
        $mform->setHelpButton('description', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');
 

        //---> Archivo adjunto

        //añadir un textarea para la descripción de la actividad
        $mform->addElement('file', 'attachedfile', get_string('attachfile', 'teamwork'));
        //boton de ayuda unico con tres opciones relacionadas con el editor html
        $mform->setHelpButton('attachedfile', array('attachfile', get_string('attachfile', 'teamwork'), 'teamwork'));


        // botones de envío y cancelación
        $this->add_action_buttons();
    }
}

/**
 * Acciones de la tabla con la lista de trabajos enviados
 *
 * @global object $cm contexto del modulo
 * @global object $teamwork referencia al teamwork
 * @param object $work datos del equipo que envia el trabajo
 * @return string html de las acciones
 */
function teamwork_sent_works_table_options($work)
{
    global $cm, $teamwork, $CFG, $course;

    $stractions = '';
    $now = time();

    //solo si estamos en el periodo de asignaciones, podemos asignar equipos a los trabajos
    if($teamwork->endsends < $now AND $now < $teamwork->startevals)
    {
        //boton de editar los equipos que corrigen el trabajo
        $stractions .= '<a href="assign.php?id='.$cm->id.'&action=editevaluators&tid='.$work->id.'"><img src="images/page_edit.png" alt="'.get_string('editevaluators', 'teamwork').'" title="'.get_string('editevaluators', 'teamwork').'" /></a>&nbsp;&nbsp;';
    }

    if( !empty($work->workdescription) OR teamwork_get_team_submit_file($work) !== false )
    {
      //boton de visualizar trabajo
      $stractions .= '<a href="viewer.php?id='.$cm->id.'&tid='.$work->id.'"><img src="images/viewer.png" alt="'.get_string('viewwork', 'teamwork').'" title="'.get_string('viewwork', 'teamwork').'" /></a>&nbsp;&nbsp;';
    }

    if($teamwork->startsends < $now AND $now < $teamwork->startevals AND !count_records_sql("select count(*) from ".$CFG->prefix."teamwork_evals where teamevaluated = ".$work->id." and evaluator NOT IN(".implode(',', array_keys(get_course_teachers($course->id))).")"))
    {
        //boton de eliminar trabajo
        $stractions .= '<a href="assign.php?id='.$cm->id.'&action=deletework&tid='.$work->id.'"><img src="images/delete.png" alt="'.get_string('deletework', 'teamwork').'" title="'.get_string('deletework', 'teamwork').'" /></a>&nbsp;&nbsp;';
    }

    return $stractions;
}

/**
 * Obtiene la lista de los equipos que evaluan a un equipo
 *
 * @param object $team referfencia al equipo
 * @return string lista de equipos
 */
function teamwork_get_team_evaluators($team)
{
    global $CFG, $course, $teamwork, $cm;

    $sql = 'select t.id, t.teamname from '.$CFG->prefix.'teamwork_teams as t, '.$CFG->prefix.'teamwork_evals as e, '.$CFG->prefix.'teamwork_users_teams as ut where
                                 e.teamevaluated = '.$team->id.' AND ut.userid = e.evaluator AND t.id = ut.teamid and t.teamworkid = "'.$teamwork->id.'"';

    //obtenemos los nombres de los usuarios
    if( !$result = get_records_sql($sql) )
    {
        //no hay equipos
        return '-';
    }
    //hay equipos
    else
    {
        $output = '';

        foreach($result as $t)
        {
            $output .= ' <a href="team.php?id='.$cm->id.'&action=userlist&tid='.$t->id.'" target="_blank">'.$t->teamname.'</a>,';
        }

        return substr($output, 0, strlen($output)-1);
    }
}

/**
 * Clase que muestra el formulario de editar (o añadir) un grupo de usuarios
 */
class teamwork_evaluation_form extends moodleform
{
    /**
     * Define el formulario
     */
    function definition()
    {
        global $CFG, $teamwork;
        $mform =& $this->_form;

        //
        /// Obtener items de la evaluación
        //

        // Obtener la ID de la plantilla que debemos usar
        if( !$tplid = get_record('teamwork_tplinstances', 'teamworkid', $teamwork->id, 'evaltype', $this->_customdata['type']) )
        {
          print_error('notemplateasigned', 'teamwork');
        }

        // Obtenemos los items de evaluación
        if( !$items = get_records('teamwork_items', 'templateid', $tplid->templateid) )
        {
          print_error('thisevaluationidnotexist', 'teamwork');
        }
        
        //
        /// Dibujo del formulario
        //

        // Marco del formulario
        $t = ($this->_customdata['type'] == 'user') ? 'userevalform' : 'teamevalform';
        $mform->addElement('header', 'general', get_string($t, 'teamwork'));
        $mform->setHelpButton('general', array($t, get_string($t, 'teamwork'), 'teamwork'));


        // Para cada elemento de la plantilla...
        foreach($items as $item)
        {
          // Si es 0 directamente no mostramos el item de evaluación
          if($item->scale != 0)
          {
            // Si se trata de un numero positivo es que es el máximo de puntuación disponible
            if($item->scale > 0)
            {
              // Generamos las opciones para el select
              for($i=0; $i <= $item->scale; $i++)
              {
                $options[$i] = $i;
              }
            }
            // Si se trata de un número negativo, hay que mirar la escala
            else
            {
              if( !$scale = get_record('scale', 'id', abs($item->scale)) )
              {
                print_error('thisevaluseanonexistscale', 'teamwork');
              }

              $options = array_reverse(make_menu_from_list($scale->scale));
            }

            // Mostramos el elemento
            $mform->addElement('select', 'item['.$item->id.']', $item->description, $options);
            //$mform->setDefault('item'.$item->id, 'random');
            //regla de validacion (no puede estar vacio el campo)
            $mform->addRule('item['.$item->id.']', null, 'required', null, 'server');
          }
        }
        
        // Botones de envío y cancelación
        $this->add_action_buttons();
    }
}
?>