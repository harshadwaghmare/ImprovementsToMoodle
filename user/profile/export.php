<?php
// File to export grade_report in pdf
require_once('../../config.php');
require_once($CFG->dirroot.'/lib/pdflib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->libdir.'/pdflib.php');
require_once($CFG->libdir.'/moodlelib.php');

$semstart = optional_param('semstartdate', '', PARAM_INT);
$semend = optional_param('semenddate', '', PARAM_INT);
$uid = optional_param('uid', '', PARAM_INT);
$sem = optional_param('sem', '', PARAM_TEXT);
$usercourses = enrol_get_all_users_courses($uid, true, NULL, 'visible DESC,sortorder ASC');

$table = new html_table();
$table->tablealign = 'center';
$table->attributes = array('border'=>'1');
$table->head = array(get_string('course_names'), get_string('marks'));
$data_rows = array();
foreach ((array)$usercourses as $usercourse) {
  //Checking for time of course
  if(!(($usercourse->startdate > $semstart) && ($usercourse->startdate < $semend))){
    continue;
  }
  $usercoursecontext = context_course::instance($usercourse->id);
  $usercoursename = format_string($usercourse->fullname, true, array('context' => $usercoursecontext));
  $usercourselink = html_writer::link(new moodle_url('/grade/report/user/index.php', array('id' => $usercourse->id, 'userid' => $uid)), $usercoursename);
  // Get usercourse grade_item
  $usercourse_item = grade_item::fetch_course_item($usercourse->id);
  // Get the stored grade
  $usercourse_grade = new grade_grade(array('itemid'=>$usercourse_item->id, 'userid'=>$uid));
  $finalgrade = $usercourse_grade->finalgrade;
  if(is_null($finalgrade)){
    $finalgrade = 0;
  }
  $data_row = array($usercoursename, round($finalgrade, 2));
  $data_rows[] = $data_row;
}
$table->data = $data_rows;

//Export 
if($sem == 'odd'){
  $semname = get_string('oddsem');
} 
else{
  $semname = get_string('evensem');
}
$user = $DB->get_record('user', array('id' => $uid));
$exporthtml = html_writer::start_tag('div', array('align' => 'center'));
$exporthtml .= get_string('studentname').' : '.$user->username.'<br>';
$exporthtml .= get_string('sem').' : '.$semname.'<br>';
$exporthtml .= html_writer::start_tag('div', array('align' => 'right'));
$exporthtml .= get_string('sem_start').' : '.date("y-m-d", $semstart).'<br>';
$exporthtml .= get_string('sem_end').' : '.date("y-m-d", $semend).'<br>';
$exporthtml .= html_writer::end_tag('div');
$exporthtml .= html_writer::end_tag('div');
$exporthtml .= html_writer::table($table);

//pdf file generation
$doc = new pdf;
$doc->setPrintHeader(false);
$doc->setPrintFooter(false);
$doc->AddPage();
$doc->writeHTML($exporthtml, true, false, false, false);
$downloadfilename = clean_filename("user_grade_report.pdf");
$doc->Output($downloadfilename,'I');
?>