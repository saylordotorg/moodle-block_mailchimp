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
 * File         Helper.php
 * Encoding     UTF-8
 * 
 **/

namespace block_mailchimp;

/**
 * Helper class for various functionality
 * 
 * @package     block_mailchimp
 *
 * @version     2.7.0
 * @author      Rogier van Dongen :: sebsoft.nl
 * @copyright   2014 Rogier van Dongen :: sebsoft.nl {@link http://www.sebsoft.nl}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * __construct() HIDE: WE'RE STATIC 
     */
    protected function __construct() {
        // Static's only please!
    }

    /**
     * Check if we have permission for this
     * @param type $name
     * @return array | boolean
     */
    final static public function get_permission($name = '') {
        $context = \context_system::instance();
        $array = array();
        // FIRST check if you are a super admin.
        $array['administration'] = (has_capability('blocks/mailchimp:administration', $context)) ? true : false;

        if (!empty($name)) {
            return $array[$name];
        } else {
            return $array;
        }
    }

    /**
     * Make sure editing mode is off and moodle doesn't use complete overview
     * @global \stdClass $USER
     * @global \moodle_page $PAGE
     * @param \moodle_url $redirect
     */
    public static function force_no_editing_mode($redirect = '') {
        global $USER, $PAGE;
        if (!empty($USER->editing)) {
            $USER->editing = 0;
            if (empty($redirect)) {
                $params = $PAGE->url->params();
                $redirect = new \moodle_url($PAGE->url, $params);
            }
            redirect($redirect);
        }
    }

    /**
     * call_api_lists
     * Method to get all the mailing lists from Mailchimp.
     * 
     * @return false in case of an error, or an array of lists
     */
    public static function call_api_lists() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MCAPI.class.php');

        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }

        $api      = new \MCAPI($CFG->block_mailchimp_apicode);
        $apilists = $api->lists();

        if ($api->errorCode) {
            return false;
        }
        if (!count($apilists['data']) > 0) {
            return array(); // Gentle message if its got no lists.
        }

        $listnames = array('' => '');
        foreach ($apilists['data'] as $list) {
            $listnames[$list['id']] = $list['name'];
        }

        return ($listnames);
    }

    /**
     * get all mailchimp subscription fields
     * 
     * @global \moodle_database $DB
     * @return boolean
     */
    public static function get_chipmail_profile_fields() {
        // TODO: this is by far a reliable way of determining mailchimp stats.
        global $DB;

        // Collect all checkbox profile fields.
        $query  = "
            SELECT uif.* FROM {user_info_field} uif
            LEFT JOIN {user_info_category} uic
                ON uif.categoryid = uic.id
            WHERE uif.datatype = 'checkbox'";
        $mailchimpfields = $DB->get_records_sql($query);
        if (!count($mailchimpfields) > 0) {
            return false;
        }

        // Build array of the records.
        $fields = array('' => '');
        foreach ($mailchimpfields as $mailchimpfield) {
            $fields[$mailchimpfield->id] = $mailchimpfield->name;
        }
        return $fields;
    }

    /**
     * Determine if current user is a mailchimp registree
     * 
     * @global \moodle_database $DB
     * @global \stdClass $USER
     * @param int $linkedprofilefield
     * @return bool
     */
    public static function is_mailchimp_registered_user($linkedprofilefield) {
        global $DB, $USER;

        $query = "
            SELECT * FROM {block_mailchimp_users} mu
            WHERE mu.userid = ?
            AND (
                mu.registered = 1 OR mu.userid IN (
                    SELECT userid FROM {user_info_data} uid
                    WHERE uid.userid = mu.userid
                    AND uid.fieldid = ?
                    AND uid.data = 1
                )
            )";

        return ($DB->get_records_sql($query, array($USER->id, $linkedprofilefield))) ? true : false;
    }

}
