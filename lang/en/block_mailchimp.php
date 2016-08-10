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
 * Strings for component 'block_mailchimp', language 'en', branch 'MOODLE_27_STABLE'
 * 
 * File         block_defaultblock.php
 * Encoding     UTF-8
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

// Default strings.
$string['blockname'] = 'MailChimp'; // User-friendly title for block.
$string['pluginname'] = 'MailChimp'; // Shortname for block.
$string['heading:administration'] = 'Manage';
$string['redirect_in'] = 'Auto-redirecting in ';
$string['seconds'] = 'seconds';

// Error strings.
$string['error:nopermission'] = 'You have no permission to do this';
$string['error:load_api_lists'] = 'Failed to load mailing lists.';
$string['error:load_interests'] = 'Failed to load interests.';
$string['error:custom_chipmail_fields'] = 'Failed to load the list of mailchimp profile fields.';
$string['error:connect_to_mailchimp'] = 'Unable to connect to the external mailchimp.';
$string['error:mailchimp_subscribe'] = 'An error occured while trying to subscribe you for the mailchimp list. Please try again later.';
$string['error:mailchimp_unsubscribe'] = 'An error occured while trying to unsubscribe you for the mailchimp list. Please try again later.';
$string['error:missing_params'] = 'We could not process your request because some key variables were missing.';
$string['error:save_api_code'] = 'We failed to connect to MailChimp using the provided Api code. Please make sure you provided the correct code.';
$string['error:guestlogin'] = 'You are not allowed to access this page as a guest.';

$string['no_lists'] = 'No lists found';
$string['no_interests'] = 'No interests found';
$string['no_profile_fields'] = 'No fields found';

// Config-specifig strings (edit_form.php).
$string['config:api_list_description'] = 'Mailing list (can be created in Mailchimp).';
$string['config:api_code_description'] = 'The Api Code linked to your Mailchimp account.';
$string['config:interest_description'] = 'Interest to filter users.';
$string['config:linked_profile_field_description'] = 'Registrations will be based on this field.';
$string['config:title_description'] = 'The title of the MailChimp block.';
$string['blocksettings'] = 'Settings';

// General strings.
$string['missing_mailing_lists'] = 'In order to use this plugin you must first create a mailing list on your mailchimp account.';
$string['missing_interests'] = 'In order to filter users based on interest you must first create interests for the specified mailing list on your mailchimp account.';
$string['missing_profile_fields'] = 'Please create profile fields under the category "mailchimp" in order to select one in the list.';

$string['goto_settings'] = 'Go to settings';
$string['not_setup_yet'] = 'This block has not yet been configured.';

$string['subscribe'] = 'Subscribe';
$string['unsubscribe'] = 'Unsubscribe';
$string['welcome_txt_subscribed'] = 'You are subscribed to the mailing list.';
$string['welcome_txt_unsubscribed'] = 'You are not yet subscribed to the mailing list.';

$string['subscribed_to_mailchimp'] = 'You have been succesfully subscribed to the mailchimp list.';
$string['unsubscribed_to_mailchimp'] = 'You have been succesfully unsubscribed from the mailchimp list.';

// Help strings.
$string['apicode'] = 'Api code';
$string['apicode_help'] = 'This is a unique code that allows you to connect to MailChimp. It can be generated at the official MailChimp website.<br />
    Only after setting up the api code correctly will you be able to select a mailing list.';

$string['listid'] = 'Mailing list ID';
$string['listid_help'] = 'You can chose a list from the mailing lists you\'ve created in MailChimp.
    This will be the mailing list your Moodle users are subscribed and unsubscribed to.<br />
    <b>Note: This setting can only be set after you have properly configured your api code.</b>';

$string['interest'] = 'Interest';
$string['interest_help'] = 'This is the interest that all users must have to be synced by this plugin. Use a common interest (for instance, Student) to filter for a specific subsection of your mailchimp mailing list.<br />
    <b>Note: This setting can only be set after you have properly configured your api code.</b>';

$string['linked_profile_field'] = 'Moodle profile field';
$string['linked_profile_field_help'] = 'Here you chose which profile field will be used by the plugin.
    Your users can subscribe and unsubscribe to your mailing list by checking or unchecking this field.<br />
    <b>Note: The profile field must be a <i>custom profile field</i> of the type <i>checkbox</i>.';

$string['title'] = 'Block title';
$string['task:mcsynchronize'] = 'Synchronise mailchimp subscriptions';
$string['task:interestsynchronize'] = 'Synchronise mailchimp interests and subscriptions';