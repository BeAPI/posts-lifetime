<?php

namespace Beapi\PostsLifetime;

use Beapi\PostsLifetime\Cron\Cron;
use DateTime;

/**
 * The purpose of the API class is to have the basic reusable methods like :
 *  - Template include
 *  - Template searcher
 *  - Date formatting
 *
 * You can put here all of the tools you use in the project but not
 * limited to an object or a context.
 * It's recommended to use static methods for simple accessing to the methods
 * and stick to the non context methods
 *
 * Class API
 * @package Beapi\PostsLifetime
 */
class Helpers {

	/**
	 * Use the trait
	 */
	use Singleton;

	/**
	 * Transform a date to a given format if possible
	 *
	 * @param string $date : date to transform
	 * @param string $from_format : the from date format
	 * @param string $to_format : the format to transform in
	 *
	 * @return string the date formatted
	 */
	public static function format_date( string $date, string $from_format, string $to_format ): string {
		$date = DateTime::createFromFormat( $from_format, $date );
		if ( false === $date ) {
			return '';
		}

		return self::datetime_i18n( $to_format, $date );
	}

	/**
	 * Format on i18n
	 *
	 * @param string $format
	 * @param DateTime $date
	 *
	 * @return string
	 */
	public static function datetime_i18n( string $format, DateTime $date ): string {
		return date_i18n( $format, $date->format( 'U' ) );
	}

	/**
	 * Get "pl_post_lifetime" post meta value
	 *
	 * @param int $post_id
	 *
	 * @return mixed|null Post meta value or null if doesn't exist
	 */
	public static function get_post_lifetime( int $post_id ): mixed {
		$post_lifetime = get_post_meta( $post_id, 'pl_post_lifetime', true );

		if ( empty( $post_lifetime ) ) {
			return null;
		}

		return $post_lifetime;
	}

	/**
	 * Set "pl_post_lifetime" post meta value.
	 *
	 * @param int $post_id
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function set_post_lifetime( int $post_id, mixed $value ): bool {

		if ( empty( $post_id ) ) {
			return false;
		}

		if ( empty( $value ) ) {
			return false;
		}

		return update_post_meta( $post_id, 'pl_post_lifetime', $value );
	}

	/**
	 * Get expired posts via WP_Query on post metas.
	 *
	 * @param string $post_type
	 * @param int $limit
	 *
	 * @return array|null
	 */
	public static function get_expired_posts( string $post_type = 'any', int $limit = - 1 ): ?array {
		$today_date = date( 'Y-m-d' );

		$query = new \WP_Query( [
			'post_type'      => $post_type,
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'pl_post_lifetime',
					'value'   => $today_date,
					'compare' => '<',
					'type'    => 'DATE',
				],
			],
		] );

		if ( ! $query->have_posts() ) {
			return null;
		}

		return $query->posts;
	}

	/**
	 * Get posts in trash with the "pl_post_lifetime" meta.
	 *
	 * @param int $limit
	 *
	 * @return array|null
	 */
	public static function get_trashed_posts_with_lifetime( int $limit = 10 ): ?array {
		$query = new \WP_Query( [
				'post_type'      => 'any',
				'posts_per_page' => $limit,
				'post_status'    => 'trash',
				'meta_query'     => [
					[
						'key'     => 'pl_post_lifetime',
						'compare' => 'EXISTS',
					]
				],
			]
		);

		if ( ! $query->have_posts() ) {
			return null;
		}

		return $query->posts;
	}
}
