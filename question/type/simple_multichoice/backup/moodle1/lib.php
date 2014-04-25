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
 * @package    qtype
 * @subpackage simple_multichoice
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Simple_Multichoice question type conversion handler
 */
class moodle1_qtype_simple_multichoice_handler extends moodle1_qtype_handler {

    /**
     * @return array
     */
    public function get_question_subpaths() {
        return array(
            'ANSWERS/ANSWER',
            'SIMPLE_MULTICHOICE',
        );
    }

    /**
     * Appends the simple_multichoice specific information to the question
     */
    public function process_question(array $data, array $raw) {

        // Convert and write the answers first.
        if (isset($data['answers'])) {
            $this->write_answers($data['answers'], $this->pluginname);
        }

        // Convert and write the simple_multichoice.
        if (!isset($data['simple_multichoice'])) {
            // This should never happen, but it can do if the 1.9 site contained
            // corrupt data.
            $data['simple_multichoice'] = array(array(
                'single'                         => 1,
                'shuffleanswers'                 => 1,
                'answernumbering'                => 'abc',
            ));
        }
        $this->write_simple_multichoice($data['simple_multichoice'], $data['oldquestiontextformat'], $data['id']);
    }

    /**
     * Converts the simple_multichoice info and writes it into the question.xml
     *
     * @param array $simple_multichoices the grouped structure
     * @param int $oldquestiontextformat - {@see moodle1_question_bank_handler::process_question()}
     * @param int $questionid question id
     */
    protected function write_simple_multichoice(array $simple_multichoices, $oldquestiontextformat, $questionid) {
        global $CFG;

        // The grouped array is supposed to have just one element - let us use foreach anyway
        // just to be sure we do not loose anything.
        foreach ($simple_multichoices as $simple_multichoice) {
            // Append an artificial 'id' attribute (is not included in moodle.xml).
            $simple_multichoice['id'] = $this->converter->get_nextid();

            // Replay the upgrade step 2009021801.
	    /*            $simple_multichoice['correctfeedbackformat']               = 0;
            $simple_multichoice['partiallycorrectfeedbackformat']      = 0;
            $simple_multichoice['incorrectfeedbackformat']             = 0;

            if ($CFG->texteditors !== 'textarea' and $oldquestiontextformat == FORMAT_MOODLE) {
                $simple_multichoice['correctfeedback']                 = text_to_html($simple_multichoice['correctfeedback'], false, false, true);
                $simple_multichoice['correctfeedbackformat']           = FORMAT_HTML;
                $simple_multichoice['partiallycorrectfeedback']        = text_to_html($simple_multichoice['partiallycorrectfeedback'], false, false, true);
                $simple_multichoice['partiallycorrectfeedbackformat']  = FORMAT_HTML;
                $simple_multichoice['incorrectfeedback']               = text_to_html($simple_multichoice['incorrectfeedback'], false, false, true);
                $simple_multichoice['incorrectfeedbackformat']         = FORMAT_HTML;
            } else {
                $simple_multichoice['correctfeedbackformat']           = $oldquestiontextformat;
                $simple_multichoice['partiallycorrectfeedbackformat']  = $oldquestiontextformat;
                $simple_multichoice['incorrectfeedbackformat']         = $oldquestiontextformat;
            }

            $simple_multichoice['correctfeedback'] = $this->migrate_files(
                    $simple_multichoice['correctfeedback'], 'question', 'correctfeedback', $questionid);
            $simple_multichoice['partiallycorrectfeedback'] = $this->migrate_files(
                    $simple_multichoice['partiallycorrectfeedback'], 'question', 'partiallycorrectfeedback', $questionid);
            $simple_multichoice['incorrectfeedback'] = $this->migrate_files(
                    $simple_multichoice['incorrectfeedback'], 'question', 'incorrectfeedback', $questionid);
	    */
//            $this->write_xml('simple_multichoice', $simple_multichoice, array('/simple_multichoice/id'));
        }
    }
}
