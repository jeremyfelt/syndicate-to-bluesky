<?php
/**
 * Initialize the plugin.
 *
 * @package organizational
 */

namespace SyndicateToBluesky;

/**
 * Initialize the plugin.
 */
class Init {

	/**
	 * Initialize the plugin.
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'log_activation' ] );
		add_action( 'init', [ __CLASS__, 'schedule_events' ] );
		add_action( 'init', array( __CLASS__, 'init_admin' ), 100 );
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );
		add_action( 'syndicate_to_bluesky_refresh_token', [ __CLASS__, 'refresh_token' ] );

		register_deactivation_hook( PLUGIN_FILE, [ __CLASS__, 'deactivation_hook' ] );
	}

	/**
	 * Log the activation of the plugin.
	 *
	 * This allows us to automatically send new published content to Bluesky
	 * when something is published without accidentally publishing content
	 * that existed on the site before activation.
	 */
	public static function log_activation(): void {
		update_option( 'syndicate_to_bluesky_activated', time() );
	}

	/**
	 * Setup scheduled events.
	 */
	public static function schedule_events(): void {
		if ( ! wp_next_scheduled( 'syndicate_to_bluesky_refresh_token' ) ) {
			wp_schedule_event( time(), 'daily', 'syndicate_to_bluesky_refresh_token' );
		}
	}

	/**
	 * Initialize the admin.
	 */
	public static function init_admin(): void {
		if ( is_admin() ) {
			Admin::init();
		}
	}

	/**
	 * Trigger a refresh of the Bluesky access token.
	 */
	public static function refresh_token(): void {
		$connection = new Connection();
		$connection->refresh_token();
	}

	/**
	 * Determine if the post should be syndicated.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post The post object.
	 */
	public static function save_post( int $post_id, \WP_Post $post ): void {
		if ( ! in_array( $post->post_type, apply_filters( 'syndicate_to_bluesky_post_types', [ 'post' ] ), true ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		if ( get_post_meta( $post_id, '_syndicate_to_bluesky', true ) ) {
			return;
		}

		$activated = get_option( 'syndicate_to_bluesky_activated' );

		// Only syndicate posts that were published after initial activation.
		if ( $activated && $activated > strtotime( $post->post_date_gmt ) ) {
			return;
		}

		$connection = new Connection();
		$connection->syndicate_post( $post );
	}

	/**
	 * Clear scheduled events on deactivation.
	 */
	public static function deactivation_hook(): void {
		wp_clear_scheduled_hook( 'syndicate_to_bluesky_refresh_token' );
	}
}
