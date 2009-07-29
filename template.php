<?php
/**
 * Página de configuración de templates, items y asignación al teamwork
 *
 * Esta pagina visible sólo por el profesor permite crear, editar y borrar templates
 * de items y asignarlos a la instancia actual del teamwork
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

require_once('../../config.php');
require_once('locallib.php');

//
///obtener parametros requeridos y opcionales
//

//el id del recurso (no es la instancia que esta guardada en la tabla teamwork)
$id = required_param('id', PARAM_INT);

//la accion ha realizar
$action =  optional_param('action', '', PARAM_ALPHA);

//la seccion donde realizar la accion
$section =  optional_param('section', 'instances', PARAM_ALPHA);


//
/// obtener datos del contexto donde se ejecuta el modulo
//

//el objeto $cm contiene los datos dell contexto de la instancia del modulo
if(!$cm = get_coursemodule_from_id('teamwork', $id))
{
	error('Course Module ID was incorrect');
}

//el objeto $course contiene los datos del curso en el que está el modulo instanciado
if(!$course = get_record('course', 'id', $cm->course))
{
	error('Course is misconfigured');
}

//el objeto $teamwork contiene los datos de la instancia del modulo
if(!$teamwork = get_record('teamwork', 'id', $cm->instance))
{
	error('Course module is incorrect');
}

//es necesario estar logueado en el curso
require_login($course->id, false, $cm);

//y ademas es necesario que tenga permisos de manager
require_capability('mod/teamwork:manage', $cm->context);


//
/// header
//

$navigation = build_navigation(get_string('templatesanditemseditor', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('templatesanditemseditor', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '',
                     true, '',
                     navmenu($course, $cm));

echo '<div class="clearer"></div><br />';

//selección de la sección que debemos mostrar
switch($section)
{
    case 'instances':
        
        switch($action)
        {
            //caso por defecto, mostrar la página principal de la gestión de templates
            default:

                /*//obtener los templates definidos para esta instancia de teamwork
                if(!$instanciedtpls = get_records('teamwork_tplinstances', 'teamworkid', $cm->instance))
                {
                    //construir la tabla
                    $table = new stdClass;
                    $table->head = array(get_string('templatesinuse', 'teamwork'));
                    $table->align = array('center');
                    $table->size = array('100%');
                    $table->data[] = array(get_string('notemplateinthisinstance', 'teamwork'));
                    $table->width = '70%';
                    $table->tablealign = 'center';
                    $table->id = 'mitabla';
                    //imprimir la tabla
                    print_table($table);
                    echo '<br />';
                }*/

                //obtener los templates definidos en el curso
                if(!$definedtpls = get_records('teamwork_templates', 'courseid', $course->id))
                {
                    //construir la tabla
                    $table = new stdClass;
                    $table->head = array(get_string('coursetemplateslisting', 'teamwork'));
                    $table->align = array('center');
                    $table->size = array('100%');
                    $table->data[] = array(get_string('notemplatesforthiscourse', 'teamwork').'<a href="template.php?id='.$cm->id.'&amp;section=templates&amp;action=add"> '.get_string('Add?', 'teamwork').'</a>');
                    $table->width = '70%';
                    $table->tablealign = 'center';
                    $table->id = 'mitabla';
                    //imprimir la tabla
                    print_table($table);
                }
        }
    
    break;

    //gestion de las plantillas
    case 'templates':
    
    break;

    //gestion de los elementos de las plantillas (items)
    case 'items':
    
    break;

    //mensaje de error al no existir la sección especificada
    default:
        print_error('sectionnotexist', 'teamwork');
}


//
/// footer
//

print_footer($course);
?>
