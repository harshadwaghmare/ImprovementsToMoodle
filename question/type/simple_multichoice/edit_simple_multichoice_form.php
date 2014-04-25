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
 * Defines the editing form for the multiple choice question type.
 *
 * @package    qtype
 * @subpackage simple_multichoice
 * @copyright  2007 Jamie Pratt and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Multiple choice editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_simple_multichoice_edit_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
       
       
    protected function definition_inner($mform) {
      //  Remove the General Feedback field from form to simplify the form.

      	if($mform->elementExists('generalfeedback')) {
	   $mform->removeElement('generalfeedback');
	}
        $menu = array(
            get_string('answersingleno', 'qtype_simple_multichoice'),
            get_string('answersingleyes', 'qtype_simple_multichoice'),
        );
	$fractionoptions = array(
            '0.0' => get_string('none'),
            '1.0' => '100%',
        );
	
        $mform->addElement('hidden', 'single',
                get_string('answerhowmany', 'qtype_simple_multichoice'), $menu);
	$mform->setType('single', PARAM_INT);
        $mform->setDefault('single', 1);

        $mform->addElement('advcheckbox', 'shuffleanswers',
                get_string('shuffleanswers', 'qtype_simple_multichoice'), null, null, array(0, 1));
        $mform->addHelpButton('shuffleanswers', 'shuffleanswers', 'qtype_simple_multichoice');
        $mform->setDefault('shuffleanswers', 1);

	$mform->addElement('hidden', 'answernumbering',
                get_string('answernumbering', 'qtype_simple_multichoice'),
                qtype_simple_multichoice::get_numbering_styles());
	$mform->setType('answernumbering', PARAM_TEXT);
	$mform->setDefault('answernumbering', 'abc');
        $this->add_per_answer_fields($mform, get_string('choiceno', 'qtype_simple_multichoice', '{no}'),
                $fractionoptions, max(4, QUESTION_NUMANS_START));

//        $this->add_combined_feedback_fields(true);
        $mform->disabledIf('shownumcorrect', 'single', 'eq', 1);

        $this->add_interactive_settings(true, true);
    }
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = $mform->createElement('editor', 'answer',
                $label, array('rows' => 1), $this->editoroptions);
        $repeated[] = $mform->createElement('select', 'fraction',
                get_string('grade'), $gradeoptions);
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $answersoption = 'answers';
        return $repeated;
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        if (!empty($question->options)) {
            $question->single = $question->options->single;
            $question->shuffleanswers = $question->options->shuffleanswers;
            $question->answernumbering = $question->options->answernumbering;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;

        $totalfraction = 0;
        $maxfraction = -1;

        foreach ($answers as $key => $answer) {
            // Check no of choices.
            $trimmedanswer = trim($answer['text']);
            $fraction = (float) $data['fraction'][$key];
            if ($trimmedanswer === '' && empty($fraction)) {
                continue;
            }
            if ($trimmedanswer === '') {
                $errors['fraction['.$key.']'] = get_string('errgradesetanswerblank', 'qtype_simple_multichoice');
            }

            $answercount++;

            // Check grades.
            if ($data['fraction'][$key] > 0) {
                $totalfraction += $data['fraction'][$key];
            }
            if ($data['fraction'][$key] > $maxfraction) {
                $maxfraction = $data['fraction'][$key];
            }
        }

        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_simple_multichoice', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_simple_multichoice', 2);
        } else if ($answercount == 1) {
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_simple_multichoice', 2);

        }

        // Perform sanity checks on fractional grades.
        if ($data['single']) {
            if ($maxfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_simple_multichoice',
                        $maxfraction * 100);
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_simple_multichoice',
                        $totalfraction * 100);
            }
        }
        return $errors;
    }

    public function qtype() {
        return 'simple_multichoice';
    }
}
