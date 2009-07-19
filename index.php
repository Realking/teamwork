<?php
/**
 * Listado de instancias de teamwork en un curso
 *
 * Este archivo muestra una lista de las instancias de la actividad teamwork
 * que existen en el ámbito de un curso
 *
 * @author Javier Aranda <internet@javierav.com>
 * @version 0.1
 * @package teamwork
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero GPL 3
 */

//incluir archivos basicos para el funcionamiento del modulo
require_once("../../config.php");
require_once("lib.php");

//obtener la id del curso sobre el que mostrar la lista de instancias
$id = required_param('id', PARAM_INT);

//select * from course where id = $id // obtener los datos del curso
if (! $course = get_record("course", "id", $id)) {
    error("Course ID is incorrect");
}

//asegurar que se cuenta con los permisos de acceso a este curso
require_course_login($course);

//añade al log que se ha visto esta pagina
add_to_log($course->id, "teamwork", "view all", "index.php?id=$course->id", "");

//obtener cadenas de texto en el idioma actual
$strsurveys = get_string("modulenameplural", "survey");
$strweek = get_string("week");
$strtopic = get_string("topic");
$strname = get_string("name");
$strstatus = get_string("status");
$strdone  = get_string("done", "survey");
$strnotdone  = get_string("notdone", "survey");

//construir la barra de navegacion superior
$navlinks = array();
$navlinks[] = array('name' => $strsurveys, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple("$strsurveys", "", $navigation,
             "", "", true, "", navmenu($course));

if (! $surveys = get_all_instances_in_course("teamwork", $course)) {
    notice(get_string('thereareno', 'moodle', $strsurveys), "../../course/view.php?id=$course->id");
}

if ($course->format == "weeks") {
    $table->head  = array ($strweek, $strname, $strstatus);
    $table->align = array ("CENTER", "LEFT", "LEFT");
} else if ($course->format == "topics") {
    $table->head  = array ($strtopic, $strname, $strstatus);
    $table->align = array ("CENTER", "LEFT", "LEFT");
} else {
    $table->head  = array ($strname, $strstatus);
    $table->align = array ("LEFT", "LEFT");
}

$currentsection = '';

foreach ($surveys as $survey) {
    if (!empty($USER->id) and survey_already_done($survey->id, $USER->id)) {
        $ss = $strdone;
    } else {
        $ss = $strnotdone;
    }
    $printsection = "";
    if ($survey->section !== $currentsection) {
        if ($survey->section) {
            $printsection = $survey->section;
        }
        if ($currentsection !== "") {
            $table->data[] = 'hr';
        }
        $currentsection = $survey->section;
    }
    //Calculate the href
    if (!$survey->visible) {
        //Show dimmed if the mod is hidden
        $tt_href = "<a class=\"dimmed\" href=\"view.php?id=$survey->coursemodule\">".format_string($survey->name,true)."</a>";
    } else {
        //Show normal if the mod is visible
        $tt_href = "<a href=\"view.php?id=$survey->coursemodule\">".format_string($survey->name,true)."</a>";
    }

    if ($course->format == "weeks" or $course->format == "topics") {
        $table->data[] = array ($printsection, $tt_href, "<a href=\"view.php?id=$survey->coursemodule\">$ss</a>");
    } else {
        $table->data[] = array ($tt_href, "<a href=\"view.php?id=$survey->coursemodule\">$ss</a>");
    }
}

echo "<br />";
print_table($table);
print_footer($course);

?>
