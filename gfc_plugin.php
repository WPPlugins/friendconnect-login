<?php

/*

 Plugin Name: FriendConnect Login
 Description: This plugin allows a user to sign in using his/her <a href="http://www.google.com/friendconnect/">Friend Connect</a> account. More detailed description can be found <a href="http://exceltips.org.ua/wordpress/friendconnect/">here</a>.
 Plugin URI: http://exceltips.org.ua/wordpress/friendconnect/
 Version: 1.0.3
 Author: Shahin Musayev
 Author URI: http://shahin.org.ua/
 
 Copyright 2009 Shahin Musayev (email: shahin@exceltips.org.ua)

 Plugin was tested with WordPress v. 2.7.1
 
 Changelog:
 
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///	Version		|	Date (dd/mm/yyyy)			|	Comment																									///
 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		v 1.0.3			04/05/2009						- Removed class constants due to the fact that PHP 4 does not support them.
														- Added check whether "json_decode" function exists. And if it is not Pear JSON library is used.
														  Thus plugin should work with PHP 4 >= 4.0.6, PHP 5.
														- Fixed Settings link.
														- Changed CSS.
														- Removed qTranslate quicktags.
		
		v 1.0.2			27/04/2009						- Added possibility to specify location of GFC core files at settings page.
														- Now both WP and GFC users are supported.
		
		v 1.0.1			23/04/2009						Added setting page to admin side to allow user to:
														 - enter SITE ID;
														 - choose between using "comment_form" and template tag;
														 - enable/disable default css;
		
		v 1.0			21/04/2009						Created. Main differences (improvements) over official plugin:
														 - completely server-side. FCauth method is used to authenticate GFC user;
														 - GFC user ID is used to create WP user (plugin doesn't create new WP user on GFC username change)
														 - added template tag to be used in themes;
														 - changed CSS;
														 - user is logged out in case he/she signs out of Friend Connect;
														 - at the moment only GFC users may sign in;

*/ 

///////////////////////////////////////////////////////////////
//					Includes go here						///
///////////////////////////////////////////////////////////////

include_once(ABSPATH . WPINC . '/registration.php');
include_once(ABSPATH . WPINC . '/user.php');
include_once(ABSPATH . WPINC . '/pluggable.php');

///////////////////////////////////////////////////////////////
//					Main plugin code goes here				///
///////////////////////////////////////////////////////////////

// Check whether we are at Admin
if ( is_admin() ) {
	
	///////////////////////////////////////////////////
	///												///
	///				Admin Side Follows				///
	///												///
	///////////////////////////////////////////////////	
	
	if ( ! class_exists( 'FriendConnectLogin_Admin') ) {
		
		// Add hook
		add_action('admin_menu', array('FriendConnectLogin_Admin','add_settings_page'));
		
		// I wrap these functions in class because it is probable that I will re-use them later.
		class FriendConnectLogin_Admin {
			
			function add_settings_page() {
				
				// Add our options page
				add_options_page('FriendConnect Login Options', 'FriendConnect Login', 8, __FILE__, array('FriendConnectLogin_Admin','settings_page'));
				
				// Add Settings link to plugin action links at Plugins page. Insure that user can manage options
				if ( current_user_can('manage_options') )
					add_filter('plugin_action_links', array('FriendConnectLogin_Admin', 'plugin_actions'), 10, 2);
			}
			
			function plugin_actions( $links, $file ) {
				
				static $this_plugin;
				
				if ( ! $this_plugin ) 
					$this_plugin = plugin_basename(__FILE__);
				
				if ( $file == $this_plugin ) {
					$settings_link = '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __('Settings') . '</a>';
					// Put Settings link before other links
					array_unshift( $links, $settings_link );
				}
				
				return $links;
			}
			
			function settings_page() {
				
				// Set default options
				$default_options = array();
				
				// Google Friend Connect Site ID
				$default_options['fc_id'] = '';
				// Location of rpc_relay.html and canvas.html
				$default_options['fc_files'] = get_option('siteurl') . '/';
				// Allow only Friend Connect users to login WordPress
				$default_options['fc_only'] = false;
				// Use template tag or allow plugin to add code using "comment_form" action
				$default_options['use_ttag'] = false;
				// Use CSS which comes with plugin
				$default_options['use_css'] = true;
				
				$options = get_option('FriendConnect_Login');
				
				// Check is there are any options saved. If not set options to default values
				if ( ! is_array($options) ) {
					$options = $default_options;
					update_option('FriendConnect_Login',$options);
				}
				
				// Check whether Settings are already submitted
				if ( isset($_POST['submit']) ) {
					
					// Check nonce
					if ( function_exists('check_admin_referer') )
						check_admin_referer('FriendConnectLogin_Settings');
					
					// Get Settings
					
					// Get checkboxes values
					foreach ( array('fc_only', 'use_ttag', 'use_css') as $option_name ) {
						if ( isset($_POST[$option_name]) ) {
							$options[$option_name] = true;
						} else {
							$options[$option_name] = false;
						}
					}
					
					// Get text values
					foreach ( array('fc_id', 'fc_files') as $option_name ) {
						if ( isset($_POST[$option_name]) and $_POST[$option_name] != '' ) {
							$options[$option_name] = $_POST[$option_name];
						} else {
							$options[$option_name] = $default_options[$option_name];
						}
					}
					
					// Update options with new values
					update_option('FriendConnect_Login',$options);
					
					if ( $options['fc_id'] != '' ) 
						echo '<div id="message" class="updated"><p>FriendConnect Login settings updated</p></div>';
				}
				
				if ( $options['fc_id'] == '' ) 
					echo '<div id="message" class="error"><p>Please enter your Google Friend Connect Site ID to allow FriendConnect Login to work.</p></div>';
				
				///////////////////////////////////////////////////////////////
				//				Settings page HTML follows					///
				///////////////////////////////////////////////////////////////

				?>
				<div class="wrap">
					
					<h2><?php _e('FriendConnect Login Options'); ?></h2>
					
					<form method="post" action="">
						<table class="form-table">
						
							<?php 
							// Add nonce
							if ( function_exists('wp_nonce_field') )
								wp_nonce_field('FriendConnectLogin_Settings');
							?>
							
							<tr>
								<th style="width:300px;" scope="row"><?php _e('Google Friend Connect Site ID:'); ?></th>
								<td valign="top">
									<input style="width:300px;" type="text" name="fc_id" value="<?php if ( isset($options['fc_id']) ) { echo $options['fc_id']; } ?>" id="fc_id"/><br/>
									<p><small><?php _e('If you don\'t know your Site ID or you still don\'t have it, you can get it <a href="http://www.google.com/friendconnect/">here</a>.'); ?></small></p>
								</td>
							</tr>
							
							<tr>
								<th style="width:300px;" scope="row"><?php _e('Location of rpc_relay.html and canvas.html:'); ?></th>
								<td valign="top">
									<input style="width:300px;" type="text" name="fc_files" value="<?php if ( isset($options['fc_files']) ) { echo $options['fc_files']; } ?>" id="fc_files"/><br/>
									<p><small><?php _e('Usually it is same as your WordPress installation path.'); ?></small></p>
								</td>
							</tr>
							
							<tr>
								<th style="width:300px;" scope="row"><?php _e('Allow only Friend Connect users:'); ?></th>
								<td valign="top">
									<label>
										<input type="checkbox" name="fc_only" id="fc_only" <?php if ( $options['fc_only'] ) { echo 'checked="checked"'; } ?> />
										<?php _e('Allow only Google Friend Connect users to login WordPress'); ?>
										<p><small><?php _e('It may be useful in case you just set up your blog and have no WordPress users.'); ?></small></p>
									</label>
								</td>
							</tr>
							
							<tr>
								<th style="width:300px;" scope="row"><?php _e('Template Tag:'); ?></th>
								<td valign="top">
									<label>
										<input type="checkbox" name="use_ttag" id="use_ttag" <?php if ($options['use_ttag']) { echo 'checked="checked"'; } ?> />
										<?php _e('Use template tag'); ?><br/>
										<p><small><?php _e('By default plugin adds Sign In button (or user\'s profile data in case he/she is already signed in) under comment form. Alternatively you may use following template tag to do this:'); ?>
											<p><code>&lt;?php if (function_exists(\'gfc_profile\')) {gfc_profile();} ?&gt;</code>.</p>
										</small></p>
									</label>
								</td>
							</tr>
							
							<tr>
								<th style="width:300px;" scope="row"><?php _e('Use default CSS:'); ?></th>
								<td valign="top">
									<label>
										<input type="checkbox" name="use_css" id="use_css" <?php if ($options['use_css']) { echo 'checked="checked"'; } ?> />
										<?php _e('Use default CSS'); ?><br/>
										<p><small><?php _e('Users data or Sign In button are enclosed in <code>&lt;div id="gfc_profile"&gt;</code> tag and plugin adds some CSS to style it. You may disable default styling and add your own style to your themes CSS file.'); ?></small></p>
									</label>
								</td>
							</tr>
							
						</table>
						
						<p class="submit">
							<input class="button-primary" type="submit" name="submit" value="<?php _e('Save Changes') ?>" />
						</p>
						
					</form>
					
					<h2><?php _e('Before you start'); ?></h2>
					<table class="form-table">		
						<tr>
							<td valign="top">
								<p>
								<?php _e('If you allow only Google Friend Connect users to login WordPress then:'); ?><br />
								<?php _e('1) Remove all logout links or replace them with the following one:'); ?></p>
									<p><code>
										&lt;a href="#" onClick="google.friendconnect.requestSignOut()"&gt;&lt;?php _e('Sign out'); ?&gt;&lt;/a&gt;
									</code></p>
								<p><?php _e('2) Remove all links to user profile. In case Google Friend Connect user will change his password plugin won\'t log him in after that.'); ?></p>
								<p>
								<?php _e('If you allow both WordPress and Google Friend Connect users to login WordPress then your code should distinguish user type and generate logout and profile links appropriately.'); ?><br />
								<?php _e('Here is an example:'); ?></p>
									<p><code>
										&lt;?php<br /> 
											
											_e('Logged in as ');<br /> 
											
											// Do not add link to profile and logout link in case of GFC user<br />
											global $gfc_userdata;<br />
											
											if ( ! isset($gfc_userdata) ) {<br />
												echo '&lt;a href="' . get_option('siteurl') . '/wp-admin/profile.php"&gt;' . $user_identity . '&lt;/a&gt;. ';<br />
												echo '&lt;a href="' . wp_logout_url(get_permalink()) . '" title="' . __('Log out of this account') . '"&gt;' . __('Logout ') . '&raquo;&lt;/a&gt;';<br />
											} else {<br />
												echo $user_identity . '. ';<br />
												echo '&lt;a href="#" onClick="google.friendconnect.requestSignOut()" title="' . __('Log out of this account') . '"&gt;' . __('Logout ') . '&raquo;&lt;/a>';<br />
											}<br />
											
										?&gt;
									</code></p>
							</td>
						</tr>
					</table>
					
					<h2><?php _e('Need support?'); ?></h2>
					<p><?php _e('See complete plugin description <a href="http://exceltips.org.ua/wordpress/friendconnect/">here</a>.'); ?></p>
					<p><?php _e('Search the <a href="http://wordpress.org/tags/friendconnect-login/">support forums</a>.'); ?></p>
					
					<h2><?php _e('Like this plugin? Have a suggestion?'); ?></h2>
					<p><?php _e('<a href="http://wordpress.org/extend/plugins/friendconnect-login/">Give it a good rating</a> on WordPress.org.'); ?></p>
					<p><?php _e('<a href="http://www.addtoany.com/share_save?linkname=FriendConnect%20Login%20Plugin&linkurl=http%3A%2F%2Fwordpress.org%2Fextend%2Fplugins%2Ffriendconnect-login%2F">Share it</a> with your friends.'); ?></p>
					<p><?php _e('<a href="mailto:shahin@exceltips.org.ua?subject=FriendConnect%20Login%20Feedback">Contact me</a>. Your comments, proposals, thanks etc are highly appreciated.'); ?></p>
				</div>
				
				<?php 
				
			} // end of function settings_page
		
		} // end of class FriendConnectLogin_Admin
	
	} // if ( ! class_exists( 'FriendConnectLogin_Admin') ) {
	
} else {

	///////////////////////////////////////////////////
	///												///
	///			User Side Follows (Hooks)			///
	///												///
	///////////////////////////////////////////////////	
	
	// Get plugin options
	$gfc_options = get_option('FriendConnect_Login');
	
	// Check is there are any options saved. If not set options to default values
	if ( ! is_array($gfc_options) ) {
		
		// Set default options
		
		// Google Friend Connect Site ID
		$gfc_options['fc_id'] = '';
		// Location of rpc_relay.html and canvas.html
		$gfc_options['fc_files'] = get_option('siteurl') . '/';
		// Allow only Friend Connect users to login WordPress
		$gfc_options['fc_only'] = false;
		// Use template tag or allow plugin to add code using "comment_form" action
		$gfc_options['use_ttag'] = false;
		// Use CSS which comes with plugin
		$gfc_options['use_css'] = true;
	}
	
	// Check Site ID
	if ( $gfc_options['fc_id'] != '' ) {
	
		// Wordpress hooks:
		// Used to identify and Sign In/Sign Out user
		add_action( 'plugins_loaded', 'gfc_main');
		// Used to put all javascript and css code
		add_action( 'wp_head', 'gfc_wp_head');
		
		// Uset to pull out correct avatar. 
		// For WP user it will be standard gravatar. For GFC user it will be FC image.
		add_filter('get_avatar', 'gfc_wp_get_avatar', 20, 5);
		
		// Do we need to add "comment_form" action
		if ( ! $gfc_options['use_ttag'] ) {
			add_action( 'comment_form', 'gfc_profile');
		}
		
	} // if ( $gfc_options['fc_id'] != '' ) {
	
} // if ( is_admin() ) {


	///////////////////////////////////////////////////
	///												///
	///			User Side Follows (Functions)		///
	///												///
	///////////////////////////////////////////////////	


// At the moment we use GFC User Id as WP username
// Use this function to change this
function gfc_username_from_id($gfc_id) {
	return $gfc_id;
}

// At the moment we use GFC User Id to generate WP password
// Use this function to change this. You may also use this function to assign special passwords to special users (e.g. yourself :) )
function gfc_pwd_from_id($gfc_id) {
	return 'p_' . $gfc_id . '_s';
}

// Used to return correct avatar
// This function is part of official plugin (http://code.google.com/p/google-friend-connect-plugins/wiki/WordPressPlugin)
// I just corrected it a little bit to return correct gravatar
function gfc_wp_get_avatar($avatar, $id_or_email, $size, $default, $alt) {
	global $wpdb;
	
	if (!empty($id_or_email->user_id)) {
		$email = $id_or_email->comment_author_email;
		$query = "SELECT * FROM `wp_users` WHERE user_email = '$email' LIMIT 1;";
		$res = $wpdb->get_col($query);

		// We dont know if this user, so return whatever was given to me
		if (count($res) <= 0)
			return $avatar;
		// Do not change the admin's image
		if ($res[0] == 1)
			return $avatar;

		// Get the image and return the altered $avatar  
		$image_url = get_usermeta( $res[0], "image_url");    
			return "<img alt='' src='{$image_url}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";    
	} else {
		return $avatar;
	}
}

// Main Function
// Checks user data and signs him/her in or out
function gfc_main() {
	
	// Do nothing in case we are at Admin
	if ( is_admin() )
		return;
	
	// Identify User
	
	// Global Wordpress variables
	global $user_login, $user_ID, $wpdb;
	
	// Global variable to store User data
	global $gfc_userdata;
	// Global variable to store plugin options
	global $gfc_options;
	
	$gfc_site_cookie_name = 'fcauth' . $gfc_options['fc_id'];
	
	
	// Check whether we have access to GFC cookie
	
	if ( isset( $_COOKIE[$gfc_site_cookie_name] ) ) {
		
		// In case cookie is set we have logged in GFC user.
		
		$gfc_site_cookie = $_COOKIE[$gfc_site_cookie_name];
		
		// Now we may form request to get User Info
		$gfc_request = 'http://www.google.com/friendconnect/api/people/@me/@self?fcauth=' . $gfc_site_cookie . '&fields=profileUrl';
		
		// Get response using curl (We may use file_get_contents. But in may case allow_url_fopen is turned Off so it is not an option:))
		$gfc_curl = curl_init($gfc_request);
		curl_setopt($gfc_curl, CURLOPT_RETURNTRANSFER, true);
		$gfc_json_result = curl_exec($gfc_curl);
		curl_close($gfc_curl);
		
		// Decode JSON data
		// json_decode requires PHP 5 >= 5.2.0
		// Since 1.0.3 we check whether "json_decode" function exists. And if it is not we use Pear JSON library
		if ( function_exists('json_decode')  ) {
			// function exist - use it!
			$gfc_userdata = json_decode($gfc_json_result);
		} else {
			// function does not exist. Loading JSON library...
			include_once('inc/JSON.php');
			
			$gfc_json = new Services_JSON();
			$gfc_userdata = $gfc_json->decode($gfc_json_result);
		} 
		
		// Get GFC user WordPress Login and Password
		$gfc_username = gfc_username_from_id($gfc_userdata->entry->id);
		$gfc_pwd = gfc_pwd_from_id($gfc_userdata->entry->id);
		
		// Get WP user info
		get_currentuserinfo();
		
		if ( ( $user_ID ) and ( $user_login == $gfc_username ) ) {
			
			// GFC=WP
			// Do nothing user is already logged in
			return;
			
		} else {
			
			// There is no WP user
			// or 
			// it differs from GFC user -> Sign out
			// Note that we can't sign out GFC user using code. Thus in case we have both WP and GFC user logged in we need to sign out WP user.
			if ( $user_ID ) wp_logout();
			
		}
		
		// Sign In GFC user
		$gfc_user_ID = gfc_login_user ($gfc_username, $gfc_pwd);
		
		// Update userinfo
		$gfc_user['ID'] = $gfc_user_ID;
		$gfc_user['user_url'] = $gfc_userdata->entry->profileUrl;
		$gfc_user['display_name'] = $gfc_userdata->entry->displayName;
		
		//$gfc_user['user_login'] = $gfc_username;
		//$gfc_user['user_pass'] = $gfc_pwd;
		//$gfc_user['user_email'] = $gfc_username . '@friendconnect.google.com';
		
		wp_update_user ( $gfc_user );
		wp_set_current_user( $gfc_user_ID );
		
		// Update User meta
		update_usermeta($gfc_user_ID, 'user_url', $gfc_userdata->entry->profileUrl);
		update_usermeta($gfc_user_ID, 'image_url', $gfc_userdata->entry->thumbnailUrl);
		update_usermeta($gfc_user_ID, 'first_name', $gfc_userdata->entry->displayName);
		update_usermeta($gfc_user_ID, 'nickname', $gfc_userdata->entry->displayName);	
		
	} else { //if ( isset( $_COOKIE[$gfc_site_cookie_name] ) ) {
		
		// In case cookie is not set GFC user is not logged in
		
		// Do we accept only GFC users
		if ( $gfc_options['fc_only'] ) {
			
			// Logout WP user if any
			wp_logout();
			wp_set_current_user (0);
			
		} else {
			
			// Check WP user cookie
			if ( isset( $_COOKIE[LOGGED_IN_COOKIE] ) ) {
				
				// Get WP user email from username
				$gfc_username = explode('|',$_COOKIE[LOGGED_IN_COOKIE]);
				$gfc_user_email = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_login = '$gfc_username[0]'");
				
				// In case we have WP user logged in and it corresponds to GFC user -> Sign out this WP user
				if ( strpos($gfc_user_email, '@friendconnect.google.com') ) {
					
					wp_logout();
					wp_set_current_user (0);
					
				}
				
			}
			
		} // if ( $gfc_options['fc_only'] ) {
		
		// I'm not sure about next line. Correct me if I'm wrong...
		unset ($gfc_userdata);
	
	}
}


function gfc_login_user ($gfc_username, $gfc_pwd) {
	
	global $wpdb;
	
	// In case username exists (We assume that only GFC user may have such username :)) - login user
	
	if ( ! username_exists( $gfc_username ) ) {
		
		// Create user
		
		$gfc_user_ID = wp_create_user($gfc_username, $gfc_pwd, $gfc_username . '@friendconnect.google.com'); 
		
	} else {
		
		$gfc_user_ID = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE user_login = '$gfc_username'");
		
	}
	
	$gfc_cred['user_login'] = $gfc_username;
	$gfc_cred['user_password'] = $gfc_pwd;
	
	//wp_set_current_user( $gfc_user_ID );
	
	// Sign in user
	wp_signon( $gfc_cred );
	
	return $gfc_user_ID;
}

// Used to populate head with CSS and JavaScript
function gfc_wp_head (){	
	
	// Populate Head
	
	// Global variable to store plugin options
	global $gfc_options;
	
	// Do we use default CSS
	if ( $gfc_options['use_css'] == true ) {
		?>
		<style type="text/css">
			
			#gfc_profile {
				font-size: 11px;
				border-top: 1px solid black;
				border-bottom: 1px solid black;
				margin: 20px 40px;
				padding: 15px;
			}
			
			#gfc_profile img {
				border: 2px solid grey;
				margin-top: 15px;
				margin-right: 10px;		
			}
			
			#gfc_profile ul {
				list-style-type:none;
			}

			#gfc_profile ul li {
				list-style-type:none;
			} 
			
		</style>
		<?php
	} ?>

	<!-- Load the Google AJAX API Loader -->
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>

	<!-- Load the Google Friend Connect javascript library. -->
	<script type="text/javascript">
		google.load('friendconnect', '0.8');
	</script>

	<!-- Initialize the Google Friend Connect OpenSocial API. -->
	<script type="text/javascript">
	
		var SITE_ID = "<?php echo $gfc_options['fc_id']; ?>"
		google.friendconnect.container.setParentUrl('<?php echo $gfc_options['fc_files']; ?>' /* location of rpc_relay.html and canvas.html */);
		google.friendconnect.container.initOpenSocialApi({
			site: SITE_ID,
			onload: function(securityToken) { 
			    if (!window.timesloaded) {
					window.timesloaded = 1;
				} else {
					window.timesloaded++;
				}
				initAllData(window.timesloaded); 
			}
		});
	
	</script>

	<script type="text/javascript">

		// Send request to GFC if needed
		function initAllData(gfc_loaded) {
			
			if ( gfc_loaded > 1 ) {
				
				//Page is loded twice or more
				//Sign In or Sign out happened -> reload page
				window.location.reload();
				
			} else {
			
			<?php 
				// Our global variable to store GFC User data
				global $gfc_userdata;
				if ( isset($gfc_userdata) ) {
					
					// Everything is fine. We have gfc_profile already populated.
					
				} else {
					
					// We need to populate gfc_profile
					// Details on how to style Sign In button can be found here:
					// http://ossamples.com/da_apisamples/button_examples.html
				?>
					google.friendconnect.renderSignInButton({ 'id': 'gfc_profile','style':'standard','text': '<?php _e('Sign In'); ?>'});
				<?php
				}
			?>
			
			}
			
		};   

	</script>
<?php 
}

// Inserts Sign In button code or GFC user profile details in case GFC user is logged in
function gfc_profile() {
	
	// Our global variable to store GFC User data
	global $gfc_userdata;
	
	if ( isset($gfc_userdata) ) {
		
	?>
		<br />
		<div id="gfc_profile">
			<img align="left" src="<?php echo $gfc_userdata->entry->thumbnailUrl; ?>">
			<ul>
				<li><strong><?php _e('Hello'); ?>,  <?php echo $gfc_userdata->entry->displayName; ?>!</strong></li>	
				<li><a href="#" onclick="google.friendconnect.requestSettings()"><?php _e('Settings'); ?></a></li>
				<li><a href="#" onclick="google.friendconnect.requestInvite()"><?php _e('Invite friend'); ?></a></li>
				<li><a href="#" onClick="google.friendconnect.requestSignOut()"><?php _e('Sign out'); ?></a></li>
			</ul>
		</div>
	<?php
	
	} else {
		
		// User is not logged in
		// Add div tag which will be populated with JavaScript
		
	?>
		<br>
		<div id="gfc_profile">
		</div>
	<?php
	}
}

?>