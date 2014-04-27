<?php
require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir.'/coursecatlib.php');

$PAGE->set_url(new moodle_url('/course/actioncat.php'));
$PAGE->set_context(context_system::instance());
require_login();

$sql = "SELECT c.id, c.name, c.visible FROM {course_categories} c";
$cat = $DB->get_records_sql($sql);

$PAGE->set_title(get_string('bulkactionsacategory'));
$PAGE->set_heading(get_string('staticmanagecat'));
echo $OUTPUT->header();

foreach ($cat as $category) {
    //making strategy for more than one selects
    //moveid
    $strmoveid = 'cmove'.$category->id;
    $mcat = optional_param($strmoveid, 0, PARAM_INT);
    //hideid
    $strhidcatparam = 'chid'.$category->id;
    $hidecatparam = optional_param($strhidcatparam, 0, PARAM_INT);
    //deleteid
    $strdeletecat = 'cdel'.$category->id;
    $deletecat = optional_param($strdeletecat, 0, PARAM_INT);
    //checking condition
    if (($mcat != 0 and $hidecatparam != 0 and $deletecat != 0)
        or ($mcat != 0 and $hidecatparam != 0)
        or ($mcat != 0 and $deletecat != 0)
        or ($hidecatparam != 0 and $deletecat != 0)) {
        echo $OUTPUT->heading($category->name);
        echo $OUTPUT->notification(get_string('multipleselected'), 'notifyproblem');
        continue;
    }
    //hide category starts
    if ($hidecatparam !== 0) {
        if ($category->visible == 1) {
            $hidecat = $category->id;
            $showcat = 0;
        }
        else {
            $showcat = $category->id;
            $hidecat = 0;
        }
        echo $OUTPUT->heading($category->name);
        if ($hidecat and confirm_sesskey()) {
            $cattohide = coursecat::get($hidecat);
            require_capability('moodle/category:manage', get_category_or_system_context($cattohide->parent));
            $cattohide->hide();
            echo $OUTPUT->notification(get_string('hidecat', '', $category->name), 'notifysuccess');
        } else if ($showcat and confirm_sesskey()) {
            $cattoshow = coursecat::get($showcat);
            require_capability('moodle/category:manage', get_category_or_system_context($cattoshow->parent));
            $cattoshow->show();
            echo $OUTPUT->notification(get_string('showcat', '', $category->name), 'notifysuccess');
        }
    }
//end hide category


    if (($deletecat !== 0) and confirm_sesskey()) {
        // Delete a category.
        $cattodelete = coursecat::get($category->id);
        $context = context_coursecat::instance($category->id);
        require_capability('moodle/category:manage', $context);
        require_capability('moodle/category:manage', get_category_or_system_context($cattodelete->parent));

        $heading = get_string('deletecategory', 'moodle', format_string($cattodelete->name, true, array('context' => $context)));

        require_once($CFG->dirroot.'/course/delete_category_form.php');

        // Start output.

        echo $OUTPUT->heading($heading);

        if ($cattodelete->can_delete_full()) {
            $cattodeletename = $cattodelete->get_formatted_name();
            $deletedcourses = $cattodelete->delete_full(true);
            foreach ($deletedcourses as $course) {
                echo $OUTPUT->notification(get_string('coursedeleted', '', $course->shortname), 'notifysuccess');
            }
            echo $OUTPUT->notification(get_string('coursecategorydeleted', '', $cattodeletename), 'notifysuccess');

        }/* else if ($data->fulldelete == 0 && $cattodelete->can_move_content_to($data->newparent)) { //uncomment these lines if you want
            $cattodelete->delete_move($data->newparent, true);                                        //to just delete the category and move
            echo $OUTPUT->continue_button(new moodle_url('/course/smanage.php'));                     //and move the courses to its parent category
            }*/
        else {
            echo $OUTPUT->notification('cannotdeletecategory', 'notifyproblem');
        }
    }
//end delete category
//move category start


    if ($mcat !== 0) {
        $movecat = $category->id;
        $movetocat = optional_param('movetocat'.$category->id, -1, PARAM_INT);
        echo $OUTPUT->heading($category->name);
        if (!empty($movecat) and ($movetocat >= 0) and confirm_sesskey()) {
            // Move a category to a new parent if required.
            $cattomove = coursecat::get($movecat);
            if ($cattomove->parent != $movetocat) {
                if ($cattomove->can_change_parent($movetocat)) {
                    $cattomove->change_parent($movetocat);
                } else {
                    echo $OUTPUT->notification(get_string('cannotmovecategory'), 'notifyproblem');
                    continue;
                }
                echo $OUTPUT->notification(get_string('categorymoved'), 'notifysuccess');
            }
        }
    }
}
//move category end
echo $OUTPUT->continue_button("smanage.php");
echo $OUTPUT->footer();
?>