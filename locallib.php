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
 * Local lib functions
 * 
 * File         locallib.php
 * Encoding     UTF-8
 * @package     block_mailchimp
 *
 * @version     3.0.0
 * @author      John Azinheira
 * @copyright   2015 Saylor Academy {@link http://www.saylor.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * */


function block_mailchimp_user_updated_handler($event) {
	global $CFG;

	$user = new \stdClass();
	$user->id = $event->userid;

	// Do nothing if the MailChimp plugin is not configured
	if (!isset($CFG->block_mailchimp_apicode) ||
        !isset($CFG->block_mailchimp_listid) ||
        !isset($CFG->block_mailchimp_linked_profile_field)) {
    	print_error('missing_config_settings', 'block_mailchimp');
		return;
	}

	$mcprofiledata = block_mailchimp_get_profile_data($user);
	$mcinternaldata = block_mailchimp_get_internal_user($user);

	// Compare profile and internal subscription status
	if (!$mcprofiledata->data == $mcinternaldata->registered) {
		// Update the internal subscription status to reflect the profile setting.
		$updateinternalstatus = block_mailchimp_update_subscription_internal($user, $mcprofiledata->data);
	}



}

/**
 * Get internal mailchimp user record (or create if not exists)
 * 
 * @global \moodle_database $DB
 * @param \stdClass $user moodle user object
 * @return \stdClass internal mailchimp user object
 */
function block_mailchimp_get_internal_user($user) {
    global $DB;

    $mcuser = $DB->get_record('block_mailchimp_users', array('userid' => $user->id));
    if (!empty($mcuser)) {
        return $mcuser;
    }
    // Create mailchimp_user.
    $mcuser               = new \stdClass();
    $mcuser->userid       = $user->id;
    $mcuser->email        = $user->email;
    $mcuser->registered   = 0;
    $mcuser->timecreated  = time();
    $mcuser->timemodified = time();
    $mcuser->id           = $DB->insert_record('block_mailchimp_users', $mcuser, true);

    return $mcuser;
}

/**
 * Get mailchimp subscription status user profile record (or create if not exists)
 * 
 * @global \moodle_database $DB
 * @param \stdClass $user moodle user object
 * @return \stdClass record from user_info_data table
 */
function block_mailchimp_get_profile_data($user) {
    global $DB, $CFG;

    $params = array('userid' => $user->id, 'fieldid' => $CFG->block_mailchimp_linked_profile_field);
    $userinfodata = $DB->get_record('user_info_data', $params);
    if (!empty($userinfodata)) {
        return $userinfodata;
    }
    // Create the profile record if we have none.
    $userinfodata             = new \stdClass();
    $userinfodata->fieldid    = $CFG->block_mailchimp_linked_profile_field;
    $userinfodata->userid     = $user->id;
    $userinfodata->data       = "0";
    $userinfodata->dataformat = 0;
    $userinfodata->id         = $DB->insert_record('user_info_data', $userinfodata, true);

    return $DB->get_record('user_info_data', array('id' => $userinfodata->id), '*', MUST_EXIST);
}

/**
 * Updates registration status on profile field for user
 * 
 * @global \moodle_database $DB
 * @param \stdClass $user moodle user object
 * @param bool $registered registration status
 * @return bool 
 */
function block_mailchimp_update_profile_subscription($user, $registered) {
        global $DB, $CFG;

        $conditions = array('userid' => $user->id, 'fieldid' => $CFG->block_mailchimp_linked_profile_field);
        return $DB->set_field('user_info_data', 'data', $registered ? 1 : 0, $conditions);
    }

 /**
 * Updates registration status on internal mailchimp user
 * 
 * @global \moodle_database $DB
 * @param \stdClass $user moodle user object
 * @param bool $registered registration status
 * @return bool 
 */
 function block_mailchimp_update_subscription_internal($user, $registered) {
    global $DB;

    $mcuser = block_mailchimp_get_internal_user($user);
    $mcuser->registered = ($registered ? 1 : 0);
    $mcuser->timemodified = time();
    return $DB->update_record('block_mailchimp_users', $mcuser);
}


