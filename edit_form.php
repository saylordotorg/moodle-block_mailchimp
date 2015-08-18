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
 * File         edit_form.php
 * Encoding     UTF-8
 */

/**
 * Custom block configuration form
 * 
 * @package     block_mailchimp
 *
 * @version     2.7.0
 * @author      Rogier van Dongen :: sebsoft.nl
 * @copyright   2014 Rogier van Dongen :: sebsoft.nl {@link http://www.sebsoft.nl}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 * * */
class block_mailchimp_edit_form extends block_edit_form
{

    protected function specific_definition($mform) {
        global $CFG, $OUTPUT;

        $image = '<a href="http://www.sebsoft.nl" target="_new"><img src="' .
                $OUTPUT->pix_url('logo', 'blocks_mailchimp') . '" /></a>';
        $mform->addElement('header', 'configheader', get_string('promo', 'block_mailchimp'));
        $mform->addElement('html', get_string('promodesc', 'block_mailchimp', $image));

        $mailchimplists = \block_mailchimp\helper::call_api_lists();

            // If we're having trouble loading mailing lists.
        if ($mailchimplists === false) {
            $mform->addElement('html', "<b>" . get_string('error:load_api_lists', 'block_mailchimp') . "</b>");
            // Or if we simply have no lists yet.
        } else if (empty($mailchimplists)) {
            $mform->addElement('html', "<b>" . get_string('missing_mailing_lists', 'block_mailchimp') . "</b>");
        }

    }
}