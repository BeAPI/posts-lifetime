<?php

namespace Beapi\PostsLifetime\Cron;

/**
 * This class needs Bea_Log to work
 * This class purpose is to handle cron process by :
 * - creating lock files
 * - Having a start and an end process methods
 *
 * Class Cron
 * @package Beapi\PostsLifetime
 */
abstract class Cron {

	/**
	 * Type for the log filename
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * @var \Bea_Log $log
	 */
	private $log;

	/**
	 * @var \WP_Filesystem_Direct $filesystem
	 */
	protected $filesystem;

	/**
	 * Lock option name
	 *
	 * @var string
	 */
	private $lock_option;

	/**
	 * Process the cron
	 *
	 * @return mixed
	 */
	abstract public function process();

	/**
	 * Check if lock exist
	 *
	 * @return bool
	 */
	public function is_locked() {
		$lock = get_option( $this->lock_option, false );

		// Check if lock exists and is not expired
		return $lock && $lock > time();
	}

	/**
	 * Create the lock with expiration time
	 *
	 * @param int $duration Duration of the lock in seconds (default: 5 minutes).
	 * @return bool
	 */
	public function create_lock( $duration = 300 ) {
		$this->lock_option = 'cron_lock_' . sanitize_key( $this->type );

		if ( $this->is_locked() ) {
			return false; // Already locked
		}

		// Set lock with expiration time
		return update_option( $this->lock_option, time() + $duration, false );
	}

	/**
	 * Delete the lock
	 *
	 * @return bool
	 */
	public function delete_lock() {
		// Delete the lock from the database
		return delete_option( $this->lock_option );
	}
}
