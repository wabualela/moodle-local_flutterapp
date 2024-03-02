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
use context_system;
use Exception;
use lbuchs\WebAuthn\WebAuthnException;
use webservice;
use completion_info;
use webservice_access_exception;
use context_module;
use mod_customcert;

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Class user_confirm
 *
 * @package    local_flutterapp
 * @copyright  2024 Wail Abualela <wailabualela@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_confirm extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([ 
            'username' => new external_value(PARAM_TEXT, 'username', VALUE_REQUIRED),
            'password' => new external_value(PARAM_TEXT, 'password', VALUE_REQUIRED),
        ]);
    }

    /**
     * confirm the user
     * @param mixed $username
     * @param mixed $password
     * @throws \webservice_access_exception
     * @return boolean
     */
    public static function execute($username, $password) {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [ 
                'username' => $username,
                'password' => $password,
            ],
        );

        $context = context_system::instance();
        self::validate_context($context);

        $user            = get_complete_user_data('username', $params['username']);
        $passwordmatched = validate_internal_user_password($user, $params['password']);

        if (!empty($user)) {

            if ($user->auth != 'email') {
                throw new \ErrorException(get_string('errorconfirm', 'local_flutterapp'));

            } else if ($passwordmatched && $user->confirmed) {
                throw new \ErrorException(get_string('erroruseralreadyconfirmed', 'local_flutterapp'));
            } else if ($passwordmatched) {
                $user->confirmed = 1;
                user_update_user($user, false);
                return true;
            }
        } else {
            throw new \ErrorException(get_string('usernameorpasssworderror', 'local_flutterapp'));
        }
    }

    /**
     * Returns the certificates for the given user
     * @return external_value the cerificacione
     */
    public static function execute_returns() {
        return new external_value(PARAM_BOOL, 'true or false');
    }
}
