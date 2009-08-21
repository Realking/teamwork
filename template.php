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

//el objeto $cm contiene los datos del contexto de la instancia del modulo
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

//iniciamos el bufer de salida (es posible que tengamos que modificar las cabeceras http y si imprimimos aqui algo no podremos hacerlo)
ob_start();

$navigation = build_navigation(get_string('templatesanditemseditor', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('templatesanditemseditor', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, '', navmenu($course, $cm));

echo '<div class="clearer"></div><br />';

//selección de la sección que debemos mostrar
switch($section)
{
    case 'instances':
        
        switch($action)
        {
            //caso por defecto, mostrar la página principal de la gestión de templates
            default:

                //obtener los templates definidos en el curso
                if(!$definedtpls = get_records('teamwork_templates', 'courseid', $course->id))
                {
                    //si no hay, construir la tabla para mostrar mensaje de aviso
                    $table = new stdClass;
                    $table->head = array(get_string('coursetemplateslisting', 'teamwork'));
                    $table->align = array('center');
                    $table->size = array('100%');
                    $table->data[] = array(get_string('notemplatesforthiscourse', 'teamwork').'<br /><br />'.print_single_button('template.php', array('id'=>$cm->id, 'section'=>'templates', 'action'=>'add'), get_string('createnewtemplate', 'teamwork'), 'get', '_self', true));
                    $table->width = '70%';
                    $table->tablealign = 'center';
                    $table->id = 'notemplatesforthiscoursetable';
                    
                    //imprimir la tabla
                    print_table($table);
                }
                else
                {
                    //mostrar la tabla con la lista de plantillas existentes
                    $table = new stdClass;
                    $table->width = '70%';
                    $table->tablealign = 'center';
                    $table->id = 'templatesforthiscoursetable';
                    $table->head = array(get_string('name', 'teamwork'), get_string('description', 'teamwork'), get_string('items', 'teamwork'), get_string('instances', 'teamwork'), get_string('actions', 'teamwork'));
                    //$table->size = array('10%', '90%');
                    $table->align = array('center', 'center', 'center', 'center', 'center');

                    foreach($definedtpls as $tpl)
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
                            $stractions .= '&nbsp;&nbsp;<a href="template.php?id='.$cm->id.'&section=instances&action=add&tplid='.$tpl->id.'&type=group"><img src="images/group_add.png" alt="'.get_string('usetemplateforgroupeval', 'teamwork').'" title="'.get_string('usetemplateforgroupeval', 'teamwork').'" /></a>';
                            $stractions .= '&nbsp;&nbsp;<a href="template.php?id='.$cm->id.'&section=instances&action=add&tplid='.$tpl->id.'&type=intra"><img src="images/user_add.png" alt="'.get_string('usetemplateforintraeval', 'teamwork').'" title="'.get_string('usetemplateforintraeval', 'teamwork').'" /></a>';
                        }

                        $table->data[] = array($tpl->name, $tpl->description, teamwork_get_items_by_template($tpl->id), teamwork_get_instances_of_tpl($tpl->id), $stractions);
                    }

                    //imprimir la tabla y el boton de añadir
                    print_heading(get_string('coursetemplateslisting', 'teamwork'));
                    print_table($table);
                    echo '<br /><div align="center"><br />';
                    //print_single_button('template.php', array('id'=>$cm->id, 'section'=>'templates', 'action'=>'add'), get_string('createnewtemplate', 'teamwork'));
                    echo '<img src="images/add.png" alt="'.get_string('createnewtemplate', 'teamwork').'" title="'.get_string('createnewtemplate', 'teamwork').'"/> <a href="template.php?id='.$cm->id.'&section=templates&action=add">'.get_string('createnewtemplate', 'teamwork').'</a> | ';
                    echo '<img src="images/page_white_get.png" alt="'.get_string('importtpl', 'teamwork').'" title="'.get_string('importtpl', 'teamwork').'"/> <a href="template.php?id='.$cm->id.'&section=templates&action=import">'.get_string('importtpl', 'teamwork').'</a> | ';
                    echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="view.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
                    echo '</div>';
                }
        }
    
    break;

    //gestion de las plantillas
    case 'templates':
        switch($action)
        {
            //muestra el formulario para añadir un template y en caso de POST lo guarda en la bbbdd
            case 'add':
                //cargamos el formulario
                $form = new teamwork_templates_form('template.php?id='.$cm->id.'&section=templates&action=add');

                //no se ha enviado, se muestra
                if(!$form->is_submitted())
                {
                    //$form->set_data(array('name'=>'mi nombre'));
                    $form->display();
                }
                //se ha enviado pero se ha cancelado, redirigir a página principal
                elseif($form->is_cancelled())
                {
                    redirect('template.php?id='.$cm->id);
                }
                //se ha enviado y no valida el formulario...
                elseif(!$form->is_validated())
                {
                    $form->display();
                }
                //se ha enviado y es válido, se procesa
                else
                {
                    //obtenemos los datos del formulario
                    $data = $form->get_data();
                    $data->courseid = $teamwork->course;
                    $data->teamworkid = $teamwork->id;

                    //insertamos los datos en la base de datos
                    $template_id = insert_record('teamwork_templates', $data);

                    //mostramos mensaje
                    echo '<p align="center">'.get_string('tpladded', 'teamwork').'</p>';
                    print_continue('template.php?id='.$cm->id.'&section=items&tplid='.$template_id);
                }
                
            break;

            //muestra el formulario para editar un template y en caso de POST actualiza la bbdd
            case 'modify':
                //se requiere el parámetro tplid con la id del template que estamos editando
                $tplid = required_param('tplid', PARAM_INT);
                
                //cargamos el formulario
                $form = new teamwork_templates_form('template.php?id='.$cm->id.'&section=templates&action=modify');

                //obtenemos los datos de la plantilla
                if(!$tpldata = get_record('teamwork_templates', 'id', $tplid))
                {
                    print_error('templatenotexist', 'teamwork');
                }

                $tpldata->tplid = $tplid;
                
                //no se ha enviado, se muestra
                if(!$form->is_submitted())
                {
                    $form->set_data($tpldata);
                    $form->display();
                }
                //se ha enviado pero se ha cancelado, redirigir a página principal
                elseif($form->is_cancelled())
                {
                    redirect('template.php?id='.$cm->id);
                }
                //se ha enviado y no valida el formulario...
                elseif(!$form->is_validated())
                {
                    $form->display();
                }
                //se ha enviado y es válido, se procesa
                else
                {
                    //obtenemos los datos del formulario
                    $data = $form->get_data();
                    $data->id = $data->tplid;

                    //actualizar los datos en la base de datos
                    update_record('teamwork_templates', $data);

                    //mostramos mensaje
                    echo '<p align="center">'.get_string('tplupdated', 'teamwork').'</p>';
                    print_continue('template.php?id='.$cm->id);
                }

            break;

            //muestra un aviso de que se va a borrar un template y sus items y pide confirmación de borrado
            case 'delete':
                //se requiere el parámetro tplid con la id del template que estamos editando
                $tplid = required_param('tplid', PARAM_INT);

                //previamente comprobar que una plantilla pueda ser borrada (porque ya cuente con valoraciones en algun teamwork)
                if(!teamwork_tpl_is_erasable($tplid))
                {
                    print_error('tplcannotbedeleted', 'teamwork', 'template.php?id='.$cm->id);
                }

                //si la plantilla puede ser borrada, pedir confirmación
                //si no ha sido enviada, mostrar la confirmacion
                if(!isset($_POST['tplid']))
                {
                    notice_yesno(get_string('confirmationfordeletetpl', 'teamwork'), 'template.php', 'template.php', array('id'=>$cm->id, 'section'=>'templates', 'action'=>'delete', 'tplid'=>$tplid), array('id'=>$cm->id), 'post', 'get');
                }
                //si se ha enviado, procesamos
                else
                {
                    //TODO implementar el borrado de las rubricas

                    //borrar items de la plantilla
                    delete_records('teamwork_items', 'templateid', $tplid);

                    //borrar plantilla en si
                    delete_records('teamwork_templates', 'id', $tplid);

                    //mostrar mensaje
                    echo '<p align="center">'.get_string('tpldeleted', 'teamwork').'</p>';
                    print_continue('template.php?id='.$cm->id);
                }

            break;

            //importa un xml de definición de plantilla al curso y teamwork actual
            case 'import':
                require_once($CFG->libdir.'/xmlize.php');
                $text = file_get_contents('./docs/prototype_template.xml');
                $content = xmlize($text);
                var_dump($content['template']['#']['items'][0]['#']['item'][0]['@']);

                
            break;

            //exporta una plantilla al formato xml
            case 'export':
                //TODO implementar la logica de control de que estemos accediendo a exportar templates que sean nuestros
                //borrar el contenido del buffer de salida
                ob_clean();

                //se requiere el parámetro tplid con la id del template que estamos editando
                $tplid = required_param('tplid', PARAM_INT);
                
                //TODO implementar cabeceras para forzar la descarga de un archivo http://javierav.com/articulos/php/2009-08-forzar-la-descarga-de-un-archivo-en-php
                //Cabeceras HTTP para forzar la descarga (varia según el navegador)
                if(isset($_SERVER['HTTP_USER_AGENT']) AND strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
                {
                   header('Content-Type: application/force-download');
                }
                else
                {
                   header('Content-Type: application/octet-stream');
                }

                //obtener los datos del template
                $tpldata = get_record('teamwork_templates', 'id', $tplid);

                //obtener los datos de los items asociados a este template
                $itemsdata = get_records('teamwork_items', 'templateid', $tplid);

                //nombre del archivo descargable
                header('Content-disposition: attachment; filename=' . teamwork_url_safe($tpldata->name) . '.xml');

                //contruir el arbol xml en un array
                
                //si no hay items asociados
                if($itemsdata === false)
                {
                    $xmlitems = '';
                }
                //si tiene elementos
                else
                {
                    //para cada item asociado...
                    foreach($itemsdata as $item)
                    {
                        $xmlitems[] =  array('item', array('order'=>$item->order, 'description'=>$item->description, 'scale'=>$item->scale, 'weight'=>$item->weight), '');
                    }
                }

                $xml = array('template', array('name'=>$tpldata->name, 'description'=>$tpldata->description), array('items', null, $xmlitems));

                echo teamwork_array2xml($xml);
                exit();
                
            break;

            //mensaje de error al no existir la acción especificada
            default:
                print_error('actionnotexist', 'teamwork');
        }
    break;

    //gestion de los elementos de las plantillas (items)
    case 'items':
        switch($action)
        {
            case 'add':

            break;

            //caso por defecto, mostrar la página principal de la gestión de items en una plantilla
            default:

                //se requiere el parámetro tplid con la id del template que estamos editando
                $tplid = required_param('tplid', PARAM_INT);

                //datos de la plantilla
                if(!$tpldata = get_record('teamwork_templates', 'id', $tplid))
                {
                    print_error('templatenotexist', 'teamwork');
                }

                //obtener la lista de items asignados a esta plantilla
                if(!$items = get_records('teamwork_items', 'templateid', $tplid))
                {
                    //si no hay, construir la tabla para mostrar mensaje de aviso
                    $table = new stdClass;
                    $table->head = array(get_string('itemslisting', 'teamwork', $tpldata->name));
                    $table->align = array('center');
                    $table->size = array('100%');
                    $table->data[] = array(get_string('noitemsforthistemplate', 'teamwork').'<br /><br />'.print_single_button('template.php', array('id'=>$cm->id, 'section'=>'items', 'action'=>'add'), get_string('addnewitem', 'teamwork'), 'get', '_self', true));
                    $table->width = '70%';
                    $table->tablealign = 'center';
                    $table->id = 'noitemsforthistemplatetable';

                    //imprimir la tabla
                    print_table($table);
                }
        }
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
