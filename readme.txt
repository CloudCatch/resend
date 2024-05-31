=== Resend for WordPress ===
Contributors:      cloudcatch, dkjensen
Tags:              resend, smtp, email, api
Tested up to:      6.5.3
Stable tag:        0.0.0-development
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP:      8.1
Requires at least: 6.0.0

Resend for WordPress seamlessly integrates the Resend.com API with your WordPress site, replacing the default PHPMailer. This ensures reliable email delivery through Resend.com's robust service, enhancing email reliability and performance.

== Description ==

Resend for WordPress replaces the default PHPMailer in WordPress with the Resend.com API, allowing you to send emails through Resend.com's reliable email delivery service.

== Attribution ==

The Resend for WordPress plugin utilizes the Resend.com API. Neither this plugin nor its author(s) are affiliated with, endorsed by, or sponsored by Resend.com.

== Changelog ==

= 1.0.0 =

* Initial release

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/resend` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==

= How do I configure the Resend for WordPress plugin? =
After activating the plugin, go to the Settings page and enter your Resend.com API key. You can find your API key in your Resend.com account dashboard.

= What happens to my existing email functionality? =
The plugin will replace the default PHPMailer in WordPress with the Resend.com API. All emails sent by your WordPress site will be routed through Resend.com.

= Can I use this plugin with other email plugins? =
This plugin is designed to replace the default PHPMailer, so it may conflict with other plugins that modify the email sending process.

== Screenshots ==

1. **Settings Page** - Configure your Resend.com API key.
2. **Email Logs** - View logs of emails sent through Resend.com.