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
 * Global Mailchimp block settings.
 * 
 * File         settings.php
 * Encoding     UTF-8
 * @package     block_mailchimp
 *
 * @version     2.7.0
 * @author      Rogier van Dongen :: sebsoft.nl
 * @copyright   2014 Rogier van Dongen :: sebsoft.nl {@link http://www.sebsoft.nl}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 **/
require_once($CFG->dirroot . '/blocks/mailchimp/classes/helper.php');

defined('MOODLE_INTERNAL') || die('moodle_internal not defined');
if ($ADMIN->fulltree) {

    // Help buttons.
    $userlang = current_language();

    $helpbuttonapicode = '
        <span class="helplink">
            <a class="tooltip" aria-haspopup="true" href="' .
            $CFG->wwwroot . '/help.php?identifier=apicode&component=block_mailchimp&lang=' . $userlang . '">
                <img class="iconhelp" src="' . $CFG->wwwroot . '/pix/help.png">
            </a>
        </span>
    ';
    $helpbuttonapilists = '
        <span class="helplink">
            <a class="tooltip" aria-haspopup="true" href="' .
            $CFG->wwwroot . '/help.php?identifier=listid&component=block_mailchimp&lang=' . $userlang . '">
                <img class="iconhelp" src="' . $CFG->wwwroot . '/pix/help.png">
            </a>
        </span>
    ';
    $helpbuttonprofilefield = '
        <span class="helplink">
            <a class="tooltip" aria-haspopup="true" href="' .
            $CFG->wwwroot . '/help.php?identifier=linked_profile_field&component=block_mailchimp&lang=' . $userlang . '">
                <img class="iconhelp" src="' . $CFG->wwwroot . '/pix/help.png">
            </a>
        </span>
    ';

    $mailchimplists = \block_mailchimp\helper::call_api_lists();
    $strheader = "";

    // If we can't make any connection with mailchimp.
    if ($mailchimplists === false) {
        $mailchimplists = array();
        $strheader      = "<p><b>" . get_string("error:load_api_lists", 'block_mailchimp') . "</b></p>";
        // If we simply have no mailing lists in mailchimp yet.
    } else if (empty($mailchimplists)) {
        $mailchimplists = array('' => get_string('no_lists', 'block_mailchimp'));
        $strheader      = "<p><b>" . get_string("missing_mailing_lists", 'block_mailchimp') . "</b></p>";
    }

    // Header.
    $image = '<a href="http://www.sebsoft.nl" target="_new"><img src="' . $OUTPUT->pix_url('logo', 'block_mailchimp') . '" /></a>';
    $settings->add(new admin_setting_heading(
            'block_mailchimp_logopromo',
            get_string('promo', 'block_mailchimp'),
            get_string('promodesc', 'block_mailchimp', $image) . $strheader));

    // Block name.
    $settings->add(new admin_setting_configtext(
        'block_mailchimp_title',
        get_string("title", 'block_mailchimp'),
        get_string("config:title_description", 'block_mailchimp'),
        get_string('blockname', 'block_mailchimp')
    ));

    // Api code.
    // We're using custom admin settings cause we want to use some validation.
    $settings->add(new \block_mailchimp\setting\apikey(
        'block_mailchimp_apicode',
        get_string("apicode", 'block_mailchimp') . $helpbuttonapicode,
        get_string("config:api_code_description", 'block_mailchimp'),
        ''
    ));

    if (!$mailchimpprofilefields = \block_mailchimp\helper::get_chipmail_profile_fields()) {
        $mailchimpprofilefields = array('' => get_string('no_profile_fields', 'block_mailchimp'));
    }

    // List id.
    $settings->add(new admin_setting_configselect(
        'block_mailchimp_listid',
        get_string("listid", 'block_mailchimp') . $helpbuttonapilists,
        get_string("config:api_list_description", 'block_mailchimp'),
        ' ',
        $mailchimplists
    ));

    // Profile field.
    $settings->add(new admin_setting_configselect(
        'block_mailchimp_linked_profile_field',
        get_string("linked_profile_field", 'block_mailchimp') . $helpbuttonprofilefield,
        get_string("config:linked_profile_field_description", 'block_mailchimp'),
        ' ',
        $mailchimpprofilefields
    ));

}