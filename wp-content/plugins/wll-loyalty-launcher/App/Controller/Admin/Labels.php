<?php

namespace WLL\App\Controller\Admin;

use WLL\App\Helper\Loyalty;
use WLL\App\Helper\Settings;
use WLL\App\Helper\WC;

defined( 'ABSPATH' ) or die;

class Labels {
	/**
	 * Getting local data.
	 *
	 * @return void
	 */
	public static function getLocalData() {
		if ( ! WC::isSecurityValid( 'local_data' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		$is_pro          = Loyalty::isPro();
		$short_code_list = Settings::getShortCodeList();
		$short_codes     = [];
		foreach ( $short_code_list as $key => $short_code ) {
			$short_codes[] = array(
				'value' => $short_code,
				'label' => $key,
			);
		}
		$localize = [
			'is_pro'                  => $is_pro,
			'common'                  => [
				'back_to_apps_url' => admin_url( 'admin.php?' . http_build_query( [ 'page' => WLR_PLUGIN_SLUG ] ) ) . '#/apps',
			],
			'plugin_name'             => WLL_PLUGIN_NAME,
			'version'                 => 'v' . WLL_PLUGIN_VERSION,
			'short_code_lists'        => $short_codes,
			'render_admin_page_nonce' => WC::createNonce( 'render_page_nonce' ),
			'common_nonce'            => WC::createNonce( 'common_nonce' ),
			'design_nonce'            => WC::createNonce( 'wll_design_settings' ),
			'content_nonce'           => WC::createNonce( 'wll_content_settings' ),
			'launcher_nonce'          => WC::createNonce( 'wll_launcher_settings' ),
			'settings_nonce'          => WC::createNonce( 'wll_launcher_settings' ),
		];
		$localize = apply_filters( 'wll_launcher_local_data', $localize );
		wp_send_json_success( $localize );
	}

	/**
	 * Getting labels data.
	 *
	 * @return void
	 */
	public static function getLabels() {
		if ( ! WC::isSecurityValid( 'common_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Basic check failed', 'wll-loyalty-launcher' ) ] );
		}
		$label_data = [
			'common'            => self::getCommonLabels(),
			'color_list'        => [
				[ 'label' => __( 'Primary', 'wll-loyalty-launcher' ), 'value' => 'primary' ],
				[ 'label' => __( 'Secondary', 'wll-loyalty-launcher' ), 'value' => 'secondary' ]
			],
			'text_color_list'   => [
				[ 'label' => __( 'White', 'wll-loyalty-launcher' ), 'value' => 'white' ],
				[ 'label' => __( 'Black', 'wll-loyalty-launcher' ), 'value' => 'black' ]
			],
			'short_codes'       => __( 'Shortcodes', 'wll-loyalty-launcher' ),
			'design'            => self::getLauncherDesignLabels(),
			'guest'             => self::getLauncherGuestLabels(),
			'member'            => self::getLauncherMemberLabels(),
			'popup_button'      => self::getLauncherContentLabels(),
			'shortcodes'        => self::getShortCodeWithLabels(),
			'social_share_list' => self::getSocialShareList(),
		];
		wp_send_json_success( $label_data );
	}

	/**
	 * Retrieves common labels used in the plugin.
	 *
	 * @return array Array containing various labels used in the plugin.
	 */
	public static function getCommonLabels() {
		return [
			'plugin_name'                  => WLL_PLUGIN_NAME,
			'version'                      => 'v' . WLL_PLUGIN_VERSION,
			'save'                         => __( 'Save Changes', 'wll-loyalty-launcher' ),
			'upgrade_text'                 => __( 'Upgrade to Pro', 'wll-loyalty-launcher' ),
			'buy_pro_url'                  => 'https://wployalty.net/pricing/?utm_campaign=wployalty-link&utm_medium=pro_url&utm_source=pricing',
			'launcher_power_by_url'        => 'https://wployalty.net/?utm_campaign=wployalty-link&utm_medium=launcher&utm_source=powered_by',
			'reset'                        => __( 'Reset', 'wll-loyalty-launcher' ),
			'back'                         => __( 'Back', 'wll-loyalty-launcher' ),
			'back_to_apps'                 => __( 'Back to WPLoyalty', 'wll-loyalty-launcher' ),
			'edit_styles'                  => __( 'Edit Styles', 'wll-loyalty-launcher' ),
			'design'                       => __( 'Design', 'wll-loyalty-launcher' ),
			'content'                      => __( 'Content', 'wll-loyalty-launcher' ),
			'launcher'                     => __( 'Launcher', 'wll-loyalty-launcher' ),
			'default'                      => __( 'Default', 'wll-loyalty-launcher' ),
			'upload_icon'                  => __( 'Upload icon', 'wll-loyalty-launcher' ),
			'icon'                         => __( 'Icon', 'wll-loyalty-launcher' ),
			'icon_buttons'                 => [
				'restore' => __( 'Restore Default', 'wll-loyalty-launcher' ),
				'browse'  => __( 'Browse Image', 'wll-loyalty-launcher' ),
			],
			'background'                   => __( 'Background', 'wll-loyalty-launcher' ),
			'text'                         => __( 'Text', 'wll-loyalty-launcher' ),
			'texts'                        => __( 'Texts', 'wll-loyalty-launcher' ),
			'link'                         => __( 'Link', 'wll-loyalty-launcher' ),
			'color'                        => __( 'Color', 'wll-loyalty-launcher' ),
			'colors'                       => __( 'Colors', 'wll-loyalty-launcher' ),
			'buttons'                      => __( 'Buttons', 'wll-loyalty-launcher' ),
			'title'                        => __( 'Title', 'wll-loyalty-launcher' ),
			'description'                  => __( 'Description', 'wll-loyalty-launcher' ),
			'visibility'                   => __( 'Visibility', 'wll-loyalty-launcher' ),
			'show'                         => __( 'Show', 'wll-loyalty-launcher' ),
			'none'                         => __( 'Do not show', 'wll-loyalty-launcher' ),
			'restore_default'              => __( 'Restore Default', 'wll-loyalty-launcher' ),
			'browse_image'                 => __( 'Browse Image', 'wll-loyalty-launcher' ),
			'left'                         => __( 'Left', 'wll-loyalty-launcher' ),
			'right'                        => __( 'Right', 'wll-loyalty-launcher' ),
			'mobile_only'                  => __( 'Mobile Only', 'wll-loyalty-launcher' ),
			'desktop_only'                 => __( 'Desktop Only', 'wll-loyalty-launcher' ),
			'mobile_and_desktop'           => __( 'Mobile and Desktop', 'wll-loyalty-launcher' ),
			'display_none'                 => __( 'Do not show', 'wll-loyalty-launcher' ),
			'image_description'            => __( 'Choose an image to preview.', 'wll-loyalty-launcher' ),
			'logo_image'                   => __( 'Your logo', 'wll-loyalty-launcher' ),
			'font_family'                  => __( 'Font Family', 'wll-loyalty-launcher' ),
			'white'                        => __( 'White', 'wll-loyalty-launcher' ),
			'black'                        => __( 'Black', 'wll-loyalty-launcher' ),
			'primary'                      => __( 'Primary', 'wll-loyalty-launcher' ),
			'secondary'                    => __( 'Secondary', 'wll-loyalty-launcher' ),
			'back_to_loyalty'              => __( 'Back to WPLoyalty', 'wll-loyalty-launcher' ),
			'reset_message'                => __( 'Reset Successfully', 'wll-loyalty-launcher' ),
			'theme_color'                  => __( 'Color', 'wll-loyalty-launcher' ),
			'no_result_found'              => __( 'No results found!', 'wll-loyalty-launcher' ),
			'toggle'                       => [
				'activate'   => __( 'click to activate', 'wll-loyalty-launcher' ),
				'deactivate' => __( 'click to de-activate', 'wll-loyalty-launcher' ),
			],
			'visibility_list'              => [
				[ 'label' => __( 'Show', 'wll-loyalty-launcher' ), 'value' => 'show' ],
				[ 'label' => __( 'None', 'wll-loyalty-launcher' ), 'value' => 'none' ],
			],
			'banner'                       => __( 'Banner', 'wll-loyalty-launcher' ),
			'enabled'                      => __( 'Enabled', 'wll-loyalty-launcher' ),
			'disabled'                     => __( 'Disabled', 'wll-loyalty-launcher' ),
			'welcome'                      => __( 'Welcome', 'wll-loyalty-launcher' ),
			'referrals'                    => __( 'Referrals', 'wll-loyalty-launcher' ),
			'points'                       => __( 'Earn & Redeem', 'wll-loyalty-launcher' ),
			'earn'                         => __( 'Earn', 'wll-loyalty-launcher' ),
			'redeem'                       => __( 'Redeem', 'wll-loyalty-launcher' ),
			'level_name'                   => __( 'Level A', 'wll-loyalty-launcher' ),
			'ok_text'                      => __( 'Yes, Reset', 'wll-loyalty-launcher' ),
			'cancel_text'                  => __( 'Cancel', 'wll-loyalty-launcher' ),
			'confirm_title'                => __( 'Reset Settings?', 'wll-loyalty-launcher' ),
			'confirm_description'          => __( 'Are you sure want to reset this settings?', 'wll-loyalty-launcher' ),
			'dummy_preview_message'        => __( 'This preview uses dummy records.', 'wll-loyalty-launcher' ),
			'powered_by'                   => __( 'Powered by', 'wll-loyalty-launcher' ),
			'wpl_loyalty_text'             => __( 'WPLoyalty', 'wll-loyalty-launcher' ),
			'rewards_title'                => __( 'My Rewards', 'wll-loyalty-launcher' ),
			'coupons_title'                => __( 'My Coupons', 'wll-loyalty-launcher' ),
			'apply_button_text'            => __( 'Apply', 'wll-loyalty-launcher' ),
			'show_launcher_condition_text' => __( 'Show widget on specific locations based on conditions', 'wll-loyalty-launcher' ),
			'add_condition_text'           => __( 'Add Condition', 'wll-loyalty-launcher' ),
			'url_text'                     => __( "URL's", 'wll-loyalty-launcher' ),
			'home_text'                    => __( "Home page", 'wll-loyalty-launcher' ),
			'contains_text'                => __( "Contains", 'wll-loyalty-launcher' ),
			'do_not_contains_text'         => __( "Do not contains", 'wll-loyalty-launcher' ),
			'match_all'                    => __( "Match All", 'wll-loyalty-launcher' ),
			'match_any'                    => __( "Match Any", 'wll-loyalty-launcher' ),
			'conditions_text'              => __( "Conditions", 'wll-loyalty-launcher' ),
			'delete_text'                  => __( "delete", 'wll-loyalty-launcher' ),

			'rewards_tab' => [
				'reward_opportunity' => __( 'Reward Opportunities', 'wll-loyalty-launcher' ),
				'my_rewards'         => __( 'My Rewards', 'wll-loyalty-launcher' ),
			],
		];
	}

	/**
	 * Retrieve the design labels for the launcher settings.
	 *
	 * @return array An associative array containing design labels for various launcher settings.
	 */
	public static function getLauncherDesignLabels() {
		return array(
			'logo'      => [
				'title'      => __( 'Logo', 'wll-loyalty-launcher' ),
				'visibility' => __( 'VISIBILITY', 'wll-loyalty-launcher' ),
				'image'      => [
					'description' => __( 'Choose your logo from the media library', 'wll-loyalty-launcher' ),
				],
			],
			'colors'    => [
				'title'       => __( 'Colors', 'wll-loyalty-launcher' ),
				'theme_title' => __( 'THEME', 'wll-loyalty-launcher' ),
				'theme_color' => __( 'Theme Color', 'wll-loyalty-launcher' ),
				'banner'      => __( 'BANNER', 'wll-loyalty-launcher' ),
				'buttons'     => __( 'BUTTONS', 'wll-loyalty-launcher' ),
				'links'       => __( 'LINKS', 'wll-loyalty-launcher' ),
				'icons'       => __( 'ICONS', 'wll-loyalty-launcher' ),
				'theme'       => [
					'primary'   => __( 'Primary', 'wll-loyalty-launcher' ),
					'secondary' => __( 'Secondary', 'wll-loyalty-launcher' ),
				],
			],
			'placement' => [
				'title'    => __( 'Placement', 'wll-loyalty-launcher' ),
				'position' => [
					'title'   => __( 'Position', 'wll-loyalty-launcher' ),
					'options' => [
						[ 'label' => __( 'Left', 'wll-loyalty-launcher' ), 'value' => 'left' ],
						[ 'label' => __( 'Right', 'wll-loyalty-launcher' ), 'value' => 'right' ]
					],
				],
				'spacing'  => [
					'title'        => __( 'Spacing', 'wll-loyalty-launcher' ),
					'description'  => __( 'The position of the panel and launcher relative to the customer\'s window. Only applies to desktop mode.', 'wll-loyalty-launcher' ),
					'side_space'   => __( 'Side spacing', 'wll-loyalty-launcher' ),
					'bottom_space' => __( 'Bottom spacing', 'wll-loyalty-launcher' ),
				],
			],
			'branding'  => __( 'Branding', 'wll-loyalty-launcher' ),
		);
	}

	/**
	 * Retrieve the guest labels for the launcher settings.
	 *
	 * @return array An associative array containing guest labels for the launcher, including title, welcome texts, and buttons.
	 */
	public static function getLauncherGuestLabels() {
		return [
			'title'   => __( 'Guest', 'wll-loyalty-launcher' ),
			'welcome' => [
				'texts'   => [
					'have_account' => __( 'Have an account?', 'wll-loyalty-launcher' ),
					'sign_in'      => __( 'Sign in', 'wll-loyalty-launcher' ),
				],
				'buttons' => [
					'create_account' => __( 'Create account', 'wll-loyalty-launcher' ),
				],
			],
		];
	}

	/**
	 * Retrieve the member labels for the launcher settings.
	 *
	 * @return array An associative array containing member labels for various launcher settings.
	 */
	public static function getLauncherMemberLabels() {
		return [
			'title'  => __( 'Member', 'wll-loyalty-launcher' ),
			'banner' => [
				'levels'            => __( 'LEVELS', 'wll-loyalty-launcher' ),
				'points'            => __( 'Points', 'wll-loyalty-launcher' ),
				'point_description' => __( 'Point description', 'wll-loyalty-launcher' ),
				'shortcodes'        => __( 'Shortcodes', 'wll-loyalty-launcher' ),
			],
		];
	}

	/**
	 * Retrieve the content labels for the launcher settings.
	 *
	 * @return array An associative array containing content labels for various launcher settings.
	 */
	public static function getLauncherContentLabels() {
		return array(
			'title'               => __( 'Launcher', 'wll-loyalty-launcher' ),
			'edit_launcher'       => __( 'Edit Launcher', 'wll-loyalty-launcher' ),
			'appearance_text'     => __( 'APPEARANCE', 'wll-loyalty-launcher' ),
			'icon_with_text'      => __( 'Icon with text', 'wll-loyalty-launcher' ),
			'icon_only'           => __( 'Icon only', 'wll-loyalty-launcher' ),
			'text_only'           => __( 'Text only', 'wll-loyalty-launcher' ),
			'icon_only_on_mobile' => __( 'Icon only on mobile', 'wll-loyalty-launcher' ),

			'appearance'    => [
				'visibility_list' => [
					[ 'label' => __( 'Icon with text', 'wll-loyalty-launcher' ), 'value' => 'icon_with_text' ],
					[ 'label' => __( 'Icon only', 'wll-loyalty-launcher' ), 'value' => 'icon_only' ],
					[ 'label' => __( 'Text only', 'wll-loyalty-launcher' ), 'value' => 'text_only' ],
				],
			],
			'view_option'   => __( 'View option', 'wll-loyalty-launcher' ),
			'view_options'  => [
				[
					'label' => __( 'Both mobile and desktop', 'wll-loyalty-launcher' ),
					'value' => 'both_mobile_desktop'
				],
				[ 'label' => __( 'Mobile only', 'wll-loyalty-launcher' ), 'value' => 'mobile_only' ],
				[ 'label' => __( 'Desktop only', 'wll-loyalty-launcher' ), 'value' => 'desktop_only' ],
			],
			'font'          => __( 'Font', 'wll-loyalty-launcher' ),
			'font_families' => self::getFontListLabels(),
		);
	}

	/**
	 * Retrieve the font list labels for selecting fonts.
	 *
	 * @return array An associative array containing font labels and their corresponding values for font selection.
	 */
	public static function getFontListLabels() {
		return apply_filters( 'wll_font_list', [
			[ 'label' => __( 'Inherit', 'wll-loyalty-launcher' ), 'value' => 'inherit' ],
			[ 'label' => __( 'Helvetica Neue', 'wll-loyalty-launcher' ), 'value' => 'helvetica' ],
			[ 'label' => __( 'Arial', 'wll-loyalty-launcher' ), 'value' => 'arial' ],
			[ 'label' => __( 'Courier New', 'wll-loyalty-launcher' ), 'value' => 'courier' ],
			[ 'label' => __( 'Impact', 'wll-loyalty-launcher' ), 'value' => 'impact' ],
		] );
	}

	/**
	 * Retrieve the shortcodes with labels for different user types.
	 *
	 * @return array An associative array containing shortcodes and labels for guest, member, and referral users.
	 */
	public static function getShortCodeWithLabels() {
		$short_code_list  = Settings::getShortCodesWithLabels();
		$guest_short_code = $member_short_code = $referral_short_code = array();
		if ( ! empty( $short_code_list['common'] ) && ! empty( $short_code_list['guest'] ) ) {
			$guest_short_code = array_merge( $short_code_list['common'], $short_code_list['guest'] );
		}
		if ( ! empty( $short_code_list['common'] ) && ! empty( $short_code_list['member'] ) ) {
			$member_short_code = array_merge( $short_code_list['common'], $short_code_list['member'] );
		}
		if ( ! empty( $short_code_list['common'] ) && ! empty( $short_code_list['referral'] ) ) {
			$referral_short_code = array_merge( $short_code_list['common'], $short_code_list['referral'] );
		}

		return [
			'content' => [
				'guest'  => [
					'welcome' => [
						'shortcodes' => $guest_short_code,
					],
				],
				'member' => [
					'banner'    => [
						'shortcodes' => $member_short_code,
					],
					'referrals' => [
						'shortcodes' => $referral_short_code,
					],
				],
			],
		];
	}

	/**
	 * Retrieve the social share list for the member's referrals.
	 *
	 * This method fetches the user details and referral URL to generate a social share list
	 * based on the user's email and referral URL. If the user details are not available or
	 * the social share list cannot be generated, a dummy social share list is provided instead.
	 *
	 * @return array An associative array containing the social share list for the member's referrals.
	 */
	public static function getSocialShareList() {
		$user              = Loyalty::getUserDetails();
		$referral_url      = Loyalty::getReferralUrl( 'dummy' );
		$social_share_list = ! empty( $referral_url ) && ! empty( $user ) && is_object( $user ) && ( ! empty( $user->user_email ) ) ? Loyalty::getSocialIconList( $user->user_email, $referral_url ) : Settings::getDummySocialShareList();

		return [
			'content' => [
				'member' => [
					'referrals' => [
						'social_share_list' => ! empty( $social_share_list ) ? $social_share_list : [],
					],
				]
			]
		];
	}
}