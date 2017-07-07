<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Extends my profile navigation tree.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir.'/filelib.php');


function local_extendedprofile_myprofile_navigation (core_user\output\myprofile\tree $tree, $user) {

    if (isloggedin()) {

        global $DB, $CFG, $USER, $OUTPUT, $SITE, $PAGE;

        $context = context_system::instance();

        $pictureheight = 100;
        $userpicture = $OUTPUT->user_picture($user,
                array('size' => $pictureheight, 'alttext' => false, 'link' => false));
        $picturearray = explode('"', $userpicture);
        $pictureurl = $picturearray[1];

        $contactwithimage = get_string('contactinfo', 'local_extendedprofile').
                "<br><br><img src =  $pictureurl />";

        $categorycontactinfo = new core_user\output\myprofile\category('contactinfo',
                    $contactwithimage, 'contact');
        $tree->add_category($categorycontactinfo);

        $name = get_string('name', 'local_extendedprofile')." : $user->lastname";
        $firstname = get_string('firstname', 'local_extendedprofile')." : $user->firstname";
        $mail = get_string('mail', 'local_extendedprofile')." : $user->email";
        $login = get_string('login', 'local_extendedprofile')." : $user->username";
        // Ne l'afficher que si utilisateur ou admin.
        $idnumber = get_string('idnumber', 'local_extendedprofile')." : $user->idnumber";

        $listtypeteacher = $DB->get_records('teacher_type', array('userid' => $user->id));
        $htmllistteachertype = "<ul>";
        foreach ($listtypeteacher as $typeteacher) {

            $htmllistteachertype .= "<li>".$typeteacher->typeteacher."</li>";
        }
        $htmllistteachertype .= "</ul>";
        $typeteacherstring = get_string('typeteacher', 'local_extendedprofile').$htmllistteachertype;

        $nodename = new core_user\output\myprofile\node('contactinfo', 'name', $name);
        $nodefirstname = new core_user\output\myprofile\node('contactinfo', 'firstname', $firstname);
        $nodemail = new core_user\output\myprofile\node('contactinfo', 'mail', $mail);
        $nodelogin = new core_user\output\myprofile\node('contactinfo', 'login', $login);
        $nodeidnumber = new core_user\output\myprofile\node('contactinfo', 'idnumber', $idnumber);

        $nodetypeteacher = new core_user\output\myprofile\node('contactinfo', 'typeteacher',
                $typeteacherstring);

        if (!isset($categorycontactinfo->nodes[$nodename->name])) {
            $categorycontactinfo->add_node($nodename);
        }
        if (!isset($categorycontactinfo->nodes[$nodefirstname->name])) {
            $categorycontactinfo->add_node($nodefirstname);
        }
        if (!isset($categorycontactinfo->nodes[$nodemail->name])) {
            $categorycontactinfo->add_node($nodemail);
        }
        if (!isset($categorycontactinfo->nodes[$nodelogin->name])) {
            $categorycontactinfo->add_node($nodelogin);
        }
        if ($DB->record_exists('teacher_type', array('userid' => $user->id))) {

            if (!isset($categorycontactinfo->nodes[$nodetypeteacher->name])) {
                $categorycontactinfo->add_node($nodetypeteacher);
            }
        }

        if (strstr($user->email, '@etu.u-cergy.fr') != false) {

            if (!isset($categorycontactinfo->nodes[$nodeidnumber->name])) {
                $categorycontactinfo->add_node($nodeidnumber);
            }

            $listvets = $DB->get_records('student_vet', array('studentid' => $user->id));
            $nbrevets = 0;

            $listvetsstring = get_string('listvets', 'local_extendedprofile')." : ";

            foreach ($listvets as $vet) {

                $category = $DB->get_record('course_categories', array('id' => $vet->categoryid));

                $listvetsstring .= "<br>&nbsp&nbsp&nbsp&nbsp$category->name";

                $nbrevets++;
            }

            if ($nbrevets == 0) {

                $listvetsstring .= get_string('novet', 'local_extendedprofile');
            }

            $nodelistvets = new core_user\output\myprofile\node('contactinfo', 'listvets', $listvetsstring,
                    'idnumber', null, null);
            if (!isset($categorycontactinfo->nodes[$nodelistvets->name])) {
                $categorycontactinfo->add_node($nodelistvets);
            }
        }

        $categoryadministration = new core_user\output\myprofile\category('newadministration',
                    get_string('administration', 'local_extendedprofile'), null);
        $tree->add_category($categoryadministration);

        if ($user->id == $USER->id) {

            $url = new moodle_url('/user/preferences.php', array('userid' => $user->id));
            $title = get_string('preferences', 'moodle');
            $node = new core_user\output\myprofile\node('newadministration', 'newpreferences', $title,
                    null, $url);
            if (!isset($tree->nodes[$node->name])) {
                $tree->add_node($node);
            }
        }

        if (!$user->deleted && $user->id != $USER->id &&
                !\core\session\manager::is_loggedinas() && has_capability('moodle/user:loginas',
                $context) && !is_siteadmin($user->id)) {

            $courseid = $PAGE->course->id;

            $url = new moodle_url('/course/loginas.php',
                    array('id' => $courseid, 'user' => $user->id, 'sesskey' => sesskey()));
            $node = new  core_user\output\myprofile\node('newadministration', 'newloginas', get_string('loginas'),
                    null, $url);
            if (!isset($tree->nodes[$node->name])) {
                $tree->add_node($node);
            }
        }

        if ($user->id == $USER->id || has_capability('local/extendedprofile:viewinfo', $context)) {

            $categoryteachedcourses = new core_user\output\myprofile\category('teachedcourses',
                    get_string('teachedcourses', 'local_extendedprofile'), null);
            $tree->add_category($categoryteachedcourses);

            $categoryfollowedcourses = new core_user\output\myprofile\category('followedcourses',
                    get_string('followedcourses', 'local_extendedprofile'), 'teachedcourses');
            $tree->add_category($categoryfollowedcourses);

            // Course category.
            $sqlcateg = "SELECT distinct c.category, z.name FROM mdl_user u, mdl_role_assignments r,"
                    . " mdl_context cx, mdl_course c, mdl_course_categories z"
                    . " WHERE u.id = r.userid AND r.contextid = cx.id AND cx.instanceid = c.id AND"
                    . " cx.contextlevel =50 AND c.category = z.id AND u.id =$user->id AND z.path NOT LIKE '/396/%'";
            $resultcateg = $DB->get_recordset_sql($sqlcateg);
            foreach ($resultcateg as $categ) {
                $courseteachedcontent = "";
                $coursefollowedcontent = "";
                $hasteacherroleincategory = 0;
                $hasstudentroleincategory = 0;

                $sqlcourscateg = "SELECT distinct c.fullname,c.id "
                        . "FROM mdl_user u, mdl_role_assignments r, mdl_context cx,"
                        . " mdl_course c, mdl_course_categories z "
                        . "WHERE u.id = r.userid AND r.contextid = cx.id AND "
                        . "cx.instanceid = c.id AND cx.contextlevel = 50 "
                        . "AND c.category = $categ->category AND u.id =$user->id AND c.visible = 1";

                $resultcategcours = $DB->get_recordset_sql($sqlcourscateg);
                foreach ($resultcategcours as $cours) {

                    $isteacher = 0;

                    $sqllistrolesincourse = "SELECT r.roleid FROM mdl_role_assignments r,"
                            . " mdl_context cx "
                            . "WHERE cx.instanceid = $cours->id AND cx.contextlevel = 50 "
                            . "AND r.userid = $user->id AND r.contextid = cx.id";

                    $listrolesincourse = $DB->get_recordset_sql($sqllistrolesincourse);

                    foreach ($listrolesincourse as $roleincourse) {

                        $teacherid = $DB->get_record('role', array('archetype' => 'teacher'))->id;
                        $editingteacherid = $DB->get_record('role', array('archetype' => 'editingteacher'))->id;

                        if ($roleincourse->roleid === $teacherid || $roleincourse->roleid === $editingteacherid) {

                            $isteacher = 1;
                        }
                    }

                    $listrolesincourse->close();

                    if ($isteacher) {

                        $courseteachedcontent .= "<a "
                                . "href = '$CFG->wwwroot/course/view.php?id=$cours->id'>$cours->fullname</a> <br>";

                        $hasteacherroleincategory = 1;
                    } else {

                        $coursefollowedcontent .= "<a "
                                . "href = '$CFG->wwwroot/course/view.php?id=$cours->id'>$cours->fullname</a> <br>";

                        $hasstudentroleincategory = 1;
                    }
                }

                $resultcategcours->close();

                $coursename = $categ->name;
                $coursestring = $categ->name;

                if ($hasteacherroleincategory) {

                    $nodeteachedcourse = new core_user\output\myprofile\node('teachedcourses',
                            $coursename, $coursestring, null, null, $courseteachedcontent);
                    if (!isset($categoryteachedcourses->nodes[$nodeteachedcourse->name])) {
                        $categoryteachedcourses->add_node($nodeteachedcourse);
                    }
                }
                if ($hasstudentroleincategory) {

                    $nodefollowedcourse = new core_user\output\myprofile\node('followedcourses',
                            $coursename, $coursestring, null, null, $coursefollowedcontent);
                    if (!isset($categoryfollowedcourses->nodes[$nodefollowedcourse->name])) {
                        $categoryfollowedcourses->add_node($nodefollowedcourse);
                    }
                }
            }

            $resultcateg->close();

            $courbeurl = new moodle_url('/local/extendedprofile/courbeconnexionsemaine.php',
                    array('id' => $user->id));

            $categorylogingraph = new core_user\output\myprofile\category('logingraph',
                   get_string('logingraph', 'local_extendedprofile'));
            $tree->add_category($categorylogingraph);

            $graphnode = new core_user\output\myprofile\node('logingraph',
                            'graph', "<img src=$courbeurl>");

            if (!isset($categorylogingraph->nodes[$graphnode->name])) {
                $categorylogingraph->add_node($graphnode);
            }

            // A n'afficher que si c'est utile.

            $sqlcoursetudiant = "SELECT distinct c.id, c.fullname,  m.name "
                . "FROM mdl_user u, mdl_role_assignments r, "
                . "mdl_context cx, mdl_course c, mdl_course_categories m "
                . "WHERE  r.userid =$user->id AND r.contextid = cx.id AND cx.instanceid = c.id AND "
                . "r.roleid =5 AND cx.contextlevel =50 AND m.id = c.category";
            $resultcourseetudiant = $DB->get_recordset_sql($sqlcoursetudiant);

            if ($resultcourseetudiant->valid()) {

                $tablecontent = "";

                $tablecontent .= "<table style='font-family: arial; font-size: 10px'>";
                $tablecontent .= "<tr><td><FONT COLOR='#780D68'><strong>".
                        get_string('coursename', 'local_extendedprofile')."</strong></td>"
                        . "<td><FONT COLOR='#780D68'><strong>".
                        get_string('teachers', 'local_extendedprofile')."</strong></td>"
                        . "<td><FONT COLOR='#780D68'><strong>".
                        get_string('chatactivity', 'local_extendedprofile')."</strong></td>"
                        . "<td><FONT COLOR='#780D68'><strong>".
                        get_string('assignmentsdelivered', 'local_extendedprofile')."</strong>"
                        . "</td><td><FONT COLOR='#780D68'><strong>".
                        get_string('quiz', 'local_extendedprofile')."</strong></td><td><FONT COLOR='#780D68'>"
                        . "<strong>".get_string('activities', 'local_extendedprofile')."</strong></td>"
                        . "<td><FONT COLOR='#780D68'><strong>".
                        get_string('minimumconsultationtime', 'local_extendedprofile').""
                        . "</strong></td><td><FONT COLOR='#780D68'><strong>".
                        get_string('lastconnexiondate', 'local_extendedprofile')."</strong></td></tr>";
                // Chat.
                $countmessage = 0;
                $countchat = 0;
                // Devoir.
                $devoirrendu = 0;
                $totaldevoirrendu = 0;
                // Quiz.
                $counttest = 0;
                $countquiz = 0;
                // Atelier.
                $atelierrendu = 0;
                $countatelier = 0;
                foreach ($resultcourseetudiant as $cours) {
                    $tablecontent .= "<tr>";
                    $tablecontent .= "<td>$cours->fullname</td>";
                    // Les enseignants.
                    $sqlenseignants = "SELECT distinct concat(u.firstname, ' ',u.lastname) as nomcomplet "
                            . "FROM mdl_user u, mdl_role_assignments r, mdl_context cx,"
                            . " mdl_course c, mdl_course_categories m "
                            . "WHERE r.userid = u.id AND r.contextid = cx.id AND cx.instanceid = $cours->id "
                            . "AND r.roleid = 3 AND cx.contextlevel = 50 AND m.id = c.category";
                    $resultenseignant = $DB->get_recordset_sql($sqlenseignants);
                    if (isset($resultenseignant)) {
                        $tablecontent .= "<td><ul>";
                        foreach ($resultenseignant as $enseignant) {

                                $tablecontent .= "<li>$enseignant->nomcomplet</li>";
                        }
                        $tablecontent .= "</ul></td>";
                    } else {
                        $tablecontent .= "<td>". get_string('noteacher', 'local_extendedprofile')."</td>";
                    }

                    $resultenseignant->close();

                    // Chat.
                    $sqlmodulechat = "select component from mdl_logstore_standard_log where"
                            . " courseid = $cours->id"
                            . " and component = 'mod_chat'";
                    $resultmodulechat = $DB->get_record_sql($sqlmodulechat);
                    if (isset($resultmodulechat->component)) {
                        $sqlcountchat = "SELECT count(id) as nombrechat FROM {chat} where"
                                . " course= $cours->id";
                        $resultcountchat = $DB->get_record_sql($sqlcountchat);
                        $sqlcountmessage = "SELECT count(m.message) as message "
                                . "FROM `mdl_chat_messages`m , mdl_chat c where m.userid = $user->id  "
                                . "and c.id = m.chatid and c.course =$cours->id "
                                . "and message not in ('enter', 'exit')";
                        $resultcountmessage = $DB->get_record_sql($sqlcountmessage);
                        $countchat += $resultcountchat->nombrechat;
                        $countmessage += $resultcountmessage->message;
                        if ($resultcountchat->nombrechat == $resultcountmessage->message) {
                            $calculchat = round(($resultcountmessage->message * 100) / $resultcountchat->nombrechat, 1);
                            $tablecontent .= "<td><FONT COLOR='#66CD00'><strong>"
                            . "$resultcountmessage->message/$resultcountchat->nombrechat</strong>"
                                    . "&nbsp;&nbsp;&nbsp($calculchat%)</td>";
                        } else {
                            $calculchat = round(($resultcountmessage->message * 100) / $resultcountchat->nombrechat, 1);
                            $tablecontent .= "<td><FONT COLOR='#FF0000'><strong>"
                            . "$resultcountmessage->message/$resultcountchat->nombrechat</strong>"
                                    . "&nbsp;&nbsp;&nbsp;($calculchat%)</td>";
                        }

                    } else {
                        $tablecontent .= "<td>-</td>";
                    }
                    // Devoirs.
                    $sqlmoduleassign = "select component from mdl_logstore_standard_log where"
                            . " courseid = $cours->id"
                            . " and component = 'mod_assign'";
                    $resultmoduleassign = $DB->get_record_sql($sqlmoduleassign);
                    if (isset($resultmoduleassign->component)) {
                        $sqlcountdevoir = "select count(id) as countdevoir from mdl_assign where"
                                . " course = $cours->id";
                        $resultcountdevoir = $DB->get_record_sql($sqlcountdevoir);
                        $sqldevoirrendu = "SELECT count(s.id) as nbrdevoirrendu FROM"
                                . " mdl_assign_submission s , mdl_assign a where"
                                . " a.id = s.assignment and course = $cours->id "
                                . "and s.userid = $user->id and s.status='submitted'";
                        $resultdevoirrendu = $DB->get_record_sql($sqldevoirrendu);
                        $devoirrendu += $resultdevoirrendu->nbrdevoirrendu;
                        $totaldevoirrendu += $resultcountdevoir->countdevoir;
                        if ($resultdevoirrendu->nbrdevoirrendu == $resultcountdevoir->countdevoir ) {
                                $tablecontent .= "<td><FONT COLOR='#66CD00'>"
                                        . "<strong>$resultdevoirrendu->nbrdevoirrendu/"
                                        . "$resultcountdevoir->countdevoir</strong>"
                                        . "&nbsp;&nbsp;&nbsp;(100%)</td>";
                                $resultcountdevoir->countdevoir += $resultcountdevoir->countdevoir;
                                $resultdevoirrendu->nbrdevoirrendu += $resultdevoirrendu->nbrdevoirrendu;
                        } else {
                                $calcul = round(($resultdevoirrendu->nbrdevoirrendu * 100) / $resultcountdevoir->countdevoir, 1);
                                $tablecontent .= "<td><FONT COLOR='#FF0000'><strong>"
                                        . "$resultdevoirrendu->nbrdevoirrendu/"
                                        . "$resultcountdevoir->countdevoir</strong>"
                                        . "&nbsp;&nbsp;&nbsp;($calcul%)</td>";
                                $resultcountdevoir->countdevoir += $resultcountdevoir->countdevoir;
                                $resultdevoirrendu->nbrdevoirrendu += $resultdevoirrendu->nbrdevoirrendu;
                        }

                    } else {
                            $tablecontent .= "<td>-</td>";
                    }
                    // Quiz.
                    $sqlmodulequiz = "select component from mdl_logstore_standard_log where "
                            . "courseid = $cours->id"
                            . " and component = 'mod_quiz'";
                    $resultmodulequiz = $DB->get_record_sql($sqlmodulequiz);
                    if (isset($resultmodulequiz->component)) {
                        $sqlcountquiz = "SELECT COUNT( id ) AS nombrequiz FROM mdl_quiz WHERE"
                                . " course = $cours->id";
                        $resultcountquiz = $DB->get_record_sql($sqlcountquiz);

                        $sqlcounttest = "SELECT count(distinct a.quiz) as count "
                                . "FROM mdl_quiz_attempts a, mdl_quiz q "
                                . "where q.id = a.quiz and q.course = $cours->id and a.userid = $user->id "
                                . "and a.state = 'finished'";
                        $resultcounttest = $DB->get_record_sql($sqlcounttest);
                        $counttest += $resultcounttest->count;
                        $countquiz += $resultcountquiz->nombrequiz;
                        if ($resultcountquiz->nombrequiz == $resultcounttest->count) {
                            $tablecontent .= "<td><FONT COLOR='#66CD00'><strong>"
                            . "$resultcounttest->count/$resultcountquiz->nombrequiz</strong>"
                                    . "&nbsp;&nbsp;&nbsp;(100%)</td>";
                            $resultcounttest->count += $resultcounttest->count;
                            $resultcountquiz->nombrequiz += $resultcountquiz->nombrequiz;
                        } else {
                            $calcul = round(($resultcounttest->count * 100) / $resultcountquiz->nombrequiz, 1);
                            $tablecontent .= "<td><FONT COLOR='#FF0000'><strong>"
                            . "$resultcounttest->count/$resultcountquiz->nombrequiz</strong>"
                                    . "&nbsp;&nbsp;&nbsp;($calcul%)</td>";
                            $resultcounttest->count += $resultcounttest->count;
                            $resultcountquiz->nombrequiz += $resultcountquiz->nombrequiz;
                        }
                    } else {
                        $tablecontent .= "<td>-</td>";
                    }
                    // Atelier.
                    $sqlmoduleworkshop = "select component from mdl_logstore_standard_log where"
                            . " courseid = $cours->id"
                            . " and component = 'mod_workshop'";
                    $resultmoduleworkshop = $DB->get_record_sql($sqlmoduleworkshop);
                    if (isset($resultmoduleworkshop->component)) {
                        $sqlcountatelier = "select count(id) as countatelier from mdl_workshop where"
                                . " course = $cours->id";
                        $resultcountatelier = $DB->get_record_sql($sqlcountatelier);

                        $sqlatelierrendu = "select count(s.id) as atelierrendu from"
                                . " mdl_workshop w , mdl_workshop_submissions s where"
                                . " w.id = s.workshopid and w.course = $cours->id and s.authorid = $user->id";
                        $resultatelierrendu = $DB->get_record_sql($sqlatelierrendu);
                        $atelierrendu += $resultatelierrendu->atelierrendu;
                        $countatelier += $resultcountatelier->countatelier;
                        if ($resultatelierrendu->atelierrendu == $resultcountatelier->countatelier ) {
                                $tablecontent .= "<td><FONT COLOR='#66CD00'><strong>"
                                        . "$resultatelierrendu->atelierrendu/$resultcountatelier->countatelier"
                                        . "</strong>&nbsp;&nbsp;&nbsp;(100%)</td>";
                                $resultatelierrendu->atelierrendu += $resultatelierrendu->atelierrendu;
                                $resultcountatelier->countatelier += $resultcountatelier->countatelier;
                        } else {
                                $calcul = round(($resultatelierrendu->atelierrendu * 100) / $resultcountatelier->countatelier, 1);
                                $tablecontent .= "<td><FONT COLOR='#FF0000'>"
                                        . "<strong>$resultatelierrendu->atelierrendu/"
                                        . "$resultcountatelier->countatelier</strong>"
                                        . "&nbsp;&nbsp;&nbsp;($calcul%)</td>";
                                $resultatelierrendu->atelierrendu += $resultatelierrendu->atelierrendu;
                                $resultcountatelier->countatelier += $resultcountatelier->countatelier;
                        }
                    } else {
                            $tablecontent .= "<td>-</td>";
                    }
                    // Durée de consultation.
                    $tableau = Array();
                    $mini = Array();
                    $maxi = Array();
                    $i = 0;

                    $tableau[$i] = report_consultation_totale_course($cours->id, $user->id);
                    if ($i == 0) {
                        $mini = $tableau[$i];
                        $maxi = $tableau[$i];
                    } else {
                        if ($tableau[$i] < $mini) {
                            $mini = $tableau[$i];
                        }
                        if ($tableau[$i] > $maxi) {
                            $maxi = $tableau[$i];
                        }
                    }

                    $tablecontent .= "<td><center>$tableau[$i]</center></td>";
                    // La dernière action dans le cours.
                    $sqllog = "select max(timecreated) as temps from mdl_logstore_standard_log where"
                            . " userid = $user->id and courseid = $cours->id";
                    $resultlog = $DB->get_record_sql($sqllog);
                    $datederniereconnexion = date('d/m/Y', $resultlog->temps);
                    $heurederniereconnexion = date('H:i:s', $resultlog->temps);

                    if (isset($resultlog->temps)) {
                        $tablecontent .= "<td>$datederniereconnexion à $heurederniereconnexion</td>";
                    } else {
                            $tablecontent .= "<td>".get_string('never', 'local_extendedprofile')."</td>";
                    }
                    $tablecontent .= "</tr>";
                }
                // Moyenne.
                $tablecontent .= "<tr><td><strong>".get_string('means',
                        'local_extendedprofile')."</strong></td><td></td>";
                if ($countchat) {
                    $moyennechat = round(($countmessage * 100) / $countchat, 1);
                    $tablecontent .= "<td><strong>$countmessage/$countchat&nbsp;&nbsp;($moyennechat%)"
                            . "</strong></td>";
                } else {
                    $tablecontent .= "<td></td>";
                }

                if ($totaldevoirrendu) {
                    $moyennedevoirs = round(($devoirrendu * 100) / $totaldevoirrendu, 1);
                    $tablecontent .= "<td><strong>$devoirrendu/$totaldevoirrendu&nbsp;&nbsp;($moyennedevoirs%)"
                            . "</strong></td>";
                } else {
                    $tablecontent .= "<td></td>";
                }
                if ($countquiz) {
                    $moyennequiz = round(($counttest * 100) / $countquiz, 1);
                    $tablecontent .= "<td><strong>$counttest/$countquiz&nbsp;&nbsp;($moyennequiz%)"
                            . "</strong></td>";
                } else {
                    $tablecontent .= "<td></td>";
                }
                if ($countatelier) {
                    $moyenneatelier = round(($counttest * 100) / $countatelier, 1);
                    $tablecontent .= "<td><strong>$counttest/$countatelier&nbsp;&nbsp;($moyenneatelier%)"
                            . "</strong></td>";
                } else {
                    $tablecontent .= "<td></td>";
                }
                $tablecontent .= "<td></td><td></td>";
                $tablecontent .= "</tr>";
                $tablecontent .= "</table>";

                $categorytablecourses = new core_user\output\myprofile\category('tablecourses',
                        get_string('tablecourses', 'local_extendedprofile'), null);
                $tree->add_category($categorytablecourses);

                $tablenode = new core_user\output\myprofile\node('tablecourses',
                    "Tableau", $tablecontent);
                if (!isset($categorytablecourses->nodes[$tablenode->name])) {
                    $categorytablecourses->add_node($tablenode);
                }
            }

            $resultcourseetudiant->close();
        }
    }
}

// Tableau cours détaillé => étudiant.
function report_consultation_totale_course($courseid, $userid) {
    global $DB;

    $inthiscourse = 0;
    $timespent = 0;
    $previoustime = -1;
    $timeout = 15 * 60;

    $sql = "SELECT timecreated, courseid FROM mdl_logstore_standard_log WHERE userid = $userid "
            . "AND courseid = $courseid ORDER BY timecreated ASC";

    $useractions = $DB->get_recordset_sql($sql);
    unset($sql);

    foreach ($useractions as $useraction) {

        if ($previoustime == -1) {

            $previoustime = $useraction->timecreated;
        }
        if (($useraction->timecreated - $previoustime) > $timeout) {

            $timespent += $timeout;
        } else {

            $timespent += ($useraction->timecreated - $previoustime);
        }

        $previoustime = $useraction->timecreated;
    }

    if ($useractions->valid()) {

        $timespent += $timeout;
    }

    $useractions->close();

    return round($timespent / 60, 0);
}