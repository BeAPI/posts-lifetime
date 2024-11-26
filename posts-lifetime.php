<?php
/*
Plugin Name: Posts Lifetime
Version: 1.0.0
Version Boilerplate: 3.5.0
Plugin URI: https://beapi.fr
Description: Define an expiration date for posts.
Author: Be API Technical team
Author URI: https://beapi.fr
Domain Path: languages
Text Domain: posts-lifetime

----

Copyright 2021 Be API Technical team (human@beapi.fr)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Require autoload
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

// Plugin constants
define( 'BEAPI_POSTS_LIFETIME_VERSION', '1.0.0' );
define( 'BEAPI_POSTS_LIFETIME_VIEWS_FOLDER_NAME', 'posts_lifetime' );

// Plugin URL and PATH
define( 'BEAPI_POSTS_LIFETIME_URL', plugin_dir_url( __FILE__ ) );
define( 'BEAPI_POSTS_LIFETIME_DIR', plugin_dir_path( __FILE__ ) );
define( 'BEAPI_POSTS_LIFETIME_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

add_action( 'plugins_loaded', 'init_beapi_posts_lifetime_plugin' );

register_activation_hook( __FILE__, array( '\Beapi\PostsLifetime\Cron\PostsLifetimeCron', 'schedule_cron' ) );
register_deactivation_hook( __FILE__, array( '\Beapi\PostsLifetime\Cron\PostsLifetimeCron', 'unschedule_cron' ) );

/**
 * Init the plugin
 */
function init_beapi_posts_lifetime_plugin(): void {
	// Client
	\Beapi\PostsLifetime\Main::get_instance();

	// Cron
	\Beapi\PostsLifetime\Cron\PostsLifetimeCron::get_instance();

	// Admin
	\Beapi\PostsLifetime\Admin::get_instance();
}
