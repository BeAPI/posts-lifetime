<?php

namespace Beapi\PostsLifetime;

/**
 * The purpose of the main class is to init all the plugin base code like :
 *  - Taxonomies
 *  - Post types
 *  - Shortcodes
 *  - Posts to posts relations etc.
 *  - Loading the text domain
 *
 * Class Main
 * @package Beapi\PostsLifetime
 */
class Main {
	/**
	 * Use the trait
	 */
	use Singleton;

	protected function init(): void {
		add_action( 'init', [ $this, 'init_translations' ] );
	}

	/**
	 * Load the plugin translation
	 */
	public function init_translations(): void {
		// Load translations
		load_plugin_textdomain( 'posts-lifetime', false, dirname( BEAPI_POSTS_LIFETIME_PLUGIN_BASENAME ) . '/languages' );
	}
}
