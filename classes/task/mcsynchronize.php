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

        // Get all users in moodle and synchronize.
        // TODO: This should really be revised, as this is possibly highly intensive.
        // Mailchimp handles timestamps in GMT, so for conversion purposes we need to set our timezone to that.
        $tz = date_default_timezone_get();
        date_default_timezone_set('GMT');
        $moodleusers = $DB->get_records('user');
        foreach ($moodleusers as $moodleuser) {
            if (isguestuser($moodleuser)) {
                continue;
            }
            $this->synchronize_user($moodleuser);
        }
        // Restore timezone.
        date_default_timezone_set($tz);
    }

    /**
     * Synchronise Mailchimp account/subscription for single moodle user
     * 
     * @param \stdClass record from user table $moodleuser
     * 
     */
    private function synchronize_user($moodleuser) {
        global $CFG, $DB;

        // First collect appropriate data.
        $mailchimpinternaluser = $this->mc_get_internal_user($moodleuser);
        $mailchimpprofiledata = $this->mc_get_profile_data($moodleuser);
        // Load external mailchimp userdata.
        $listmemberinfo = \block_mailchimp\helper::listMemberInfo($CFG->block_mailchimp_listid, $mailchimpinternaluser->email);
        // In case of an error, the external user does not yet exist.
        if (!$listmemberinfo) {
            $externaluserregapistered = false;
        }
        else {
            $externaluserregistered = true;
        }
        // If there's no subscription and we have no external user, abandon.
        if (!$externaluserregistered) {
            if ($mailchimpprofiledata->data == '0') {
                // Synchronize internal user for profile setting?
                if ((bool)$mailchimpinternaluser->registered) {
                    $this->mc_update_subscription_internal($moodleuser, false);
                }
            } else if ((bool)$mailchimpinternaluser->registered) {
                // If there IS an internal subscription status but no external user, delete this status.
                $this->mc_update_profile_subscription($moodleuser, false);
                $this->mc_update_subscription_internal($moodleuser, false);
            } else if ((bool)$mailchimpinternaluser->registered && $mailchimpprofiledata->data == '1') {
                // If there's no external user, no internal status, but a profile setting to subscribe, handle it.
                $externaluservars = array(
                    'EMAIL' => $moodleuser->email,
                    'FNAME' => $moodleuser->firstname,
                    'LNAME' => $moodleuser->lastname
                );
                \block_mailchimp\helper::listSubscribe($CFG->block_mailchimp_listid, $moodleuser->email, $externaluservars, 'html');
                $this->mc_update_subscription_internal($moodleuser, true);
            }

            // We might want to update the email.
            if ($mailchimpinternaluser->email != $moodleuser->email) {
                $this->mc_update_email_internal($moodleuser);
            }
        } else if ($externaluserregistered === true) {
            $externaluserinfo = $listmemberinfo;
            // User is externally known.
            if ($externaluserinfo['status'] == 'pending') {
                // We do absolutely nothing while statusses are pending.
                return;
            } else if ($externaluserinfo['status'] == 'unsubscribed') {
                // Handle unsubscription sync.
                $this->mc_handle_externally_unsubscribed($externaluserinfo, $moodleuser,
                        $mailchimpinternaluser, $mailchimpprofiledata);
            } else if ($externaluserinfo['status'] == 'subscribed') {
                // Handle subscription sync.
                $this->mc_handle_externally_subscribed($externaluserinfo, $moodleuser,
                        $mailchimpinternaluser, $mailchimpprofiledata);
            } else if ($externaluserinfo['status'] == 'cleaned') {
                // No idea what to do here.
            }
        }
    }

    /**
     * Handles the case where the externally known Mailchimp account has the status of unsubscribed
     * 
     * @global type $CFG
     * @param type $externaluserinfo
     * @param type $moodleuser
     * @param type $mailchimpinternaluser
     * @param type $mailchimpprofiledata
     *
     */
    private function mc_handle_externally_unsubscribed($externaluserinfo, $moodleuser,
            $mailchimpinternaluser, $mailchimpprofiledata) {
        global $CFG;

        $timestamp = strtotime($externaluserinfo['timestamp']);
        if ((int)$timestamp >= $mailchimpinternaluser->timemodified) {
            // We should synchronize from mailchimp to moodle.
            if ((bool)$mailchimpinternaluser->registered) {
                $this->mc_update_subscription_internal($moodleuser, false);
            }
            if ($mailchimpprofiledata->data == '1') {
                $this->mc_update_profile_subscription($moodleuser, false);
            }
        } else {
            // We should synchronize moodle to mailchimp.
            if ($mailchimpprofiledata->data == '1') {
                $this->mc_update_subscription_internal($moodleuser, true);
                $externaluservars      = array(
                    'EMAIL' => $moodleuser->email,
                    'FNAME' => $moodleuser->firstname,
                    'LNAME' => $moodleuser->lastname
                );
                \block_mailchimp\helper::listSubscribe($CFG->block_mailchimp_listid, $mailchimpinternaluser->email, $externaluservars, 'html');
                // We may want to update the internal email.
                if ($moodleuser->email != $mailchimpinternaluser->email) {
                    $this->mc_update_email_internal($user);
                }
            } else if ($mailchimpprofiledata->data == '0') {
                $this->mc_update_subscription_internal($moodleuser, false);
                // Do we need to process a change in email address?
                if ($moodleuser->email != $mailchimpinternaluser->email) {
                    $externaluservars      = array(
                        'EMAIL' => $moodleuser->email,
                        'FNAME' => $moodleuser->firstname,
                        'LNAME' => $moodleuser->lastname
                    );
                    \block_mailchimp\helper::listUpdateMember($CFG->block_mailchimp_listid, $mailchimpinternaluser->email, $externaluservars, 'html');
                    $this->mc_update_email_internal($user);
                }
            }
        }
    }

    /**
     * Handles the case where the externally known Mailchimp account has the status of subscribed
     * 
     * @global type $CFG
     * @param type $externaluserinfo
     * @param type $moodleuser
     * @param type $mailchimpinternaluser
     * @param type $mailchimpprofiledata
     * 
     */
    private function mc_handle_externally_subscribed($externaluserinfo, $moodleuser,
            $mailchimpinternaluser, $mailchimpprofiledata) {
        global $CFG;

        $timestamp = strtotime($externaluserinfo['timestamp']);
        if ((int)$timestamp >= $mailchimpinternaluser->timemodified) {
            // We should synchronize from mailchimp to moodle.
            if (!(bool)$mailchimpinternaluser->registered) {
                $this->mc_update_subscription_internal($moodleuser, true);
            }
            if ($mailchimpprofiledata->data == '0') {
                $this->mc_update_profile_subscription($moodleuser, true);
            }
        } else {
            // We should synchronize moodle to mailchimp.
            if ($mailchimpprofiledata->data == '0') {
                $this->mc_update_subscription_internal($moodleuser, false);
                // We may want to update the internal email.
                if ($moodleuser->email != $mailchimpinternaluser->email) {
                    $this->mc_update_email_internal($moodleuser);
                    $externaluservars      = array(
                        'EMAIL' => $moodleuser->email,
                        'FNAME' => $moodleuser->firstname,
                        'LNAME' => $moodleuser->lastname
                    );
                    \block_mailchimp\helper::listUpdateMember($CFG->block_mailchimp_listid, $mailchimpinternaluser->email, $externalmergevars, 'html');
                }
                // Unsubscribe from mailchimp.
                \block_mailchimp\helper::listUnsubscribe($CFG->block_mailchimp_listid, $moodleuser->email);
            } else if ($mailchimpprofiledata->data == '1') {
                $this->mc_update_subscription_internal($moodleuser, true);
                // Do we need to process a change in email address?
                if ($moodleuser->email != $mailchimpinternaluser->email) {
                    $externaluservars      = array(
                        'EMAIL' => $moodleuser->email,
                        'FNAME' => $moodleuser->firstname,
                        'LNAME' => $moodleuser->lastname
                    );
                    \block_mailchimp\helper::listUpdateMember($CFG->block_mailchimp_listid, $mailchimpinternaluser->email, $externaluservars, 'html');
                    $this->mc_update_email_internal($moodleuser);
                }
            }
        }
    }

    /**
     * Get internal mailchimp user record (or create if not exists)
     * 
     * @global \moodle_database $DB
     * @param \stdClass $user moodle user object
     * @return \stdClass internal mailchimp user object
     */
    private function mc_get_internal_user($user) {
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
    private function mc_get_profile_data($user) {
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
    private function mc_update_profile_subscription($user, $registered) {
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
    private function mc_update_subscription_internal($user, $registered) {
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
    private function mc_update_email_internal($user) {
        global $DB;

        $mcuser = $this->mc_get_internal_user($user);
        $mcuser->email = $user->email;
        $mcuser->timemodified = time();
        return $DB->update_record('block_mailchimp_users', $mcuser);
    }

}