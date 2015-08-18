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
 * Version information for Mailchimp block
 * 
 * File         version.php
 * Encoding     UTF-8
 * @package     block_mailchimp
 *
 * @version     2.7.0
 * @author      Rogier van Dongen :: sebsoft.nl
 * @copyright   2014 Rogier van Dongen :: sebsoft.nl {@link http://www.sebsoft.nl}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * */
$plugin = new stdClass();
$plugin->version   = 2014051200;
$plugin->requires  = 2014051200; // YYYYMMDDHH (This is the release version for Moodle 2.7).
$plugin->cron      = 0;
$plugin->component = 'block_mailchimp'; // Full name of the plugin (used for diagnostics).
$plugin->release   = '2.7.0';
$plugin->maturity  = MATURITY_STABLE;