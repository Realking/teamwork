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
/// Usadas por moodle y de obligada inclusión
//

$string['modulename'] = 'Trabajo en Equipo';
$string['modulenameplural'] = 'Trabajos en Equipo';



//
/// Usadas por el módulo teamwork
//

// En el formulario de creación/modificación de la actividad
$string['description'] = 'Descripción';
$string['timing'] = 'Fechas';
$string['evaluationweights'] = 'Pesos de las Calificaciones';
$string['otheroptions'] = 'Otras opciones';

$string['startsends'] = 'Inicio de los envíos';
$string['endsends'] = 'Finalización de los envíos';
$string['startevals'] = 'Inicio de las evaluaciones';
$string['endevals'] = 'Finalización de las evaluaciones'; 
$string['wggrading'] = 'Calificación de las Calificaciones';
$string['wgteam'] = 'Calificación de los Alumnos';
$string['wgteacher'] = 'Calificación del Profesor';
$string['wgintra'] = 'Calificación de Participación';
$string['bgteam'] = 'Eliminar extremos en Calificación Alumnos';
$string['bgintra'] = 'Eliminar extremos en Calificación Participación';
$string['maxgrade'] = 'Calificación Máxima';

$string['deactivateeval'] = 'Desactivada';


// En la vista principal view.php
$string['phase1'] = 'Esperando el inicio del envío de trabajos';
$string['phase2'] = 'Esperando ser asignado a un equipo';
$string['phase3'] = 'Envío de trabajos';
$string['phase4'] = 'Esperando el inicio de la calificación de trabajos';
$string['phase5'] = 'Calificación de trabajos';
$string['phase6'] = 'Esperando las calificaciones finales';
$string['phase7'] = 'Alumno calificado, fin de la actividad';
$string['currentphase'] = 'Fase actual';
$string['timebefore'] = 'faltan $a';
$string['timeafter'] = '$a después';
$string['youaremanager'] = 'Usted tiene permisos de gestión';
$string['groupbelong'] = 'Grupo al que perteneces';
$string['youareleader'] = '¡Eres el líder del grupo!';
$string['teamsubmission'] = 'Envío del equipo';
$string['submissionnothavetext'] = 'Este envío no contiene texto.';
$string['attachedfiles'] = 'Archivos adjuntos:';
$string['editsubmission'] = 'Editar el envío';
$string['submissioncontent'] = 'Contenido del envío';
$string['attachfile'] = 'Adjuntar archivo';
$string['submissionnotsent'] = 'Este grupo no ha realizado ningún envío';
$string['evaluateteam'] = 'Evaluar a este equipo';
$string['waitingworksforevaluate'] = ' Lista de Trabajos Pendientes de Evaluar';
$string['waitingworksforevaluateteacher'] = ' Lista de Trabajos Pendientes de Calificar';
$string['evaluatemember'] = 'Evaluar a este compañero de equipo';
$string['waitingcoworkersforevaluate'] = ' Lista de Compañeros Pendientes de Evaluar';
$string['waitingcoworkersforevaluateteacher'] = ' Lista de Alumnos Pendientes de Calificar';
$string['dograde'] = 'Calcular las calificaciones';
$string['dogradeask'] = '¿Desea realmente calcular las calificaciones?';
$string['gradeinprogress'] = 'El cálculo de calificaciones se encuentra en proceso. Puede durar unos minutos.';
$string['studentgrade'] = 'Calificación obtenida';


// En el editor de templates template.php
$string['templatesanditemseditor'] = 'Editor de plantillas y criterios de evaluación';
$string['coursetemplateslisting'] = 'Listado de plantillas disponibles';
$string['coursetemplatesasignedlisting'] = 'Listado de plantillas asignadas';
$string['notemplatesforthiscourse'] = 'No hay plantillas definidas a nivel de curso.';
$string['createnewtemplate'] = 'Crear nueva plantilla';
$string['sectionnotexist'] = 'La sección especificada no existe.';
$string['actionnotexist'] = 'La acción especificada no existe.';
$string['edittemplate'] = 'Editar una plantilla';
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
$string['tplcannotbedeleted'] = 'La plantilla no puede ser borrada';
$string['confirmationfordeletetpl'] = '¿Desea realmente borrar la plantilla?';
$string['notemplateasigned'] = 'No existe ninguna plantilla asignada. ¡Debe asignar una plantilla a cada tipo de evaluación!';
$string['evalcriteria'] = 'Criterio de Evaluación';
$string['edititem'] = 'Editar un elemento';
$string['elementweight'] = 'Peso del Elemento';
$string['deleteitem'] = 'Eliminar elemento';
$string['itemnotexist'] = 'El elemento especificado no existe';
$string['itemnoteditable'] = 'El elemento no es editable';
$string['confirmationfordeleteitem'] = '¿Desea realmente borrar el elemento?';
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
$string['youdonthavepermissiontoexportthistemplate'] = 'No tiene permiso para exportar esta plantilla';
$string['importtemplate'] = 'Importar plantilla';
$string['importscaleerror'] = '¡Atención! La escala de uno o varios elementos importados no se encuentra disponible en este curso, por lo que se ha establecido un valor de 0. Revise los elementos.';
$string['youdonthavepermissiontochangeorder'] = 'No tiene permiso para cambiar el orden de estos elementos';


// En el editor de equipos team.php
$string['teamseditor'] = 'Editor de equipos de usuarios';
$string['addnewteam'] = 'Añadir nuevo equipo';
$string['editteam'] = 'Editar equipo';
$string['teamname'] = 'Nombre del equipo';
$string['teamnotexist'] = 'El equipo no existe';
$string['confirmationfordeleteteam'] = '¿Desea realmente borrar el equipo?';
$string['definedteamlist'] = 'Lista de equipos definidos';
$string['notexistanyteam'] = 'No se han definido equipos. ¡Debe definir al menos dos para que la actividad funcione!';
$string['teammembers'] = 'Miembros del equipo: $a';
$string['editmembers'] = 'Editar miembros';
$string['deleteteam'] = 'Borrar equipo';
$string['addnewusers'] = 'Añadir usuarios al grupo';
$string['donothaveanyuserinthisteam'] = 'Este equipo no tiene miembros. ¡Añádalos!';
$string['studentname'] = 'Nombre del alumno';
$string['removeuserfromteam'] = 'Quitar el usuario del grupo';
$string['asignusertogroupleyend'] = 'Leyenda: &nbsp; $a->red alumnos en otro equipo &nbsp; $a->green alumnos que ya están en este equipo';
$string['confirmationfordeleteuserfromteam'] = '¿Desea realmente quitar este alumno del equipo?';
$string['setthisuserasteamleader'] = 'Establecer este usuario como líder del equipo';
$string['thisuserisleader'] = 'Líder del equipo';
$string['thisusernotisinthisgroup'] = 'Este usuario no pertenece al equipo indicado';
$string['thisteamnotisinthisactivity'] = 'Este equipo no pertenece a esta actividad';
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
$string['youcannotdeletethisteambecauseithavememebers'] = 'No se puede borrar un equipo que tiene miembros. Borre estos primero.';


// En el archivo locallib.php
$string['upitem'] = 'Subir elemento';
$string['downitem'] = 'Bajar elemento';


// En el archivo assign.php
$string['assignseditor'] = 'Editor de Asignaciones';
$string['sentworkslist'] = 'Lista de Trabajos Enviados';
$string['teamsthatevalthiswork'] = 'Equipos que evalúan a este trabajo';
$string['editevaluators'] = 'Editar los equipos que corrigen a este equipo';
$string['deletework'] = 'Eliminar el trabajo';
$string['symbolicwork'] = 'Crear trabajos simbólicos';
$string['addevaluators'] = 'Añadir evaluadores';
$string['donothavesentworks'] = 'No se ha enviado ningún trabajo';
$string['viewwork'] = 'Ver el trabajo';
$string['confirmationfordeletework'] = '¿Desea realmente borrar el trabajo?';
$string['symbolycworktext'] = 'Texto generado automáticamente por la aplicación';
$string['youcannotcreatesymbolicworks'] = 'No se pueden crear trabajos simbólicos';
$string['teamevaluators'] = 'Equipos que evalúan al equipo: $a';
$string['donothaveanyevaluatorforthisteam'] = 'No hay ningún equipo que evalúe a este';
$string['removeteamforeval'] = 'Quitar equipo de la evaluación';
$string['addnewevaluators'] = 'Añadir equipos como evaluadores';
$string['asignteamforevalleyend'] = 'Leyenda: &nbsp; $a equipos que ya están como evaluadores de este equipo';
$string['cannotdeletethisworkbecausethishaveevaluators'] = 'No se puede borrar el trabajo ya que este grupo tiene asignados evaluadores. Eliminelos antes de continuar.';
$string['confirmationforremovefromevaluators'] = '¿Desea realmente quitar este grupo como evaluador?';
$string['confirmationforcreatesymbolicsworks'] = '¿Desea realmente crear trabajos simbólicos para todos los equipos?';


// En el archivo index.php
$string['noinstances'] = 'No hay ninguna instancia de este módulo';
$string['areyouleader?'] = '¿Eres el líder?';
$string['duedate'] = 'Fecha límite';


// En el archivo viewer.php
$string['worksviewer'] = 'Visor de Trabajos';
$string['youarentallowedtoseethiswork'] = 'No tiene permiso para ver este trabajo';
$string['thisteamnotexistornotisfromthisinstance'] = 'El equipo no existe o no pertenece a esta instancia';


// En el archivo eval.php
$string['assessmentscollection'] = 'Recogida de Evaluaciones';
$string['thisevaluationnotisforyou'] = 'Esta evaluación no te pertenece';
$string['thisevaluationalreadyhasbeenundertaken'] = 'Esta evaluación ya ha sido realizada. No puede cambiarla.';
$string['evaluationform'] = 'Formulario de Evaluación';
$string['thisevaluationidnotexist'] = 'No existe esta evaluación en el sistema';
$string['youareevaluatingtheteam'] = 'Estás evaluando al equipo $a';
$string['youareevaluatingtheuser'] = 'Estás evaluando a tu compañero de equipo $a';
$string['evaluationsavedok'] = 'Su evaluación ha sido guardada';
$string['teamevalform'] = 'Formulario de Evaluación de Equipo';
$string['userevalform'] = 'Formulario de Evaluación de Compañero';
$string['thisevaluseanonexistscale'] = 'Esta evaluación usa una escala que no existe. Contacte con el profesor.';
$string['itemnotinthisevaluation'] = 'El elemento no pertenece a esta evaluación';
$string['thenumberofsubmititemnotisequaltonumberofthisevaluationitems'] = 'El numero de elementos de la evaluación no coincide';
$string['evaluationshavebeenclosed'] = 'Ya no se puede evaluar este trabajo';


// En el archivo details.php
$string['evalsdetails'] = 'Detalle de las calificaciones';
$string['detailsnotavailable'] = 'Los detalles no están disponibles hasta que haya comenzado el periodo de evaluación';
$string['teamsevals'] = 'Calificaciones a los equipos';
$string['usersevals'] = 'Calificaciones a los compañeros';
$string['evaluator'] = 'Evaluador';


// Generales
$string['teamworkisnoeditable'] = 'No se puede editar la actividad';
$string['submit'] = 'Enviar';
?>
