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
 * TODO describe file test
 *
 * @package    local_flutterapp
 * @copyright  2024 Wail Abualela <wailabualela@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

$url = new moodle_url('/local/flutterapp/test.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

$userid   = 2;
$courseid = 2;


$certificates = $DB->get_records('customcert', [ 'course' => $courseid ]);

$certificates_data = [];

foreach ($certificates as $certificate) {
    echo '';

    $cm = get_coursemodule_from_instance('customcert', $certificate->id, 0, false, MUST_EXIST);

    // Capabilities check.
    if (!has_capability('mod/customcert:view', \context_module::instance($cm->id), null, true)) {
        continue;
    }

    if ($userid != $USER->id) {
        if (!has_capability('mod/customcert:viewreport', \context_module::instance($cm->id), null, true)) {
            continue;
        }
    } else {
        // Make sure the user has met the required time.
        if ($certificate->requiredtime) {
            if (\mod_customcert\certificate::get_course_time($certificate->course) < ($certificate->requiredtime * 60)) {
                continue;
            }
        }
    }

    $issue = $DB->get_record('customcert_issues', [ 'customcertid' => $certificate->id, 'userid' => $userid ]);

    // If the user doesn't have an issue, then there is nothing to do.
    if (!$issue) {
        continue;
    }

    $certificate_element = array(
        'id'     => $certificate->id,
        'course' => $certificate->course,
        'name'   => $certificate->name,
    );

    array_push($certificates_data, $certificate_element);
}

var_dump($certificates_data);

echo $OUTPUT->footer();
