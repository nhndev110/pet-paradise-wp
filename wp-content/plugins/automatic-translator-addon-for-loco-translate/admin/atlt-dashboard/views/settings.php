<?php
    $text_domain = 'loco-translate-addon';

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atlt_optin_nonce']) && wp_verify_nonce($_POST['atlt_optin_nonce'], 'atlt_save_optin_settings')) {

            // Handle feedback checkbox
            if (get_option('cpfm_opt_in_choice_cool_translations')) {
                $feedback_opt_in = isset($_POST['atlt-dashboard-feedback-checkbox']) ? 'yes' : 'no';
                update_option('atlt_feedback_opt_in', $feedback_opt_in);
            }

        // If user opted out, remove the cron job
        if ($feedback_opt_in === 'no' && wp_next_scheduled('atlt_extra_data_update') ){
                
            wp_clear_scheduled_hook('atlt_extra_data_update');
        
        }

        if ($feedback_opt_in === 'yes' && !wp_next_scheduled('atlt_extra_data_update')) {

                wp_schedule_event(time(), 'every_30_days', 'atlt_extra_data_update');   

                if (class_exists('ATLT_cronjob')) {

                    ATLT_cronjob::atlt_send_data();
                } 
        }
        
    }
    ?>
    
    <div class="atlt-dashboard-settings">
        <div class="atlt-dashboard-settings-container">
            <?php
            if (isset($GLOBALS['atlt_admin_notices'])) {
                foreach ($GLOBALS['atlt_admin_notices'] as $notice) {
                    echo $notice;
                }
            }
            ?>
            <div class="header">
                <h1><?php _e('Loco Addon Settings', $text_domain); ?></h1>
                <div class="atlt-dashboard-status">
                    <span><?php _e('Inactive', $text_domain); ?></span>
                    <a href="https://locoaddon.com/pricing/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=settings" class='atlt-dashboard-btn' target="_blank">
                        <img src="<?php echo esc_url(ATLT_URL . 'admin/atlt-dashboard/images/upgrade-now.svg'); ?>" alt="<?php esc_attr_e('Upgrade Now', $text_domain); ?>">
                        <?php _e('Upgrade Now', $text_domain); ?>
                    </a>
                </div>
            </div>

            <p class="description">
                <?php _e('Configure your settings for the Loco Addon to optimize your translation experience. Enter your API keys and manage your preferences for seamless integration.', $text_domain); ?>
            </p>

            <div class="atlt-dashboard-api-settings-container">
                <div class="atlt-dashboard-api-settings">
                    <form method="post">
                        <div class="atlt-dashboard-api-settings-form">
                        <?php wp_nonce_field('atlt_save_optin_settings', 'atlt_optin_nonce'); ?>

                        <?php
                             // Define all API-related settings in a single configuration array
                            $api_settings = [
                                'gemini' => [
                                    'name' => 'Gemini',
                                    'doc_url' => 'https://locoaddon.com/docs/pro-plugin/how-to-use-gemini-ai-to-translate-plugins-or-themes/generate-gemini-api-key/',
                                    'placeholder' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
                                ],
                                'openai' => [
                                    'name' => 'OpenAI',
                                    'doc_url' => 'https://locoaddon.com/docs/how-to-generate-open-api-key/',
                                    'placeholder' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
                                ]
                            ];

                        foreach ($api_settings as $key => $settings): ?>
                            <label for="<?php echo esc_attr($key); ?>-api">
                                <?php printf(__('Add %s API key', $text_domain), esc_html($settings['name'])); ?>
                            </label>
                            <input 
                                type="text" 
                                id="<?php echo esc_attr($key); ?>-api" 
                                placeholder="<?php echo esc_attr($settings['placeholder']); ?>" 
                                disabled
                            >
                            <?php
                            printf(
                                __('%s to See How to Generate %s API Key', $text_domain),
                                '<a href="' . esc_url($settings['doc_url']) . '" target="_blank">' . esc_html__('Click Here', $text_domain) . '</a>',
                                esc_html($settings['name'])
                            );
                        endforeach; ?>
                        </div>
                        <!-- Feedback Opt-In -->
                        <?php if (get_option('cpfm_opt_in_choice_cool_translations')) : ?>
                              
                              <div class="atlt-dashboard-feedback-container">
                                  <div class="feedback-row">
                                      <input type="checkbox" 
                                          id="atlt-dashboard-feedback-checkbox" 
                                          name="atlt-dashboard-feedback-checkbox"
                                          <?php checked(get_option('atlt_feedback_opt_in'), 'yes'); ?>>
                                      <p><?php _e('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', $text_domain); ?></p>
                                      <a href="#" class="atlt-see-terms">[See terms]</a>
                                  </div>
                                  <div id="termsBox" style="display: none;padding-left: 20px; margin-top: 10px; font-size: 12px; color: #999;">
                                          <p><?php _e("Opt in to receive email updates about security improvements, new features, helpful tutorials, and occasional special offers. We'll collect:", 'ccpw'); ?></p>
                                          <ul style="list-style-type:auto;">
                                              <li><?php esc_html_e('Your website home URL and WordPress admin email.', 'ccpw'); ?></li>
                                              <li><?php esc_html_e('To check plugin compatibility, we will collect the following: list of active plugins and themes, server type, MySQL version, WordPress version, memory limit, site language and database prefix.', 'ccpw'); ?></li>
                                          </ul>
                                  </div>
                              </div>
                        <?php endif; ?>
                        <div class="atlt-dashboard-save-btn-container">
                        <button type="submit" class="button button-primary"<?php if (!get_option('cpfm_opt_in_choice_cool_translations')) echo 'disabled'; ?>><?php _e('Save', $text_domain); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
