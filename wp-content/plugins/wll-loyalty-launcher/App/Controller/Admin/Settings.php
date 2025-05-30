<?php

namespace WLL\App\Controller\Admin;

use WLL\App\Helper\Guest;
use WLL\App\Helper\Input;
use WLL\App\Helper\Member;
use WLL\App\Helper\Util;
use WLL\App\Helper\Validation;
use WLL\App\Helper\WC;
use WLL\App\Helper\Settings as SettingsHelper;

defined( 'ABSPATH' ) or die;

class Settings {
	/**
	 * Get settings.
	 *
	 * @return void
	 */
	public static function getSettings() {
		if ( ! WC::isSecurityValid( 'wll_launcher_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		$is_admin_side = Util::isAdminSide();
		//design
		$design_settings = SettingsHelper::getDesignSettings();
		//content admin side not translated values fetch
		$guest_content    = Guest::getGuestContentData( $is_admin_side );
		$member_content   = Member::getMemberContentData( $is_admin_side );
		$content_settings = [ 'content' => array_merge( $guest_content, $member_content ) ];
		//popup button
		$popup_button_settings = SettingsHelper::getLauncherButtonContentData( $is_admin_side );
		wp_send_json_success( array_merge( $design_settings, $content_settings, $popup_button_settings ) );
	}

	/**
	 * Save design settings.
	 *
	 * This method saves the design settings after validation.
	 *
	 * @return void
	 */
	public static function saveDesign() {
		if ( ! WC::isSecurityValid( 'wll_design_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		$design = (string) Input::get( 'design' );
		if ( empty( $design ) ) {
			wp_send_json_error( [ 'message' => __( 'Settings not saved!', 'wll-loyalty-launcher' ) ] );
		}
		$design = json_decode( stripslashes( base64_decode( $design ) ), true );
		if ( empty( $design ) ) {
			wp_send_json_error( [ 'message' => __( 'Settings not saved!', 'wll-loyalty-launcher' ) ] );
		}
		// validation
		$validate_data = Validation::validateDesignTab( [ 'design' => $design ] );
		if ( is_array( $validate_data ) ) {
			foreach ( $validate_data as $key => $validate ) {
				$validate_data[ $key ] = [ current( $validate ) ];
			}
			wp_send_json_error( [
				'message'     => __( 'Settings not saved!', 'wll-loyalty-launcher' ),
				'field_error' => $validate_data
			] );
		}
		update_option( 'wll_launcher_design_settings', [ 'design' => $design ] );
		wp_send_json_success( [ 'message' => __( 'Settings saved!', 'wll-loyalty-launcher' ) ] );
	}

	/**
	 * Save content settings.
	 *
	 * This method saves the content settings after validation.
	 *
	 * @return void
	 */
	public static function saveContent() {
		if ( ! WC::isSecurityValid( 'wll_content_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		$content = (string) Input::get( 'content' );
		if ( empty( $content ) ) {
			wp_send_json_error( [ 'message' => __( 'Settings not saved!', 'wll-loyalty-launcher' ) ] );
		}
		$content = json_decode( stripslashes( base64_decode( $content ) ), true );
		if ( empty( $content ) ) {
			wp_send_json_error( [ 'message' => __( 'Settings not saved!', 'wll-loyalty-launcher' ) ] );
		}

		$validate_data = Validation::validateContentTab( [ 'content' => $content ] );
		if ( is_array( $validate_data ) ) {
			foreach ( $validate_data as $key => $validate ) {
				$validate_data[ $key ] = [ current( $validate ) ];
			}
			wp_send_json_error( [
				'message'     => __( 'Settings not saved!', 'wll-loyalty-launcher' ),
				'field_error' => $validate_data
			] );
		}
		update_option( 'wll_launcher_content_settings', [ 'content' => $content ] );
		wp_send_json_success( [ 'message' => __( 'Settings saved!', 'wll-loyalty-launcher' ) ] );
	}

	/**
	 * Save launcher settings.
	 *
	 * This method saves the launcher settings after validation.
	 *
	 * @return void
	 */
	public static function saveLauncher() {
		if ( ! WC::isSecurityValid( 'wll_launcher_settings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		$launcher = (string) Input::get( 'launcher' );
		if ( empty( $launcher ) ) {
			wp_send_json_error( [ 'message' => __( 'Settings not saved!', 'wll-loyalty-launcher' ) ] );
		}
		$launcher = json_decode( stripslashes( base64_decode( $launcher ) ), true );
		if ( empty( $launcher ) ) {
			wp_send_json_error( [ 'message' => __( 'Settings not saved!', 'wll-loyalty-launcher' ) ] );
		}
		$validate_data = Validation::validateLauncherTab( [ 'launcher' => $launcher ] );
		if ( is_array( $validate_data ) ) {
			foreach ( $validate_data as $key => $validate ) {
				$validate_data[ $key ] = [ current( $validate ) ];
			}
			wp_send_json_error( [
				'message'     => __( 'Settings not saved!', 'wll-loyalty-launcher' ),
				'field_error' => $validate_data
			] );
		}
		update_option( 'wll_launcher_icon_settings', [ 'launcher' => $launcher ] );
		wp_send_json_success( [ 'message' => __( 'Settings saved!', 'wll-loyalty-launcher' ) ] );
	}
}