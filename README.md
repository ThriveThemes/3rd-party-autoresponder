## Third Party Autoresponder Integration Example

This plugin demonstrates the integration of a third party autoresponder with the Thrive Themes API Connections, exemplified through integrating with the CleverReach API.

### CleverReach quick guide

Dashboard link: https://eu2.cleverreach.com/admin/index.php

In order to gain access to the API, we need to generate an access token with OAuth:
1. Create / use an OAuth App from here: https://eu2.cleverreach.com/admin/account_rest.php and look for the Client ID and Secret keys.
2. Add the Client ID and Secret on the plugin page and click Connect: `[your_site]/wp-admin/admin.php?page=thrive_third_party_autoresponder_section`
3. Note: the access token generated through this method lasts one month, after which it has to be regenerated. There are code examples of how to refresh your access token here: https://rest.cleverreach.com/howto/ 
4. After this, CleverReach should be visible in the list of API Connection Autoresponders.

### Testing the Integration
#### Subscribing to mailing lists
In order to test it, you can add / use an existing mailing list from here: https://eu2.cleverreach.com/admin/customer_groups.php

Afterwards:
- add the mailing list to the Lead Generation element from Thrive Architect and check that submitting the form successfully adds subscribers.
- create an **'Add user in autoresponder'** automation from Thrive Automator and verify that triggering the automation adds subscribers
#### Tags
Users can be tagged both through Thrive Architect and Thrive Automator, by:
- adding tags to the Lead Generation element from Thrive Architect - this will attach the specified tags to the subscribing user
- starting an **'Add user in autoresponder'** automation from Thrive Automator.
- starting a **'Tag user in autoresponder'** automation and then check that triggering the automation adds the tag to the user. Please note that the user already has to be subscribed to the mailing list in order for this automation to work.

#### Custom Fields
CleverReach supports using Custom Fields. For a short guide on how to add them to your mailing lists, check https://support.cleverreach.de/hc/en-us/articles/202372851-Using-custom-data-fields-in-recipient-lists.

This integration currently supports inter-group (global) Text-type custom fields.
They can be added to the Lead Generation element in Thrive Architect, and also as the Thrive Automator fields inside the automations.

### Technical details
#### Resources
This plugin integrates with the CleverReach REST API. It was implemented relying on information provided at:
- https://rest.cleverreach.com/howto/ - OAuth setup and other examples
- https://rest.cleverreach.com/explorer/v3/ - rest route information

#### Implementation steps
In order to add an autoresponder, create a folder for it in the `autoresponders` folder, where you can include your own structure and implementation.

![image](https://user-images.githubusercontent.com/26145465/159694514-3bc1b523-a6db-414d-a4e8-b8b314b6e13a.png)

Make sure that the main class of your autoresponder extends `class-autoresponder.php`. 
The class should implement the abstract methods listed in `Autoresponder`, along with additional methods depending on the features that you want to add to your autoresponder. Tags and Custom Fields are disabled by default in the abstract class, and their function definition showcases the additional steps required to enable them:

![image](https://user-images.githubusercontent.com/26145465/159706041-950fe2b0-7813-4152-af94-8685c97f5494.png)

For an `Autoresponder` class implementation example with all features enabled, see `class-main.php` from the `clever-reach` folder, along with the other helper classes.

The newly added autoresponder must be registered inside the `init()` function from `Thrive\ThirdPartyAutoResponderDemo\Main`, like this:

`static::register_autoresponder( 'clever-reach', 'Thrive\ThirdPartyAutoResponderDemo\AutoResponders\CleverReach\Main' );`

This adds the autoresponder to the API Connections lists from Thrive Dashboard, Thrive Automator and Thrive Architect.
