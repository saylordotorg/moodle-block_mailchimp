#MailChimp Plugin for Moodle#


This plugin allows users to automatically subscribe and unsubscribe themselves from a specified MailChimp mailing list and syncs subscription status with a profile field within Moodle. It is an updated version of the Sebsoft MailChimp Plugin found at [https://bitbucket.org/sebastianberm/moodle-block-mailchimp/](https://bitbucket.org/sebastianberm/moodle-block-mailchimp/). It uses the v3.0 MailChimp API wrapper by DrewM found at [https://github.com/drewm/mailchimp-api/tree/api-v3](https://github.com/drewm/mailchimp-api/tree/api-v3).

##Installation Instructions:##


- Create a checkbox custom user profile field in Moodle settings - [https://docs.moodle.org/28/en/User\_profile\_fields](https://docs.moodle.org/28/en/User_profile_fields)
- Copy the moodle-block_mailchimp folder to {{moodle-dir}}/blocks/mailchimp
- Set up a MailChimp API key in the API Keys section of your MailChimp account settings
- Enter in your API key on the the plugin settings page (Site Administration->Plugins->Blocks->MailChimp), select the mailing list you would like to sync user subscriptions to, and select the profile field created in the first step
- Add the plugin block to the desired page, such as the homepage or students' "My Home" page 


##Original Readme:##
SEBSOFT MAILCHIMP PLUGIN

The Sebsoft MailChimp Plugin offers your Moodle users the possibility to sign up for your mailing list as easily 
as pushing a button. It also allows you to subscribe or unsubscribe your users by altering the specially assigned 
profile field. Never again will you be forced to subscribe all your users manually!

INSTALLATION

- Copy the mailchimp folder to your blocks directory
- Set up your unique MailChimp Api code, to be generated at www.mailchimp.com
- If the api code is correct you can select the mailing list to use
- Set up which Moodle profile field you wish to use. This must be a profile field of the type 'checkbox'
- Happy Mailing!
