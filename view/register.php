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
 * File handling mailchimp registration
 * 
 * File         register.php
 * Encoding     UTF-8
 * @package     block_mailchimp
 *
 * @version     2.7.0
 * @author      Rogier van Dongen :: sebsoft.nl
 * @copyright   2014 Rogier van Dongen :: sebsoft.nl {@link http://www.sebsoft.nl}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 **/

require(__DIR__ . "/../../../config.php");
require_once($CFG->dirroot . '/blocks/mailchimp/classes/MCAPI.class.php');

// Make sure the user is logged in...
require_login();
if (isguestuser()) {
    print_error('error:guestlogin', 'block_mailchimp');
}

$issubmitted = optional_param('submit', 'false', PARAM_ALPHA);
$sourcepage = required_param('sourcePage', PARAM_RAW);

if ($issubmitted != "true") {
    // Simple checking if the sourcePage at least contains the source domainname.
    if (stristr($CFG->wwwroot, $sourcepage)) {
        redirect($sourcepage);
    } else {
        redirect(new moodle_url("/"));
    }
}

// Kill off if we're missing something.
if (!isset($CFG->block_mailchimp_apicode) ||
        !isset($CFG->block_mailchimp_listid) ||
        !isset($CFG->block_mailchimp_linked_profile_field)) {
    print_error('missing_config_settings', 'block_mailchimp');
}

$actionregister = (!\block_mailchimp\helper::is_mailchimp_registered_user($CFG->block_mailchimp_linked_profile_field));

// Now lets call for the records we're going to work with.
$usertoprocess = $DB->get_record('block_mailchimp_users', array('userid' => $USER->id));
$params = array('fieldid' => $CFG->block_mailchimp_linked_profile_field, 'userid' => $USER->id);
$profilefieldtoprocess = $DB->get_record('user_info_data', $params);

if (!$usertoprocess) {
    $userparams = new stdClass();
    $userparams->userid = $USER->id;
    $userparams->email = $USER->email;
    $userparams->registered = $actionregister ? 1 : 0;
    $userparams->timecreated = time();
    $userparams->timemodified = time();

    $DB->insert_record('block_mailchimp_users', $userparams);
    $usertoprocess = $DB->get_record('block_mailchimp_users', array('userid' => $USER->id));
} else {
    $usertoprocess->registered = $actionregister;

    $DB->update_record('block_mailchimp_users', $usertoprocess);
}

if (!$profilefieldtoprocess) {
    $profilefieldparams = new stdClass();
    $profilefieldparams->userid = $USER->id;
    $profilefieldparams->fieldid = $CFG->block_mailchimp_linked_profile_field;
    $profilefieldparams->data = $actionregister ? 1 : 0;
    $profilefieldparams->dataformat = 0;

    $DB->insert_record('user_info_data', $profilefieldparams);
    $profilefieldtoprocess = $DB->get_record('user_info_data', array('userid' => $USER->id));
} else {
    $profilefieldtoprocess->data = $actionregister;

    $DB->update_record('user_info_data', $profilefieldtoprocess);
}

// Mailchimp.
$api = new MCAPI($CFG->block_mailchimp_apicode);
if ($api->errorCode) {
    print_error('error:connect_to_mailchimp', 'block_mailchimp');
}

if ($actionregister) {
    $mergevars = Array(
        'EMAIL' => $USER->email,
        'FNAME' => $USER->firstname,
        'LNAME' => $USER->lastname
    );
    // We can already have a subscription, in which case we'll get an error code returned.
    $api->listSubscribe($CFG->block_mailchimp_listid, $USER->email, $mergevars);

    redirect($CFG->wwwroot, get_string('subscribed_to_mailchimp', 'block_mailchimp'));
} else {
    // We can have already been unsubscribed, in which case we'll get an error code returned.
    $api->listUnsubscribe($CFG->block_mailchimp_listid, $USER->email);

    redirect($CFG->wwwroot, get_string('unsubscribed_to_mailchimp', 'block_mailchimp'));
}