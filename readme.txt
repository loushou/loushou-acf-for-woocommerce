=== Loushou: ACF for WooCommerce ===
Contributors: loushou
Donate link: http://looseshoe.com/
Tags: acf, woocommerce, advanced custom fields, my account, checkout
Requires at least: 4.5
Tested up to: 4.5.2
Stable tag: trunk
License: GNU General Public License, version 3 (GPL-3.0)
License URI: http://www.gnu.org/copyleft/gpl.html

A plugin that creates some much needed communication between Advanced Custom Fields and WooCommerce.

== Description ==

Many developers I know consider Advanced Custom Fields a must have plugin on most of their website work, and I am sure that idea permeates the entire community. WooCommerce has been the leading eCommerce engine in the world, for quite some time now. Both of these plugins are in the list of the top used plugins on the repository. As such, it is about time that someone write a bridge between the two.

That is where I come in. This plugin is made to allow an admin user, or developer, to create ACFs using the normal ACF interface, and assign them to some of the common pages of WooCommerce, without any additional work, or coding. Adding fields to the checkout or My Account page is now as easy as pointing and clicking, inside the already familiar interface of ACF (and ACF Pro).

I really hope this helps others, because I know it will help me, and at least the devs I know.

== Installation ==

= Basic Installation =

These instructions are pretty universal, standard methods employed when you install any plugin. If you have ever installed one for your WordPress installation, you can probably skip this part.

The below instructions assume that you have:

1. Downloaded the Loushou: ACF for WooCommerce software from WordPress.org.
1. Have already installed Advanced Custom Fields (or Pro) and WooCommerce, and set them up to your liking.
1. Possess a basic understanding of ACF & WooCommerce concepts.
1. Have either some basic knowledge of the WordPress admin screen or some basic ftp and ssh knowledge.
1. The ability to follow an outlined list of instructions. ;-)

Via the WordPress Admin:

1. Login to the admin dashboard of your WordPress site.
1. Click the 'Plugins' menu item on the left sidebar, usually found somewhere near the bottom.
1. Near the top left of the loaded page, you will see an Icon, the word 'Plugins', and a button next to those, labeled 'Add New'. Click 'Add New'.
1. In the top left of this page, you will see another Icon and the words 'Install Plugins'. Directly below that are a few links, one of which is 'Upload'. Click 'Upload'.
1. On the loaded screen, below the links described in STEP #4, you will see a location to upload a file. Click the button to select the file you downloaded from http://WordPress.org/.
1. Once the file has been selected, click the 'Install Now' button.
    * Depending on your server setup, you may need to enter some FTP credentials, which you should have handy.
1. If all goes well, you will see a link that reads 'Activate Plugin'. Click it.
1. Once you see the 'Activated' confirmation, you will see new icons in the menu.
1. Start using Loushou: ACF for WooCommerce

Via SSH:

1. FTP the file you downloaded from http://WordPress.org/ to your server. (We assume you know how to do this)
1. Login to your server via ssh. (.... you know this too).
1. Issue the command `cd /path/to/your/website/installation/wp-content/plugins/`, where /path/to/your/website/installation/wp-content/plugins/ is the plugins directory on your site.
1. `cp /path/to/lou-acf-wc.latest.zip .`, to copy the downloaded file to your plugins directory.
1. `unzip lou-acf-wc.latest.zip`, to unzip the downloaded file, creating a lou-acf-wc directory inside your plugins directory.
1. Open a browser, and follow steps 1-2 under 'Via the WordPress Admin' above, to get to your 'plugins' page.
1. Find Loushou: ACF for WooCommerce on your plugins list, click 'Activate' which is located directly below the name of the plugin.
1. Start using Loushou: ACF for WooCommerce

== Frequently Asked Questions ==

= How do I make the fields appear on my Checkout or My Account pages? =

Using the normal ACF interface, when you select the 'Location' -> 'Rules' (most of the time you select 'post type' and 'post' in this area), you will choose 'WooCommerce' as the first drop down under 'Show this field group if' and either 'Checkout' or 'My Account' as the value of the thrid drop down (next to the 'and' button). Once you save, the fields will appear on the checkout or my account page, appropriately.

== Screenshots ==
1. Make sure you have the required plugins installed, up to date, and activated.
2. You can easily assign an ACF group the the WooCommerce Checkout.
3. Assigning an ACF group to the My Account page is pretty easy too.
4. You can see the fields are embeded in the checkout form. Make sure to style them as you see fit, with your theme.
5. Viewing your My Account page, you can see that your fields get their own section on the page. Make sure to style them as you see fit, with your theme.

== Changelog ==

= 1.0.0 =
* Created an awesome plugin that hopefully others will use
