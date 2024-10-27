<?php
/**
 * Manage admin settings for the plugin.
 *
 * @package syndicate-to-bluesky
 */

namespace SyndicateToBluesky;

/**
 * Manage admin settings for the plugin.
 */
class Admin {

	/**
	 * Initialize customizations in the WordPress admin.
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_options_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	/**
	 * Add the options page.
	 */
	public static function add_options_page(): void {
		add_options_page(
			esc_html__( 'Bluesky', 'syndicate-to-bluesky' ),
			esc_html__( 'Bluesky', 'syndicate-to-bluesky' ),
			'manage_options',
			'syndicate-to-bluesky',
			[ __CLASS__, 'settings_page' ]
		);
	}

	/**
	 * Register plugin settings fields.
	 */
	public static function register_settings(): void {
		register_setting(
			'syndicate-to-bluesky',
			'bluesky_domain',
			[
				'type'              => 'string',
				'description'       => __( 'The domain of your Bluesky instance', 'syndicate-to-bluesky' ),
				'default'           => 'https://bsky.social',
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		register_setting(
			'syndicate-to-bluesky',
			'bluesky_password',
			[
				'type'              => 'string',
				'description'       => __( 'The password of your Bluesky account (will not be stored permanently)', 'syndicate-to-bluesky' ),
				'sanitize_callback' => 'stripslashes',
			]
		);

		register_setting(
			'syndicate-to-bluesky',
			'bluesky_identifier',
			[
				'type'              => 'string',
				'description'       => __( 'The identifier of your Bluesky account', 'syndicate-to-bluesky' ),
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
	}

	/**
	 * Render the settings page.
	 */
	public static function settings_page(): void {
		$connection = new Connection();

		// A password has been temporarily stored, use it to retrieve the access token.
		if ( $connection->get_identifier() && $connection->get_password() ) {
			$connection->get_access_token();
		}

		printf(
			'<h2 id="syndicate-to-bluesky">%1$s</h2>',
			esc_html__( 'Syndicate to Bluesky', 'syndicate-to-bluesky' )
		);
		?>
		<div>
			<form method="post" action="options.php">
				<?php settings_fields( 'syndicate-to-bluesky' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr class="domain-wrap">
							<th>
								<label for="bluesky-domain"><?php esc_html_e( 'Bluesky Domain', 'syndicate-to-bluesky' ); ?></label>
							</th>
							<td>
								<input type="text" name="bluesky_domain" id="bluesky-domain" value="<?php echo esc_attr( $connection->get_domain() ); ?>" placeholder="https://bsky.social" />
								<p class="description" id="bluesky-domain-description">
									<?php esc_html_e( 'The domain of your Bluesky instance. (This has to be a valid URL including "http(s)")', 'syndicate-to-bluesky' ); ?>
								</p>
							</td>
						</tr>

						<tr class="user-identifier-wrap">
							<th>
								<label for="bluesky-identifier"><?php esc_html_e( 'Bluesky "Identifier"', 'syndicate-to-bluesky' ); ?></label>
							</th>
							<td>
								<input type="text" name="bluesky_identifier" id="bluesky-identifier" aria-describedby="email-description" value="<?php echo esc_attr( $connection->get_identifier() ); ?>">
								<p class="description" id="bluesky-identifier-description">
									<?php esc_html_e( 'Your Bluesky identifier.', 'syndicate-to-bluesky' ); ?>
								</p>
							</td>
						</tr>

						<tr class="user-password-wrap">
							<th>
								<label for="bluesky-password"><?php esc_html_e( 'Password', 'syndicate-to-bluesky' ); ?></label>
							</th>
							<td>
								<input type="text" name="bluesky_password" id="bluesky-password" class="regular-text code" value="<?php echo esc_attr( $connection->get_password() ); ?>">
								<p class="description" id="bluesky-password-description">
									<?php esc_html_e( 'Your Bluesky application password. It is needed to get an Access-Token and will not be stored anywhere.', 'syndicate-to-bluesky' ); ?>
								</p>
							</td>
						</tr>

					</tbody>
				</table>
				<?php do_settings_sections( 'syndicate-to-bluesky' ); ?>

				<?php submit_button(); ?>
			</form>

			<details>
				<summary><?php esc_html_e( 'Debug Information', 'syndicate-to-bluesky' ); ?></summary>
				<table class="form-table" role="presentation">
					<tbody>
						<tr class="access-token-wrap">
							<th>
								<label for="bluesky-did"><?php esc_html_e( 'DID', 'syndicate-to-bluesky' ); ?></label>
							</th>
							<td>
								<input id="bluesky-did" type="text" class="regular-text code" value="<?php echo esc_attr( $connection->get_did() ); ?>" readonly>
							</td>
						</tr>
						<tr class="access-token-wrap">
							<th>
								<label for="bluesky-access-jwt"><?php esc_html_e( 'Access Token', 'syndicate-to-bluesky' ); ?></label>
							</th>
							<td>
								<input id="bluesky-access-jwt" type="text" class="regular-text code" value="<?php echo esc_attr( $connection->get_access_jwt() ); ?>" readonly>
							</td>
						</tr>
						<tr class="access-token-wrap">
							<th>
								<label for="bluesky-refresh-jwt"><?php esc_html_e( 'Refresh Token', 'syndicate-to-bluesky' ); ?></label>
							</th>
							<td>
								<input id="bluesky-refresh-jwt" type="text" class="regular-text code" value="<?php echo esc_attr( $connection->get_refresh_jwt() ); ?>" readonly>
							</td>
						</tr>
					</tbody>
				</table>
			</details>
		</div>
		<?php
	}
}
