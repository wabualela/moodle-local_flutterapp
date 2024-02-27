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

namespace local_flutterapp\external;

use core_external\external_api;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_function_parameters;
use context_user;
use webservice;
use completion_info;
use webservice_access_exception;
use context_module;
use mod_customcert;

/**
 * Class download_certificate
 *
 * @package    local_flutterapp
 * @copyright  2024 Wail Abualela <wailabualela@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_certificate extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'the course that certificate belongs to', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Summary of execute
     * @param mixed $courseid the course that certificate belongs to
     * @return void
     */
    public static function execute($courseid) {
        global $USER, $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [ 'courseid' => $courseid,],
        );

        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // TODO: check if the file download is enabled

        $certificateid = array_values(get_coursemodules_in_course('customcert', $params['courseid']))[0]->instance;
        $cm            = get_coursemodule_from_instance('customcert', $certificateid, 0, false, MUST_EXIST);
        $course        = $DB->get_record('course', [ 'id' => $params['courseid'],], '*', MUST_EXIST);
        $certificate   = $DB->get_record('customcert', [ 'id' => $certificateid ], '*', MUST_EXIST);
        $template      = $DB->get_record('customcert_templates', [ 'id' => $certificate->templateid ], '*', MUST_EXIST);
        $issue         = $DB->get_record('customcert_issues', [ 'userid' => $USER->id, 'customcertid' => $certificate->id ]);

        // Capabilities check.
        require_capability('mod/customcert:view', context_module::instance($cm->id));

        // Make sure the user has met the required time.
        if ($certificate->requiredtime) {
            if (\mod_customcert\certificate::get_course_time($certificate->course) < ($certificate->requiredtime * 60)) {
                exit();
            }
        }

        if (!$issue) {

            mod_customcert\certificate::issue_certificate($certificateid, $USER->id);

            // Set the custom certificate as viewed.
            $completion = new completion_info($course);
            $completion->set_module_viewed($cm);
        }

        $template = new mod_customcert\template($template);
        $template->generate_pdf(false, $USER->id);
    }

    /**
     * Returns the certificates for the given user
     * @return external_value the cerificacione
     */
    public static function execute_returns() {
        return new external_value(PARAM_RAW, 'certificate');
    }
}
