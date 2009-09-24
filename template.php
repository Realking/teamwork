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
            case 'add':
                //es requerido la id de la plantilla la cual asignar
                $tplid = required_param('tplid', PARAM_INT);

                //es requerido el tipo de asignación
                $type = required_param('type', PARAM_ALPHA);

                //verificar que la actividad no cuente ya con una plantilla para ese tipo
                if(teamwork_check_tpl_type($type, $teamwork->id))
                {
                    print_error('asignationforthisevaltypeexist', 'teamwork');
                }
                
                //verificar que el tipo de evaluacion es uno de los validos
                if($type != 'team' AND $type != 'user')
                {
                    print_error('evaltypenotexist', 'teamwork');
                }

                //realizar la asignacion
                $data = new stdClass;
                $data->templateid = $tplid;
                $data->teamworkid = $teamwork->id;
                $data->evaltype = $type;
                insert_record('teamwork_tplinstances', $data);

                redirect('template.php?id='.$cm->id);

            break;

            //elimina la asignación de una plantilla a una instancia
            case 'delete':

                //es requerido la id de la instancia del template a eliminar
                $instid = required_param('instid', PARAM_INT);

                $result = get_record('teamwork_tplinstances', 'id', $instid, 'teamworkid', $teamwork->id);

                //verificar que esa plantilla se encontraba previamente asignada a esta instancia de la actividad
                if(!count($result))
                {
                    print_error('youdonthavepermissionfordeletethisasignation', 'teamwork');
                }
                
                //verificar que podemos editar el template (señal de que no hay aportaciones de los usuarios
                if(!teamwork_tpl_is_editable($result->templateid))
                {
                    print_error('youdonthavepermissionfordeletethisasignation', 'teamwork');
                }

                //eliminar la asignación
                delete_records('teamwork_tplinstances', 'id', $instid);

                redirect('template.php?id='.$cm->id);

            break;

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
                        $stractions = teamwork_tpl_table_options($tpl, $cm);

                        $table->data[] = array($tpl->name, $tpl->description, teamwork_get_items_by_template($tpl->id), teamwork_get_instances_of_tpl($tpl->id), $stractions);
                    }

                    //disponibles: imprimir la tabla y el boton de añadir
                    print_heading(get_string('coursetemplateslisting', 'teamwork'));
                    print_table($table);

                    //mostrar la tabla con la lista de plantillas asignadas
                    echo '<br />';
                    print_heading(get_string('coursetemplatesasignedlisting', 'teamwork'));

                    if(!$asignedtpls = get_records_sql('select t.id, t.name, i.evaltype, i.id from '.$CFG->prefix.'teamwork_tplinstances i, '.$CFG->prefix.'teamwork_templates t where t.id = i.templateid and i.teamworkid = '.$teamwork->id))
                    {
                        //no hay ninguna plantilla asignada
                        echo '<p align="center">'.get_string('notemplateasigned', 'teamwork').'</p>';
                    }
                    else
                    {
                        //mostramos la tabla con las plantillas asignadas (nombre, tipo, acciones)
                        $table = new stdClass;
                        $table->width = '40%';
                        $table->tablealign = 'center';
                        $table->id = 'templatesasignedtable';
                        $table->head = array(get_string('name', 'teamwork'), get_string('evaltype', 'teamwork'), get_string('actions', 'teamwork'));
                        $table->align = array('center', 'center', 'center', 'center', 'center');

                        foreach($asignedtpls as $tpl)
                        {
                            if($tpl->evaltype == 'team')
                            {
                                $evaltype = get_string('evalteam','teamwork');
                                $stractions = '<a href="template.php?id='.$cm->id.'&section=instances&action=delete&instid='.$tpl->id.'"><img src="images/group_delete.png" alt="'.get_string('notusetemplateforgroupeval', 'teamwork').'" title="'.get_string('notusetemplateforgroupeval', 'teamwork').'" /></a>';

                            }
                            else
                            {
                                $evaltype = get_string('evaluser','teamwork');
                                $stractions = '<a href="template.php?id='.$cm->id.'&section=instances&action=delete&instid='.$tpl->id.'"><img src="images/user_delete.png" alt="'.get_string('notusetemplateforusereval', 'teamwork').'" title="'.get_string('notusetemplateforusereval', 'teamwork').'" /></a>';
                            }


                            $table->data[] = array($tpl->name, $evaltype, $stractions);
                        }

                        print_table($table);
                    }
                }

                //imprimir opciones inferiores
                echo '<br /><div align="center"><br />';
                echo '<img src="images/add.png" alt="'.get_string('createnewtemplate', 'teamwork').'" title="'.get_string('createnewtemplate', 'teamwork').'"/> <a href="template.php?id='.$cm->id.'&section=templates&action=add">'.get_string('createnewtemplate', 'teamwork').'</a> | ';
                echo '<img src="images/page_white_get.png" alt="'.get_string('importtpl', 'teamwork').'" title="'.get_string('importtpl', 'teamwork').'"/> <a href="template.php?id='.$cm->id.'&section=templates&action=import">'.get_string('importtpl', 'teamwork').'</a> | ';
                echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="view.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
                echo '</div>';
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

                //si no se ha enviado el archivo
                if(!isset($_POST['save']))
                {
                    print_heading_with_help(get_string('importtemplate', 'teamwork'), 'importtemplate', 'teamwork');

                    //imprimir formulario de envío de archivos
                    global $CFG;
                    $struploadafile = get_string("uploadafile");
                    $strmaxsize = get_string('maxsize', '', display_size($course->maxbytes));
                    echo '<div style="text-align:center">';
                    echo '<form enctype="multipart/form-data" method="post" action="template.php">';
                    echo '<fieldset class="invisiblefieldset">';
                    echo "<p>$struploadafile ($strmaxsize)</p>";
                    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
                    echo '<input type="hidden" name="section" value="templates" />';
                    echo '<input type="hidden" name="action" value="import" />';
                    upload_print_form_fragment(1,array('importfile'),false,null,0,0,false);
                    echo '<input type="submit" name="save" value="'.get_string('uploadthisfile').'" />';
                    echo '</fieldset>';
                    echo '</form>';
                    echo '</div>';
                }
                //si se ha enviado
                else
                {
                    //parsear el xml
                    require_once($CFG->libdir.'/xmlize.php');
                    $text = file_get_contents($_FILES['importfile']['tmp_name']);
                    $content = xmlize($text);

                    //obtener los datos de la plantilla
                    $tpldata = new stdClass;
                    $tpldata->name = $content['template']['@']['name'] . '(imp)';
                    $tpldata->description = $content['template']['@']['description'];
                    $tpldata->courseid = $course->id;
                    $tpldata->teamworkid = $teamwork->id;

                    //insertar la plantilla en la base de datos
                    $newtplid = insert_record('teamwork_templates', $tpldata);

                    //obtener los datos de los elementos de la plantilla
                    $itemdata = new stdClass;
                    $itemdata->templateid = $newtplid;

                    $scale_error = false;

                    foreach($content['template']['#']['items'][0]['#']['item'] as $item)
                    {
                        $itemdata->itemorder = $item['@']['order'];
                        $itemdata->description = $item['@']['description'];

                        //comprobar que la escala existe y la tenemos disponible en esta actividad
                        $status = teamwork_check_scale(abs($item['@']['scale']));
                        $itemdata->scale = ($item['@']['scale'] >= 0 or $status) ? $item['@']['scale'] : 0;
                        $scale_error = (!$status) ? true : $scale_error;
                        
                        $itemdata->weight = $item['@']['weight'];

                        //insertar el elemento en la base de datos
                        insert_record('teamwork_items', $itemdata);
                    }
                    
                    //TODO implementar la importación de las rubricas

                    //mostrar mensaje
                    echo '<p align="center">'.get_string('templateimportok', 'teamwork').'</p>';
                    if($scale_error){ notify(get_string('importscaleerror', 'teamwork')); }
                    print_continue('template.php?id='.$cm->id);
                }
                
            break;

            //exporta una plantilla al formato xml
            case 'export':
                //borrar el contenido del buffer de salida
                ob_clean();

                //se requiere el parámetro tplid con la id del template que estamos editando
                $tplid = required_param('tplid', PARAM_INT);
                
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

                //verificar que la plantilla que intentamos exportar sea de esta actividad
                if($tpldata->teamworkid != $teamwork->id)
                {
                    print_error('youdonthavepermissiontoexportthistemplate','teamwork');
                }

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
                        $xmlitems[] =  array('item', array('order'=>$item->itemorder, 'description'=>$item->description, 'scale'=>$item->scale, 'weight'=>$item->weight), '');
                    }
                }

                $xml = array('template', array('name'=>$tpldata->name, 'description'=>$tpldata->description), array('items', null, $xmlitems));

                //TODO implementar la exportación de rubricas

                echo teamwork_array2xml($xml);
                exit();
                
            break;

            //crea una plantilla nueva a partir de la que se especifica
            case 'copy':

                //se requiere el parámetro tplid con la id del template original
                $tplid = required_param('tplid', PARAM_INT);

                //comprobar que esa plantilla pertenece a este curso y de paso obtenemos los datos de la plantilla original
                if(!$tpldata = get_record('teamwork_templates', 'id', $tplid, 'courseid', $cm->course))
                {
                    print_error('youdonthavepermissiontocopy', 'teamwork');
                }

                unset($tpldata->id);
                $tpldata->name .= ' (bis)';

                //insertar los datos de la nueva plantilla
                $newtplid = insert_record('teamwork_templates', $tpldata);

                //obtenemos los datos de los elementos de esa plantilla
                $itemdata = get_records('teamwork_items', 'templateid', $tplid);

                foreach($itemdata as $item)
                {
                    unset($item->id);
                    $item->templateid = $newtplid;

                    insert_record('teamwork_items', $item);
                }

                //TODO implementar el copiado de las rubricas

                //mostrar mensaje
                echo '<p align="center">'.get_string('tplcopiedok', 'teamwork').'</p>';
                print_continue('template.php?id='.$cm->id);

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
            //añade un nuevo elemento (item o criterio de evaluación) a la plantilla
            case 'add':

                //se requiere el parámetro tplid con la id del template que estamos editando
                $tplid = required_param('tplid', PARAM_INT);

                //cargamos el formulario
                $form = new teamwork_items_form('template.php?id='.$cm->id.'&section=items&action=add');

                //no se ha enviado, se muestra
                if(!$form->is_submitted())
                {
                    $form->set_data(array('tplid'=>$tplid));
                    $form->display();
                }
                //se ha enviado pero se ha cancelado, redirigir a página principal
                elseif($form->is_cancelled())
                {
                    redirect('template.php?id='.$cm->id.'&section=items&tplid='.$tplid);
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
                    $formdata = $form->get_data();
                    unset($formdata->itemid);

                    $data = new StdClass;
                    $data->description = $formdata->description;
                    $data->scale = $formdata->scale;
                    $data->weight = $formdata->weight;
                    $data->templateid = $formdata->tplid;

                    //obtener el numero de items que ya contiene esta plantilla para calcular el orden
                    $data->itemorder = count_records('teamwork_items', 'templateid', $formdata->tplid) + 1;

                    //insertamos los datos en la base de datos
                    insert_record('teamwork_items', $data);

                    //mostramos mensaje
                    echo '<p align="center">'.get_string('itemadded', 'teamwork').'</p>';
                    print_continue('template.php?id='.$cm->id.'&section=items&tplid='.$tplid);
                }

            break;

            //modifica un elemento de una plantilla (siempre que se pueda)
            case 'modify':

                //se requiere el parámetro tplid con la id del template que estamos editando
                $tplid = required_param('tplid', PARAM_INT);

                //verificar que se pueda realmente editar este elemento
                if(!teamwork_tpl_is_editable($tplid))
                {
                    print_error('itemnoteditable', 'teamwork');
                }

                //se requiere el parámetro itemid con la id del elemento que estamos editando
                $itemid = required_param('itemid', PARAM_INT);

                //cargamos el formulario
                $form = new teamwork_items_form('template.php?id='.$cm->id.'&section=items&action=modify');

                //no se ha enviado, se muestra
                if(!$form->is_submitted())
                {
                    //obtenemos los datos del elemento
                    if(!$itemdata = get_record('teamwork_items', 'id', $itemid))
                    {
                        print_error('itemnotexist', 'teamwork');
                    }

                    $itemdata->tplid = $tplid;
                    $itemdata->itemid = $itemid;

                    $form->set_data($itemdata);
                    $form->display();
                }
                //se ha enviado pero se ha cancelado, redirigir a página principal
                elseif($form->is_cancelled())
                {
                    redirect('template.php?id='.$cm->id.'&section=items&tplid='.$tplid);
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
                    $formdata = $form->get_data();
                    
                    $data = new stdClass;
                    $data->id = $formdata->itemid;
                    $data->description = $formdata->description;
                    $data->scale = $formdata->scale;
                    $data->weight = $formdata->weight;

                    //actualizar los datos en la base de datos
                    update_record('teamwork_items', $data);

                    //mostramos mensaje
                    echo '<p align="center">'.get_string('itemupdated', 'teamwork').'</p>';
                    print_continue('template.php?id='.$cm->id.'&section=items&tplid='.$tplid);
                }

            break;

            //elimina un elemento de una plantilla (siempre que se pueda)
            case 'delete':

                //se requiere el parámetro tplid con la id del template que estamos editando
                $tplid = required_param('tplid', PARAM_INT);

                //verificar que se pueda realmente editar este elemento
                if(!teamwork_tpl_is_editable($tplid))
                {
                    print_error('itemnoteditable', 'teamwork');
                }

                //se requiere el parámetro itemid con la id del elemento que estamos editando
                $itemid = required_param('itemid', PARAM_INT);

                //si la plantilla puede ser borrada, pedir confirmación
                //si no ha sido enviada, mostrar la confirmacion
                if(!isset($_POST['itemid']))
                {
                    notice_yesno(get_string('confirmationfordeleteitem', 'teamwork'), 'template.php', 'template.php', array('id'=>$cm->id, 'section'=>'items', 'action'=>'delete', 'itemid'=>$itemid, 'tplid'=>$tplid), array('id'=>$cm->id, 'section'=>'items', 'tplid'=>$tplid), 'post', 'get');
                }
                //si se ha enviado, procesamos
                else
                {
                    //TODO implementar el borrado de las rubricas

                    //borrar items de la plantilla
                    delete_records('teamwork_items', 'id', $itemid);

                    //mostrar mensaje
                    echo '<p align="center">'.get_string('itemdeleted', 'teamwork').'</p>';
                    print_continue('template.php?id='.$cm->id.'&section=items&tplid='.$tplid);
                }
                
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
                    $table->data[] = array(get_string('noitemsforthistemplate', 'teamwork').'<br /><br />'.print_single_button('template.php', array('id'=>$cm->id, 'section'=>'items', 'action'=>'add', 'tplid'=>$tplid), get_string('addnewitem', 'teamwork'), 'get', '_self', true));
                    $table->width = '70%';
                    $table->tablealign = 'center';
                    $table->id = 'noitemsforthistemplatetable';

                    //imprimir la tabla
                    print_table($table);

                    //imprimir opciones inferiores
                    echo '<br /><div align="center"><br />';
                    echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="template.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
                    echo '</div>';
                }
                //si hay elementos que listar
                else
                {
                    $table = new stdClass;
                    $table->width = '70%';
                    $table->tablealign = 'center';
                    $table->id = 'itemstable';
                    $table->head = array(get_string('evalcriteria', 'teamwork'), get_string('grade'), get_string('elementweight', 'teamwork'), get_string('actions', 'teamwork'));
                    //$table->size = array('10%', '90%');
                    $table->align = array('center', 'center', 'center', 'center');

                    foreach($items as $item)
                    {
                        $stractions = teamwork_item_table_options($item, $cm, $tpldata);

                        $scale = ($item->scale < 0) ? get_record('scale', 'id', abs($item->scale))->name : $item->scale;

                        $table->data[] = array($item->description, $scale, $item->weight, $stractions);
                    }

                    //disponibles: imprimir la tabla y el boton de añadir
                    print_heading(get_string('itemslisting', 'teamwork', $tpldata->name));
                    print_table($table);

                    //imprimir opciones inferiores
                    echo '<br /><div align="center"><br />';
                    echo '<img src="images/add.png" alt="'.get_string('addnewitem', 'teamwork').'" title="'.get_string('addnewitem', 'teamwork').'"/> <a href="template.php?id='.$cm->id.'&section=items&action=add&tplid='.$tplid.'">'.get_string('addnewitem', 'teamwork').'</a> | ';
                    echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="template.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
                    echo '</div>';
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