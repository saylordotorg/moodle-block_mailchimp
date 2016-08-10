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
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');

        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }
        $apilists = helper::lists();
        if (!$apilists) {
            return false;
        }

        if (!count($apilists['lists']) > 0) {
            return array(); // Gentle message if its got no lists.
        }

        $listnames = array('' => '');
        foreach ($apilists['lists'] as $list) {
            $listnames[$list['id']] = $list['name'];
        }

        return ($listnames);
    }

    /**
     * lists
     * Method to list all campaigns from API wrapper.
     * 
     * @return false in case of an error, or an array of lists
     */
    public static function lists($sort_field='date_created', $sort_dir='DESC') {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }

        $method = "lists/"."?sort_field=".$sort_field."&sort_dir=".$sort_dir;

        if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
            debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
            return false;
        }
        $apilists = $api->get($method);

        if (!$apilists) {
            debugging("ERROR: Unable to get lists from mailchimp, method: ".$method);
            return false;
        }

        return $apilists;
    }

    /**
     * call_interests 
     * Method to get all the interests from Mailchimp.
     * 
     * @return false in case of an error, or an array of interests
     */
    public static function call_interests() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');

        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }
        if (!isset($CFG->block_mailchimp_listid)) {
            return false; //The list needs to be set first.
        }

        $interests = helper::interests();

        if (!$interests) {
            return false;
        }

        if (!count($interests) > 0) {
            return array(); // Gentle message if there are no interests.
        }

        //$interests[id][name]; $interests[id][categoryid];

        return $interests;
    }

    //interests()
    /**
     * lists
     * Method to grab and format list of interests present in the MailChimp list.
     * 
     * @return false in case of an error, or an array of interests
     */
    public static function interests() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode) || !isset($CFG->block_mailchimp_listid)) {
            return false;
        }
        $listid = $CFG->block_mailchimp_listid;

        $interest = array('' => '');

        // First, get interest categories, then query each category to get specific interests.

        // Get Categories
        $method = "lists/".$listid."/interest-categories";
        if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
            debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
            return false;
        }
        $interestcategories = $api->get($method);

        if (!$interestcategories || !isset($interestcategories['categories'])) {
            debugging("ERROR: Unable to get interest categories from mailchimp, method: ".$method);
            return false;
        }

        // Get Interests

        $interests[] = "";

        foreach ($interestcategories['categories'] as $category) {
            $method = "lists/".$listid."/interest-categories/".$category['id']."/interests";
            if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
                debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
            return false;
            }
            $interestresponse = $api->get($method);

            if (!$interestresponse) {
                debugging("ERROR: Unable to get interests from interest-category ".$category['title']." from mailchimp, method: ".$method);
                return false;
            }

            foreach ($interestresponse['interests'] as $interest) {
                $interests[$interest['id']] = $interest['name'];
            } 
        }

        return $interests;
    }

    /**
    * listDelete
    * Delete a user from the list.
    *
    * @param $email_address the email address to delete
    *
    */

    public static function listDelete($email_address) {
        global $CFG;
        $listid = $CFG->block_mailchimp_listid;

        //First obtain the memberID for the email address.
        $memberid = helper::getMemberID($listid, $email_address);

        $method = "lists/".$listid."/members/".$memberid;

        if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
           debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
           return false;
        }

       $result = $api->delete($method);
        if ($result !== false) {
            debugging("ERROR: Unable to remove user ".$email_address." from mailchimp, method: ".$method."\nResult: ".print_r($result)."\n");
        return false;
        }

        return true;

    }

    /**
     * listSubscribe
     * Subscribe user to the list.
     * 
     * @param listid is the id for the list
     * @param email_address is the email address to subscribe
     * @param externaluserargs is an array containing the arguments to pass to mailchimp.
     * @param email_type is the type of email to send the user
     * @param memberlist (optional) if not set, the memberlist is retreived from mailchimp
     * @return false in case of an error
     */
    public static function listSubscribe($listid, $email_address, $externaluserargs, $email_type='html', $memberlist=null) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }
        if (isset($CFG->block_mailchimp_interest) && (!$CFG->block_mailchimp_interest == "0")) {
            //If interest is specified in the config, be sure to add that when subscribing users.
            $interest = $CFG->block_mailchimp_interest;
        }

        if (!$memberid = helper::getMemberID($listid, $email_address, $memberlist)) {
            //If member is not already present in the list, add them to the list.
            $method = "lists/".$listid."/members";


            if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
               debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
               return false;
            }

            $args = array(
             'email_address' => strtolower($email_address),
                'email_type' => $email_type,
                'status' => 'subscribed',
                'VIP' => false,
                'merge_fields' => array(
                    'FNAME' => $externaluserargs['FNAME'],
                    'LNAME' => $externaluserargs['LNAME']
                )
            );

            if (isset($interest)) {
                $args['interests'][$interest] = true;
            }


            if (!$api->post($method, $args)) {
                debugging("ERROR: Unable to subscribe user ".$email_address." to mailchimp, method: ".$method);
                return false;
            }
        }
        if ($memberid) {
            //There is already a member with this email address in the list, we must update their information to subscribe.
            $method = "lists/".$listid."/members/".$memberid;

            if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
                debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
                return false;
            }

            $args = array(
                'email_type' => 'html',
                'status' => 'subscribed'
            );

            if (isset($interest)) {
                $args['interest'][$interest] = true;
            }


            if (!$api->patch($method, $args)) {
                debugging("ERROR: Unable to update mailchimp user ".$email_address." status to subscribed, method: ".$method);
                return false;
            }
        }

        return true;
    }

    /**
     * listUpdateMember
     * Update the user's information.
     * 
     * @param listid is the id for the list
     * @param email_address is the email address subscribed in mailchimp
     * @param externaluserargs is an array containing the arguments to pass to mailchimp
     *              EMAIL contains the email address to update the user to.
     * @param email_type is the type of email to send the user
     * @return false in case of an error
     */
    public static function listUpdateMember($listid, $email_address, $externaluserargs, $email_type='html') {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }
        if (isset($CFG->block_mailchimp_interest) && (!$CFG->block_mailchimp_interest == "0")) {
            //If interest is specified in the config, be sure to add that when updating users.
            $interest = $CFG->block_mailchimp_interest;
        }
        if (!$memberid = helper::getMemberID($listid, $email_address)) {
            debugging("ERROR: Unable to list member info for ".$email_address);
            return false;
        }

        $method = "lists/".$listid."/members/".$memberid;

        if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
            debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
            return false;
        }

        $args = array(
            'email_address' => strtolower($externaluserargs['EMAIL']),
            'email_type' => $email_type,
            'merge_fields' => array(
                'FNAME' => $externaluserargs['FNAME'],
                'LNAME' => $externaluserargs['LNAME']
            )
        );

        if (isset($interest)) {
            $args['interests'][$interest] = true;
        }


        if (!$api->patch($method, $args)) {
            debugging("ERROR: Unable to update mailchimp user ".$email_address." to ".$externaluserargs['EMAIL'].", method: ".$method);
            return false;
        }

        return true;
    }

    /**
     * listUnsubscribe
     * Unsubscribe the user from the mailing list. Does not remove the user, just sets status to unsubscribed.
     * 
     * @param listid is the id for the list
     * @param email_address is the email address subscribed in mailchimp
     * @return false in case of an error
     */
    public static function listUnsubscribe($listid, $email_address, $externaluserargs=null, $email_type='html', $memberlist=null) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }
        $memberid = helper::getMemberID($listid, $email_address, $memberlist);
        if (!$memberid) {
            //If member is not already present in the list, add them to the list with an unsubscribed status.
            $method = "lists/".$listid."/members";

            if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
               debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
               return false;
            }

            $args = array(
                'email_address' => strtolower($email_address),
                'email_type' => $email_type,
                'status' => 'unsubscribed',
                'VIP' => false,
                'merge_fields' => array(
                    'FNAME' => $externaluserargs['FNAME'],
                    'LNAME' => $externaluserargs['LNAME']
                )
            );
            if (!$api->post($method, $args)) {
                debugging("ERROR: Unable to add unsubscribed user ".$email_address." to mailchimp, method: ".$method);
                return false;
            }
            return true;
        }            

        // Member is present in the list, update the status to unsubscribed.
        $method = "lists/".$listid."/members/".$memberid;

        if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
            debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
            return false;
        }

        $args = array(
            'email_address' => strtolower($email_address),
            'status' => 'unsubscribed'
        );


        if (!$api->patch($method, $args)) {
            debugging("ERROR: Unable to unsubscribe mailchimp user ".$email_address.", method: ".$method);
            return false;
        }

        return true;
    }

     /**
     * getMemberID
     * Method to retrieve the ID of a MC user from an email address.
     * 
     * @param listid is id for campaign list
     * @param email_address is email address of user get ID for
     * @param memberlist (optional) if not set, the memberlist is retreived from mailchimp
     * @return false in case of an error, or an ID number.
     */

    public static function getMemberID($listid, $email_address, $memberlist=null) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode) | !isset($CFG->block_mailchimp_listid)) {
            debugging("ERROR: API key or Campaign list is not set.");
            return false;
        }
        $email_address = strtolower($email_address);
        if ($memberlist === null) { //memberlist is not supplied. Calculate MD5 hash (the user's ID) and ping MailChimp
            $email_address_md5 = md5($email_address);
            $method = "lists/".$CFG->block_mailchimp_listid."/members".$email_address_md5;
            $args['fields'] = "members.id,members.email_address";

            if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
                debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
                return false;
            }
            if (!$api->get($method, $args)) {
                //User is not present
                return false;
            }
            else {
                return $email_address_md5;
            }
        }


        if (!$memberlist === null) {

            if (!count($memberlist['members']) > 0) {
                debugging("ERROR: No members present in the supplied members list.");
                return false;
            }
            // Iterate through the supplied list and match the email address
            $maxkey = count($memberlist['members']) - 1;
            $minkey = 0;
            $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);

            while($minkey <= $maxkey) {
                $listemail = strtolower($memberlist['members'][$searchkey]['email_address']);
                if ($email_address == $listemail) {
                    return $memberlist['members'][$searchkey]['id'];
                }
                else if ($email_address > $listemail) {
                    $minkey = $searchkey + 1;
                    $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
                }
                else if ($email_address < $listemail) {
                    $maxkey = $searchkey - 1;
                    $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
                }
                else {
                    Debugging("SUPER ERROR\n");
                }
            }

            return false;
        }
        // Something really funky must have happened.
        return false;
    }

    /**
     * listMemberInfo
     * Get all the information for particular members of a list
     * 
     * @param string $id the list id to connect to. Get by calling lists()
     * @param array $email_address an array of up to 50 email addresses to get information for OR the "id"(s) for the member returned from listMembers, Webhooks, and Campaigns. For backwards compatibility, if a string is passed, it will be treated as an array with a single element (will not work with XML-RPC).
     * @return array array of list members with their info in an array (see Returned Fields for details)
                int success the number of subscribers successfully found on the list
                int errors the number of subscribers who were not found on the list
                an array of arrays where each one has member info:
                    string id The unique id for this email address on an account
                    string email The email address associated with this record
                    string email_type The type of emails this customer asked to get: html, text, or mobile
                    array merges An associative array of all the merge tags and the data for those tags for this email address. <em>Note</em>: Interest Groups are returned as comma delimited strings - if a group name contains a comma, it will be escaped with a backslash. ie, "," =&gt; "\,". Groupings will be returned with their "id" and "name" as well as a "groups" field formatted just like Interest Groups
                    string status The subscription status for this email address, either pending, subscribed, unsubscribed, or cleaned
                    string ip_signup IP Address this address signed up from. This may be blank if single optin is used.
                    string timestamp_signup The date/time the double optin was initiated. This may be blank if single optin is used.
                    string ip_opt IP Address this address opted in from.
                    string timestamp_opt The date/time the optin completed
                    int member_rating the rating of the subscriber. This will be 1 - 5 as described <a href="http://eepurl.com/f-2P" target="_blank">here</a>
                    string campaign_id If the user is unsubscribed and they unsubscribed from a specific campaign, that campaign_id will be listed, otherwise this is not returned.
                    array lists An associative array of the other lists this member belongs to - the key is the list id and the value is their status in that list.
                    string timestamp The date/time this email address entered it's current status
                    string info_changed The last time this record was changed. If the record is old enough, this may be blank.
                    int web_id The Member id used in our web app, allows you to create a link directly to it
                    bool is_gmonkey Whether the member is a <a href="http://mailchimp.com/features/golden-monkeys/" target="_blank">Golden Monkey</a> or not.
                    array geo the geographic information if we have it. including:
                        string latitude the latitude
                        string longitude the longitude
                        string gmtoff GMT offset
                        string dstoff GMT offset during daylight savings (if DST not observered, will be same as gmtoff
                        string timezone the timezone we've place them in
                        string cc 2 digit ISO-3166 country code
                        string region generally state, province, or similar
                    array clients the client we've tracked the address as using with two keys:
                        string name the common name of the client
                        string icon_url a url representing a path to an icon representing this client
                    array static_segments static segments the member is a part of including:
                        int id the segment id
                        string name the name given to the segment
                        string added the date the member was added

                    false on error.
     */
    public static function listMemberInfo($listid, $email_address) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }

        $args['fields'] = "members.id,members.email_address,total_items,members.email_type,members.status,members.last_changed,members.merge_fields,total_items";

        if (!$memberid = helper::getMemberID($listid, $email_address)) {
            //debugging("ERROR: Unable to list member id for ".$email_address".");
            //Generally, we get debug messages in getMemberID() anyway.
            return false;
        }

        //TODO: Get the member info from getMemberID, since it's getting the info as part of the user check.

        $method = "lists/".$listid."/members/".$memberid;

        if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
            debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
            return false;
        }
        $memberinfo = $api->get($method, $args);

        return $memberinfo;
    }

    /**
     * listMemberInfoSync
     * Get all the information for a specific member of the list. Used during synchronization.
     * 
     * @param string $email_address the email address to get information for.
     * @param array $memberlist an array containing all members and associated info in the MC list.
     * @return array $memberinfo
     *      an array with the member info of the specific user, returns false on error or if there is no match in the list.
     */
    public static function listMemberInfoSync($email_address, $memberlist) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode)) {
            return false;
        }

        $memberinfo = false;
        $email = strtolower($email_address);

        $maxkey = count($memberlist['members']) - 1;
        $minkey = 0;
        $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);

        while($minkey <= $maxkey) {
            $listemail = strtolower($memberlist['members'][$searchkey]['email_address']);
            if ($email == $listemail) {
                return $memberlist['members'][$searchkey];
            }
            else if ($email > $listemail) {
                $minkey = $searchkey + 1;
                $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
            }
            else if ($email < $listemail) {
                $maxkey = $searchkey - 1;
                $searchkey = round((($maxkey + $minkey)/2), 0, PHP_ROUND_HALF_UP);
            }
            else {
                Debugging("SUPER ERROR\n");
            }
        }

        return false;
    }
     /**
     * getMembersSync
     * Method to retrieve all members from MC list.
     * 
     * @return false in case of an error, or an array of members and info.
     */

    public static function getMembersSync() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/mailchimp/classes/MailChimp.php');
        if (!isset($CFG->block_mailchimp_apicode) | !isset($CFG->block_mailchimp_listid)) {
            debugging("ERROR: API key or mailing list is not set.");
            return false;
        }

        $args['offset'] = '0';
        $args['count'] = '367'; //Fails if more than 367
        $args['fields'] = "members.id,members.email_address,total_items,members.email_type,members.status,members.last_changed,members.merge_fields,members.interests,total_items";
        //Takes about 3 minutes with 367 count and 27408 members.

        $method = "lists/".$CFG->block_mailchimp_listid."/members";

        if(!$api = new \DrewM\MailChimp\MailChimp($CFG->block_mailchimp_apicode)) {
            debugging("ERROR: Unable to create mailchimp wrapper object \DrewM\MailChimp\MailChimp.");
            return false;
        }

        echo("Getting list of mailing list members.\n");

        $newmemberlist['members'] = true;

        while(!empty($newmemberlist['members'])) {
            $newmemberlist = $api->get($method, $args);
            if (!$newmemberlist) {
                debugging("ERROR: Unable to get member list from mailchimp, method: ".$method);
                $memberlist = false;
                return false;
            }

            if (!empty($memberlist['members'])) {
                $memberlist['members'] = array_merge($memberlist['members'], $newmemberlist['members']);
            }
            else {
                $memberlist = $newmemberlist;
            }

            $args['offset'] += $args['count'];
        }

        if (!count($memberlist['members']) > 0) {
            debugging("ERROR: No members present in the mailchimp list. Unable to synchronize users.");
            return false;
        }

        echo("Returned with ".$memberlist['total_items']." members.\n");

        return $memberlist;
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
