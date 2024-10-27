<?php
/**
 * Manage the connection to Bluesky.
 *
 * @package syndicate-to-bluesky
 */

namespace SyndicateToBluesky;

/**
 * Manage the connection to Bluesky.
 */
class Connection {

	/**
	 * The Bluesky domain.
	 *
	 * @var string
	 */
	public $domain = 'https://bsky.social';

	/**
	 * The Bluesky identifier.
	 *
	 * @var string
	 */
	public $identifier = '';

	/**
	 * The Bluesky password.
	 *
	 * @var string
	 */
	public $password = '';

	/**
	 * The Bluesky access JWT.
	 *
	 * @var string
	 */
	public $access_jwt = '';

	/**
	 * The Bluesky refresh JWT.
	 *
	 * @var string
	 */
	public $refresh_jwt = '';

	/**
	 * The Bluesky DID.
	 *
	 * @var string
	 */
	public $did = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->domain      = get_option( 'bluesky_domain', $this->domain );
		$this->identifier  = get_option( 'bluesky_identifier', $this->identifier );
		$this->password    = get_option( 'bluesky_password', $this->password );
		$this->access_jwt  = get_option( 'bluesky_access_jwt', $this->access_jwt );
		$this->refresh_jwt = get_option( 'bluesky_refresh_jwt', $this->refresh_jwt );
		$this->did         = get_option( 'bluesky_did', $this->did );
	}

	/**
	 * Set the password.
	 *
	 * @param string $password The password.
	 */
	public function set_password( string $password ): void {
		$this->password = $password;
		update_option( 'bluesky_password', $this->password );
	}

	/**
	 * Set the access JWT.
	 *
	 * @param string $access_jwt The access JWT.
	 */
	public function set_access_jwt( string $access_jwt ): void {
		$this->access_jwt = $access_jwt;
		update_option( 'bluesky_access_jwt', $this->access_jwt );
	}

	/**
	 * Set the refresh JWT.
	 *
	 * @param string $refresh_jwt The refresh JWT.
	 */
	public function set_refresh_jwt( string $refresh_jwt ): void {
		$this->refresh_jwt = $refresh_jwt;
		update_option( 'bluesky_refresh_jwt', $this->refresh_jwt );
	}

	/**
	 * Set the DID.
	 *
	 * @param string $did The DID.
	 */
	public function set_did( string $did ): void {
		$this->did = $did;
		update_option( 'bluesky_did', $this->did );
	}

	/**
	 * Get the DID.
	 *
	 * @return string
	 */
	public function get_did(): string {
		return $this->did;
	}

	/**
	 * Get the access JWT.
	 *
	 * @return string
	 */
	public function get_access_jwt(): string {
		return $this->access_jwt;
	}

	/**
	 * Get the refresh JWT.
	 *
	 * @return string
	 */
	public function get_refresh_jwt(): string {
		return $this->refresh_jwt;
	}

	/**
	 * Get the identifier.
	 *
	 * @return string
	 */
	public function get_identifier(): string {
		return $this->identifier;
	}

	/**
	 * Get the password.
	 *
	 * @return string
	 */
	public function get_password(): string {
		return $this->password;
	}

	/**
	 * Get the domain.
	 *
	 * @return string
	 */
	public function get_domain(): string {
		return trailingslashit( $this->domain );
	}

	/**
	 * Get the access token.
	 */
	public function get_access_token(): void {
		if (
			! empty( $this->get_domain() )
			&& ! empty( $this->get_identifier() )
			&& ! empty( $this->get_password() )
		) {
			$session_url = $this->get_domain() . 'xrpc/com.atproto.server.createSession';

			$body = wp_json_encode(
				[
					'identifier' => $this->get_identifier(),
					'password'   => $this->get_password(),
				]
			);

			if ( ! $body ) {
				return;
			}

			$response = wp_safe_remote_post(
				esc_url_raw( $session_url ),
				[
					'headers' => [
						'Content-Type' => 'application/json',
					],
					'body'    => $body,
				]
			);

			if (
				is_wp_error( $response ) ||
				wp_remote_retrieve_response_code( $response ) >= 300
			) {
				// @todo show/save error.
				return;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if (
				! empty( $data['accessJwt'] )
				&& ! empty( $data['refreshJwt'] )
				&& ! empty( $data['did'] )
			) {
				$this->set_access_jwt( sanitize_text_field( $data['accessJwt'] ) );
				$this->set_refresh_jwt( sanitize_text_field( $data['refreshJwt'] ) );
				$this->set_did( sanitize_text_field( $data['did'] ) );
				$this->set_password( '' );
			}
		}
	}

	/**
	 * Refresh the token.
	 */
	public function refresh_token(): void {
		$session_url = $this->get_domain() . 'xrpc/com.atproto.server.refreshSession';

		$response = wp_safe_remote_post(
			esc_url_raw( $session_url ),
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->get_refresh_jwt(),
				],
			]
		);

		if (
			is_wp_error( $response ) ||
			wp_remote_retrieve_response_code( $response ) >= 300
		) {
			// @todo show/save error.
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if (
			! empty( $data['accessJwt'] )
			&& ! empty( $data['refreshJwt'] )
		) {
			$this->set_access_jwt( sanitize_text_field( $data['accessJwt'] ) );
			$this->set_refresh_jwt( sanitize_text_field( $data['refreshJwt'] ) );
		}
	}

	/**
	 * Syndicate the post.
	 *
	 * @param \WP_Post $post The post ID.
	 */
	public function syndicate_post( \WP_Post $post ): void {
		/**
		 * We should check to see if the token has been refreshed recently
		 * and only refresh it if it has been a while.
		 */
		$this->refresh_token();

		if ( ! $this->get_access_jwt() || ! $this->get_did() || ! $this->get_domain() ) {
			return;
		}

		$record = new Record( $post );

		$body = wp_json_encode(
			[
				'collection' => 'app.bsky.feed.post',
				'did'        => esc_html( $this->get_did() ),
				'repo'       => esc_html( $this->get_did() ),
				'record'     => $record->get(),
			]
		);

		if ( ! $body ) {
			return;
		}

		$response = wp_safe_remote_post(
			esc_url_raw( $this->get_domain() . 'xrpc/com.atproto.repo.createRecord' ),
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->get_access_jwt(),
				],
				'body'    => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			update_post_meta( $post->ID, '_syndicate_to_bluesky', $response->get_error_message() );
		} else {
			update_post_meta( $post->ID, '_syndicate_to_bluesky', wp_remote_retrieve_body( $response ) );
		}
	}
}
