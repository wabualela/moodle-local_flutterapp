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
 * External functions and service declaration for Flutter Moodle Mobile App
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    local_flutterapp
 * @category   webservice
 * @copyright  2024 Wail Abualela <wailabualela@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_flutterapp_get_certificate' => [
        'classname'   => 'local_flutterapp\external\get_certificate',
        'description' => 'Get a user certificate.',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [ MOODLE_OFFICIAL_MOBILE_SERVICE, 'flutter_app' ],
    ],
    'local_flutterapp_user_confirm'    => [
        'classname'   => 'local_flutterapp\external\user_confirm',
        'description' => 'Confirm the user with username and password',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [ MOODLE_OFFICIAL_MOBILE_SERVICE, 'flutter_app' ],
    ],
];
