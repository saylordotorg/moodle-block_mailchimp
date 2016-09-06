<?php
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
 * EVENTS!
 * 
 * File         events.php
 * Encoding     UTF-8
 * @package     block_mailchimp
 *
 * @version     3.0.0
 * @author      John Azinheira
 * @copyright   2015 Saylor Academy {@link http://www.saylor.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * */


defined('MOODLE_INTERNAL') || die();

$observers = array(
    // Listen for when a user profile is updated.
    array(
        'eventname' => '\core\event\user_updated',
        'includefile' => '/blocks/mailchimp/locallib.php',
        'callback' => 'block_mailchimp_user_updated_handler',
        'internal' => false
    ),
);
