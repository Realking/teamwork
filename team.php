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

//iniciamos el bufer de salida (es posible que tengamos que modificar las cabeceras http y si imprimimos aqui algo no podremos hacerlo)
ob_start();

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
        //solo si no hay ningún equipo creado se puede usar el generador de equipos
        if($teams === false)
        {
            echo '<img src="images/asterisk.png" alt="'.get_string('teamgenerator', 'teamwork').'" title="'.get_string('teamgenerator', 'teamwork').'"/> <a href="team.php?id='.$cm->id.'&action=teamgenerator">'.get_string('teamgenerator', 'teamwork').'</a> | ';
        }
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
            header('Location: team.php?id='.$cm->id);
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

            //redireccionamos a la lista de miembros de un equipo
            header('Location: team.php?id='.$cm->id.'&action=userlist&tid='.$tid);
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
            header('Location: team.php?id='.$cm->id);
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
            header('Location: team.php?id='.$cm->id);
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
            header('Location: team.php?id='.$cm->id);
        }
        
    break;

    //muestra la lista de usuarios asignados al equipo
    case 'userlist':
        //parametros requeridos
        $tid = required_param('tid', PARAM_INT);

        $team = get_record('teamwork_teams', 'id', $tid);

        //obtener la lista de componentes del grupo
        if(!$members = get_records_sql('select u.id, u.firstname, u.lastname, u.picture, u.imagealt from '.$CFG->prefix.'user u, '.$CFG->prefix.'teamwork_users_teams t where t.teamid = '.$tid.' and u.id = t.userid order by u.lastname asc'))
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
            $table->head = array('', get_string('studentname', 'teamwork'), get_string('actions', 'teamwork'));
            $table->align = array('center','center', 'center');
            $table->size = array('10%','80%', '10%');

            foreach($members as $member)
            {
                $stractions = teamwork_usersteams_table_options($member, $tid, $team);
                $name = '<a href="../../user/view.php?id='.$member->id.'&course='.$course->id.'" target="_blank">'.$member->firstname.' '.$member->lastname.'</a>';

                //si es el lider del equipo
                if($member->id == $team->teamleader)
                {
                    $name .= '&nbsp;&nbsp;<img src="images/leader.png" alt="'.get_string('thisuserisleader', 'teamwork').'" title="'.get_string('thisuserisleader', 'teamwork').'" />';
                }
                
                $table->data[] = array(print_user_picture($member, $course->id, null, 0, true),$name, $stractions);
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

            $count_members = count_records('teamwork_users_teams', 'teamid', $tid);

            $data = new stdClass;

            foreach($selection as $user)
            {
                //si el estudiante pertenece a este curso Y no se encuentra en algun grupo
                if(in_array($user, $students) AND !in_array($user, $students_in_groups))
                {
                    //insertamos el usuario en el equipo
                    $data->userid = $user;
                    $data->teamid = $tid;
                    insert_record('teamwork_users_teams', $data);
                }
            }

            //si el grupo no tiene ningún usuario, el primero de los añadidos será el nuevo lider
            if(!$count_members AND count($selection) > 0)
            {
                unset($data);
                $data = new stdClass;
                $data->id = $tid;
                $data->teamleader = $selection[0];
                update_record('teamwork_teams', $data);
            }

            header('Location: team.php?id='.$cm->id.'&action=userlist&tid='.$tid);
            

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

            $ofirst = optional_param('ofirst', null);
            $olast = optional_param('olast', 'asc');

            if($ofirst !== null)
            {
                if($ofirst == 'asc')
                {
                    $orderby = 'u.firstname ASC';
                }
                else
                {
                    $orderby = 'u.firstname DESC';
                }
            }
            else
            {
                if($olast == 'desc')
                {
                    $orderby = 'u.lastname DESC';
                }
                else
                {
                    $orderby = 'u.lastname ASC';
                }
            }

            $firstinitial = optional_param('sfirst', '');
            $lastinitial = optional_param('slast', '');

            //cargar la lista de alumnos de un curso
            $students = get_course_students($course->id, $orderby, '', '', '', $firstinitial, $lastinitial, null, '', 'u.id, firstname, lastname, picture, imagealt');

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

            echo '<p align="center">';
            echo get_string('name') . ': ' . teamwork_alphabetical_list('team.php', 'sfirst');
            echo '<br />';
            echo get_string('lastname') . ': ' . teamwork_alphabetical_list('team.php', 'slast');
            echo '</p>';
            
            //inicio del formulario
            echo '<form method="post" action="'.teamwork_create_url('team.php').'">';

            //cabecera de la tabla
            echo '<table width="40%" cellspacing="1" cellpadding="5" id="userstable" class="generaltable boxaligncenter">';
            echo '<tbody><tr>';
            echo '<th scope="col" class="header c0" style="vertical-align: top; text-align: center; width: 10%; white-space: nowrap;"/>';
            $firstname = (!isset($_GET['ofirst'])) ? teamwork_create_url('team.php', array('ofirst'=>'desc'), array('olast')) : teamwork_create_url('team.php', array('ofirst'=>(($ofirst == 'asc') ? 'desc' : 'asc')), array('olast'));
            $lastname = (!isset($_GET['olast'])) ? teamwork_create_url('team.php', array('olast'=>'desc'), array('ofirst')) : teamwork_create_url('team.php', array('olast'=>(($olast == 'asc') ? 'desc' : 'asc')), array('ofirst'));
            $imgfirst = ($ofirst == 'asc' AND !isset($_GET['olast'])) ? '&nbsp;<img src="../../pix/t/up.gif" />' : (($ofirst == 'desc' AND !isset($_GET['olast'])) ? '&nbsp;<img src="../../pix/t/down.gif" />': '');
            $imglast = ($olast == 'asc'  AND !isset($_GET['ofirst'])) ? '&nbsp;<img src="../../pix/t/up.gif" />' : (($olast == 'desc'  AND !isset($_GET['ofirst'])) ? '&nbsp;<img src="../../pix/t/down.gif" />': '');
            echo '<th scope="col" class="header c1" style="vertical-align: top; text-align: center; width: 80%; white-space: nowrap;"><a href="'.$firstname.'">'.get_string('name').'</a>'.$imgfirst.' / <a href="'.$lastname.'">'.get_string('lastname').'</a>'.$imglast.'</th>';
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

            $leader = get_record('teamwork_teams', 'id', $tid)->teamleader;
            $members = array_values(get_records('teamwork_users_teams', 'teamid', $tid));

            if($leader == $uid)
            {
                $value = (count($members) > 0) ? $members[0]->userid : 0;

                $data = new stdClass;
                $data->id = $tid;
                $data->teamleader = $value;
                update_record('teamwork_teams', $data);
            }

            //redireccionar
            header('Location: team.php?id='.$cm->id.'&action=userlist&tid='.$tid);
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
        header('Location: team.php?id='.$cm->id.'&action=userlist&tid='.$tid);

    break;

    //generador de equipos aleatoriamente en base a unas premisas...
    case 'teamgenerator':

        //solo estará disponible esta funcionalidad si no hay equipos creados para esta actividad
        if(count_records('teamwork_teams', 'teamworkid', $teamwork->id))
        {
            print_error('youdontusetherandombecauseteamsexist', 'teamwork');
        }

        //cargamos el formulario
        $form = new teamwork_randomteams_form('team.php?id='.$cm->id.'&action=teamgenerator');

        //no se ha enviado, se muestra
        if(!$form->is_submitted())
        {
            //$form->set_data(array('name'=>'mi nombre'));
            $form->display();
        }
        //se ha enviado pero se ha cancelado, redirigir a página principal
        elseif($form->is_cancelled())
        {
            header('Location: team.php?id='.$cm->id);
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

            //
            /// Operaciones de creacion de los equipos
            //

            //segun el tipo de distribucion
            if($data->distribution == 'firstname')
            {
                $orderby = 'u.firstname ASC';
            }
            else
            {
                $orderby = 'u.lastname ASC';
            }

            //cargar la lista de alumnos de un curso
            if(! $students = get_course_students($course->id, $orderby, '', '', '', '', '', null, '', 'u.id, firstname, lastname'))
            {
                print_error('thiscoursenothavestudents', 'teamwork');
            }

            $students = array_values($students);
            $nstudents = count($students);

            //si la asignacion es aleatoria, barajamos el array de datos
            if($data->distribution == 'random')
            {
                shuffle($students);
            }

            //segun el tipo de agrupación
            if(! (int) $data->specify)
            {
                //Especifica el número de equipos
                $nteams = (int) $data->number;
                $nusersperteam = floor($nstudents/$nteams);
            }
            else
            {
                //Especifica el numero de alumnos por equipo
                $nusersperteam = (int) $data->number;
                $nteams = ceil($nstudents/$nusersperteam);
            }
            
            //repartir alumnos a los equipos
            $teams = array();

            for($i = 0; $i < $nteams; $i++)
            {
                //nombre del grupo
                $teams[$i]['name'] = groups_parse_name(trim($data->namingscheme), $i);

                //miembros del equipo
                for($j = 0; $j < $nusersperteam; $j++)
                {
                    $user = array_shift($students);

                    if($user === null)
                    {
                        break 2;
                    }

                    $teams[$i]['members'][] = $user;
                }
            }

            //
            /// Operaciones de inserción de equipos o de vista previa
            //

            //si se pide solo una vista previa de como quedarían los equipos...
            if(isset($data->preview))
            {
                $table = new stdClass;
                $table->width = '100%';
                $table->tablealign = 'center';
                $table->id = 'previewteams';
                $table->head = array(get_string('randomteampreviewteam', 'teamwork', $nteams), get_string('randomteampreviewmembers', 'teamwork'), get_string('randomteampreviewcount', 'teamwork', $nstudents));
                $table->align = array('center', 'center', 'center');

                foreach($teams as $team)
                {
                    $line = array();
                    
                    //nombre del equipo
                    $line[] = $team['name'];

                    $unames = array();

                    foreach($team['members'] as $member)
                    {
                        $unames[] = fullname($member, true);
                    }

                    $line[] = implode(', ', $unames);
                    $line[] = count($team['members']);

                    $table->data[] = $line;
                }

                //volvemos a mostrar el formulario
                $form->display();

                //mostramos la tabla
                print_table($table);
            }
            //si se quiere crear los equipos definitivamente
            else
            {
                $teamdata = new stdClass;
                $userdata = new stdClass;

                //para cada equipo a crear
                foreach($teams as $team)
                {
                    //crear los datos del equipo
                    $teamdata->teamworkid = $teamwork->id;
                    $teamdata->teamname = $team['name'];
                    $teamdata->teamleader = $team['members'][0]->id;

                    //insertar los datos
                    $teamid = insert_record('teamwork_teams', $teamdata);

                    foreach($team['members'] as $member)
                    {
                        //datos de la asignación
                        $userdata->userid = $member->id;
                        $userdata->teamid = $teamid;

                        //insertar datos de la asignación
                        insert_record('teamwork_users_teams', $userdata);
                    }
                }

                //redireccionar a la página con la lista de miembros del equipo
                header('Location: team.php?id='.$cm->id);
            }
        }

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
