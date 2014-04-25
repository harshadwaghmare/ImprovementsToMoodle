<?php
require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir.'/coursecatlib.php');

$categoryid = required_param('categoryid', PARAM_INT);
$PAGE->set_url(new moodle_url('/course/action.php'));
$PAGE->set_context(context_system::instance());
require_login();

$category = $DB->get_record("course_categories", array("id"=>$categoryid));
$categoryname = format_string($category->name, true, array('context' => context_coursecat::instance($categoryid)));

$PAGE->set_title("Bulk Action over selected courses");
$PAGE->set_heading($categoryname);
echo $OUTPUT->header();

$coursearr = array();
if ($courses = get_courses($categoryid, '', 'c.id,c.shortname,c.fullname,c.visible')) {
    foreach ($courses as $course) {
        //delete bulk
        $strcid = 'did'.$course->id;
        $dcourseid = optional_param($strcid, 0, PARAM_INT);
        if ($dcourseid !== 0) {
            $flag = 1;
            if (!can_delete_course($course->id)) {
                echo 'course '.$course->fullname.' was not deleted due to restricted permissions';
                continue;
            }
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad', 'error');
            }
            add_to_log(SITEID, "course", "delete", "view.php?id=$course->id", "$course->fullname (ID $course->id)");
            echo $OUTPUT->heading($course->fullname);
            delete_course($course);
            fix_course_sortorder();
        } 
        //hide/show bulk
        $strhid = 'hid'.$course->id;
        $hcourseid = optional_param($strhid, 0, PARAM_INT);
        if ($hcourseid !== 0) {
            $flag = 1;
            //hide/show
            if ($course->visible)
                $hide = $course->id;
            else $show = $course->id;
            if ((!empty($hide) or !empty($show)) && confirm_sesskey()) {
                // Hide or show a course.
                if (!empty($hide)) {
                    $course = $DB->get_record('course', array('id' => $hide), '*', MUST_EXIST);
                    $visible = 0;

                    echo $OUTPUT->heading($course->fullname);
                    echo $OUTPUT->notification(get_string('coursehide', '', $course->fullname), 'notifysuccess');
                } else {
                    $course = $DB->get_record('course', array('id' => $show), '*', MUST_EXIST);
                    $visible = 1;

                    echo $OUTPUT->heading($course->fullname);
                    echo $OUTPUT->notification(get_string('courseshown', '', $course->fullname), 'notifysuccess');
                }
                $coursecontext = context_course::instance($course->id);
                require_capability('moodle/course:visibility', $coursecontext);
                // Set the visibility of the course. we set the old flag when user manually changes visibility of course.
                $params = array('id' => $course->id, 'visible' => $visible, 'visibleold' => $visible, 'timemodified' => time());
                $DB->update_record('course', $params);
                cache_helper::purge_by_event('changesincourse');
                add_to_log($course->id, "course", ($visible ? 'show' : 'hide'), "edit.php?id=$course->id", $course->id);
            }
            //end hide/show
        }
    }

} else {
    //control never reaches here
    print_error('No courses found');
}
if (empty($flag)) {
    echo $OUTPUT->notification(get_string('coursenotselected'), 'notifyproblem');
}
echo $OUTPUT->continue_button("staticoperations.php?categoryid=$categoryid");
echo $OUTPUT->footer();

?>