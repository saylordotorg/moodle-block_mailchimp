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
 * Strings for component 'block_mailchimp', language 'nl', branch 'MOODLE_27_STABLE'
 * 
 * File         block_mailchimp.php
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

// DEFAULT.
$string['blockname'] = 'MailChimp';
$string['pluginname'] = 'MailChimp';
$string['heading:administration'] = 'Beheer';
$string['redirect_in'] = 'Automatisch verwijzen in ';
$string['seconds'] = 'seconden';

// Error strings.
$string['error:nopermission'] = 'U heeft geen toestemming om dit te doen';
$string['error:load_api_lists'] = 'Het laden van de mailinglists is mislukt.';
$string['error:custom_chipmail_fields'] = 'Het laden van de mailchimp profielvelden is mislukt.';
$string['error:connect_to_mailchimp'] = 'Kon geen verbinding maken met de MailChimp servers.';
$string['error:mailchimp_subscribe'] = 'Er is iets fout gegaan bij het aanmelden op de mailinglist. Probeer het later opnieuw a.u.b.';
$string['error:mailchimp_unsubscribe'] = 'Er is iets fout gegaan bij het afmelden op de mailinglist. Probeer het later opnieuw a.u.b.';
$string['error:missing_params'] = 'Je verzoek kon niet worden verwerkt omdat er vereiste waarden niet zijn ingevuld.';
$string['error:save_api_code'] = 'Er kon geen verbinding met MailChimp worden gemaakt met deze api code.';
$string['error:guestlogin'] = 'U heeft onvoldoende rechten om deze pagina te bekijken als gast.';

$string['no_lists'] = 'Geen lijsten gevonden';
$string['no_profile_fields'] = 'Geen velden gevonden';

$string['promo'] = 'Mailchimp plugin voor Moodle';
$string['promodesc'] = 'Deze plugin is ontwikkeld door Sebsoft Managed Hosting & Software Development
    (<a href=\'http://www.sebsoft.nl/\' target=\'_new\'>http://www.sebsoft.nl</a>).<br /><br />
    {$a}<br /><br />
    Deze plugin mag onder de voorwaarden van de GPL licentie.<br />';

// Config-specifig strings (edit_form.php).
$string['config:api_list_description'] = 'Mailing lijst (deze kunnen in MailChimp worden gemaakt).';
$string['config:api_code_description'] = 'De Api Code gekoppeld aan je MailChimp account.';
$string['config:linked_profile_field_description'] = 'Registraties zullen automatisch worden gedaan bij MailChimp op basis van dit veld.';
$string['config:title_description'] = 'De titel van het blok.';
$string['blocksettings'] = 'Instellingen';

// General strings.
$string['missing_mailing_lists'] = 'Om gebruik te kunnen maken van deze plugin, is het noodzakelijk om eerst bij MailChimp een mailing list aan te maken.';
$string['missing_profile_fields'] = 'Maak a.u.b. profielvelden aan in de categorie \'mailchimp\' om een veld te kunnen selecteren.';
$string['missing_config_settings'] = 'Sommige vereiste instellingen zijn nog niet ingesteld. Deze plugin zal pas werken nadat alle vereiste waarden goed zijn ingesteld.';

$string['goto_settings'] = 'Ga naar instellingen';
$string['not_setup_yet'] = 'Dit block is nog niet ingesteld.';

$string['subscribe'] = 'Aanmelden';
$string['unsubscribe'] = 'Afmelden';
$string['welcome_txt_subscribed'] = 'Je bent al aangemeld op deze mailinglist.';
$string['welcome_txt_unsubscribed'] = 'Je bent nog niet aangemeld op deze mailinglist.';

$string['subscribed_to_mailchimp'] = 'Je bent nu succesvol aangemeld op deze mailinglist. Controleer je e-mail voor een bevestiging.';
$string['unsubscribed_to_mailchimp'] = 'Je bent nu succesvol afgemeld van deze mailinglist.';

// Help strings.
$string['apicode'] = 'Api code';
$string['apicode_help'] = 'Dit is het unieke code waarmee Moodle connectie maakt met MailChimp. Het kan op de officiele MailChimp site gegenereerd worden.<br />
    Pas wanneer dit veld correct geconfigureerd is kunt u kiezen welke mail lijst u wilt gebruiken.';

$string['listid'] = 'Mail lijst ID';
$string['listid_help'] = 'U kunt hier een lijst kiezen uit de lijsten die u heeft aangemaakt in MailChimp.
    Dit zal de mail lijst zijn waarop uw gebruikers worden ingeschrijven of uitgeschreven.<br />
    <b>Let op: Dit kunt u pas selecteren zodra het <i>api code</i> juist is geconfigureerd.</b>';

$string['linked_profile_field'] = 'Moodle profielveld';
$string['linked_profile_field_help'] = 'Hier kiest u welk profielveld door de plugin gebruikt gaat worden.
    Uw gebruikers kunnen zich op de mail lijst in- of uitschrijven door dit profielveld aan of uit te vinken.<br />
    <b>Let op: Dit moet een <i>custom profielveld</i> zijn van het type <i>checkbox</i>.</b>';

$string['title'] = 'Blok titel';
$string['task:mcsynchronize'] = 'Mailchimp aanmeldingen synchroniseren';