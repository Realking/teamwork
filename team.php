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

$navigation = build_navigation(get_string('teamseditor', 'teamwork'), $cm);
$pagetitle = strip_tags($course->shortname.': '.get_string('modulename', 'teamwork').': '.format_string($teamwork->name,true).': '.get_string('teamseditor', 'teamwork'));

print_header($pagetitle, $course->fullname, $navigation, '', '', true, '', navmenu($course, $cm));

echo '<div class="clearer"></div><br />';

//selección de la acción a realizar
switch($action)
{
    //muestra la lista de grupos actualmente existentes en esta actividad
    case 'list':

        //obtener la lista de grupos existente en este teamwork
        if(!$teams = get_records('teamwork_teams', 'teamworkid', $teamwork->id))
        {
            //no hay grupos definidos
            print_heading(get_string('definedteamlist', 'teamwork'));
            echo '<p align="center">'.get_string('notexistanyteam', 'teamwork').'</p>';

        }
        //tenemos grupos, mostramos la lista
        else
        {
            $table = new stdClass;
            $table->width = '70%';
            $table->tablealign = 'center';
            $table->id = 'teamstable';
            $table->head = array(get_string('teamname', 'teamwork'), get_string('teammembers', 'teamwork'), get_string('actions', 'teamwork'));
            $table->align = array('center', 'center', 'center');

            foreach($teams as $team)
            {
                $stractions = teamwork_group_table_options($team);
                $members = teamwork_get_team_members($team);

                $table->data[] = array($team->teamname, $members, $stractions);
            }

            //disponibles: imprimir la tabla y el boton de añadir
            print_heading(get_string('definedteamlist', 'teamwork'));
            print_table($table);
        }

        //imprimir opciones inferiores
        echo '<br /><div align="center"><br />';
        echo '<img src="images/add.png" alt="'.get_string('addnewteam', 'teamwork').'" title="'.get_string('addnewteam', 'teamwork').'"/> <a href="team.php?id='.$cm->id.'&action=addteam">'.get_string('addnewteam', 'teamwork').'</a> | ';
        echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="view.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
        echo '</div>';
    break;

    //añade un grupo vacio
    case 'addteam':
        //verificar que se pueda realmente editar este teamwork
        if(!teamwork_is_editable($teamwork))
        {
            print_error('teamworkisnoeditable', 'teamwork');
        }
        
        //cargamos el formulario
        $form = new teamwork_groups_form('team.php?id='.$cm->id.'&action=addteam');

        //no se ha enviado, se muestra
        if(!$form->is_submitted())
        {
            $form->display();
        }
        //se ha enviado pero se ha cancelado, redirigir a página principal
        elseif($form->is_cancelled())
        {
            redirect('team.php?id='.$cm->id, '', 0);
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
            echo '<p align="center">'.get_string('teamcreated', 'teamwork').'</p>';
            print_continue('team.php?id='.$cm->id.'&action=userlist&tid='.$tid);
        }

    break;

    //edita la información sobre un grupo ya creado (no edita los usuarios)
    case 'editteam':

        //verificar que se pueda realmente editar este teamwork
        if(!teamwork_is_editable($teamwork))
        {
            print_error('teamworkisnoeditable', 'teamwork');
        }

        //parametros requeridos
        $tid = required_param('tid', PARAM_INT);

        //cargamos el formulario
        $form = new teamwork_groups_form('team.php?id='.$cm->id.'&action=editteam');

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
            redirect('team.php?id='.$cm->id, '', 0);
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
            print_continue('team.php?id='.$cm->id);
        }
        
    break;

    //elimina un grupo existente
    case 'deleteteam':
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
            notice_yesno(get_string('confirmationfordeleteteam', 'teamwork'), 'team.php', 'team.php', array('id'=>$cm->id, 'action'=>'deleteteam', 'tid'=>$tid), array('id'=>$cm->id), 'post', 'get');
        }
        //si se ha enviado, procesamos
        else
        {
            //borrar items de la plantilla
            delete_records('teamwork_teams', 'id', $tid);

            //borrar lista de asociaciones usuario-equipo
            delete_records('teamwork_users_teams', 'teamid', $tid);

            //mostrar mensaje
            echo '<p align="center">'.get_string('teamdeleted', 'teamwork').'</p>';
            print_continue('team.php?id='.$cm->id);
        }
        
    break;

    //muestra la lista de usuarios asignados al equipo
    case 'userlist':
        //parametros requeridos
        $tid = required_param('tid', PARAM_INT);

        $team = get_record('teamwork_teams', 'id', $tid);

        //obtener la lista de componentes del grupo
        if(!$members = get_records_sql('select u.id, u.firstname, u.lastname from '.$CFG->prefix.'user u, '.$CFG->prefix.'teamwork_users_teams t where t.teamid = '.$tid.' and u.id = t.userid'))
        {
            //no hay grupos definidos
            print_heading(get_string('teammembers', 'teamwork', $team->teamname));
            echo '<p align="center">'.get_string('donothaveanyuserinthisteam', 'teamwork').'</p>';

        }
        //tenemos grupos, mostramos la lista
        else
        {
            $table = new stdClass;
            $table->width = '40%';
            $table->tablealign = 'center';
            $table->id = 'usersteamstable';
            $table->head = array(get_string('studentname', 'teamwork'), get_string('actions', 'teamwork'));
            $table->align = array('center', 'center');
            $table->size = array('90%', '10%');

            foreach($members as $member)
            {
                $stractions = teamwork_usersteams_table_options($member, $tid, $team);
                $name = '<a href="../../user/view.php?id='.$member->id.'&course='.$course->id.'" target="_blank">'.$member->firstname.' '.$member->lastname.'</a>';

                //si es el lider del equipo
                if($member->id == $team->teamleader)
                {
                    $name .= '&nbsp;&nbsp;<img src="images/leader.png" alt="'.get_string('thisuserisleader', 'teamwork').'" title="'.get_string('thisuserisleader', 'teamwork').'" />';
                }
                
                $table->data[] = array($name, $stractions);
            }

            //disponibles: imprimir la tabla y el boton de añadir
            print_heading(get_string('teammembers', 'teamwork', $team->teamname));
            print_table($table);
        }
        
        //imprimir opciones inferiores
        echo '<br /><div align="center"><br />';
        if(teamwork_is_editable($teamwork))
        {
            echo '<img src="images/add.png" alt="'.get_string('addnewusers', 'teamwork').'" title="'.get_string('addnewusers', 'teamwork').'"/> <a href="team.php?id='.$cm->id.'&action=adduser&tid='.$tid.'">'.get_string('addnewusers', 'teamwork').'</a> | ';
        }
        echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="team.php?id='.$cm->id.'">'.get_string('goback', 'teamwork').'</a>';
        echo '</div>';
    break;

    //asigna un usuario a un grupo
    //TODO si es el primer usuario del grupo, hacerlo lider del mismo
    case 'adduser':
        //verificar que se pueda realmente editar este teamwork
        if(!teamwork_is_editable($teamwork))
        {
            print_error('teamworkisnoeditable', 'teamwork');
        }
        
        //parametros requeridos
        $tid = required_param('tid', PARAM_INT);

        //si se envia la lista de los miembros a incorporar...
        if(isset($_POST['submit']))
        {
            //usuarios a añadir
            $selection = optional_param('selection', array());

            //cargar la lista de alumnos de un curso para comprobar si los alumnos a insertar son de este curso
            $students = get_course_students($course->id, 'u.lastname ASC', '', '', '', '', '', null, '', 'u.id');

            if($students !== false)
            {
                foreach($students as $item)
                {
                    $aux[] = $item->id;
                }

                $students = $aux;
                unset($aux);
            }
            else
            {
                $students = array();
            }

            //obtener una lista de los alumnos del curso que se encuentran asignados a algun grupo, que es lo mismo que
            //obtener la lista de grupos de la actividad y sus alumnos asociados
            $students_in_groups = get_records_sql('select ut.userid from '.$CFG->prefix.'teamwork_teams t, '.$CFG->prefix.'teamwork_users_teams ut
                                                   where t.teamworkid = '.$teamwork->id.' and ut.teamid = t.id');

            if( $students_in_groups !== false)
            {
                foreach( $students_in_groups as $item)
                {
                    $aux[] = $item->userid;
                }

                 $students_in_groups = $aux;
                unset($aux);
            }
            else
            {
                 $students_in_groups = array();
            }

            foreach($selection as $user)
            {
                //si el estudiante pertenece a este curso Y no se encuentra en algun grupo
                if(in_array($user, $students) AND !in_array($user, $students_in_groups))
                {
                    //insertamos el usuario en el equipo
                    $data = new stdClass;
                    $data->userid = $user;
                    $data->teamid = $tid;
                    insert_record('teamwork_users_teams', $data);
                }
            }

            //mostrar mensaje
            echo '<p align="center">'.get_string('usersaddedok', 'teamwork').'</p>';
            print_continue('team.php?id='.$cm->id.'&action=userlist&tid='.$tid);

        }
        //si no, mostramos la lista de los alumnos del curso
        else
        {
            //cargar la lista de miembros del equipo para eliminarlos de los disponibles
            $current_members = get_records('teamwork_users_teams', 'teamid', $tid);

            if($current_members !== false)
            {
                foreach($current_members as $item)
                {
                    $aux[] = $item->userid;
                }

                $current_members = $aux;
                unset($aux);
            }
            else
            {
                $current_members = array();
            }

            //cargar la lista de alumnos de un curso
            $students = get_course_students($course->id, 'u.lastname ASC', '', '', '', '', '', null, '', 'u.id, firstname, lastname, picture, imagealt');

            //obtener una lista de los alumnos del curso que se encuentran asignados a algun grupo, que es lo mismo que
            //obtener la lista de grupos de la actividad y sus alumnos asociados
            $students_in_groups = get_records_sql('select ut.userid from '.$CFG->prefix.'teamwork_teams t, '.$CFG->prefix.'teamwork_users_teams ut
                                                   where t.teamworkid = '.$teamwork->id.' and ut.teamid = t.id');
            
            if( $students_in_groups !== false)
            {
                foreach( $students_in_groups as $item)
                {
                    $aux[] = $item->userid;
                }

                 $students_in_groups = $aux;
                unset($aux);
            }
            else
            {
                 $students_in_groups = array();
            }

            //titulo
            $team = get_record('teamwork_teams', 'id', $tid);
            print_heading(get_string('teammembers', 'teamwork', $team->teamname));

            //menu de ordenacion alfabetica
            $base_url = $CFG->wwwroot.'/mod/teamwork/team.php?id='.$cm->id.'&action=adduser&tid='.$tid;
            $sfirst = optional_param('sfirst', null);
            $slast = optional_param('slast', null);

            echo '<p align="center">';
            $base_url_mod = ($slast !== null) ? $base_url . '&slast='.$slast : $base_url;
            echo get_string('name') . ': ' . teamwork_alphabetical_list($base_url_mod, 'sfirst');
            echo '<br />';
            $base_url_mod = ($sfirst !== null) ? $base_url . '&sfirst='.$sfirst : $base_url;
            echo get_string('lastname') . ': ' . teamwork_alphabetical_list($base_url_mod, 'slast');
            echo '</p>';
            
            //inicio del formulario
            echo '<form method="post" action="'.$base_url.'">';

            //cabecera de la tabla
            echo '<table width="40%" cellspacing="1" cellpadding="5" id="userstable" class="generaltable boxaligncenter">';
            echo '<tbody><tr>';
            echo '<th scope="col" class="header c0" style="vertical-align: top; text-align: center; width: 10%; white-space: nowrap;"/>';
            echo '<th scope="col" class="header c1" style="vertical-align: top; text-align: center; width: 80%; white-space: nowrap;">'.get_string('name').' / '.get_string('lastname').'</th>';
            echo '<th scope="col" class="header c2 lastcol" style="vertical-align: top; text-align: center; width: 10%; white-space: nowrap;">'.get_string('actions', 'teamwork').'</th>';
            echo '</tr>';

            foreach($students as $student)
            {
                $name = '<a href="../../user/view.php?id='.$student->id.'&course='.$course->id.'" target="_blank">'.$student->firstname.' '.$student->lastname.'</a>';
                $css = '';

                //si el usuario existe en el grupo
                if(in_array($student->id, $current_members))
                {
                    //el alumno ya esta en el grupo, marcamos el fondo de verde
                    $css = ' teamwork_highlight_green';
                }
                elseif(in_array($student->id, $students_in_groups))
                {
                    //el alumno ya pertenece a algun grupo de esta actividad, lo marcamos de rojo
                    $css = ' teamwork_highlight_red';
                }

                echo '<tr class="r0">';
                    echo '<td class="cell c0'.$css.'" style="text-align: center; width: 10%;">';
                        echo print_user_picture($student, $course->id, null, 0, true);
                    echo '</td>';
                    echo '<td class="cell c1'.$css.'" style="text-align: center; width: 80%;">';
                        echo $name;
                    echo '</td>';
                    echo '<td class="cell c2 lastcol'.$css.'" style="text-align: center; width: 10%;">';
                    //si css esta vacio (no pertenece a ningun grupo), mostramos el marcador para seleccionar el usuario
                    if(empty($css))
                    {
                        echo '<input type="checkbox" name="selection[]" value="'.$student->id.'" />';
                    }
                    echo '</td>';
		echo '</tr>';
            }
            
            //pie de la tabla
            echo '</tbody></table>';

            //fin de formulario
            echo '<p align="center"><input type="submit" name="submit" value="'.get_string('addnewusers', 'teamwork').'" /></p>';
            echo '</form>';

            //leyenda
            $var = new stdClass();
            $var->red = '<span class="teamwork_highlight_red">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
            $var->green = '<span class="teamwork_highlight_green">&nbsp;&nbsp;&nbsp;&nbsp;</span>';
            echo '<br /><p align="center">'.get_string('asignusertogroupleyend', 'teamwork', $var).'</p>';

            //imprimir opciones inferiores
            echo '<br /><div align="center">';
            echo '<img src="images/arrow_undo.png" alt="'.get_string('goback', 'teamwork').'" title="'.get_string('goback', 'teamwork').'"/> <a href="team.php?id='.$cm->id.'&action=userlist&tid='.$tid.'">'.get_string('goback', 'teamwork').'</a>';
            echo '</div>';
        }

        
    break;

    //muestra la lista de miembros del grupo y elimina el especificado
    //TODO si el usuario que se elimina es el lider, pasar el cargo a otro y si no hay nadie a null
    case 'deleteuser':
        //verificar que se pueda realmente editar este teamwork
        if(!teamwork_is_editable($teamwork))
        {
            print_error('teamworkisnoeditable', 'teamwork');
        }

        //parametros requeridos
        $tid = required_param('tid', PARAM_INT);
        $uid = required_param('uid', PARAM_INT);

        //si el grupo puede ser borrado, pedir confirmación
        //si no ha sido enviada, mostrar la confirmacion
        if(!isset($_POST['tid']))
        {
            notice_yesno(get_string('confirmationfordeleteuserfromteam', 'teamwork'), 'team.php', 'team.php', array('id'=>$cm->id, 'action'=>'deleteuser', 'tid'=>$tid, 'uid'=>$uid), array('id'=>$cm->id, 'action'=>'userlist', 'tid'=>$tid), 'post', 'get');
        }
        //si se ha enviado, procesamos
        else
        {
            //borrar alumno del equipo
            delete_records('teamwork_users_teams', 'userid', $uid, 'teamid', $tid);

            //mostrar mensaje
            echo '<p align="center">'.get_string('userdeletedfromteam', 'teamwork').'</p>';
            print_continue('team.php?id='.$cm->id.'&action=userlist&tid='.$tid);
        }

    break;

    //establece un nuevo lider en el grupo
    case 'setleader':
        //verificar que se pueda realmente editar este teamwork
        if(!teamwork_is_editable($teamwork))
        {
            print_error('teamworkisnoeditable', 'teamwork');
        }

        //parametros requeridos
        $tid = required_param('tid', PARAM_INT);
        $uid = required_param('uid', PARAM_INT);

        //verificar que el usuario pertenezca al grupo
        if(!count_records('teamwork_users_teams', 'userid', $uid, 'teamid', $tid))
        {
            print_error('thisusernotisinthisgroup', 'teamwork');
        }

        //verificar que el equipo pertenezca a este teamwork
        if(!count_records('teamwork_teams', 'id', $tid, 'teamworkid', $teamwork->id))
        {
            print_error('thisteamnotisinthisactivity', 'teamwork');
        }

        //establecer el nuevo lider
        $data = new stdClass;
        $data->id = $tid;
        $data->teamleader = $uid;

        update_record('teamwork_teams', $data);

        //mostrar mensaje
        echo '<p align="center">'.get_string('leaderseterok', 'teamwork').'</p>';
        print_continue('team.php?id='.$cm->id.'&action=userlist&tid='.$tid);

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
