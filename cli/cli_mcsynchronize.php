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
 * File         cli_mcsynchronize.php
 * Encoding     UTF-8
 */

namespace block_mailchimp\task;

/**
 * CLI task implementation to synchronize MailChimp mailinglist subscriptions.
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
        "Use this script to manually initiate a sync of users'
        mailing list subscription status with MailChimp.

        Options:
        -h, --help            Print out this help

        Example:
        \$sudo -u www-data /usr/bin/php blocks/mailchimp/cli/
        cli_mcsynchronize.php\n\n";

    echo $help;
    die;
}

$sync = new mcsynchronize();

cli_heading('Initiating MailChimp sync');
$sync->execute();

cli_heading('Finished MailChimp sync');
