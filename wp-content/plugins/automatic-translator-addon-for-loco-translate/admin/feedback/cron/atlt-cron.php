<?php
if (!defined('ABSPATH')) {
    exit();
}
if (!class_exists('ATLT_cronjob')) {
    class ATLT_cronjob
    {

        public function __construct()
        {
         
        }

        public function atlt_cron_init_hooks()
        {

            //initialize Cron Jobs
            add_filter('cron_schedules', array($this, 'atlt_cron_schedules'));
            add_action('atlt_extra_data_update', array($this, 'atlt_cron_extra_data_autoupdater'));
        }

        /*
        |--------------------------------------------------------------------------
        |  cron custom schedules
        |--------------------------------------------------------------------------
         */

        public function atlt_cron_schedules($schedules)
        {

            if (!isset($schedules['every_30_days'])) {

                $schedules['every_30_days'] = array(
                    'interval' => 30 * 24 * 60 * 60, // 2,592,000 seconds
                    'display'  => __('Once every 30 days'),
                );
            }

            return $schedules;
        }

         /*
        |--------------------------------------------------------------------------
        |  cron extra data autoupdater
        |--------------------------------------------------------------------------
         */

        function atlt_cron_extra_data_autoupdater() {
            $opt_in = get_option('atlt_feedback_opt_in');
            $opt_in = is_string($opt_in) ? strtolower($opt_in) : 'no';
            
            if ($opt_in === 'yes' || $opt_in === true || $opt_in === '1') {
                if (class_exists('ATLT_cronjob')) {
                    ATLT_cronjob::atlt_send_data();
                }
            }
            
        }

        /*
        |--------------------------------------------------------------------------
        |  cron send data
        |--------------------------------------------------------------------------
         */ 

        static public function atlt_send_data() {
            $feedback_url = ATLT_FEEDBACK_API.'wp-json/coolplugins-feedback/v1/site';
            $extra_data_details = Helpers::atlt_get_user_info();
            $server_info    = $extra_data_details['server_info'];
            $extra_details  = $extra_data_details['extra_details'];
            $site_url       = get_site_url();
            $install_date   = get_option('atlt-install-date');
            $unique_key     = '8';  // Ensure this key is unique per plugin to prevent collisions when site URL and install date are the same across plugins
            $site_id        = $site_url . '-' . $install_date . '-' . $unique_key;
            $initial_version = get_option('atlt_initial_save_version');
            $initial_version = is_string($initial_version) ? sanitize_text_field($initial_version) : 'N/A';
            $plugin_version     = ATLT_VERSION;
            $admin_email    = sanitize_email(get_option('admin_email') ?: 'N/A');
            $post_data = array(
                
                'site_id'           => md5($site_id),
                'plugin_version'    => $plugin_version,
                'plugin_name'       => 'Loco Automatic Translate Addon',
                'plugin_initial'    => $initial_version,
                'email'             => $admin_email,
                'site_url'          => esc_url_raw($site_url),
                'server_info'       => $server_info,
                'extra_details'     => $extra_details,
            );
            
            $response = wp_remote_post($feedback_url, array(      
                'method'    => 'POST',
                'timeout'   => 30,
                'headers'   => array(
                    'Content-Type' => 'application/json',
                ),
                'body'      => wp_json_encode($post_data),
            ));

            if (is_wp_error($response)) {
                error_log('ATLT Feedback Send Failed: ' . $response->get_error_message());
                return;
            }
            
            $response_body  = wp_remote_retrieve_body($response);
            $decoded        = json_decode($response_body, true);
            
            if (!wp_next_scheduled('atlt_extra_data_update')) {
                wp_schedule_event(time(), 'every_30_days', 'atlt_extra_data_update');
            }
        }

    }
}
