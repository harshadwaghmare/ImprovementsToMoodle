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
 * Allows the admin to create, delete and rename course categories rearrange courses
 *
 * @package   core
 * @copyright 2013 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../config.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/coursecatlib.php');

/**
 * Limit the total number of categories where user has 'moodle/course:manage'
 * permission in order for "Move categories" dropdown to be displayed on this page.
 * If number of categories exceeds this limit, user can always use edit category
 * form to change the parent. Otherwise the page size becomes too big.
 */
if (!defined('COURSECAT_QUICKMOVE_LIMIT')) {
    define('COURSECAT_QUICKMOVE_LIMIT', 200);
}

// Category id.
$id = optional_param('categoryid', 0, PARAM_INT);
// Which page to show.
$page = optional_param('page', 0, PARAM_INT);
// How many per page.
$perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT);

$search    = optional_param('search', '', PARAM_RAW);  // search words
$blocklist = optional_param('blocklist', 0, PARAM_INT);
$modulelist= optional_param('modulelist', '', PARAM_PLUGIN);
if (!$id && !empty($search)) {
    $searchcriteria = array('search' => $search);
} else if (!$id && !empty($blocklist)) {
    $searchcriteria = array('blocklist' => $blocklist);
} else if (!$id && !empty($modulelist)) {
    $searchcriteria = array('modulelist' => $modulelist);
} else {
    $searchcriteria = array();
}

require_login();
// Retrieve coursecat object
// This will also make sure that category is accessible and create default category if missing
$coursecat = coursecat::get($id);

if ($id) {
    $PAGE->set_category_by_id($id);
    $PAGE->set_url(new moodle_url('/course/staticoperations.php', array('categoryid' => $id)));
    // This is sure to be the category context.
    $context = $PAGE->context;
    if (!can_edit_in_category($coursecat->id)) {
        redirect(new moodle_url('/course/index.php', array('categoryid' => $coursecat->id)));
    }
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/course/staticoperations.php'));
    if (!can_edit_in_category()) {
        redirect(new moodle_url('/course/index.php'));
    }
}

$canmanage = has_capability('moodle/category:manage', $context);

// Prepare the standard URL params for this page. We'll need them later.
$urlparams = array('categoryid' => $id);
if ($page) {
    $urlparams['page'] = $page;
}
if ($perpage) {
    $urlparams['perpage'] = $perpage;
}
$urlparams += $searchcriteria;

$PAGE->set_pagelayout('coursecategory');
$courserenderer = $PAGE->get_renderer('core', 'course');

if (can_edit_in_category()) {
    // Integrate into the admin tree only if the user can edit categories at the top level,
    // otherwise the admin block does not appear to this user, and you get an error.
    require_once($CFG->libdir . '/adminlib.php');
    if ($id) {
        navigation_node::override_active_url(new moodle_url('/course/index.php', array('categoryid' => $id)));
    }
    admin_externalpage_setup('coursemgmt', '', $urlparams, $CFG->wwwroot . '/course/manage.php');
    $settingsnode = $PAGE->settingsnav->find_active_node();
    if ($id && $settingsnode) {
        $settingsnode->make_inactive();
        $settingsnode->force_open();
        $PAGE->navbar->add($settingsnode->text, $settingsnode->action);
    }
} else {
    $site = get_site();
    $PAGE->set_title("$site->shortname: $coursecat->name");
    $PAGE->set_heading($site->fullname);
    $PAGE->set_button($courserenderer->course_search_form('', 'navbar'));
}

// Start output.
echo $OUTPUT->header();

if ($coursecat->id) {
    // Print the category selector.
    $displaylist = coursecat::make_categories_list();
    $select = new single_select(new moodle_url('/course/staticoperations.php'), 'categoryid', $displaylist, $coursecat->id, null, 'switchcategory');
    $select->set_label(get_string('categories').':');

    echo html_writer::start_tag('div', array('class' => 'categorypicker'));
    echo $OUTPUT->render($select);
    echo html_writer::end_tag('div');
}

if (!empty($searchcriteria)) {
    $courses = coursecat::get(0)->search_courses($searchcriteria, array('recursive' => true,
        'offset' => $page * $perpage, 'limit' => $perpage, 'sort' => array('fullname' => 1)));
    $numcourses = count($courses);
    $totalcount = coursecat::get(0)->search_courses_count($searchcriteria, array('recursive' => true));
} else if ($coursecat->id) {
    // Print out all the sub-categories (plain mode).
    // In order to view hidden subcategories the user must have the viewhiddencategories.
    // capability in the current category..
    if (has_capability('moodle/category:viewhiddencategories', $context)) {
        $categorywhere = '';
    } else {
        $categorywhere = 'AND cc.visible = 1';
    }
    // We're going to preload the context for the subcategory as we know that we
    // need it later on for formatting.
    $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
    $sql = "SELECT cc.*, $ctxselect
              FROM {course_categories} cc
              JOIN {context} ctx ON cc.id = ctx.instanceid
             WHERE cc.parent = :parentid AND
                   ctx.contextlevel = :contextlevel
                   $categorywhere
          ORDER BY cc.sortorder ASC";
    $subcategories = $DB->get_recordset_sql($sql, array('parentid' => $coursecat->id, 'contextlevel' => CONTEXT_COURSECAT));
    // Prepare a table to display the sub categories.
    $table = new html_table;
    $table->attributes = array(
        'border' => '0',
        'cellspacing' => '2',
        'cellpadding' => '4',
        'class' => 'generalbox boxaligncenter category_subcategories'
    );
    $table->head = array(new lang_string('subcategories'));
    $table->data = array();
    $baseurl = new moodle_url('/course/manage.php');
    foreach ($subcategories as $subcategory) {
        // Preload the context we will need it to format the category name shortly.
        context_helper::preload_from_record($subcategory);
        $context = context_coursecat::instance($subcategory->id);
        // Prepare the things we need to create a link to the subcategory.
        $attributes = $subcategory->visible ? array() : array('class' => 'dimmed');
        $text = format_string($subcategory->name, true, array('context' => $context));
        // Add the subcategory to the table.
        $baseurl->param('categoryid', $subcategory->id);
        $table->data[] = array(html_writer::link($baseurl, $text, $attributes));
    }

    $subcategorieswereshown = (count($table->data) > 0);
    if ($subcategorieswereshown) {
        echo html_writer::table($table);
    }

    $courses = get_courses_page($coursecat->id, 'c.sortorder ASC',
            'c.id,c.sortorder,c.shortname,c.fullname,c.summary,c.visible',
            $totalcount, $page*$perpage, $perpage);
    $numcourses = count($courses);
} else {
    $subcategorieswereshown = true;
    $courses = array();
    $numcourses = $totalcount = 0;
}

if (!$courses) {
    // There is no course to display.
    if (empty($subcategorieswereshown)) {
        echo $OUTPUT->heading(get_string("nocoursesyet"));
    }
} else {
    // Display a basic list of courses with paging/editing options.
    $table = new html_table;
    $table->attributes = array('border' => 0, 'cellspacing' => 0, 'cellpadding' => '4', 'class' => 'generalbox boxaligncenter');
    $table->head = array(
        get_string('courses'),
        get_string('delete'),
        get_string('hide/show')
    );
    $table->colclasses = array(null, null, 'mdl-align');
    if (!empty($searchcriteria)) {
        // add 'Category' column
        array_splice($table->head, 1, 0, array(get_string('category')));
        array_splice($table->colclasses, 1, 0, array(null));
    }
    $table->data = array();

    $count = 0;
    $abletomovecourses = false;

    // Checking if we are at the first or at the last page, to allow courses to
    // be moved up and down beyond the paging border.
    if ($totalcount > $perpage) {
        $atfirstpage = ($page == 0);
        if ($perpage > 0) {
            $atlastpage = (($page + 1) == ceil($totalcount / $perpage));
        } else {
            $atlastpage = true;
        }
    } else {
        $atfirstpage = true;
        $atlastpage = true;
    }

    $baseurl = new moodle_url('/course/staticoperations.php', $urlparams + array('sesskey' => sesskey()));
    foreach ($courses as $acourse) {
        $coursecontext = context_course::instance($acourse->id);

        $count++;
        $up = ($count > 1 || !$atfirstpage);
        $down = ($count < $numcourses || !$atlastpage);

        $courseurl = new moodle_url('/course/view.php', array('id' => $acourse->id));
        $attributes = array();
        $attributes['class'] = $acourse->visible ? '' : 'dimmed';
        $coursename = get_course_display_name_for_list($acourse);
        $coursename = format_string($coursename, true, array('context' => $coursecontext));
        $coursename = html_writer::link($courseurl, $coursename, $attributes);


        $table->data[] = new html_table_row(array(
            new html_table_cell($coursename),
            new html_table_cell(html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'did'.$acourse->id, 'value' => 1))),
            new html_table_Cell(html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'hid'.$acourse->id, 'value' => 1)))
        ));
        if (!empty($searchcriteria)) {
            // add 'Category' column
            $category = coursecat::get($acourse->category, IGNORE_MISSING, true);
            $cell = new html_table_cell($category->get_formatted_name());
            $cell->attributes['class'] = $category->visible ? '' : 'dimmed_text';
            array_splice($table->data[count($table->data) - 1]->cells, 1, 0, array($cell));
        }
    }
    
    //harshad action todo
    $actionurl = new moodle_url('/course/action.php');
    $pagingurl = new moodle_url('/course/manage.php', array('categoryid' => $id, 'perpage' => $perpage) + $searchcriteria);

    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $pagingurl);
    echo html_writer::start_tag('form', array('id' => 'movecourses', 'action' => $actionurl, 'method' => 'post'));
    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'categoryid', 'value' => $id));
    foreach ($searchcriteria as $key => $value) {
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
    }
    echo html_writer::table($table);
    echo html_writer::empty_tag('div', array('align' => 'center'));
    echo html_writer::empty_tag('br');
    echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'value' => get_string('doit'), 'style' => 'width: 80px; height: 25px;'));
    echo html_writer::empty_tag('/div');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('form');
    echo html_writer::empty_tag('br');
}

echo $OUTPUT->footer();
