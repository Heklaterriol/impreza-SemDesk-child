=== SeminarDesk for WordPress ===

Contributors: SeminarDesk  
Requires at least: 5.2  
Tested up to: 6.4  
Requires PHP: 7.3  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  
Stable tag: master  

Connects SeminarDesk to WordPress.

== Description ==

This plugin allows you to connect [SeminarDesk](https://www.seminardesk.com) to your WordPress site in order to automatically create posts for events, dates and facilitators when those items are created or updated via SeminarDesk.  

== Installation ==

The plugin requires at least WordPress 5.2 and PHP 7.3 and can be installed via ZIP file. The most recent version is provided [here](https://www.seminardesk.de/wordpress-plugin).  

If you have direct access to your hosting environment, you can also clone the [plugin's Git repository](https://bitbucket.org/seminardesk/seminardesk-wordpress/branch/master) and checkout the master branch.  

== Setup ==

The plugin works by handling the Webhooks that are triggered by SeminarDesk.  

You need to complete the following steps in order to create the connection:  

At first, create a new WordPress user with "Author" role, you could name it `SeminarDesk`, for example.  

Then add the following URL in SeminarDesk under "Administration > Webhooks", alongside the username and password of the user you created in step 1.  

`https://**your-wordpress-site.com**/wp-json/seminardesk/v1/webhooks`

When adding the webhook URL, you should choose "Select individual events" and then enable all "Event", "Event Date", "Facilitator" and "Label Group" checkboxes. That way, all item types supported by the plugin will be published to your WordPress site. If you are not using label groups and labels or facilitators at all, you can of course omit these events.

**Note**: Make sure that you do not unintentionally select other event types like "Profile Created", otherwise additional data might be sent to your WordPress site.  

From now on, SeminarDesk will publish events to the WordPress plugin whenever a new item like event or facilitator is created, updated or deleted. To initially publish all items, you can select "Send all items" from the webhook's action menu.

**Note**: Don't create/edit SeminarDesk CPTs and TXNs manually via admin panel, if debug mode of the plugin is active. This might break things.  

== Customization ==

= Slugs =

In WordPress the events and facilitators can be accessed through URL addresses, which are defined by slugs. You can deactivate or customize the slugs in the WordPress Dashboard "SeminarDesk > Settings" depending of your use case. Remember that the slugs need to be unique in WordPress.

List of available slugs and their default url address with permalink structure set to `post-name` in the WordPress settings:

- events: `./**your-wordpress-root**/events`
- dates: `./**your-wordpress-root**/schedule`
  - upcoming dates: `./**your-wordpress-root**/schedule/upcoming`
  - past dates: `./**your-wordpress-root**/schedule/past`
  - year: `./**your-wordpress-root**/schedule/year` (not customizable)
  - year-month: `./**your-wordpress-root**/schedule/year-month` (not customizable)
- facilitators: `./**your-wordpress-root**/facilitators`

= Templates =

The Templates in general determine how the page looks and behaves, when accessing the slugs on the front-end of WordPress. The SeminarDesk Plugin comes with a number of template files as an example. You can create your own templates matching the individual needs of your website. A good starting point for this is to place copies of the default templates in a separate folder and customize their code. It’s important that you don’t edit the template files directly in the plugin. This ensures that any changes to the template files will not be lost, when the plugin is updated to a new version.  

Here are the steps you can follow to create your custom templates (e.g. via ftp):

1. Locate the default template files: `./**your-wordpress-root**/wp-content/plugin/seminardesk-wordpress/templates`
2. Create the separate template folder
  Option 1: in the wordpress plugin folder: `./**your-wordpress-root**/wp-content/plugin/seminardesk-custom/templates`
  Option 2: in your theme folder `./**your-wordpress-root**/wp-content/theme/**your-theme**/seminardesk-custom/templates`
1. Copy the default template files in the created folder
2. Customize the copied template files to your needs...
3. **Optional:** Create asset files with the same name as the template (e.g. sd_cpt_event.{CSS, JS}) in the subfolder 'assets' of your custom template folder

= Hooks =

The SeminarDesk Plugin implements action hooks, which can be used to add custom functionality (e.g. aggregator) via child theme or your own plugin. Four kinds of hooks exist for SeminarDesk's CPTs "events", "event dates", "facilitators" and "labels":  

1. put -> hooks into the creation/update of a CPT
2. create -> hooks into the creation of a CPT
3. update -> hooks into the update of a CPT
4. delete -> hooks into the deletion of a CPT

[Find out more about action hooks in WordPress.](https://developer.wordpress.org/plugins/hooks/actions/)  

#### Event hooks

The action hooks `wpsd_webhook_put_event`, `wpsd_webhook_create_event`, `wpsd_webhook_update_event` and `wpsd_webhook_delete_event` for events are supported by the plugin.

#### Event date hooks

The action hooks `wpsd_webhook_put_date`, `wpsd_webhook_create_date`, `wpsd_webhook_update_date` and `wpsd_webhook_delete_date` for event dates are supported by the plugin.  

#### Facilitator hooks

The actions hooks `wpsd_webhook_put_facilitator`, `wpsd_webhook_create_facilitator`, `wpsd_webhook_update_facilitator` and `wpsd_webhook_delete_facilitator` for facilitators are supported by the plugin.  

#### Label hooks

The actions hooks `wpsd_webhook_put_label`, `wpsd_webhook_create_label`, `wpsd_webhook_update_label` and `wpsd_webhook_delete_label` for labels are supported by the plugin

#### Example

Code snipped for action hook `wpsd_webhook_put_date`:  

```php
// Includes ... make utility functions from SeminarDesk's plugin available in your child theme or custom plugin
use Inc\Utils\TemplateUtils;
use Inc\Utils\WebhookUtils;
/**
 * Creates or updates the events calendar's "event" via webhook from SeminarDesk's "event date"
 * 
 * @param mixed $wpsd_webhook Notification part of the Webhook request
 * @return void 
 */
function wpsd_tec_events( $wpsd_webhook ){
  $date_payload = $wpsd_webhook['payload']; // payload of the request in JSON
  /**
   * Put your custom code here
   */
}
add_action( 'wpsd_webhook_put_date', 'wpsd_tec_events' );
```

= Posts per page   =

The number of posts per page can be change directly via WordPress settings for all post types globally. The setting is called "Blog pages show at most" and can be found in "Dashboard > Settings > Reading". By default the value is set to "10". This means that a page is not showing more than 10 post. If the query has more than 10 posts, the result is split into multiple pages via pagination. If the value is set to "-1" all post are shown on one page regardless their number.

If necessary, the number of posts per page can be also changed for a specific post type using the WP action hook `pre_get_posts` and override the query var `posts_per_page`. The necessary code can be placed in the `function.php` of the child theme. This is a code snipped for the archive 'facilitator' to show all posts on one page:  

```php
function modify_query( $query ) {
  if ( $query->is_archive() && $query->is_main_query() && get_query_var('post_type') === 'sd_cpt_facilitator'){
    $query->set( 'posts_per_page', -1); // all posts
  }
}
add_action( 'pre_get_posts', 'modify_query' );
```

= Exclude from Search =

In case you want to exclude a certain type from the search, you can achieve this by modifying the search query. This is a code snipped to exclude all event dates (CPT 'sd_txn_dates') from the search by removing 'sd_cpt_date' from the query_var 'post_type':

```php
function modify_query( $query ) {
  if ( is_search() ){
    $post_types = get_post_types();
    unset($post_types['sd_cpt_date']); 
    $query->set( 'post_type', $post_types );
  }
}
add_action( 'pre_get_posts', 'modify_query' );
```

== Further notes ==

= Using REST API =

The SeminarDesk REST API of the SeminarDesk plugin is built on top of the [WordPress REST API](https://developer.wordpress.org/rest-api/). It's using the namespace `seminardesk` and provides various endpoints.  

The POST endpoint `POST https://**your-wordpress-site.com**/wpsd01/wp-json/seminardesk/v1/webhooks` is used to receive webhooks and their payloads.  

There are various GET endpoints to access the Custom Post Types events, dates, facilitators and labels as a list of items or a single item in JSON format (e.g. `GET https://**your-wordpress-site.com**/wpsd01/wp-json/seminardesk/v1/cpt_events`). When the option "Debug" under "Developer" on the admin page is activated the items includes the entry `sd_data` with a complete dump of all fields of this item. For privacy concerts you might want to keep this disabled or even disable the GET endpoints all together on the admin page under "REST API".  

The full list of endpoints in JSON format can be found here: `https://**your-wordpress-site.com**/wpsd01/wp-json/seminardesk/v1/`.  

= Using Webhooks & HTTP client =

Webhooks and their payloads can be send manually using CURL or other HTTP clients (e.g. CURL, Postman).  

For CURL you can follow this example:

```bash
curl \
  --user '**user**:**password**' \
  --header "Content-Type: application/json" \
  --request POST \
  --data @/**your_directory**/**json_payload** \
  https://**your-wordpress-site.com**/sdv01/wp-json/seminardesk/v1/webhooks
```

This is a example of an Webhook payload with empty notification:

```json
{
  "id": "3b27fe327ae240bf838a3fb6693e9feb",
  "attempt": 1,
  "properties": {
    "timestamp": "1583509807000"
  },
  "notifications": [
    // put your custom notifications here
  ]
}
```

= Reset Database =

The SeminarDesk plugin can be reset on the database level via the Webhook action 'all.delete'. This action deletes all SeminarDesk entries (cpts, txns and their terms) from the WordPress database. At the admin page of the SeminarDesk plugin 'Debug' and 'Deleted All' need to be enabled to allow this action.

Example of the notification of Webhook action 'all.delete':

```json
"notifications": [
  {
    "action": "all.delete",
    "payload": {
    }
  }
]
```

= Plugin Updates & Compatibility =

In some case after updating the SeminarDesk plugin it might be necessary to also update the WordPress database or other resources for compatibility reason. SeminarDesk provides the Webhook action 'plugin.update' for this task. If necessary SeminarDesk will advice you to run this Webhook action with a special string, which will be provided.  

Example of the notification with Webhook action 'plugin.update':

```json
"notifications": [
  {
    "action": "plugin.update",
    "payload": {
      "update": "special_string"
    }
  }
]
```

== Changelog ==

= 1.5.0 - 14.11.2023 =

- add setting to enable/disable slugs and rest api via admin settings
- add setting to enable/disable slugs individual
- revise readme paragraph "slugs" and "REST"
- minor fix of the example archive template regarding cpt facilitators
- fix txn after new payload specs
- tested up to WP 6.4

= 1.4.0 - 24.06.2023 =

- add custom action hooks for SeminarDesk's CPTs "events", "event dates", "facilitators" and "labels"
- add featured image functionality to SeminarDesk's CPTs
- Consider renamed event date webhook payload property "status" which is now called "bookingPageStatus"
- revise utility function `get_value_by_language()` - if value not set returns empty string not NULL
- utility function `set_term()` include label name
- tested up to WP 6.2
- update composer dependencies (e.g. autoload)

= 1.3.0 - 07.10.2022 =

- add date facilitators to templates and txn date and keep backwards compatibly for event facilitators
- exclude sd_data from GET endpoints when debug is disabled.
- enqueues with version number based on file date
- Extend and revise README.

= 1.2.1 - 18.05.2022 =

- Some small fixes regarding TemplateUtils.

= 1.2.0 - 21.01.2022 =

- Rename custom field 'preview_available' to 'sd_preview_available'.
- Revise TemplateUtils.
- Rewrite permalink of event date to point to its event.
- Order event archive by upcoming rewrite permalink of event date to point to its event.
- Add default css classes (e.g. sd-img-remote).
- Code clean-up, convert spaces to tabs in code.
- Revise php documentation.
- Extend and revise README.

= 1.1.0 - 19.11.2021 =

- Add support label groups and labels.
- Add feature to sort facilitator by last name.
- Consider booking settings for an event ( 'registrationAvailable', 'detailpageAvailable', previewAvailable ).
- Improve template examples, custom template handling and remove text domain from templates.
- Add webhook action 'all.delete' to delete all SeminarDesk entries (cpts, txns and their terms) via webhook from the WordPress database.
- Optimize uninstall of the SeminarDesk plugin to be clean.
- Extend admin settings for SeminarDesk plugin.
- Fix basic auth issue - Digest non-'default' authorization params (used e.g. in 1und1 shared hosting setups). thx @Felix.
- Remove wordpress-stubs.
- Code revision, minor bug fixes and improvements.

= 1.0.1 - 17.09.2020 =

- Renamed plugin file, added readme file and added Autoload generated sources and dependencies to make plugin installable via Git.

= 1.0.0 - 13.08.2020 =

- Initial release.
