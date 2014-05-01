<?php
/**
 * Report of user in the courses
 */
require_once '../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->libdir.'/formslib.php';
require_login();
$headername = get_string('grade_report');
$header = "$SITE->shortname: $headername";
$user = $USER;
$PAGE->set_url('/user/profile/grade_report.php', array('id'=>$user->id));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('grade_report'));
$PAGE->set_heading(get_string('grade_report'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('grade_report'));

$startseuser = optional_param('startseuser', '2014', PARAM_INT); 
$startsemm = optional_param('startsemm', '1', PARAM_INT); 
$startsemd = optional_param('startsemd', '1', PARAM_INT); 
$endseuser = optional_param('endseuser', '2014', PARAM_INT); 
$endsemm = optional_param('endsemm', '5', PARAM_INT); 
$endsemd = optional_param('endsemd', '31', PARAM_INT);
 
define('SEM_START_DATE', make_timestamp($startseuser, $startsemm, $startsemd));
define('SEM_END_DATE', make_timestamp($endseuser, $endsemm, $endsemd));

$sem = optional_param('sem', 'odd', PARAM_TEXT);
$baseurl = new moodle_url('grade_report.php');

echo html_writer::start_tag('div', array('align' => 'center'));
echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
echo html_writer::label(get_string('evensem'), 'sem');
echo html_writer::empty_tag('input', array('type'=>'radio', 'name'=>'sem', 'value' => 'even'));
echo html_writer::label(get_string('oddsem'), 'sem');
echo html_writer::empty_tag('input', array('type'=>'radio', 'name'=>'sem', 'value' => 'odd'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');
echo html_writer::label(get_string('sem_start'), null);
echo html_writer::select_time('years', 'startseuser');
echo html_writer::select_time('months', 'startsemm');
echo html_writer::select_time('days', 'startsemd');
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');
echo html_writer::label(get_string('sem_end'), null);
echo html_writer::select_time('years', 'endseuser');
echo html_writer::select_time('months', 'endsemm');
echo html_writer::select_time('days', 'endsemd');
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', array('type'=>'submit', 'value'=>'Go'));
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::empty_tag('br');

$usercourses = enrol_get_all_users_courses($user->id, true, NULL, 'visible DESC,sortorder ASC');
$table = new html_table();
$table->tablealign = 'center';
$table->head = array(get_string('my_courses'), get_string('marks'));
$data_rows = array();
foreach ((array)$usercourses as $usercourse) {
  if(!(($usercourse->startdate > SEM_START_DATE) && ($usercourse->startdate < SEM_END_DATE))){
    continue;
  }
  $usercoursecontext = context_course::instance($usercourse->id);
  $usercoursename = format_string($usercourse->fullname, true, array('context' => $usercoursecontext));
  $usercourselink = html_writer::link(new moodle_url('/grade/report/user/index.php', array('id' => $usercourse->id, 'userid' => $user->id)), $usercoursename);
  // Get usercourse grade_item
  $usercourse_item = grade_item::fetch_course_item($usercourse->id);
  // Get the stored grade
  $usercourse_grade = new grade_grade(array('itemid'=>$usercourse_item->id, 'userid'=>$user->id));
  $finalgrade = $usercourse_grade->finalgrade;
  if(is_null($finalgrade)){
    $finalgrade = 0;
  }
  $data_row = array($usercourselink, round($finalgrade, 2));
  $data_rows[] = $data_row;
}
$table->data = $data_rows;
echo html_writer::table($table);
echo html_writer::start_tag('form', array('action' => 'export.php', 'method' => 'post'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'uid', 'value' => $user->id));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'semstartdate', 'value' => SEM_START_DATE));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'semenddate', 'value' => SEM_END_DATE));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sem', 'value' => $sem));
echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Export'));
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
