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
use core_external\external_warnings;
use core_user;
use context_system;
use invalid_parameter_exception;
use moodle_exception;
use core_privacy;
use moodle_url;
use stdClass;

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
            [
                'firstname'       => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user', VALUE_REQUIRED),
                'lastname'        => new external_value(core_user::get_property_type('lastname'), 'The family name of the user', VALUE_REQUIRED),
                'email'           => new external_value(core_user::get_property_type('email'), 'A valid and unique email address', VALUE_REQUIRED),
                'phone'           => new external_value(core_user::get_property_type('phone1'), 'A valid phone number', VALUE_REQUIRED),
                'auth'            => new external_value(PARAM_TEXT, 'The auth type Whatsapp or Google', VALUE_REQUIRED),
                'certificatename' => new external_value(PARAM_TEXT, 'Full name for certificate', VALUE_REQUIRED),
                'age'             => new external_value(PARAM_TEXT, 'Your age', VALUE_REQUIRED),
            ],
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
        $firstname,
        $lastname,
        $email,
        $phone,
        $auth,
        $certificatename,
        $age,
    ) {
        global $CFG, $PAGE, $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            array(
                'firstname'       => $firstname,
                'lastname'        => $lastname,
                'email'           => $email,
                'phone'           => $phone,
                'auth'            => $auth,
                'certificatename' => $certificatename,
                'age'             => $age,
            ),
        );

        // We need this to make work the format text functions.
        $context = context_system::instance();
        $PAGE->set_context($context);

        $userinfo['email']           = $params['email'];
        $userinfo['firstname']       = $params['firstname'];
        $userinfo['lastname']        = $params['lastname'];
        $userinfo['phone1']          = $params['phone'];
        $userinfo['certificatename'] = $params['certificatename'];
        $userinfo['age']             = $params['age'];

        if (empty($params['auth'])) {
            throw new moodle_exception('authempty', 'local_flutterapp');
        }

        if ($params['auth'] === \local_flutterapp\api::AUTH_WHATSAPP) {

            if (!\auth_twilio\api::is_enabled()) {
                throw new moodle_exception('notenabled', 'auth_twilio');
            }

            $userinfo['username'] = $params['phone'];
            \local_flutterapp\api::user_exists_phone($userinfo['username']);
            if ($user = \auth_twilio\api::create_new_confirmed_account($userinfo)) {
                $certificatname = $DB->get_record_select('user_info_field', 'shortname = :name', [ 'name' => $DB->sql_compare_text('certificatename') ]);
                $age            = $DB->get_record_select('user_info_field', 'shortname = :name', [ 'name' => $DB->sql_compare_text('age') ]);
                $DB->insert_record('user_info_data', [
                    'userid'  => $user->id,
                    'data'    => $params['certificatename'],
                    'fieldid' => $certificatname->id,
                ]);
                $DB->insert_record('user_info_data', [
                    'userid'  => $user->id,
                    'data'    => $params['age'],
                    'fieldid' => $age->id,
                ]);
            }

        } else if ($params['auth'] === \local_flutterapp\api::AUTH_OAUTH) {

            if (!\auth_oauth2\api::is_enabled()) {
                throw new moodle_exception('notenabled', 'auth_oauth2');
            }

            $issuer = \core\oauth2\api::get_issuer(1);
            if (!$issuer->is_available_for_login()) {
                throw new moodle_exception('issuernologin', 'auth_oauth2');
            }

            $userinfo['username'] = $params['email'];
            \local_flutterapp\api::user_exists_email($userinfo['username']);
            \local_flutterapp\api::create_new_confirmed_account($userinfo, $issuer);
        } else {
            throw new moodle_exception('authnotfound', 'local_flutterapp');
        }

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
