<?php
/**
 * Plugin Name: Pronto Push - Desktop push notifications for Mavericks
 * Plugin URI: http://prontopush.com/
 * Description: Send instant push notifications to Pronto Push subscribers when you add new blog posts.
 * Version: 1.0.2
 * Author: Connor LaCombe
 * Author URI: http://twitter.com/mynamesconnor
 * License: GPL2
 */
 
 
 function _push($title, $url, $channel, $pushkey) {
	
	
	$response = wp_remote_post( "https://prontopush.com/api/push/{$channel}/{$pushkey}", array(
	'method' => 'POST',
	'timeout' => 45,
	'redirection' => 5,
	'httpversion' => '1.0',
	'blocking' => true,
	'headers' => array(),
	'body' => array("title"=>"", "body"=>$title, "button"=>"Read", "url"=>$url),
	'cookies' => array()
    )
);
}

function _http($url) {
	
	$response = wp_remote_post( $url, array(
	'method' => 'POST',
	'timeout' => 45,
	'redirection' => 5,
	'httpversion' => '1.0',
	'blocking' => true,
	'headers' => array(),
	'body' => array(),
	'cookies' => array()
    )
);

return $response["body"];
}
 
 
 
//check page
if(strstr($_SERVER['REQUEST_URI'], "post-new.php") !== false || (strstr($_SERVER['REQUEST_URI'], "post.php") !== false && strstr($_SERVER['REQUEST_URI'], "post=") !== false)) {
	if(get_option("prontopush_channel") !== false) {
		wp_enqueue_script('new_post.js', plugins_url('pronto-push-desktop-push-notifications-for-mavericks/js/new_post.js', dirname( __FILE__ )), array('jquery')); 
	}
}
if(strstr($_SERVER['REQUEST_URI'], "post.php") !== false) {
	if($_REQUEST["prontopush"] == "on" && get_option("prontopush_channel") !== false && $_REQUEST["publish"] == "Publish" && $_REQUEST["visibility"] == "public") {
		$title = $_REQUEST["post_title"];
		$link = get_permalink($_REQUEST["post_ID"]);
		
		_push($title, $link, get_option("prontopush_channel"), get_option("prontopush_key"));
	}
}

if(stristr($_SERVER['HTTP_USER_AGENT'], "mac") !== false && stristr($_SERVER['HTTP_USER_AGENT'], "10_9") !== false && !isset($_COOKIE["prontopush_asked"]) && get_option("prontopush_window") == "yes" && stristr($_SERVER['REQUEST_URI'], 'wp-') === false) {
	if(get_option("prontopush_name") !== false) {
		setcookie("prontopush_asked", "yes", time()+3600*24*365*10);
		wp_enqueue_script('show_asking_window.js', plugins_url('pronto-push-desktop-push-notifications-for-mavericks/js/show_asking_window.js', dirname( __FILE__ )), array('jquery')); 
		wp_localize_script('show_asking_window.js', 'prontopush_name', get_option("prontopush_name"));
		wp_localize_script('show_asking_window.js', 'prontopush_id', get_option("prontopush_channel"));
	}
}

if(get_option("prontopush_window") !== false) {
	add_option("prontopush_window", "yes");
}

/** Step 2. */
add_action( 'admin_menu', 'prontopush_menu' );

/** Step 1. */
function prontopush_menu() {
	add_menu_page( 'Pronto Push Dashboard', 'Pronto Push', 'manage_options', 'pronto-push-desktop-push-notifications-for-mavericks/dashboard.php', 'pp_dashboard', plugins_url( 'pronto-push-desktop-push-notifications-for-mavericks/icon.png' ) );
	add_submenu_page( 'pronto-push-desktop-push-notifications-for-mavericks/dashboard.php', 'Pronto Push Dashboard', 'Dashboard', 'manage_options', 'pronto-push-desktop-push-notifications-for-mavericks/dashboard.php', 'pp_dashboard' ); 
	add_submenu_page( 'pronto-push-desktop-push-notifications-for-mavericks/dashboard.php', 'Pronto Push Settings', 'Settings', 'manage_options', 'pronto-push-desktop-push-notifications-for-mavericks/settings.php', 'pp_settings' ); 
}

function pp_dashboard() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	if(get_option("prontopush_channel") === false) {
		wp_die( __( 'You need to setup your Pronto Push account information first. Click the &quot;Settings&quot; on the left.' ) );
	}
	else {
		$id = get_option("prontopush_channel");
		$key = get_option("prontopush_key");
		$channelInfo = json_decode(_http("https://prontopush.com/api/getChannelInfo?id={$id}&key={$key}"), true);
		$subscribers = json_decode($channelInfo["subscribers"], true);
		$waiting = json_decode($channelInfo["waiting"], true);
		$maxSubs = $channelInfo["maxSubs"];
		?>
        <style>
		.pp_button {
	text-align: center;
	background-color: #98c924;
	background-image: -webkit-gradient(linear, left top, left bottom, from(rgb(152, 201, 36)),to(rgb(129, 171, 29)));
	background-image: -webkit-linear-gradient(top, rgb(152, 201, 36), rgb(129, 171, 29));
	background-image: -moz-linear-gradient(top, rgb(152, 201, 36), rgb(129, 171, 29));
	background-image: -o-linear-gradient(top, rgb(152, 201, 36), rgb(129, 171, 29));
	background-image: -ms-linear-gradient(top, rgb(152, 201, 36), rgb(129, 171, 29));
	background-image: linear-gradient(top, rgb(152, 201, 36), rgb(129, 171, 29));
	filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr='#98c924', EndColorStr='#81ab1d');
	padding-left: 10px;
	padding-right: 10px;
	padding-top: 4px;
	padding-bottom: 4px;
	color: #fff !important;
	text-shadow: rgba(0,0,0,0.4) 0 -1px 0;
	box-shadow: inset rgba(255,255,255,0.3) 0 1px 0;
	cursor: pointer;
	border-radius: 200px;
	border: 1px solid #6c9115;
	font-size: 14px;
	text-decoration: none;
}
		.pp_button:hover {
	background-color: #7aa319;
	background-image: -webkit-gradient(linear, left top, left bottom, from(rgb(122, 163, 25)),to(rgb(99, 133, 18)));
	background-image: -webkit-linear-gradient(top, rgb(122, 163, 25), rgb(99, 133, 18));
	background-image: -moz-linear-gradient(top, rgb(122, 163, 25), rgb(99, 133, 18));
	background-image: -o-linear-gradient(top, rgb(122, 163, 25), rgb(99, 133, 18));
	background-image: -ms-linear-gradient(top, rgb(122, 163, 25), rgb(99, 133, 18));
	background-image: linear-gradient(top, rgb(122, 163, 25), rgb(99, 133, 18));
	color: #fff !important;
	filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr='#7aa319', EndColorStr='#638512');
}
.pp_button:active {
	background-color: #81ac1d;
	background-image: -webkit-gradient(linear, left top, left bottom, from(rgb(129, 172, 29)),to(rgb(151, 199, 35)));
	background-image: -webkit-linear-gradient(top, rgb(129, 172, 29), rgb(151, 199, 35));
	background-image: -moz-linear-gradient(top, rgb(129, 172, 29), rgb(151, 199, 35));
	background-image: -o-linear-gradient(top, rgb(129, 172, 29), rgb(151, 199, 35));
	background-image: -ms-linear-gradient(top, rgb(129, 172, 29), rgb(151, 199, 35));
	color: #fff !important;
	background-image: linear-gradient(top, rgb(129, 172, 29), rgb(151, 199, 35));
	filter: progid:DXImageTransform.Microsoft.gradient(GradientType=0,StartColorStr='#81ac1d', EndColorStr='#97c723');
}
</style>
        <div class="wrap">
    		<h2>Pronto Push: Plugin Dashboard</h2>
            
            <div style="font-size: 20px; margin: 20px;"><img src="https://s3.amazonaws.com/prontopush/icons/<?php echo $channelInfo["icon"]; ?>" height="30" width="30"> <?php echo $channelInfo["name"]; ?></div>
            <div style="overflow: auto;">
            <?php
			if(count($subscribers) == $maxSubs) {
				//limit
				?>
                <div style="margin: 20px; float: left; text-align: center;">
                    <div style="font-size: 20px; margin-bottom: 20px; text-align: center;">Subscribers</div>
                    <div style="font-size: 40px; margin-bottom: 20px; text-align: center; color: #F00;">MAXED OUT</div>
                    <div style="font-size: 20px; text-align: center;"><?php echo number_format(count($waiting)); ?> on waitlist</div><br>People on the waitlist will be subscribed automatically once you upgrade.<br><br>
                    <a href="https://prontopush.com/Developers/EditApp.php?id=<?php echo $id; ?>" target="_blank" class="pp_button">Upgrade Now &raquo;</a>
                </div>
                <?php
			}
			else { ?>
            <div style="margin: 20px; float: left; text-align: center;">
            	<div style="font-size: 20px; margin-bottom: 20px; text-align: center;">Subscribers</div>
            	<div style="font-size: 40px; margin-bottom: 20px; text-align: center; <?php echo (count($subscribers) + 20) >= ($maxSubs) ? "color: red;" : ""; ?>"><?php echo number_format(count($subscribers)); ?></div>
                <div style="font-size: 20px; text-align: center;">out of <?php echo number_format($maxSubs); ?></div><br>
                <a href="https://prontopush.com/Developers/EditApp.php?id=<?php echo $id; ?>" target="_blank" class="pp_button">Upgrade Now &raquo;</a>
            </div>
            <?php } ?>
            </div>
        </div>
        <div><a href="http://support.prontopush.com/" target="_blank">Contact Support</a> | <a href="https://prontopush.com/Account/" target="_blank">View Account</a> | <a href="http://twitter.com/prontopush" target="_blank">Follow us on Twitter</a></div>
        <?php
	}
}

function pp_settings() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	if(strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
		$id = $_REQUEST["channel_id"];
		$key = $_REQUEST["push_key"];
		$win = $_REQUEST["show_window"];
		
		if($id != "" && $key != "") {
			$channelInfo = json_decode(_http("https://prontopush.com/api/getChannelInfo?id={$id}&key={$key}"), true);
			if($channelInfo["name"] != "") {
				if(get_option("prontopush_channel") === false) {
					add_option("prontopush_channel", $id);
				}
				else {
					update_option("prontopush_channel", $id);
				}
				
				if(get_option("prontopush_key") === false) {
					add_option("prontopush_key", $key);
				}
				else {
					update_option("prontopush_key", $key);
				}
				
				if(get_option("prontopush_name") === false) {
					add_option("prontopush_name", $channelInfo["name"]);
				}
				else {
					update_option("prontopush_name", $channelInfo["name"]);
				}
				
				update_option("prontopush_window", $win == "on" ? "yes" : "no");
				$saved = true;
			}
			else {
				$error = "Your channel ID or push key is invalid.";
			}
		}
		else {
			$error = "You must fill in your channel ID and push key.";
		}
	}
	?>
    <div class="wrap">
    	<h2>Pronto Push: Plugin Settings</h2>
        
        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
        	<input type="hidden" name="c" value="update_settings">
        	<div style="margin: 10px;">You can find the required information on your Pronto Push developer page. (<a href="https://prontopush.com/Developers" target="_blank">https://ProntoPush.com/Developers</a>)</div>
            <?php if($error != "") { ?><div style="background: #E4828F; border: 1px solid #BB1F19; border-radius: 4px; margin: 10px; padding: 5px;"><?php echo $error; ?></div><?php } ?>
            <?php if($saved == true) { ?><div style="background: #8EFF7E; border: 1px solid #1EE021; border-radius: 4px; margin: 10px; padding: 5px;">Settings have been saved.</div><?php } ?>
        	<div style="margin: 10px; overflow: auto;">
                <label>
                    <div style="width: 200px; float: left;">Channel ID</div>
                    <input type="text" name="channel_id" autofocus value="<?php echo get_option("prontopush_channel") == false ? $id : get_option("prontopush_channel"); ?>">
                </label>
            </div>
            <div style="margin: 10px; overflow: auto;">
                <label>
                    <div style="width: 200px; float: left;">Push Key</div>
                    <input type="text" name="push_key" value="<?php echo get_option("prontopush_key") == false ? $key : get_option("prontopush_key"); ?>">
                </label>
            </div>
            <div style="margin: 10px; overflow: auto;">
                <label>
                    <div style="width: 200px; float: left;">Show &quot;Subscribe via Pronto Push&quot; window when someone visits your website?</div>
                    <input type="checkbox" name="show_window" <?php echo get_option("prontopush_window") == "yes" ? "checked" : ""; ?>>
                </label>
            </div>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
        </form>
    </div>
    <?php
}
?>