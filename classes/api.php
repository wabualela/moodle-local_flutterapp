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

namespace local_flutterapp;

/**
 * Class api
 *
 * @package    local_flutterapp
 * @copyright  2024 Wail Abualela <wailabualela@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api
{
    const AUTH_WHATSAPP = 'whatsapp';
    const AUTH_OAUTH    = 'oauth2';
    /**
     * Create an account with a linked login that is already confirmed.
     *
     * @param array $userinfo as returned from an oauth client.
     * @param \core\oauth2\issuer $issuer
     * @return bool
     */
    public static function create_new_confirmed_account($userinfo, $issuer)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        $user             = new \stdClass();
        $user->auth       = 'oauth2';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->secret     = random_string(15);
        $user->password   = '';
        $user->confirmed  = 1;  // Set the user to confirmed.

        $user = self::save_user($userinfo, $user);

        return $user;
    }

    /**
     * Create a new user & update the profile fields
     *
     * @param array $userinfo
     * @param object $user
     * @return object
     */
    private static function save_user(array $userinfo, object $user): object
    {
        // Map supplied issuer user info to Moodle user fields.
        $userfieldmapping = new \core\oauth2\user_field_mapping();
        $userfieldlist    = $userfieldmapping->get_internalfields();
        $hasprofilefield  = false;
        foreach ($userfieldlist as $field) {
            if (isset($userinfo[ $field ]) && $userinfo[ $field ]) {
                $user->$field = $userinfo[ $field ];

                // Check whether the profile fields exist or not.
                $hasprofilefield = $hasprofilefield || strpos($field, \core_user\fields::PROFILE_FIELD_PREFIX) === 0;
            }
        }

        // Create a new user.
        $user->id = user_create_user($user, false, true);

        // If profile fields exist then save custom profile fields data.
        if ($hasprofilefield) {
            profile_save_data($user);
        }

        return $user;
    }

    /**
     * check is user exist by phone
     * @param string $phone
     * @throws \moodle_exception
     * @return void
     */
    public static function user_exists_phone($phone)
    {
        global $DB;
        if ($DB->record_exists('user', [ 'username' => $phone ])) {
            throw new \moodle_exception('userexists', 'local_flutterapp');
        }

        if ($DB->record_exists('user', [ 'phone1' => $phone ])) {
            throw new \moodle_exception('userexists', 'local_flutterapp');
        }
    }

    /**
     * check is user exist by email
     * @param string $email
     * @throws \moodle_exception
     * @return void
     */
    public static function user_exists_email($email)
    {
        global $DB;
        if ($DB->record_exists('user', [ 'username' => $email ])) {
            throw new \moodle_exception('userexists', 'local_flutterapp');
        }

        if ($DB->record_exists('user', [ 'email' => $email ])) {
            throw new \moodle_exception('userexists', 'local_flutterapp');
        }
    }
}
