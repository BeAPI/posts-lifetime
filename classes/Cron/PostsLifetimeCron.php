<?php

namespace Beapi\PostsLifetime\Cron;

use Beapi\PostsLifetime\Helpers;
use Beapi\PostsLifetime\Singleton;

class PostsLifetimeCron extends Cron {

	protected function init(): void {
		add_action( 'posts_lifetime_cron', [ $this, 'process' ] );
	}

	/**
	 * Use the trait
	 */
	use Singleton;

	/**
	 *
	 * @var string
	 */
	protected $type = 'posts_lifetime';

	/**
	 * Process posts : trash and/or delete posts that must be.
	 *
	 * @return void
	 */
	public function process(): void {
		// Check if lock is active
		if ( $this->is_locked() ) {
			return;
		}

		if ( false === $this->create_lock() ) {
			return;
		}

		try {
			// Move expired posts to trash
			$this->trash_expired_posts();

			// Delete posts in trash regarding retention option
			$this->delete_expired_posts();
		} finally {
			// Unlock cron
			$this->delete_lock();
		}

	}

	/**
	 * Add posts_lifetime_cron to crons when plugin is activated
	 *
	 * @return void
	 */
	public static function schedule_cron(): void {
		if ( is_multisite() ) {
			$sites = get_sites( [ 'fields' => 'ids' ] );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );

				if ( ! wp_next_scheduled( 'posts_lifetime_cron' ) ) {
					wp_schedule_event( time(), 'daily', 'posts_lifetime_cron' );
				}

				restore_current_blog();
			}
		} else {
			if ( ! wp_next_scheduled( 'posts_lifetime_cron' ) ) {
				wp_schedule_event( time(), 'daily', 'posts_lifetime_cron' );
			}
		}
	}

	/**
	 * Remove posts_lifetime_cron from crons when plugin is deactivated
	 *
	 * @return void
	 */
	public static function unschedule_cron(): void {
		if ( is_multisite() ) {
			$sites = get_sites( [ 'fields' => 'ids' ] );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );

				$timestamp = wp_next_scheduled( 'posts_lifetime_cron' );
				if ( $timestamp ) {
					wp_unschedule_event( $timestamp, 'posts_lifetime_cron' );
				}

				restore_current_blog();
			}
		} else {
			$timestamp = wp_next_scheduled( 'posts_lifetime_cron' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'posts_lifetime_cron' );
			}
		}
	}

	/**
	 * Move expired posts to trash and inform post author.
	 *
	 * @return void
	 */
	private function trash_expired_posts(): void {
		// Get expired posts
		$expired_posts    = Helpers::get_expired_posts();
		$retention_period = get_option( 'trash_retention_period' );

		if ( empty( $expired_posts ) ) {
			return;
		}

		// Put in trash if expired
		foreach ( $expired_posts as $post ) {
			wp_trash_post( $post->ID );

			// Get post author email
			$author_id    = $post->post_author;
			$author_email = get_the_author_meta( 'user_email', $author_id );

			// If no author email for the post, use the admin email
			if ( ! $author_email ) {
				$author_email = get_option( 'admin_email' );
			}

			if ( $author_email ) {
				// Prepare email content
				$subject = sprintf(
					__( 'Your post "%s" has been moved to trash', 'posts-lifetime' ),
					get_the_title( $post->ID )
				);

				$message = sprintf(
					__( "Hello,\n\nYour post titled \"%s\" has been automatically moved to trash because its lifetime expired. It will be permanently deleted in %d days.\n\nYou can review and edit your post here: %s\n\nRegards,\nYour Site Team", 'posts_lifetime' ),
					get_the_title( $post->ID ),
					$retention_period,
					esc_url( get_edit_post_link( $post->ID ) )
				);

				// Send email to the post author
				wp_mail( $author_email, $subject, $message );
			}
		}
	}

	/**
	 * Definitely delete expired posts in trash.
	 *
	 * @return bool
	 */
	protected function delete_expired_posts(): bool {

		$retention_period = get_option( 'trash_retention_period', 30 );

		// Find limit datetime for suppression
		try {
			$threshold_date = new \DateTime();
			$threshold_date->modify( "-{$retention_period} days" );
		} catch ( \Exception $e ) {
			error_log( 'Error calculating threshold date: ' . $e->getMessage() );

			return false;
		}

		// Get posts in trash
		$query = new \WP_Query( [
			'post_type'      => 'any',
			'posts_per_page' => - 1,
			'post_status'    => 'trash',
		] );

		if ( empty( $query->posts ) ) {
			return false;
		}

		$posts_deleted = false;

		// Check if post in trash have to be deleted
		foreach ( $query->posts as $post ) {

			$trash_date_meta = get_post_meta( $post->ID, '_wp_trash_meta_time', true );

			if ( empty( $trash_date_meta ) || ! is_numeric( $trash_date_meta ) ) {
				continue;
			}

			try {
				$deleted_date = ( new \DateTime() )->setTimestamp( (int) $trash_date_meta );
			} catch ( \Exception $e ) {
				error_log( sprintf( 'Error parsing trash date for post ID %d: %s', $post->ID, $e->getMessage() ) );
				continue;
			}

			// Check if post is expired, and delete it
			if ( $deleted_date < $threshold_date ) {
				wp_delete_post( $post->ID, true );
				$posts_deleted = true;

			}
		}

		return $posts_deleted;
	}
}
