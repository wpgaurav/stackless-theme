<?php
/**
 * Anonymous updates for themes downloaded from GauravTiwari.org.
 * Included only in the direct-download distribution, never WordPress.org builds.
 *
 * @package GatilabThemeUpdates
 * @license GPL-2.0-or-later
 */

namespace Gatilab\ThemeUpdates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( __NAMESPACE__ . '\\Client', false ) ) {
	return;
}

final class Client {
	const HOST = 'gauravtiwari.org';
	const API  = 'https://gauravtiwari.org/wp-json/gt-theme-updates/v1/themes/';
	private static $booted = false;

	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;
		add_filter( 'update_themes_' . self::HOST, array( __CLASS__, 'check' ), 10, 4 );
		add_filter( 'upgrader_pre_download', array( __CLASS__, 'download' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_after_update' ), 10, 2 );
	}

	private static function owns( $slug, $uri ) {
		return is_string( $slug ) && preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug )
			&& $uri === 'https://' . self::HOST . '/product/' . $slug . '/';
	}

	private static function key( $slug ) {
		return 'gt_theme_release_v1_' . md5( $slug );
	}

	public static function allowed_package( $url ) {
		$parts = is_string( $url ) ? wp_parse_url( $url ) : false;
		return is_array( $parts ) && ( $parts['scheme'] ?? '' ) === 'https'
			&& empty( $parts['user'] ) && empty( $parts['pass'] ) && empty( $parts['port'] )
			&& (bool) preg_match( '/^[a-z0-9-]+\.[a-f0-9]+\.r2\.cloudflarestorage\.com$/D', $parts['host'] ?? '' );
	}

	private static function valid_release( $data, $slug ) {
		return is_array( $data ) && ( $data['schema'] ?? 0 ) === 1
			&& ( $data['theme'] ?? '' ) === $slug && ( $data['channel'] ?? '' ) === 'direct'
			&& is_string( $data['version'] ?? null ) && preg_match( '/^[0-9]+(?:\.[0-9]+){1,3}(?:-[a-z0-9.]+)?$/iD', $data['version'] )
			&& is_string( $data['sha256'] ?? null ) && preg_match( '/^[a-f0-9]{64}$/D', $data['sha256'] )
			&& (int) ( $data['package_expires'] ?? 0 ) > time() + 60
			&& self::allowed_package( $data['package'] ?? null );
	}

	public static function release( $slug, $refresh = false ) {
		if ( ! self::owns( $slug, 'https://' . self::HOST . '/product/' . $slug . '/' ) ) {
			return false;
		}
		$cached = get_site_transient( self::key( $slug ) );
		if ( ! $refresh && self::valid_release( $cached, $slug ) && (int) ( $cached['_checked_at'] ?? 0 ) > time() - 6 * HOUR_IN_SECONDS ) {
			return $cached;
		}
		if ( ! $refresh && get_site_transient( self::key( $slug ) . '_retry' ) ) {
			return self::valid_release( $cached, $slug ) ? $cached : false;
		}
		global $wp_version;
		$response = wp_safe_remote_get(
			self::API . rawurlencode( $slug ),
			array(
				'timeout' => 10, 'redirection' => 0, 'limit_response_size' => 65536,
				'headers' => array( 'Accept' => 'application/json' ),
				'user-agent' => 'Gatilab-Theme-Updater/1.0 WordPress/' . $wp_version,
			)
		);
		$data = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) || ! self::valid_release( $data, $slug ) ) {
			set_site_transient( self::key( $slug ) . '_retry', true, 15 * MINUTE_IN_SECONDS );
			return self::valid_release( $cached, $slug ) ? $cached : false;
		}
		$data['_checked_at'] = time();
		// Retain the offered checksum after the signed URL expires; refresh before download.
		set_site_transient( self::key( $slug ), $data, 7 * DAY_IN_SECONDS );
		delete_site_transient( self::key( $slug ) . '_retry' );
		return $data;
	}

	public static function check( $update, $theme_data, $slug, $locales ) {
		unset( $locales );
		if ( ! self::owns( $slug, $theme_data['UpdateURI'] ?? '' ) ) {
			return $update;
		}
		$release = self::release( $slug );
		if ( ! $release ) {
			return $update;
		}
		return array(
			'id' => $theme_data['UpdateURI'], 'theme' => $slug, 'version' => $release['version'],
			'url' => 'https://' . self::HOST . '/product/' . $slug . '/',
			'package' => $release['package'], 'requires' => $release['requires'] ?? '',
			'requires_php' => $release['requires_php'] ?? '', 'tested' => $release['tested'] ?? '',
		);
	}

	public static function download( $reply, $package, $upgrader, $extra ) {
		// Bulk theme runs pass a theme slug but omit type/action in hook_extra.
		if ( false !== $reply || ! is_a( $upgrader, 'Theme_Upgrader' ) || empty( $extra['theme'] ) || ( isset( $extra['action'] ) && 'update' !== $extra['action'] ) ) {
			return $reply;
		}
		$slug = $extra['theme'] ?? '';
		$theme = wp_get_theme( $slug );
		if ( ! self::owns( $slug, $theme->get( 'UpdateURI' ) ) ) {
			return $reply;
		}
		$cached = get_site_transient( self::key( $slug ) );
		// Match the offered package before refreshing an expired signed URL.
		$offered = is_string( $package ) ? wp_parse_url( $package ) : false;
		$stored = is_array( $cached ) ? wp_parse_url( $cached['package'] ?? '' ) : false;
		if ( ! self::allowed_package( $package ) || ! $stored || ( $offered['host'] ?? '' ) !== ( $stored['host'] ?? '' ) || ( $offered['path'] ?? '' ) !== ( $stored['path'] ?? '' ) ) {
			return new \WP_Error( 'gt_theme_update_metadata', __( 'Check for theme updates again before installing this release.', '__GT_THEME_TEXT_DOMAIN__' ) );
		}
		$release = self::release( $slug );
		if ( ! $release || $release['version'] !== $cached['version'] || $release['sha256'] !== $cached['sha256'] ) {
			return new \WP_Error( 'gt_theme_update_changed', __( 'The theme release changed. Check for updates again.', '__GT_THEME_TEXT_DOMAIN__' ) );
		}
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$file = wp_tempnam( $slug . '.zip' );
		if ( ! $file ) {
			return new \WP_Error( 'gt_theme_update_temp', __( 'A temporary theme download could not be created.', '__GT_THEME_TEXT_DOMAIN__' ) );
		}
		global $wp_version;
		$response = wp_safe_remote_get( $release['package'], array( 'timeout' => 120, 'redirection' => 0, 'stream' => true, 'filename' => $file, 'limit_response_size' => 50 * MB_IN_BYTES, 'user-agent' => 'Gatilab-Theme-Updater/1.0 WordPress/' . $wp_version ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			wp_delete_file( $file );
			return new \WP_Error( 'gt_theme_update_download', __( 'The theme download failed. Please try again.', '__GT_THEME_TEXT_DOMAIN__' ) );
		}
		if ( ! hash_equals( $release['sha256'], hash_file( 'sha256', $file ) ) ) {
			wp_delete_file( $file );
			return new \WP_Error( 'gt_theme_update_checksum', __( 'The theme download could not be verified. Please try again.', '__GT_THEME_TEXT_DOMAIN__' ) );
		}
		return $file;
	}

	public static function clear_after_update( $upgrader, $extra ) {
		unset( $upgrader );
		if ( ( $extra['type'] ?? '' ) !== 'theme' ) {
			return;
		}
		$slugs = $extra['themes'] ?? array( $extra['theme'] ?? '' );
		foreach ( $slugs as $slug ) {
			if ( is_string( $slug ) && '' !== $slug ) {
				delete_site_transient( self::key( $slug ) );
				delete_site_transient( self::key( $slug ) . '_retry' );
			}
		}
	}
}
