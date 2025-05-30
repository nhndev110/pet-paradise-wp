<?php

namespace WLL\App\Helper;
defined( 'ABSPATH' ) || exit;

class Settings {
	/**
	 * Retrieves short codes with their respective labels based on the loyalty settings.
	 *
	 * @return array Returns an array containing short code values and their labels grouped by different categories such as common, guest, member, and referral.
	 *                - For 'common', short codes related to general site information.
	 *                - For 'guest', short codes related to guest users.
	 *                - For 'member', short codes related to logged-in members.
	 *                - For 'referral', short codes related to referral campaigns and rewards.
	 */
	public static function getShortCodesWithLabels() {
		return apply_filters( 'wll_short_code_with_labels', [
			'common'   => [
				[ 'value' => '{wlr_site_title}', 'label' => __( 'Displays site title', 'wll-loyalty-launcher' ) ]
			],
			'guest'    => [
				[
					'value' => '{wlr_signup_url}',
					'label' => __( 'Sign-up URL (Registration URL)', 'wll-loyalty-launcher' )
				],
				[
					'value' => '{wlr_signin_url}',
					'label' => __( 'Sign-in URL (The Login URL in your site)', 'wll-loyalty-launcher' )
				],
			],
			'member'   => [
				[ 'value' => '{wlr_user_name}', 'label' => __( 'Displays customer’s name', 'wll-loyalty-launcher' ) ],
				[
					'value' => '{wlr_user_points}',
					// translators: %s will replace the points label
					'label' => sprintf( __( 'Displays customer’s %s', 'wll-loyalty-launcher' ), Loyalty::getPointLabel( 3 ) )
				],
				[
					'value' => '{wlr_point_label}',
					'label' => __( 'Displays the “points label” as configured in the settings', 'wll-loyalty-launcher' )
				],
			],
			'referral' => [
				[
					'value' => '{wlr_referral_advocate_point}',
					// translators: %s will replace the points label
					'label' => sprintf( __( 'Displays %s reward for existing customers / advocates as configured in the referral campaign', 'wll-loyalty-launcher' ), Loyalty::getPointLabel( 3 ) )
				],
				[
					'value' => '{wlr_referral_advocate_point_percentage}',
					// translators: %s will replace the points label
					'label' => sprintf( __( 'Displays %s percentage for existing customers / advocates as configured in the referral campaign', 'wll-loyalty-launcher' ), Loyalty::getPointLabel( 3 ) )
				],
				[
					'value' => '{wlr_referral_advocate_reward}',
					'label' => __( 'Displays any direct coupon rewards for existing customers / advocates as configured in the referral campaign', 'wll-loyalty-launcher' )
				],
				[
					'value' => '{wlr_referral_friend_point}',
					// translators: %s will replace the points label
					'label' => sprintf( __( 'Displays %s reward for friends as configured in the referral campaign', 'wll-loyalty-launcher' ), Loyalty::getPointLabel( 3 ) )
				],
				[
					'value' => '{wlr_referral_friend_point_percentage}',
					// translators: %s will replace the points label
					'label' => sprintf( __( 'Displays %s percentage for friends as configured in the referral campaign', 'wll-loyalty-launcher' ), Loyalty::getPointLabel( 3 ) )
				],
				[
					'value' => '{wlr_referral_friend_reward}',
					'label' => __( 'Displays any direct coupon reward for friends as configured in the referral campaign', 'wll-loyalty-launcher' )
				],
			],
		] );
	}

	/**
	 * Get the list of dummy social share items.
	 *
	 * @return array
	 */
	public static function getDummySocialShareList() {
		return [
			[
				'action_type'   => 'facebook_share',
				'icon'          => 'wlr wlrf-facebook_share',
				'share_content' => 'Hello',
				'url'           => 'https://www.facebook.com/sharer/sharer.php?quote=kadh&u=http%3A%2F%2Flocalhost%3A5000%3Fwlr_ref%3DREF-9D8-TK7-93O&display=page',
				'image_icon'    => '',
				'name'          => 'Facebook'
			],
			[
				'action_type'   => 'twitter_share',
				'icon'          => 'wlr wlrf-twitter_share',
				'share_content' => 'hey',
				'url'           => 'https://twitter.com/intent/tweet?text=hey',
				'image_icon'    => '',
				'name'          => 'Twitter'
			],
			[
				'action_type'   => 'whatsapp_share',
				'icon'          => 'wlr wlrf-whatsapp_share',
				'share_content' => 'oi',
				'url'           => 'https://api.whatsapp.com/send?text=oi',
				'image_icon'    => '',
				'name'          => 'WhatsApp'
			],
			[
				'action_type'   => 'email_share',
				'icon'          => 'wlr wlrf-email_share',
				'share_content' => '',
				'url'           => 'mailto:?subject=Morning&amp;body=good%20morning',
				'image_icon'    => '',
				'name'          => 'E-mail',
				'share_subject' => 'Morning',
				'share_body'    => 'Good morning'
			]
		];
	}

	/**
	 * Process short codes within a given message.
	 *
	 * @param string $message The message containing short codes to process.
	 * @param bool $is_admin_page Optional. Whether the message is on an admin page (default is false).
	 *
	 * @return string The message with short codes processed or the original message if no short codes were found.
	 */
	public static function processShortCodes( $message, $is_admin_page = false ) {
		if ( empty( $message ) ) {
			return $message;
		}
		$short_codes = self::getShortCodeList( $is_admin_page );
		if ( ! is_array( $short_codes ) ) {
			return $message;
		}
		foreach ( $short_codes as $key => $value ) {
			$message = str_replace( $key, $value, $message );
		}

		return apply_filters( 'wll_process_message_short_codes', $message, $short_codes );
	}

	/**
	 * Get the list of short codes based on the context of whether it is an admin page or not.
	 *
	 * @param bool $is_admin_page Whether the short codes are being retrieved for an admin page (default is true).
	 *
	 * @return array The array of short codes with their corresponding values based on the context.
	 */
	public static function getShortCodeList( $is_admin_page = true ) {
		$short_code_list = [
			'{wlr_site_title}'                         => get_bloginfo(),
			'{wlr_user_name}'                          => '',
			'{wlr_user_points}'                        => 0,
			'{wlr_point_label}'                        => Loyalty::getPointLabel( 3 ),
			//have to check this func object __const with alagesan
			'{wlr_referral_advocate_point}'            => '',
			'{wlr_referral_advocate_point_percentage}' => '',
			'{wlr_referral_advocate_reward}'           => '',
			'{wlr_referral_friend_point}'              => '',
			'{wlr_referral_friend_point_percentage}'   => '',
			'{wlr_referral_friend_reward}'             => '',
		];
		if ( $is_admin_page ) {
			$short_code_list['{wlr_user_name}']                          = 'Stark';
			$short_code_list['{wlr_point_label}']                        = Loyalty::getPointLabel( 3 );
			$short_code_list['{wlr_user_points}']                        = '4000';
			$short_code_list['{wlr_signup_url}']                         = '#';
			$short_code_list['{wlr_signin_url}']                         = '#';
			$short_code_list['{wlr_referral_advocate_point}']            = 5;
			$short_code_list['{wlr_referral_advocate_point_percentage}'] = '5 %';
			$short_code_list['{wlr_referral_advocate_reward}']           = 'REFERRED';
			$short_code_list['{wlr_referral_friend_point}']              = 10;
			$short_code_list['{wlr_referral_friend_point_percentage}']   = '10 %';
			$short_code_list['{wlr_referral_friend_reward}']             = 'NOOBIE';
		} else {
			$user       = WC::getLoginUser();
			$user_email = WC::getLoginUserEmail();
			if ( ! empty( $user_email ) ) {
				$points                                                      = Loyalty::getUserPoint( $user_email );
				$referral_points                                             = self::getReferralData();
				$short_code_list['{wlr_user_points}']                        = $points;
				$short_code_list['{wlr_point_label}']                        = Loyalty::getPointLabel( $points );
				$short_code_list['{wlr_user_name}']                          = ( ! empty( $user->display_name ) ? $user->display_name : '' );
				$short_code_list['{wlr_referral_advocate_point}']            = ! empty( $referral_points['advocate_points'] ) ? $referral_points['advocate_points'] : 0;
				$short_code_list['{wlr_referral_advocate_point_percentage}'] = ! empty( $referral_points['advocate_point_percentage'] ) ? $referral_points['advocate_point_percentage'] . ' %' : '0 %';
				$short_code_list['{wlr_referral_advocate_reward}']           = ! empty( $referral_points['advocate_reward'] ) ? implode( ',', $referral_points['advocate_reward'] ) : '';
				$short_code_list['{wlr_referral_friend_point}']              = ! empty( $referral_points['friend_points'] ) ? $referral_points['friend_points'] : 0;
				$short_code_list['{wlr_referral_friend_point_percentage}']   = ! empty( $referral_points['friend_point_percentage'] ) ? $referral_points['friend_point_percentage'] . ' %' : '0 %';
				$short_code_list['{wlr_referral_friend_reward}']             = ! empty( $referral_points['friend_reward'] ) ? implode( ',', $referral_points['friend_reward'] ) : '';
			}
			$my_account_url                      = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
			$short_code_list['{wlr_signup_url}'] = $my_account_url;
			$short_code_list['{wlr_signin_url}'] = $my_account_url;
		}

		return apply_filters( 'wlr_launcher_short_code_list', $short_code_list );
	}

	/**
	 * Retrieves referral data including advocate and friend points, point percentage, and rewards.
	 *
	 * @return array Returns an array containing the following keys:
	 *                - advocate_points: Total points earned by the advocate
	 *                - advocate_point_percentage: Total percentage points earned by the advocate
	 *                - advocate_reward: List of rewards earned by the advocate
	 *                - friend_points: Total points earned by the friend
	 *                - friend_point_percentage: Total percentage points earned by the friend
	 *                - friend_reward: List of rewards earned by the friend
	 */
	public static function getReferralData() {
		$campaign_table     = new \Wlr\App\Models\EarnCampaign();
		$referral_campaigns = $campaign_table->getCampaignByAction( 'referral' );
		$referral_data      = [
			'advocate_points'           => 0,
			'advocate_point_percentage' => 0,
			'advocate_reward'           => [],
			'friend_points'             => 0,
			'friend_point_percentage'   => 0,
			'friend_reward'             => []
		];
		if ( empty( $referral_campaigns ) ) {
			return $referral_data;
		}
		$reward_table = new \Wlr\App\Models\Rewards();
		foreach ( $referral_campaigns as $campaign ) {
			$point_rule = ! empty( $campaign->point_rule ) ? json_decode( $campaign->point_rule, true ) : [];
			/* advocate */
			$advocate_type = ! empty( $point_rule['advocate']['campaign_type'] ) ? $point_rule['advocate']['campaign_type'] : '';
			if ( $advocate_type == 'point' ) {
				$earn_type = ! empty( $point_rule['advocate']['earn_type'] ) ? $point_rule['advocate']['earn_type'] : '';
				if ( $earn_type == 'fixed_point' ) {
					$referral_data['advocate_points'] += (int) ( ! empty( $point_rule['advocate']['earn_point'] ) ? $point_rule['advocate']['earn_point'] : 0 );
				} elseif ( $earn_type == 'subtotal_percentage' ) {
					$referral_data['advocate_point_percentage'] += (int) ( ! empty( $point_rule['advocate']['earn_point'] ) ? $point_rule['advocate']['earn_point'] : 0 );
				}
			} elseif ( $advocate_type == 'coupon' ) {
				$reward_id = (int) ( ! empty( $point_rule['advocate']['earn_reward'] ) ? $point_rule['advocate']['earn_reward'] : 0 );
				$reward    = ! empty( $reward_id ) ? $reward_table->findReward( $reward_id ) : new \stdClass();
				if ( ! empty( $reward ) && is_object( $reward ) && isset( $reward->display_name ) ) {
					$referral_data['advocate_reward'][] = ! empty( $reward->display_name ) ? $reward->display_name : '';
				}
			}

			/* friend */
			$friend_type = ! empty( $point_rule['friend']['campaign_type'] ) ? $point_rule['friend']['campaign_type'] : '';
			if ( $friend_type == 'point' ) {
				$earn_type = ! empty( $point_rule['friend']['earn_type'] ) ? $point_rule['friend']['earn_type'] : '';
				if ( $earn_type == 'fixed_point' ) {
					$referral_data['friend_points'] += (int) ( ! empty( $point_rule['friend']['earn_point'] ) ? $point_rule['friend']['earn_point'] : 0 );
				} elseif ( $earn_type == 'subtotal_percentage' ) {
					$referral_data['friend_point_percentage'] += (int) ( ! empty( $point_rule['friend']['earn_point'] ) ? $point_rule['friend']['earn_point'] : 0 );
				}
			} elseif ( $friend_type == 'coupon' ) {
				$reward_id = (int) ( ! empty( $point_rule['friend']['earn_reward'] ) ? $point_rule['friend']['earn_reward'] : 0 );
				$reward    = ! empty( $reward_id ) ? $reward_table->findReward( $reward_id ) : new \stdClass();
				if ( ! empty( $reward ) && is_object( $reward ) && isset( $reward->display_name ) ) {
					$referral_data['friend_reward'][] = ! empty( $reward->display_name ) ? $reward->display_name : '';
				}
			}
		}

		return $referral_data;
	}

	/**
	 * Retrieves the design settings for the launcher.
	 *
	 * @return array
	 */
	public static function getDesignSettings() {
		$theme_color = Settings::get( 'theme_color', 'loyalty', '#6F38C5' );

		return (array) apply_filters( 'wll_launcher_design_content_data', [
			'design' => [
				'logo'     => [
					'is_show' => Settings::opt( 'design.logo.is_show', 'show' ),
					'image'   => Settings::opt( 'design.logo.image' ),
				],
				'colors'   => [
					'theme'   => [
						'primary' => Settings::opt( 'design.colors.theme.primary', $theme_color ),
						'text'    => Settings::opt( 'design.colors.theme.text', 'white' ),
					],
					'buttons' => [
						'background' => Settings::opt( 'design.colors.buttons.background', '#FF6B00' ),
						'text'       => Settings::opt( 'design.colors.buttons.text', 'white' ),
					],
				],
				'branding' => [
					'is_show' => Loyalty::isPro() ? Settings::opt( 'design.branding.is_show', 'show' ) : 'show',
				]
			]
		] );
	}

	/**
	 * Get the value for a specific key from a given option name.
	 *
	 * @param mixed $key The specific key to retrieve the value for.
	 * @param string $option_name The name of the option where the value is stored.
	 * @param mixed $default The default value to return if the key is not found (default is an empty string).
	 *
	 * @return mixed The value associated with the key from the given option name, or the default value if not found.
	 */
	public static function get( $key, $option_name, $default = '' ) {
		$settings = self::getSettings( $option_name );

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Retrieves settings based on the provided option name.
	 *
	 * @param string $option_name The name of the settings option to retrieve. Must be one of: 'design', 'content', 'launcher_button'.
	 *
	 * @return array The settings associated with the specified option name. If the option name is invalid, an empty array is returned.
	 */
	public static function getSettings( string $option_name ) {
		if ( empty( $option_name ) || ! in_array( $option_name, [
				'design',
				'content',
				'launcher_button',
				'loyalty'
			] ) ) {
			return [];
		}
		switch ( $option_name ) {
			case 'design':
				return get_option( 'wll_launcher_design_settings', [] );
			case 'content':
				return get_option( 'wll_launcher_content_settings', [] );
			case 'launcher_button':
				return get_option( 'wll_launcher_icon_settings', [] );
			case 'loyalty':
				return get_option( 'wlr_settings', [] );
		}

		return [];
	}

	/**
	 * Get the value for a specific key from a given option name in the system.
	 *
	 * @param mixed $key The specific key to retrieve the value for.
	 * @param mixed $default The default value to return if the key is not found. Default is an empty string.
	 * @param string $option_name The name of the option where the value is stored. Default is 'design'.
	 *
	 * @return mixed The value associated with the key from the given option name, or the default value if not found after applying filters.
	 */
	public static function opt( $key, $default = '', $option_name = 'design' ) {
		if ( empty( $option_name ) || empty( $key ) ) {
			return $default;
		}
		$settings = self::getSettings( $option_name );
		if ( strpos( $key, "." ) !== false ) {
			$identifiers = explode( ".", $key );//splitting keys for sub array values
			$value       = self::getOptValue( $settings, $identifiers );
		}
		$value = ( ! empty( $value ) ) ? $value : $default;

		return apply_filters( 'wll_launcher_option_setting', $value, $key );
	}

	/**
	 * Get the value based on a series of nested keys from a given data array or object.
	 *
	 * @param mixed $data The data array or object from which to extract the value.
	 * @param array $identifiers An array of keys representing the nested structure to traverse.
	 *
	 * @return mixed The value associated with the nested keys from the data structure, or an empty string if not found.
	 */
	public static function getOptValue( $data, $identifiers ) {
		$identifier = array_shift( $identifiers ); //shifting first key from array
		if ( is_object( $data ) && isset( $data->$identifier ) && is_object( $data->$identifier ) ) {
			$value = self::getOptValue( $data->$identifier, $identifiers );
		} elseif ( is_object( $data ) && isset( $data->$identifier ) ) {
			$value = ! empty( $data->$identifier ) ? $data->$identifier : '';
		} elseif ( is_array( $data ) && isset( $data[ $identifier ] ) && is_array( $data[ $identifier ] ) ) {
			$value = empty( $identifiers ) ? $data[ $identifier ] : self::getOptValue( $data[ $identifier ], $identifiers );
		} else {
			$value = isset( $data[ $identifier ] ) ? $data[ $identifier ] : '';
		}

		return $value;
	}

	/**
	 * Retrieves the data for the launcher button content.
	 *
	 * @param bool $is_admin_side Whether to display data for admin side.
	 *
	 * @return array Returns an array containing the launcher button content data.
	 */
	public static function getLauncherButtonContentData( $is_admin_side = false ) {
		$text_data = [
			'launcher' => [
				'appearance' => [
					'text' => Settings::opt( 'launcher.appearance.text', 'My Rewards', 'launcher_button' ),
					'icon' => [
						'selected' => Settings::opt( 'launcher.appearance.icon.selected', 'default', 'launcher_button' ),
					]
				],
			],
		];
		array_walk_recursive( $text_data, function ( &$value, $key ) use ( $is_admin_side ) {
			/*$is_admin_side = isset($is_admin_side) && is_bool($is_admin_side) && $is_admin_side;*/
			//phpcs:ignore
			$value = ( ! $is_admin_side ) ? __( $value, 'wll-loyalty-launcher' ) : $value;
		} );
		$data = [
			'launcher' => [
				'appearance'             => [
					'selected' => Settings::opt( 'launcher.appearance.selected', 'icon_with_text', 'launcher_button' ),
					'icon'     => [
						'image' => Settings::opt( 'launcher.appearance.icon.image', '', 'launcher_button' ),
						'icon'  => Settings::opt( 'launcher.appearance.icon.icon', 'gift', 'launcher_button' ),
					],
				],
				'placement'              => [
					'position'       => Settings::opt( 'launcher.placement.position', 'right', 'launcher_button' ),
					'side_spacing'   => Settings::opt( 'launcher.placement.side_spacing', 0, 'launcher_button' ),
					'bottom_spacing' => Settings::opt( 'launcher.placement.bottom_spacing', 0, 'launcher_button' ),
				],
				'view_option'            => Settings::opt( 'launcher.view_option', 'mobile_and_desktop', 'launcher_button' ),
				'font_family'            => Settings::opt( 'launcher.font_family', 'inherit', 'launcher_button' ),
				'show_conditions'        => Settings::opt( 'launcher.show_conditions', [], 'launcher_button' ),
				'condition_relationship' => Settings::opt( 'launcher.condition_relationship', "and", 'launcher_button' )
			]
		];

		return apply_filters( 'wll_launcher_popup_button_content_data', array_merge_recursive( $text_data, $data ) );
	}
}