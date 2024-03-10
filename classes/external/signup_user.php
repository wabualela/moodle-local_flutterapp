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
use core_external\external_function_parameters;
use core_external\external_warnings;
use core_user;
use context_system;
use invalid_parameter_exception;
use moodle_exception;
use core_privacy;
use moodle_url;

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Class singup_user
 *
 * @package    local_flutterapp
 * @copyright  2024 Wail Abualela <wailabualela@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signup_user extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            array(
                'username'  => new external_value(core_user::get_property_type('username'), 'Username'),
                'firstname' => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user'),
                'lastname'  => new external_value(core_user::get_property_type('lastname'), 'The family name of the user'),
                'email'     => new external_value(core_user::get_property_type('email'), 'A valid and unique email address'),
                'phone'     => new external_value(core_user::get_property_type('phone1'), 'A valid phone number'),
                'redirect'  => new external_value(
                    PARAM_LOCALURL,
                    'Redirect the user to this site url after confirmation.',
                    VALUE_DEFAULT,
                    '',
                ),
            ),
        );
    }

    /**
     * Get the signup required settings and profile fields.
     *
     * @param  string $username               username
     * @param  string $firstname              the first name(s) of the user
     * @param  string $lastname               the family name of the user
     * @param  string $email                  a valid and unique email address
     * @param  string $phone                  A valid phone number'
     * @param  string $redirect               Site url to redirect the user after confirmation
     * @return array settings and possible warnings
     * @since Moodle 3.2
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function execute(
        $username,
        $firstname,
        $lastname,
        $email,
        $phone,
        $redirect = '',
    ) {
        global $CFG, $PAGE;

        $params   = self::validate_parameters(
            self::execute_parameters(),
            array(
                'username'  => $username,
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'email'     => $email,
                'phone'     => $phone,
                'redirect'  => $redirect,
            ),
        );

        // We need this to make work the format text functions.
        $context = context_system::instance();
        $PAGE->set_context($context);


        // Validate the data sent.
        $data           = $params;
        $data['email2'] = $data['email'];
        // Force policy agreed if a site policy is set. The client is responsible of implementing the interface check.
        $manager = new core_privacy\local\sitepolicy\manager();
        if ($manager->is_defined()) {
            $data['policyagreed'] = 1;
        }
        unset($data['recaptcharesponse']);
        unset($data['customprofilefields']);

        // Save the user.
        $user = signup_setup_new_user((object) $data);

        $authplugin = get_auth_plugin('oauth2');

        // Check if we should redirect the user once the user is confirmed.
        $confirmationurl = null;
        if (!empty($params['redirect'])) {
            // Pass via moodle_url to fix thinks like admin links.
            $redirect = new moodle_url($params['redirect']);

            $confirmationurl = new moodle_url('/login/confirm.php', array('redirect' => $redirect->out()));
        }
        $authplugin->user_signup_with_confirmation($user, false, $confirmationurl);

        $result = [
            'success'  => true,
            'warnings' => array(),
        ];

        return $result;
    }

    /**
     * Describes the signup_user return value.
     *
     * @return external_single_structure
     * @since Moodle 3.2
     */
    public static function execute_returns() {

        return new external_single_structure(
            array(
                'success'  => new external_value(PARAM_BOOL, 'True if the user was created false otherwise'),
                'warnings' => new external_warnings(),
            ),
        );
    }
}
