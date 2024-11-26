<?php

namespace Beapi\PostsLifetime;

use WP_Post;

class Admin {
	protected function init(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
		add_action( 'admin_init', [ $this, 'trash_retention_period' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_post_lifetime_metabox' ] );
		add_action( 'save_post', [ $this, 'save_post_lifetime_metabox' ] );
	}

	/**
	 * Use the trait
	 */
	use Singleton;

	/**
	 * Register the widget on dashboard
	 *
	 * @return void
	 */
	public function register_dashboard_widget(): void {
		wp_add_dashboard_widget(
			'beapi_posts_lifetime_widget',
			__( 'Posts Lifetime Info', 'posts_lifetime' ),
			[ $this, 'render_dashboard_widget' ]
		);
	}

	/**
	 * Render the widget content
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		$expired_posts    = Helpers::get_trashed_posts_with_lifetime();
		$retention_period = get_option( 'trash_retention_period', 30 ); // 30 days default if not set
		?>
        <div>
            <h4><?php esc_html_e( 'Expired posts', 'posts_lifetime' ); ?></h4>
            <p>
				<?php
				printf(
					esc_html__( 'Here are the last 10 expired posts, to be deleted within %d days after being in trash', 'posts_lifetime' ),
					$retention_period
				);
				?>
            </p>
            <ul>
				<?php if ( ! empty( $expired_posts ) ) : ?>
					<?php foreach ( $expired_posts as $post ) :
						$trash_time = get_post_meta( $post->ID, '_wp_trash_meta_time', true );
						?>
                        <li>
                            <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" target="_blank">
								<?php echo esc_html( get_the_title( $post->ID ) ); ?>
                            </a>
                            <span><?php
								if ( ! empty( $trash_time ) ) {
									// Convert timestamp in readable date
									$trash_date = date( 'd-m-Y H:i:s', $trash_time );
									echo 'In trash since : ' . esc_html( $trash_date );
								} else {
									echo 'This post has not been moved to trash.';
								} ?></span>
                        </li>
					<?php endforeach; ?>
				<?php else : ?>
                    <li><?php esc_html_e( 'No expired posts.', 'posts_lifetime' ); ?></li>
				<?php endif; ?>
            </ul>
        </div>
		<?php
	}

	/**
	 * Add metabox to define "pl_post_lifetime" post meta
	 *
	 * @return void
	 */
	public function add_post_lifetime_metabox(): void {
		add_meta_box(
			'pl_post_lifetime_metabox',
			__( 'Post Lifetime', 'posts_lifetime' ),
			[ $this, 'render_post_lifetime_metabox' ],
		);
	}

	/**
	 * Display metabox content
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function render_post_lifetime_metabox( WP_Post $post ): void {
		$post_lifetime = Helpers::get_post_lifetime( $post->ID );
		wp_nonce_field( 'save_pl_post_lifetime', 'pl_post_lifetime_nonce' );
		?>
        <p>
            <label for="pl_post_lifetime">
				<?php _e( 'Define the post expiration date:', 'posts_lifetime' ); ?>
            </label>
        </p>
        <p>
            <input
                    type="date"
                    name="pl_post_lifetime"
                    id="pl_post_lifetime"
                    value="<?php echo esc_attr( $post_lifetime ); ?>"
                    class="widefat"
            />
        </p>
		<?php
	}

	/**
	 * Save "pl_post_lifetime" post meta on save_post.
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function save_post_lifetime_metabox( int $post_id ): void {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check the nonce
		if ( ! isset( $_POST['pl_post_lifetime_nonce'] ) || ! wp_verify_nonce( $_POST['pl_post_lifetime_nonce'], 'save_pl_post_lifetime' ) ) {
			return;
		}

		$raw_date = sanitize_text_field( $_POST['pl_post_lifetime'] );

		$formatted_date = Helpers::format_date( $raw_date, 'Y-m-d', 'Y-m-d' );

		if ( ! empty( $formatted_date ) ) {
			Helpers::set_post_lifetime( $post_id, $formatted_date );
		} else {
			delete_post_meta( $post_id, 'pl_post_lifetime' );
		}
	}

	/**
	 * Register an option for the trash retention period in the WordPress settings.
	 *
	 * @return void
	 */
	public function trash_retention_period(): void {
		register_setting( 'general', 'trash_retention_period', [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 30, // 30 jours par défaut
		] );

		add_settings_field(
			'trash_retention_period',
			__( 'Trash retention period (days) for posts', 'posts_lifetime' ),
			function () {
				$value = get_option( 'trash_retention_period', 30 );
				echo '<input type="number" name="trash_retention_period" value="' . esc_attr( $value ) . '" class="small-text" min="1">';
			},
			'general',
			'default'
		);
	}
}
