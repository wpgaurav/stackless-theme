<?php
/**
 * License-gated updates for the Stackless theme downloaded from GauravTiwari.org.
 * Included only in the direct-download distribution, never WordPress.org builds.
 * The key gates theme updates only; every theme feature works without it.
 *
 * @package Stackless
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Stackless_Theme_License', false ) ) {
	return;
}

final class Stackless_Theme_License {
	const SLUG         = 'stackless-theme';
	const PRODUCT_ID   = 1183774;
	const SERVER       = 'https://gauravtiwari.org/';
	const ACCOUNT      = 'https://gauravtiwari.org/account/licenses/';
	const OPTION       = 'stackless_theme_license';
	const UPDATE_CACHE = 'stackless_theme_license_update';
	const NOTICE       = 'stackless_theme_license_notice';
	const CRON         = 'stackless_theme_license_check';
	const PAGE         = 'stackless-license';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_stackless_theme_license', array( __CLASS__, 'handle' ) );
		add_action( 'admin_notices', array( __CLASS__, 'reminder' ) );
		add_filter( 'pre_set_site_transient_update_themes', array( __CLASS__, 'updates' ) );
		add_action( self::CRON, array( __CLASS__, 'check' ) );
		add_action( 'switch_theme', array( __CLASS__, 'unschedule' ) );
		add_action( 'admin_init', array( __CLASS__, 'schedule' ) );
	}

	private static function version() {
		return (string) wp_get_theme( self::SLUG )->get( 'Version' );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::CRON );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON );
	}

	public static function state() {
		$data = get_option( self::OPTION, array() );
		return wp_parse_args(
			is_array( $data ) ? $data : array(),
			array( 'key' => '', 'status' => 'unregistered', 'activation_hash' => '', 'expires' => '', 'checked_at' => 0 )
		);
	}

	private static function save( $data ) {
		update_option( self::OPTION, $data, false );
	}

	private static function request( $action, $params ) {
		if ( ! in_array( $action, array( 'activate_license', 'check_license', 'deactivate_license', 'get_license_version' ), true ) ) {
			return new WP_Error( 'stackless_license_action', __( 'Unsupported license request.', 'stackless-theme' ) );
		}
		$response = wp_remote_post(
			add_query_arg( 'fluent-cart', $action, self::SERVER ),
			array(
				'timeout'             => 15,
				'redirection'         => 0,
				'sslverify'           => true,
				'limit_response_size' => 131072,
				'body'                => array_merge(
					array( 'item_id' => self::PRODUCT_ID, 'site_url' => home_url( '/' ), 'current_version' => self::version() ),
					$params
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'stackless_license_connection', __( 'Could not reach the license server. Your saved license has been kept.', 'stackless-theme' ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 422 === $code && is_array( $data ) && ! empty( $data['error_type'] ) ) {
			$messages = array(
				'license_not_found'  => __( 'This license key was not found. Copy it again from your GauravTiwari.org account.', 'stackless-theme' ),
				'key_mismatch'       => __( 'This key belongs to another product. Enter your Stackless theme license key.', 'stackless-theme' ),
				'license_not_active' => __( 'This license is inactive. Contact support from your GauravTiwari.org account.', 'stackless-theme' ),
				'license_expired'    => __( 'This license has expired. Get a new free key from the Stackless theme page.', 'stackless-theme' ),
			);
			return new WP_Error( 'stackless_license_invalid', $messages[ $data['error_type'] ] ?? __( 'This key could not be verified for the Stackless theme.', 'stackless-theme' ) );
		}
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
			return new WP_Error( 'stackless_license_response', __( 'The license server could not verify this request. Please try again.', 'stackless-theme' ) );
		}
		return $data;
	}

	private static function status( $data ) {
		$status = $data['status'] ?? $data['license'] ?? 'invalid';
		if ( isset( $data['success'] ) && ! $data['success'] && 'valid' === $status ) {
			return 'invalid';
		}
		return in_array( $status, array( 'valid', 'expired', 'disabled', 'invalid', 'inactive', 'unregistered' ), true ) ? $status : 'invalid';
	}

	public static function activate( $key ) {
		$key = trim( sanitize_text_field( $key ) );
		if ( '' === $key || strlen( $key ) > 512 ) {
			return new WP_Error( 'stackless_license_key', __( 'Enter a valid license key.', 'stackless-theme' ) );
		}
		if ( ! empty( self::state()['key'] ) ) {
			return new WP_Error( 'stackless_license_existing', __( 'Deactivate the saved license before activating a different key.', 'stackless-theme' ) );
		}
		$data = self::request( 'activate_license', array( 'license_key' => $key ) );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['success'] ) || 'valid' !== self::status( $data ) || empty( $data['activation_hash'] ) ) {
			return new WP_Error( 'stackless_license_invalid', __( 'This key could not be activated for the Stackless theme.', 'stackless-theme' ) );
		}
		self::save(
			array(
				'key'             => $key,
				'status'          => 'valid',
				'activation_hash' => sanitize_text_field( $data['activation_hash'] ),
				'expires'         => sanitize_text_field( $data['expiration_date'] ?? '' ),
				'checked_at'      => time(),
			)
		);
		self::clear_update();
		return true;
	}

	public static function check() {
		$state = self::state();
		if ( empty( $state['key'] ) ) {
			return new WP_Error( 'stackless_license_missing', __( 'No license key is saved.', 'stackless-theme' ) );
		}
		$params = $state['activation_hash'] ? array( 'activation_hash' => $state['activation_hash'] ) : array( 'license_key' => $state['key'] );
		$data   = self::request( 'check_license', $params );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$state['status']     = self::status( $data );
		$state['checked_at'] = time();
		if ( isset( $data['expiration_date'] ) ) {
			$state['expires'] = sanitize_text_field( $data['expiration_date'] );
		}
		self::save( $state );
		self::clear_update();
		return 'valid' === $state['status'] ? true : new WP_Error( 'stackless_license_status', __( 'The saved license is not currently valid. The theme keeps working; updates are paused.', 'stackless-theme' ) );
	}

	public static function deactivate() {
		$state = self::state();
		if ( empty( $state['key'] ) ) {
			return new WP_Error( 'stackless_license_missing', __( 'No license key is saved.', 'stackless-theme' ) );
		}
		$data = self::request( 'deactivate_license', array( 'license_key' => $state['key'], 'activation_hash' => $state['activation_hash'] ) );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['success'] ) ) {
			return new WP_Error( 'stackless_license_deactivate', __( 'The server could not deactivate this website. Your saved key has been kept so you can retry.', 'stackless-theme' ) );
		}
		delete_option( self::OPTION );
		self::clear_update();
		return true;
	}

	private static function clear_update() {
		delete_transient( self::UPDATE_CACHE );
		$cache = get_site_transient( 'update_themes' );
		if ( is_object( $cache ) ) {
			unset( $cache->response[ self::SLUG ], $cache->no_update[ self::SLUG ] );
			set_site_transient( 'update_themes', $cache );
		}
	}

	public static function updates( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked[ self::SLUG ] ) ) {
			return $transient;
		}
		$state   = self::state();
		$expired = $state['expires'] && 'lifetime' !== $state['expires'] && strtotime( $state['expires'] ) && strtotime( $state['expires'] ) < time();
		if ( 'valid' !== $state['status'] || empty( $state['key'] ) || $expired ) {
			unset( $transient->response[ self::SLUG ], $transient->no_update[ self::SLUG ] );
			return $transient;
		}
		$data = get_transient( self::UPDATE_CACHE );
		if ( false === $data ) {
			$params = $state['activation_hash'] ? array( 'activation_hash' => $state['activation_hash'] ) : array( 'license_key' => $state['key'] );
			$data   = self::request( 'get_license_version', $params );
			if ( ! is_wp_error( $data ) ) {
				set_transient( self::UPDATE_CACHE, $data, 4 * HOUR_IN_SECONDS );
			}
		}
		if ( is_wp_error( $data ) ) {
			return $transient;
		}
		if ( ! is_array( $data ) || 'valid' !== ( $data['license_status'] ?? '' ) || empty( $data['new_version'] ) || ! version_compare( $data['new_version'], self::version(), '>' ) || empty( $data['package'] ) ) {
			unset( $transient->response[ self::SLUG ] );
			return $transient;
		}
		$package = esc_url_raw( $data['package'] );
		if ( 'https' !== wp_parse_url( $package, PHP_URL_SCHEME ) ) {
			return $transient;
		}
		$transient->response[ self::SLUG ] = array(
			'theme'        => self::SLUG,
			'new_version'  => sanitize_text_field( $data['new_version'] ),
			'url'          => 'https://gauravtiwari.org/product/' . self::SLUG . '/',
			'package'      => $package,
			'requires'     => sanitize_text_field( $data['requires'] ?? '6.6' ),
			'requires_php' => sanitize_text_field( $data['requires_php'] ?? '7.4' ),
		);
		return $transient;
	}

	public static function menu() {
		add_theme_page( __( 'Stackless License', 'stackless-theme' ), __( 'Stackless License', 'stackless-theme' ), 'edit_theme_options', self::PAGE, array( __CLASS__, 'page' ) );
	}

	public static function reminder() {
		if ( ! current_user_can( 'edit_theme_options' ) || ! empty( self::state()['key'] ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'themes', 'update-core', 'dashboard' ), true ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'Activate your free Stackless theme license key to receive theme updates.', 'stackless-theme' ),
			esc_url( admin_url( 'themes.php?page=' . self::PAGE ) ),
			esc_html__( 'Add License Key', 'stackless-theme' )
		);
	}

	public static function handle() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You cannot manage this theme license.', 'stackless-theme' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'stackless_theme_license' );
		$action = sanitize_key( wp_unslash( $_POST['license_action'] ?? '' ) );
		if ( 'activate' === $action ) {
			$result = self::activate( wp_unslash( $_POST['license_key'] ?? '' ) );
		} elseif ( 'check' === $action ) {
			$result = self::check();
		} elseif ( 'deactivate' === $action ) {
			$result = self::deactivate();
		} else {
			$result = new WP_Error( 'stackless_license_action', __( 'Choose a license action.', 'stackless-theme' ) );
		}
		$labels  = array(
			'activate'   => __( 'License activated. Theme updates are on.', 'stackless-theme' ),
			'deactivate' => __( 'License deactivated on this website.', 'stackless-theme' ),
			'check'      => __( 'License verified.', 'stackless-theme' ),
		);
		$message = is_wp_error( $result ) ? $result->get_error_message() : $labels[ $action ];
		set_transient( self::NOTICE . '_' . get_current_user_id(), array( 'error' => is_wp_error( $result ), 'message' => $message ), MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'themes.php?page=' . self::PAGE ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		$state  = self::state();
		$notice = get_transient( self::NOTICE . '_' . get_current_user_id() );
		delete_transient( self::NOTICE . '_' . get_current_user_id() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Stackless License', 'stackless-theme' ); ?></h1>
			<?php if ( $notice ) : ?>
				<div class="notice <?php echo $notice['error'] ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Your free license key turns on theme updates. It is lifetime and works on any number of websites. The theme works fully without it.', 'stackless-theme' ); ?></p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php esc_html_e( 'Status', 'stackless-theme' ); ?></th><td><strong><?php echo esc_html( ucfirst( $state['status'] ) ); ?></strong></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Installed version', 'stackless-theme' ); ?></th><td><?php echo esc_html( self::version() ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Website', 'stackless-theme' ); ?></th><td><?php echo esc_html( home_url( '/' ) ); ?></td></tr>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="stackless_theme_license">
				<?php wp_nonce_field( 'stackless_theme_license' ); ?>
				<?php if ( ! $state['key'] ) : ?>
					<p><label for="stackless-license-key"><strong><?php esc_html_e( 'License key', 'stackless-theme' ); ?></strong></label></p>
					<p><input id="stackless-license-key" name="license_key" type="password" class="regular-text" autocomplete="off" spellcheck="false" maxlength="512" required></p>
					<p class="description">
						<?php esc_html_e( 'Find your key under Licenses in your GauravTiwari.org account. It is sent securely to GauravTiwari.org to verify this website.', 'stackless-theme' ); ?>
						<a href="<?php echo esc_url( self::ACCOUNT ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open my licenses', 'stackless-theme' ); ?></a>
					</p>
					<p><button class="button button-primary" name="license_action" value="activate"><?php esc_html_e( 'Activate License', 'stackless-theme' ); ?></button></p>
				<?php else : ?>
					<p><?php esc_html_e( 'A license key is saved. It is hidden for security.', 'stackless-theme' ); ?></p>
					<p>
						<button class="button button-primary" name="license_action" value="check"><?php esc_html_e( 'Check License', 'stackless-theme' ); ?></button>
						<button class="button" name="license_action" value="deactivate"><?php esc_html_e( 'Deactivate License', 'stackless-theme' ); ?></button>
					</p>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}
}
