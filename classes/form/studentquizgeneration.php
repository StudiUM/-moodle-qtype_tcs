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
 * This file contains the form to generate a student quiz.
 *
 * @package   mod_concordance
 * @copyright 2020 Université de Montréal
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_concordance\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Student quiz generation form.
 *
 * @package   mod_concordance
 * @copyright 2020 Université de Montréal
 * @author    Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquizgeneration extends \moodleform {

    /**
     * Define the form - called by parent constructor
     */
    public function definition() {
        global $PAGE, $OUTPUT;
        $mform = $this->_form;

        // Panelists.
        $mform->addElement('header', 'paneliststoincludeheader',
                \html_writer::tag('h3', get_string('paneliststoinclude', 'mod_concordance')));
        $statelabel = get_string('quizstate', 'mod_concordance');
        $mform->addElement('html',
                "<table id='paneliststoincludetable'><thead><tr><th></th><th>$statelabel</th></tr></thead>");
        $mform->addElement('html', '<tbody>');
        foreach ($this->_customdata['panelists'] as $panelist) {
            $label = $panelist->get('firstname').' '.$panelist->get('lastname');
            $mform->addElement('html', '<tr><td>');
            $mform->addElement('checkbox', 'paneliststoinclude['. $panelist->get("id").']', '', $label);
            $quizstate = '';
            $quizstateclass = '';
            if (key_exists($panelist->get("userid"), $this->_customdata['attempts'])) {
                $state = $this->_customdata['attempts'][$panelist->get('userid')]->state;
                if (!empty($state)) {
                    $quizstate = get_string('state' . $state, 'mod_quiz');
                    if ($state == \quiz_attempt::FINISHED) {
                        $quizstateclass = 'label-success';
                    }
                    if ($state == \quiz_attempt::IN_PROGRESS) {
                        $quizstateclass = 'label-warning';
                        $mform->freeze(['paneliststoinclude['. $panelist->get("id").']']);
                    }
                } else {
                    $quizstate = get_string('notcompleted', 'mod_concordance');
                    $mform->freeze(['paneliststoinclude['. $panelist->get("id").']']);
                }
            } else {
                $quizstate = get_string('notcompleted', 'mod_concordance');
                $mform->freeze(['paneliststoinclude['. $panelist->get("id").']']);
            }

            $mform->addElement('html', '</td>');
            $mform->addElement('html', "<td><span class='mt-1 label $quizstateclass'>$quizstate</span></td></tr>");
        }
        $mform->addElement('html', '</tbody></table>');

        // Questions.
        $mform->addElement('header', 'questionstoincludeheader',
                \html_writer::tag('h3', get_string('questionstoinclude', 'mod_concordance')));
        $structure = $this->_customdata['structure'];
        foreach ($structure->get_sections() as $section) {
            $mform->addElement('html', \html_writer::start_div('concordancequestionsection'));
            if (!empty($section->heading)) {
                $mform->addElement('html', \html_writer::tag('h4', $section->heading));
            }
            foreach ($structure->get_slots_in_section($section->id) as $slot) {
                $pagenumber = $structure->get_page_number_for_slot($slot);
                // Put page in a heading for accessibility and styling.
                if ($structure->is_first_slot_on_page($slot)) {
                    $page = get_string('page') . ' ' . $pagenumber;
                    $tag = !empty($section->heading) ? 'h5' : 'h4';
                    $mform->addElement('html', \html_writer::tag($tag, $page));
                }
                $question = $structure->get_question_in_slot($slot);

                $qtype = \question_bank::get_qtype($question->qtype, false);
                $namestr = $qtype->local_name();
                $icon = $OUTPUT->pix_icon('icon', $namestr, $qtype->plugin_name(), array('title' => $namestr,
                        'class' => 'activityicon', 'alt' => ' ', 'role' => 'presentation'));
                $label = quiz_question_tostring($question);
                $mform->addElement('checkbox', 'questionstoinclude['. $slot .']', '', $icon . $label);
                $mform->setDefault('questionstoinclude['. $slot .']', 1);
            }
            $mform->addElement('html', \html_writer::end_div());
        }

        // Disable short forms.
        $mform->setDisableShortforms();
        $this->add_action_buttons(false, get_string('generate', 'mod_concordance'));

        $PAGE->requires->string_for_js('cannotremoveallsectionslots', 'mod_concordance');
        $PAGE->requires->string_for_js('cannotremoveslots', 'mod_quiz');
        $PAGE->requires->js_call_amd('mod_concordance/studentquizgeneration', 'init', []);
    }
}
