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

defined('MOODLE_INTERNAL') || die();

/**
 * Handles displaying the mailchimp block
 * 
 * Encoding     UTF-8
 * @package     block_mailchimp
 *
 * @version     2.7.0
 * @author      Rogier van Dongen :: sebsoft.nl
 * @copyright   2014 Rogier van Dongen :: sebsoft.nl {@link http://www.sebsoft.nl}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * */
class block_mailchimp extends block_base {

    public function init() {
        global $CFG;
        $blocktitle = get_config('mailchimp', 'block_mailchimp_title');
        if (!$blocktitle || empty($blocktitle)) {
            $blocktitle = get_string('blockname', 'block_mailchimp');
        }

        $this->title   = $blocktitle;
        include($CFG->dirroot . '/blocks/mailchimp/version.php');
        $this->version = $plugin->version;
        $this->cron    = $plugin->cron;
    }

    public function get_content() {
        global $CFG;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         = new stdClass();
        $this->content->text   = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            print_error('No instance ' . 'block_mailchimp');
        }

        $permissions = \block_mailchimp\helper::get_permission();

            // Make sure the correct settings are set.
        if (!isset($CFG->block_mailchimp_apicode) ||
                !isset($CFG->block_mailchimp_linked_profile_field) ||
                !isset($CFG->block_mailchimp_listid)) {
            $this->content->text .= get_string('not_setup_yet', 'block_mailchimp');
            return false;
        }

        if ($permissions['administration']) {
            // Global block settings.
            $url = "{$CFG->wwwroot}/admin/settings.php?section=blocksettingmailchimp";
            $this->content->text .= get_string('missing_config_settings', 'block_mailchimp');
            $this->content->text .= "<br /><a href='$url'>" . get_string('goto_settings', 'block_mailchimp') . "</a><br />";
        }
        $isregistered = \block_mailchimp\helper::is_mailchimp_registered_user($CFG->block_mailchimp_linked_profile_field);
        $submitbutton = (!$isregistered) ? 'subscribe' : 'unsubscribe';
        $welcometxtid = ($isregistered) ? 'welcome_txt_subscribed' : 'welcome_txt_unsubscribed';

        if (isloggedin() && !isguestuser()) {
            // Now time to start outputting the info.
            $this->content->text .= get_string($welcometxtid, 'block_mailchimp');
            $this->content->text .= '
            <div id="mailchimp">
            <form name="process_mailchimp" method="POST" action="' . $CFG->wwwroot . '/blocks/mailchimp/view/register.php">
                <input type="hidden" name="sourcePage" value="' . $_SERVER['PHP_SELF'] . '" />
                <input type="hidden" name="submit" value="true" />

                <input type="submit" name="process_mailchimp" value="' . get_string($submitbutton, 'block_mailchimp') . '" />
            </form>
            </div>';
        }
    }

    public function applicable_formats() {
        return array(
            'all' => true,
            'mod' => false
        );
    }

    public function specialization() {
        global $COURSE;
        $this->course = $COURSE;
    }

    public function instance_allow_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function hide_header() {
        return true;
    }

    /**
     * has own config
     */
    public function has_config() {
        return true;
    }

}