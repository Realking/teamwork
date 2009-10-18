<?php
/**
 * Cadenas de internacionalizacion español de España es_ES UTF8
 *
 * Este archivo contiene las cadenas usadas en el módulo para su
 * internacionalizacion
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

//
///usadas por moodle y de obligada inclusión
//

$string['modulename'] = 'Trabajo en Equipo';
$string['modulenameplural'] = 'Trabajos en Equipo';



//
/// usadas por el módulo teamwork
//

//en el formulario de creación/modificación de la actividad
$string['description'] = 'Descripción';
$string['timing'] = 'Fechas';
$string['evaluationweights'] = 'Pesos de las Calificaciones';
$string['otheroptions'] = 'Otras opciones';

$string['startsends'] = 'Inicio de los envíos';
$string['endsends'] = 'Finalización de los envíos';
$string['startevals'] = 'Inicio de las evaluaciones';
$string['endevals'] = 'Finalización de las evaluaciones'; 
$string['wggranding'] = 'Calificación de las Calificaciones';
$string['wgteam'] = 'Calificación de los Alumnos';
$string['wgteacher'] = 'Calificación del Profesor';
$string['wgintra'] = 'Calificación de Participación';
//$string['evaluationinfo'] = 'La suma de las 4 opciones debe ser 100';
$string['bgteam'] = 'Eliminar extremos en calificación del equipo';
$string['bgintra'] = 'Eliminar extremos en calificación de participación';
$string['allowselecteval'] = 'Permitir que los alumnos elijan los trabajos';
$string['selectevalmin'] = 'Número mínimo de trabajos que un alumno debe calificar';
$string['selectevalmax'] = 'Número máximo de trabajos que un alumno puede calificar';
$string['selectteammax'] = 'Número máximo de veces que puede ser calificado un trabajo';

$string['deactivateeval'] = 'Desactivada';
$string['deactivateextremecut'] = 'No eliminar';
$string['nolimit'] = 'Sin límite';


//en la vista principal view.php
$string['phase1'] = 'Esperando el inicio del envío de trabajos';
$string['phase2'] = 'Envío de trabajos';
$string['phase3'] = 'Esperando el inicio de la calificación de trabajos';
$string['phase4'] = 'Calificación de trabajos';
$string['phase5'] = 'Esperando las calificaciones finales';
$string['phase6'] = 'Alumno calificado, fin de la actividad';
$string['currentphase'] = 'Fase actual';
$string['timebefore'] = 'faltan $a';
$string['timeafter'] = '$a después';
$string['youaremanager'] = 'Usted tiene permisos de gestión';
$string['managetemplates'] = 'Gestionar las plantillas';


//en el editor de templates template.php
$string['templatesanditemseditor'] = 'Editor de plantillas y criterios de evaluación';
$string['coursetemplateslisting'] = 'Listado de plantillas disponibles';
$string['coursetemplatesasignedlisting'] = 'Listado de plantillas asignadas';
$string['notemplatesforthiscourse'] = 'No hay plantillas definidas a nivel de curso.';
$string['createnewtemplate'] = 'Crear nueva plantilla';
$string['sectionnotexist'] = 'La sección especificada no existe.';
$string['actionnotexist'] = 'La acción especificada no existe.';
$string['edittemplate'] = 'Editar una plantilla';
$string['tpladded'] = 'Plantilla añadida correctamente';
$string['itemslisting'] = 'Listado de criterios de evaluación de la plantilla: $a';
$string['noitemsforthistemplate'] = 'No hay elementos definidos en esta plantilla';
$string['addnewitem'] = 'Añadir un elemento';
$string['name'] = 'Nombre';
$string['items'] = 'Elementos';
$string['instances'] = 'Instancias';
$string['actions'] = 'Acciones';
$string['edit'] = 'Editar';
$string['edititems'] = 'Editar elementos';
$string['deletetpl'] = 'Eliminar plantilla';
$string['newtplfrom'] = 'Crear plantilla nueva a partir de esta';
$string['exporttpl'] = 'Exportar plantilla (xml)';
$string['importtpl'] = 'Importar plantilla (xml)';
$string['templatenotexist'] = 'La plantilla especificada no existe';
$string['goback'] = 'Volver';
$string['usetemplateforgroupeval'] = 'Usar plantilla para la evaluación de grupos';
$string['usetemplateforintraeval'] = 'Usar plantilla para la evaluación entre miembros del equipo';
$string['tplupdated'] = 'Plantilla actualizada correctamente';
$string['tplcannotbedeleted'] = 'La plantilla no puede ser borrada';
$string['tpldeleted'] = 'Plantilla borrada correctamente';
$string['confirmationfordeletetpl'] = '¿Desea realmente borrar la plantilla?';
$string['notemplateasigned'] = 'No existe ninguna plantilla asignada. ¡Debe asignar una plantilla a cada tipo de evaluación!';
$string['evalcriteria'] = 'Criterio de Evaluación';
$string['edititem'] = 'Editar un elemento';
$string['elementweight'] = 'Peso del Elemento';
$string['itemadded'] = 'Criterio añadido correctamente';
$string['deleteitem'] = 'Eliminar elemento';
$string['itemnotexist'] = 'El elemento especificado no existe';
$string['itemupdated'] = 'Elemento actualizado correctamente';
$string['itemnoteditable'] = 'El elemento no es editable';
$string['confirmationfordeleteitem'] = '¿Desea realmente borrar el elemento?';
$string['itemdeleted'] = 'Elemento borrado correctamente';
$string['evaltype'] = 'Tipo de Evaluación';
$string['evalteam'] = 'Evaluación de Grupos';
$string['evaluser'] = 'Evaluación entre Miembros';
$string['notusetemplateforgroupeval'] = 'Dejar de usar esta plantilla para la evaluación de grupos';
$string['notusetemplateforusereval'] = 'Dejar de usar esta plantilla para la evaluación entre miembros del equipo';
$string['youdonthavepermissionfordeletethisasignation'] = 'No tiene permiso para eliminar esta asignación';
$string['asignationforthisevaltypeexist'] = 'Ya existe una asignación de plantilla para este tipo de evaluación';
$string['evaltypenotexist'] = 'El tipo de evaluación no existe';
$string['usedbyusereval'] = 'Usado en la evaluación entre miembros del equipo';
$string['usedbygroupeval'] = 'Usado en la evaluación de los equipos';
$string['youdonthavepermissiontocopy'] = 'No tiene permiso para copiar esa plantilla';
$string['tplcopiedok'] = 'La plantilla se ha copiado correctamente';
$string['youdonthavepermissiontoexportthistemplate'] = 'No tiene permiso para exportar esta plantilla';
$string['importtemplate'] = 'Importar plantilla';
$string['templateimportok'] = 'Plantilla importada correctamente';
$string['importscaleerror'] = '¡Atención! La escala de uno o varios elementos importados no se encuentra disponible en este curso, por lo que se ha establecido un valor de 0. Revise los elementos.';
$string['youdonthavepermissiontochangeorder'] = 'No tiene permiso para cambiar el orden de estos elementos';


//en el editor de equipos team.php
$string['teamseditor'] = 'Editor de equipos de usuarios';
$string['addnewteam'] = 'Añadir nuevo equipo';
$string['editteam'] = 'Editar equipo';
$string['teamname'] = 'Nombre del equipo';
$string['teamcreated'] = 'Equipo creado correctamente';
$string['teamnotexist'] = 'El equipo no existe';
$string['teamupdated'] = 'Equipo actualizado correctamente';
$string['confirmationfordeleteteam'] = '¿Desea realmente borrar el equipo?';
$string['teamdeleted'] = 'Equipo borrado correctamente';
$string['definedteamlist'] = 'Lista de equipos definidos';
$string['notexistanyteam'] = 'No se han definido equipos. ¡Debe definir al menos dos para que la actividad funcione!';
$string['teammembers'] = 'Miembros del equipo: $a';
$string['editmembers'] = 'Editar miembros';
$string['deleteteam'] = 'Borrar equipo';
$string['addnewusers'] = 'Añadir usuarios al grupo';
$string['donothaveanyuserinthisteam'] = 'Este equipo no tiene miembros. ¡Añádalos!';
$string['studentname'] = 'Nombre del alumno';
$string['removeuserfromteam'] = 'Quitar el usuario del grupo';
$string['select'] = 'Seleccionar';
$string['asignusertogroupleyend'] = 'Leyenda: &nbsp; $a->red alumnos en otro grupo &nbsp; $a->green alumnos que ya están en este grupo';
$string['usersaddedok'] = 'Usuarios añadidos correctamente';
$string['confirmationfordeleteuserfromteam'] = '¿Desea realmente quitar este alumno del equipo?';
$string['userdeletedfromteam'] = 'El usuario ya no pertenece al aquipo';
$string['setthisuserasteamleader'] = 'Establecer este usuario como líder del equipo';
$string['thisuserisleader'] = 'Líder del equipo';
$string['thisusernotisinthisgroup'] = 'Este usuario no pertenece al equipo indicado';
$string['thisteamnotisinthisactivity'] = 'Este equipo no pertenece a esta actividad';
$string['leaderseterok'] = 'Líder establecido correctamente';
$string['teamgenerator'] = 'Generador automático de equipos';
$string['createrandomteams'] = 'Crear equipos aleatoriamente';
$string['numberofteams'] = 'Número de equipos';
$string['membersperteam'] = 'Miembros por equipo';
$string['typeofspecify'] = 'Especificar';
$string['numberofteams-members'] = 'Número de equipos / miembros';
$string['distribution'] = 'Distribuir alumnos';
$string['random'] = 'Aleatoriamente';
$string['byfirstname'] = 'Alfabéticamente por Nombres';
$string['bylastname'] = 'Alfabéticamente por Apellidos';
$string['namingscheme'] = 'Esquema de nombrado';
$string['namingschemetpl'] = 'Equipo #';
$string['thiscoursenothavestudents'] = 'Este curso no tiene estudiantes';
$string['randomteampreviewteam'] = 'Equipos ($a)';
$string['randomteampreviewmembers'] = 'Miembros del Equipo';
$string['randomteampreviewcount'] = 'Nº Miembros ($a)';
$string['youdontusetherandombecauseteamsexist'] = 'No puedes usar el generador ya que existen equipos creados';
$string['numberteammemberscannotbezero'] = 'Este valor debe ser 1 o más';
$string['badnamingscheme'] = "Debe contener exactamente un caracter '@' o '#'";

//en el archivo locallib.php
$string['upitem'] = 'Subir elemento';
$string['downitem'] = 'Bajar elemento';

//generales
$string['teamworkisnoeditable'] = 'No se puede editar la actividad';
$string['submit'] = 'Enviar';


$string[''] = '';
?>
