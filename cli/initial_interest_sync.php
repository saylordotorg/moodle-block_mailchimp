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

/*
 * File         initial_interest_sync.php
 * Encoding     UTF-8
 */

namespace block_mailchimp\task;

/**
 * CLI scipt to initiate an initial sync to apply an interest group to Moodle users in a MailChimp list.
 * 
 * @package     block_mailchimp
 *
 * @version     3.0.0
 * @author      John Azinheira
 * @copyright   2015 Saylor Academy {@link http://www.saylor.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 *
 * */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once(__DIR__.'/../classes/task/mcsynchronize.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help'=>false),
    array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_heading('Help');
    $help =
        "Perform an initial sync to apply interest.

        This scripts flags users that are present in both MailChimp and Moodle
        with a specified interest.

        This sync also updates the subscription status in Moodle to reflect
        the subscription status in MailChimp (we assume for this sync 
        that MailChimp is the source of truth for subscription status.

        A normal sync is then performed at the end which should add any 
        users that are present in Moodle but not in MailChimp.

        Use this script when initially setting up the plugin and you already 
        have users (even a partial list) in MailChimp where some may be in 
        Moodle and some are not and will not be in Moodle but you want those 
        in Moodle to be flagged as a specific group (like 'Student').

        Options:
        -h, --help            Print out this help

        Example:
        \$sudo -u www-data /usr/bin/php blocks/mailchimp/cli/initial_interest_sync.php\n\n";

    echo $help;
    die;
}


class interestsynchronize extends \block_mailchimp\task\mcsynchronize {

    /**
     * Get human-friendly task name
     * 
     * @return string
     */
    public function get_name() {
        return get_string('task:interestsynchronize', 'block_mailchimp');
    }

    /**
     * Execute task
     * 
     * @global \moodle_database $DB
     * @global \stdClass $CFG
     * @return void
     * @throws \moodle_exception
     */
    public function execute_interestsync() {
        global $DB, $CFG;

        // Not going to work if we're missing settings.
        if (!isset($CFG->block_mailchimp_apicode) ||
                !isset($CFG->block_mailchimp_listid) ||
                !isset($CFG->block_mailchimp_linked_profile_field)) {
            cli_error("No API code or list selected. Aborting interest sync.", 1);
            return;
        }

        if (empty($CFG->block_mailchimp_interest) || ($CFG->block_mailchimp_interest == "0")) {
            cli_error("No interest selected. Aborting interest sync.", 1);
            return;
        }

        $listid = $CFG->block_mailchimp_listid;

        // Get all users in MailChimp.
        $listusers = \block_mailchimp\helper::getMembersSync();
        if (!$listusers) {
            cli_error("ERROR: Failed to get list of all members. Unable to synchronize users.", 1);
            return;
        }
        $listuserscount = count($listusers['members']);

        // Get list of users in Moodle
        cli_heading('Getting list of users in Moodle');
        $moodleusers = $DB->get_records('user');


        cli_heading('Sorting user lists');
        // Sort MailChimp users list
        // foreach ($listusers['members'] as $key => $row) {
        //     $emails[$key] = $row['email_address'];
        // }
        // array_multisort($emails, SORT_ASC, $listusers['members']);
        // unset($emails);

        // Sort Moodle users list
        foreach ($moodleusers as $key => $row) {
                $emails[$key] = $row->email;
        }
        array_multisort($emails, SORT_ASC, $moodleusers);
        unset($emails);


        // // Update all the users that are present in moodle so they have the interest added.
        // cli_heading('Adding interest to MailChimp users that are present in Moodle');
        // foreach ($moodleusers as $moodleuser) {

        //         foreach ($listusers['members'] as $listuser) {
        //             // Search through the moodleusers to find the user with corresponding email address
        //             $maxkey = count($moodleusers) - 1;
        //             $minkey = 0;
        //             $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);

        //             $moodleuser = false;
        //             $listuseremail = strtowlower($listuser['email_address']);

        //             while($minkey <= $maxkey) {
        //                 $moodleuseremail = strtolower($moodleusers[$searchkey]->email);
        //                 if ($listuseremail == $moodleuseremail) {
        //                     $moodleuser = $moodleusers[$searchkey];
        //                     break;
        //                 }
        //                 else if ($listuseremail > $moodleuseremail) {
        //                     $minkey = $searchkey + 1;
        //                     $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
        //                 }
        //                 else if ($listuseremail < $moodleuseremail) {
        //                     $maxkey = $searchkey - 1;
        //                     $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
        //                 }
        //         }

        //         // No corresponding moodleuser for the user in mailchimp
        //         if ($moodleuser == false) {
        //             // Do nothing for now
        //             break;
        //         }







        //     // Maybe add some error handling here

        // }

        // Update subscription status info
        cli_heading('Applying interest label and updating subscription status');
        foreach ($listusers['members'] as $listuserkey => $listuser) {
            $statuspercent = round((($listuserkey/$listuserscount) * 100), 1, PHP_ROUND_HALF_UP);
            echo $statuspercent,"%        \r";

            // Search through the mailchimp users to find the user with corresponding email address
            $maxkey = count($moodleusers) - 1;
            $minkey = 0;
            $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);

            $moodleuser = false;
            $listuseremail = strtolower($listuser['email_address']);

            while($minkey <= $maxkey) {
                $moodleuseremail = strtolower($moodleusers[$searchkey]->email);
                if ($listuseremail == $moodleuseremail) {
                    $moodleuser = $moodleusers[$searchkey];
                    break;
                }
                else if ($listuseremail > $moodleuseremail) {
                    $minkey = $searchkey + 1;
                    $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
                }
                else if ($listuseremail < $moodleuseremail) {
                    $maxkey = $searchkey - 1;
                    $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
                }
            }

            // No corresponding moodleuser for the user in mailchimp
            if ($moodleuser == false) {
                // Do nothing for now
                break;
            }

            // Apply interest label to the user in MailChimp (First and last names are updated from Moodle)
            $args['EMAIL'] = strtolower($moodleuser->email);
            $args['FNAME'] = $moodleuser->firstname;
            $args['LNAME'] = $moodleuser->lastname;
            $updatestatus = \block_mailchimp\helper::listUpdateMember($listid, $moodleuser->email, $args, 'html');


            // Get the profile data for this moodleuser
            $moodleuserprofiledata = $this->mc_get_profile_data($moodleuser);

            // Check subscription status in MC
            if ($listuser['status'] == 'unsubscribed' && $moodleuserprofiledata->data == '1') {
                // User is unsubscribed in mailchimp but marked as subscribed in moodle. Mailchimp is the source of truth for this sync. We'll unsubscribe in moodle
                $this->mc_update_profile_subscription($moodleuser, false);
            }
            if ($listuser['status'] == 'subscribed' && $moodleuserprofiledata->data == '0') {
                // User is subscribed in mailchimp but marked as unsubscribed in moodle. Subscribe the user in moodle
                $this->mc_update_profile_subscription($moodleuser, true);
            }
        }
        // New line for next output
        echo 'Done.',"\n";

    }


}

$sync = new interestsynchronize();

cli_heading('Initiating interest sync');
$sync->execute_interestsync();

cli_heading('Initiating user sync');
$sync->execute();

cli_heading('Finished interest sync');
