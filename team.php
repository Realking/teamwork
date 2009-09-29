<?php
/**
 * Página de configuración de grupos de trabajo de la actividad
 *
 * Esta pagina visible sólo por el profesor permite crear, editar y borrar los grupos
 * de alumnos para esta actividad
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

//la accion a realizar
$action =  optional_param('action', 'list', PARAM_ALPHA);


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

$navigation = build_navigation(get_string('groupseditor', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('groupseditor', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, '', navmenu($course, $cm));

echo '<div class="clearer"></div><br />';

//selección de la acción a realizar
switch($action)
{
    //muestra la lista de grupos actualmente existentes en esta actividad
    case 'list':
        
        //imprimir opciones inferiores
        echo '<br /><div align="center"><br />';
        echo '<img src="images/add.png" alt="'.get_string('addnewgroup', 'teamwork').'" title="'.get_string('addnewgroup', 'teamwork').'"/> <a href="group.php?id='.$cm->id.'&action=groupadd">'.get_string('addnewgroup', 'teamwork').'</a> | ';
        echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="view.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
        echo '</div>';
    break;

    //añade un grupo vacio
    case 'groupadd':
        //verificar que se pueda realmente editar este teamwork
        if(!teamwork_is_editable($teamwork))
        {
            print_error('teamworkisnoeditable', 'teamwork');
        }
        
        //cargamos el formulario
        $form = new teamwork_groups_form('group.php?id='.$cm->id.'&action=groupadd');

        //no se ha enviado, se muestra
        if(!$form->is_submitted())
        {
            $form->display();
        }
        //se ha enviado pero se ha cancelado, redirigir a página principal
        elseif($form->is_cancelled())
        {
            redirect('group.php?id='.$cm->id, '', 0);
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
            $data->teamworkid = $teamwork->id;
            $data->teamname = $formdata->teamname;

            //insertar los datos
            $tid = insert_record('teamwork_teams', $data);

            //mostramos mensaje
            echo '<p align="center">'.get_string('groupcreated', 'teamwork').'</p>';
            print_continue('group.php?id='.$cm->id.'&action=useradd&tid='.$tid);
        }

    break;

    //edita la información sobre un grupo ya creado (no edita los usuarios)
    case 'groupedit':

        //verificar que se pueda realmente editar este teamwork
        if(!teamwork_is_editable($teamwork))
        {
            print_error('teamworkisnoeditable', 'teamwork');
        }

        //parametros requeridos
        $tid = required_param('tid', PARAM_INT);

        //cargamos el formulario
        $form = new teamwork_groups_form('group.php?id='.$cm->id.'&action=groupedit');

        //no se ha enviado, se muestra
        if(!$form->is_submitted())
        {
            //obtenemos los datos del elemento
            if(!$teamdata = get_record('teamwork_teams', 'id', $tid))
            {
                print_error('teamnotexist', 'teamwork');
            }

            $teamdata->tid = $tid;

            $form->set_data($teamdata);
            $form->display();
        }
        //se ha enviado pero se ha cancelado, redirigir a página principal
        elseif($form->is_cancelled())
        {
            redirect('group.php?id='.$cm->id, '', 0);
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
            $data->id = $tid;
            $data->teamname = $formdata->teamname;

            //actualizar los datos en la base de datos
            update_record('teamwork_teams', $data);

            //mostramos mensaje
            echo '<p align="center">'.get_string('teamupdated', 'teamwork').'</p>';
            print_continue('group.php?id='.$cm->id);
        }
        
    break;

    //elimina un grupo existente
    case 'groupdelete':
        //verificar que se pueda realmente editar este teamwork
        if(!teamwork_is_editable($teamwork))
        {
            print_error('teamworkisnoeditable', 'teamwork');
        }

        //parametros requeridos
        $tid = required_param('tid', PARAM_INT);

        //si el grupo puede ser borrado, pedir confirmación
        //si no ha sido enviada, mostrar la confirmacion
        if(!isset($_POST['tid']))
        {
            notice_yesno(get_string('confirmationfordeletegroup', 'teamwork'), 'group.php', 'group.php', array('id'=>$cm->id, 'action'=>'groupdelete', 'tid'=>$tid), array('id'=>$cm->id), 'post', 'get');
        }
        //si se ha enviado, procesamos
        else
        {
            //borrar items de la plantilla
            delete_records('teamwork_teams', 'id', $tid);

            //mostrar mensaje
            echo '<p align="center">'.get_string('teamdeleted', 'teamwork').'</p>';
            print_continue('group.php?id='.$cm->id);
        }
        
    break;

    //muestra la lista de usuarios disponibles y le asigna el especificado al grupo
    case 'useradd':

    break;

    //muestra la lista de miembros del grupo y elimina el especificado
    case 'userdelete':

    break;

    //establece un nuevo lider en el grupo
    case 'leaderset':

    break;

    //mensaje de error al no existir la acción especificada
    default:
        print_error('actionnotexist', 'teamwork');
}

//
/// footer
//

print_footer($course);
?>
