=== FriendConnect Login ===
Contributors: Shahin.Musayev
Donate link: http://exceltips.org.ua/wordpress/friendconnect/
Tags: google, comment, friend, login, member, community, google friend connect, social, friends, comments, friend connect
Requires at least: 2.7.1
Tested up to: 2.7.1
Stable tag: 1.0.3


This plugin allows a user to sign in using his/her Friend Connect account.

== Description ==

= Introduction =

Google Friend Connect (GFC) allows users to become members of your site community using their Google, Yahoo!, 
AIM or OpenID accounts and do lots of stuff like interacting with other visitors by making friends, sharing media, posting comments, playing games, and more. 
To find more information about Google Friend Connect you may go [here](http://www.google.com/friendconnect/ "Google Friend Connect").

Unfortunately users logged using GFC still have to enter their name, email address and website to post comment via WordPress 
(or even register and login via WordPress in case "Users must be registered and logged in to comment" option is turned on). 
Fortunately this plugin fixes this problem.

= Compatability =

PHP 4 >= 4.0.6, PHP 5  
At the moment plugin was tested with WordPress v 2.7.1 and seems to be fully compatible :).

= History =

Version 1.0.3

 * removed class constants due to the fact that PHP 4 does not support them;
 * added check whether `json_decode` function exists. And if it is not [Pear JSON library](http://pear.php.net/pepr/pepr-proposal-show.php?id=198) is used. Thus plugin should work with PHP 4 >= 4.0.6, PHP 5;
 * fixed Settings link;

Version 1.0.2

 * added possibility to specify location of GFC core files at settings page;
 * now both WP and GFC users are supported;

Version 1.0.1

Added setting page to admin side to allow user to:  

 * enter SITE ID;
 * choose between using "comment_form" action and template tag;
 * enable/disable default css;

Version 1.0

Created. Main differences (improvements) over [official plugin](http://code.google.com/p/google-friend-connect-plugins/wiki/WordPressPlugin):  

 * completely server-side. FCauth method is used to authenticate GFC user;
 * GFC user ID is used to create WP user (plugin doesn't create new WP user on GFC username change);
 * added template tag to be used in themes;
 * changed CSS;
 * user is logged out in case he/she signs out of Friend Connect;
 * only GFC users may sign in;

= Feedback and Donations =

Don't be very strict critisithing my plugin. It's my first WordPress plugin experience. Moreover it is my first PHP/Java/OpenSocial API experience :-). 
Do not hesitate to [contact me](mailto:shahin@exceltips.org.ua?subject=FriendConnect%20Login%20Feedback): your comments, proposals, thanks etc are highly appreciated.
	
If you think this plugin is useful, please consider [donating](http://exceltips.org.ua/wordpress/friendconnect/#item7 "Donate") some appropriate amount. Thank you.

== Installation ==

1. First of all set up you site with [Google Friend Connect](http://www.google.com/friendconnect/ "Google Friend Connect").
2. Download plugin.
3. Unzip it and upload to your plugins folder (`/wp-content/plugins/`).
4. Activate (Plugins->Installed) and configure (Settings->FriendConnect Login) plugin from WordPress Admin panel.
5. Adjust you theme:

* If you allow only Google Friend Connect users to login WordPress then:

   1) Remove all logout links or replace them with the following one:

	`<a onclick="google.friendconnect.requestSignOut()" href="#"><?php _e('Sign out'); ?></a>`

   2) Remove all links to user profile. In case Google Friend Connect user will change his password plugin won't log him in after that.

* If you allow both WordPress and Google Friend Connect users to login WordPress then your code should distinguish user type and generate logout and profile links appropriately. Here is an example:

	`<?php
	_e('Logged in as ');
	// Do not add link to profile and logout link in case of GFC user
	global $gfc_userdata;
	if ( ! isset($gfc_userdata) ) {
	echo '<a href="' . get_option('siteurl') . '/wp-admin/profile.php">' . $user_identity . '</a>. ';
	echo '<a href="' . wp_logout_url(get_permalink()) . '" title="' . __('Log out of this account') . '">' . __('Logout ') . '&raquo;</a>';
	} else {
	echo $user_identity . '. ';
	echo '<a href="#" onClick="google.friendconnect.requestSignOut()" title="' . __('Log out of this account') . '">' . __('Logout ') . '&raquo;</a>';
	}
	?>`

* By default plugin adds Sign In button (or user's profile data in case he/she is already signed in) under comment form. Alternatively you may use following template tag to do this:

	`<?php if (function_exists('gfc_profile')) {gfc_profile();} ?>`

   Best place to put it is under comment form or "You must be logged in..." line in your `comments.php`
 
== Screenshots ==

1. GFC Sign In button under comments form. GFC user is not logged in.
2. GFC user profile data under comments form. GFC user is logged in.
3. Submitted comments (Avatars look the same, because I use same image as my gravatar and my Google avatar). 
4. Options Page.

== Frequently Asked Questions ==

At the moment there are no questions from users. Please use [support forum](http://wordpress.org/tags/friendconnect-login "Support Forum") in case you have some.
