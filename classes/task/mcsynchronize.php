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
 * File         mcsynchronize.php
 * Encoding     UTF-8
 */

namespace block_mailchimp\task;

/**
 * Task implementation to synchronize MailChimp mailinglist subscriptions
 * 
 * @package     block_mailchimp
 *
 * @version     3.0.0
 * @author      John Azinheira
 * @copyright   2015 Saylor Academy {@link http://www.saylor.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @version     2.7.0
 * @author      Rogier van Dongen :: sebsoft.nl
 * @copyright   2014 Rogier van Dongen :: sebsoft.nl {@link http://www.sebsoft.nl}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * */
class mcsynchronize extends \core\task\scheduled_task {

    /**
     * Get human-friendly task name
     * 
     * @return string
     */
    public function get_name() {
        return get_string('task:mcsynchronize', 'block_mailchimp');
    }

    /**
     * Execute task
     * 
     * @global \moodle_database $DB
     * @global \stdClass $CFG
     * @return void
     * @throws \moodle_exception
     */
    public function execute() {
        global $DB, $CFG;

        // Not going to work if we're missing settings.
        if (!isset($CFG->block_mailchimp_apicode) ||
                !isset($CFG->block_mailchimp_listid) ||
                !isset($CFG->block_mailchimp_linked_profile_field)) {
            return;
        }

        echo '== Beginning synchronization of MailChimp subscribers ==', "\n";

        // Get all users in MailChimp and synchronize.
        echo 'Getting list of users in MailChimp.', "\n";
        $listusers = \block_mailchimp\helper::getMembersSync();
        if (!$listusers) {
            debugging("ERROR: Failed to get list of all members. Unable to synchronize users.");
            return;
        }

        // If there is an interest specified, filter out users who do not have this interest marked.
        if (isset($CFG->block_mailchimp_interest) && (!$CFG->block_mailchimp_interest == "0")) {
            foreach ($listusers['members'] as $key => $externaluser) {
                if ($externaluser['interests'][$CFG->block_mailchimp_interest] == false) {
                    unset($listusers['members'][$key]);
                }
            }
            // Reindex the array
            $listusers['members'] = array_values($listusers['members']);

        }

        $listuserscount = count($listusers['members']);

        // Get list of users in Moodle
        echo 'Getting list of users in Moodle.', "\n";
        $moodleusers = $DB->get_records('user');

        $moodleuserscount = count($moodleusers);

        // Convert Moodle email addresses to lower case. Mailchimp stores emails in lower case and calculates the MD5 hash on the lower case email.
        foreach ($moodleusers as $moodleuser) {
             $moodleuser->email = strtolower($moodleuser->email);
        }

        // Sort MailChimp users list
        echo 'Sorting list of MailChimp users.', "\n";
        foreach ($listusers['members'] as $key => $row) {
            $emails[$key] = $row['email_address'];
        }
        array_multisort($emails, SORT_ASC, $listusers['members']);
        unset($emails);

        // Sort Moodle users list
        echo 'Sorting list of Moodle users.', "\n";
        foreach ($moodleusers as $key => $row) {
                $emails[$key] = $row->email;
        }
        array_multisort($emails, SORT_ASC, $moodleusers);
        unset($emails);

        // Syncronize the list of users in Moodle with those in Mailchimp
        echo '== Starting sync of Moodle users with users in MailChimp ==', "\n";
        foreach ($moodleusers as $moodleusersynckey => $moodleuser) {
            $statuspercent = round((($moodleusersynckey/$moodleuserscount) * 100), 1, PHP_ROUND_HALF_UP);
            echo $statuspercent, "%        \r";
            if (isguestuser($moodleuser)) {
                continue;
            }
            $this->synchronize_user($moodleuser, $listusers);
        }
        echo 'Done.', "\n";

        //Iterate through mailchimp list and compare to moodle users' emails. If the email is not found in moodle, delete from mailchimp list.
        echo'== Starting MailChimp list cleanup ==', "\n";
        foreach ($listusers['members'] as $listuserskey => $externaluser) {
            $statuspercent = round((($listuserskey/$listuserscount) * 100), 1, PHP_ROUND_HALF_UP);
            echo $statuspercent, "%        \r";
            $this->synchronize_mcuser($externaluser, $moodleusers);
        }
        echo 'Done.', "\n";

        echo '== Finished MailChimp syncronization ==', "\n";

        // Clean up static caches, since this process runs for a long time and (potentially) changes many DB records. See https://docs.moodle.org/dev/Task_API#Caches
        \core\task\manager::clear_static_caches();
    }

    /**
    * Synchronize an account in Mailchimp with users in moodle.
    * Do this to make sure there aren't extra addresses in Mailchimp and that the list matches users in moodle.
    * 
    * @param $externaluser Mailchimp user
    * @param $moodleusers Array of all Moodle users
    *
    */
    private function synchronize_mcuser($externaluser, $moodleusers) {

        // Search for the external email address in list of users
        $emailmatch = $this->synchronize_mcuser_ispresent($externaluser['email_address'], $moodleusers);

        if ($emailmatch == FALSE) {
            // No match was found. Delete the email from mailchimp.
            if(!\block_mailchimp\helper::listDelete($externaluser['email_address'])) {
                debugging("ERROR: Could not remove user ".$externaluser['email_address']." from the MailChimp list.");
            }
            else {
                echo("MSG: Removed ".$externaluser['email_address']." from the MailChimp list.\n");
            }
        }

        // Do nothing if $emailmatch = TRUE and the address was found. User should be synced at this point.
    }

    /**
    * Determines if a specified member is present in the supplied user list (in the 'members' array). The list must be sorted.
    *
    * @param $query The member to search for (by email address)
    * @param $memberlist The list to search through
    * @return TRUE when the email is found in the list, FALSE if not found
    *
    **/
    private function synchronize_mcuser_ispresent($query, $memberlist) {
    $maxkey = count($memberlist) - 1;
    $minkey = 0;
    $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);

    while($minkey <= $maxkey) {
        $listemail = $memberlist[$searchkey]->email;
        if ($query == $listemail) {
            return TRUE;
        }
        else if ($query > $listemail) {
            $minkey = $searchkey + 1;
            $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
        }
        else if ($query < $listemail) {
            $maxkey = $searchkey - 1;
            $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
        }
    }

    Return FALSE;
}

    /**
     * Synchronise Mailchimp account/subscription for single moodle user
     * 
     * @param \stdClass record from user table $moodleuser
     * 
     */
    private function synchronize_user($moodleuser, $listusers) {
        global $CFG, $DB;

        // First collect appropriate data.
        $mailchimpprofiledata = $this->mc_get_profile_data($moodleuser);
        $mailchimpinternaluser = $this->mc_get_internal_user($moodleuser);

        $externaluservars = array(
            'EMAIL' => $moodleuser->email,
            'FNAME' => $moodleuser->firstname,
            'LNAME' => $moodleuser->lastname
        );

        // We might want to update the email.
        if ($mailchimpinternaluser->email != $moodleuser->email) {
            $this->mc_update_email_internal($moodleuser);
        }

        // Load external mailchimp userdata.
        $listmemberinfo = \block_mailchimp\helper::listMemberInfoSync($mailchimpinternaluser->email, $listusers);
        // In case of an error, the external user does not yet exist.
        if (!$listmemberinfo) {
            $externaluserregistered = false;
        }
        else {
            $externaluserregistered = true;
        }


        // If there's no subscription and we have no external user, abandon.
        // External: NA
        if (!$externaluserregistered) { 
            if ($mailchimpprofiledata->data == '0') {
                // Add user to the mailchimp list, just as unsubscribed (we want the list to match moodle users)
                // Internal: U

                \block_mailchimp\helper::listUnsubscribe($CFG->block_mailchimp_listid, $moodleuser->email, $externaluservars, 'html', $listusers);
            } else if ($mailchimpprofiledata->data == '1') {
                // If there's no external user but a profile setting to subscribe, handle it.
                // Internal: S
                \block_mailchimp\helper::listSubscribe($CFG->block_mailchimp_listid, $moodleuser->email, $externaluservars, 'html', $listusers);
                // Update the internal status if for some reason not synced
                if (!(bool)$mailchimpinternaluser->registered) {
                    $this->mc_update_subscription_internal($moodleuser, true);
                }
            }


        }
        // External: S/U
        else if ($externaluserregistered === true) {
            $externaluserinfo = $listmemberinfo;
            // User is externally known.
            if ($externaluserinfo['status'] == 'pending') {
                // We do absolutely nothing while statusses are pending.
                return;


            } else if ($externaluserinfo['status'] == 'unsubscribed') {
                // Handle unsubscription sync.
                // External: U

                $comparison = $this->compareModified($mailchimpinternaluser->timemodified, $externaluserinfo['last_changed']);

                if (!$comparison) {
                    // Error in comparison. Do something clever
                }
                else if ((int)$comparison == 1) {
                    // Internal subscription status is newer, change mailchimp subscription status if needed.
                    if ($mailchimpprofiledata->data == '1') {
                        // Internal: S
                        // Subscribe the user in mailchimp
                        \block_mailchimp\helper::listSubscribe($CFG->block_mailchimp_listid, $moodleuser->email, $externaluservars, 'html', $listusers);
                    }
                    // If data == 0, internal and external subscription status match.
                }
                else if ((int)$comparison == 2) {
                    // External subscription status is newer, change the internal status to unsubscribed if needed.
                    if ($mailchimpprofiledata->data == '1') {
                        // Internal: S
                        // Unsubscribe the user in Moodle since MailChimp is last modified.
                        $this->mc_update_profile_subscription($moodleuser, false);
                        $this->mc_update_subscription_internal($moodleuser, false);
                    }
                }


            } else if ($externaluserinfo['status'] == 'subscribed') {
                // Handle subscription sync.
                // External: S

                $comparison = $this->compareModified($mailchimpinternaluser->timemodified, $externaluserinfo['last_changed']);

                if (!$comparison) {
                    // Error in comparison. Do something clever. Get a fez.

                }
                else if ((int)$comparison == 1) {
                    // Internal subscription status is newer, change mailchimp subscription status if needed.
                    if ($mailchimpprofiledata->data == '0') {
                        // Internal: U
                        // Unsubscribe the user from mailchimp
                        \block_mailchimp\helper::listUnsubscribe($CFG->block_mailchimp_listid, $moodleuser->email, $externaluservars, 'html', $listusers);
                    }
                    // If data == 1, internal and external subscription status match.
                }
                else if ((int)$comparison == 2) {
                    // External subscription status is newer, change the internal status to subscribed if needed.
                    if ($mailchimpprofiledata->data == '0') {
                        // Internal: U
                        // Subscribe the user in Moodle since MailChimp is last modified.
                        $this->mc_update_profile_subscription($moodleuser, true);
                        $this->mc_update_subscription_internal($moodleuser, true);
                    }
                }
            } else if ($externaluserinfo['status'] == 'cleaned') {
                // No idea what to do here.
            }
        }
    }

    /**
    * Compares the internal timestamp to the external date string obtained from MailChimp
    *
    * @param string $internaltimestamp - the unix timestamp stored internally
    * @param string $externaldate - the date string returned from MailChimp as the 'last_changed' field for the user
    *
    * @return 1 if the internal timestamp is newer, 2 if the external date is newer, false on error
    */
    protected function compareModified($internaltimestamp, $externaldate) {
        // Mailchimp reports timestrings in GMT
        $date = new \DateTime($externaldate, new \DateTimeZone('GMT'));
        $externaltimestamp = $date->format('U');

        if ((int)$internaltimestamp > (int)$externaltimestamp) {
            return 1;
        }
        else if ((int)$externaltimestamp >= (int)$internaltimestamp) {
            return 2;
        }

        //Something went wrong with the comparison
        debugging("ERROR: Failed to compare last modified timestamps during synchronization.");
        return false;
    }

    /**
     * Get internal mailchimp user record (or create if not exists)
     * 
     * @global \moodle_database $DB
     * @param \stdClass $user moodle user object
     * @return \stdClass internal mailchimp user object
     */
    protected function mc_get_internal_user($user) {
        global $DB;

        $mcuser = $DB->get_record('block_mailchimp_users', array('userid' => $user->id));
        if (!empty($mcuser)) {
            return $mcuser;
        }

        // Get subscription status from the profile field.
        $mcuserprofile = $this->mc_get_profile_data($user);

        // Create mailchimp_user.
        $mcuser               = new \stdClass();
        $mcuser->userid       = $user->id;
        $mcuser->email        = $user->email;
        $mcuser->registered   = $mcuserprofile->data;
        $mcuser->timecreated  = time();
        $mcuser->timemodified = time();
        $mcuser->id           = $DB->insert_record('block_mailchimp_users', $mcuser, true);

        unset($mcuserprofile);
        return $mcuser;
    }

    /**
     * Get mailchimp subscription status user profile record (or create if not exists)
     * 
     * @global \moodle_database $DB
     * @param \stdClass $user moodle user object
     * @return \stdClass record from user_info_data table
     */
    protected function mc_get_profile_data($user) {
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
    protected function mc_update_profile_subscription($user, $registered) {
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
    protected function mc_update_subscription_internal($user, $registered) {
        global $DB;

        $mcuser = $this->mc_get_internal_user($user);
        $mcuser->registered = ($registered ? 1 : 0);
        $mcuser->timemodified = time();
        return $DB->update_record('block_mailchimp_users', $mcuser);
    }

    /**
     * Updates registration status on internal mailchimp user
     * 
     * @global \moodle_database $DB
     * @param \stdClass $user moodle user object
     * @param bool $registered registration status
     * @return bool 
     */
    protected function mc_update_email_internal($user) {
        global $DB;

        $mcuser = $this->mc_get_internal_user($user);
        $mcuser->email = $user->email;
        $mcuser->timemodified = time();
        return $DB->update_record('block_mailchimp_users', $mcuser);
    }

}