<!-- Right Sidebar -->
<div class="atlt-dashboard-sidebar">
    <div class="atlt-dashboard-status">
        <h3><?php _e('Auto Translation status', $text_domain); ?></h3>
        <div class="atlt-dashboard-sts-top">
            <?php

            $all_data = get_option('cpt_dashboard_data', array());

            if (!is_array($all_data) || !isset($all_data['atlt'])) {

                $all_data['atlt'] = []; // Ensure $all_data['atlt'] is an array

            }

            $totals = array_reduce($all_data['atlt'] ?? [], function($carry, $translation) {
                // Ensure $translation['string_count'] is numeric
                $carry['string_count'] += intval($translation['string_count'] ?? 0);
                $carry['character_count'] += intval($translation['character_count'] ?? 0);
                $carry['time_taken'] += intval($translation['time_taken'] ?? 0);
                $plugin_theme = sanitize_key($translation['plugins_themes'] ?? ''); // Sanitize plugin/theme key
                $carry['plugins_themes'][$plugin_theme] = 1; // Ensure this is sanitized
                return $carry;
            }, ['string_count' => 0, 'character_count' => 0, 'time_taken' => 0, 'plugins_themes' => []]);
            // Update the time taken string using the new function
            $time_taken_str = atlt_format_time_taken($totals['time_taken'] ,$text_domain);
            ?>
            <span><?php echo esc_html(atlt_format_number($totals['string_count'], $text_domain)); ?></span>
            <span><?php _e('Total Strings Translated!', $text_domain); ?></span>
        </div>
        <ul class="atlt-dashboard-sts-btm">
            <li><span><?php _e('Total Characters', $text_domain); ?></span> <span><?php echo esc_html(atlt_format_number($totals['character_count'], $text_domain)); ?></span></li>
            <li><span><?php _e('Total Plugins / Themes', $text_domain); ?></span> <span><?php echo esc_html(count($totals['plugins_themes'])); ?></span></li>
            <li><span><?php _e('Time Taken', $text_domain); ?></span> <span><?php echo esc_html($time_taken_str); ?></span></li>
        </ul>
    </div>

    <div class="atlt-dashboard-translate-full">
        <h3><?php _e('Automatically Translate Full Webpage', $text_domain); ?></h3>
        <div class="atlt-dashboard-addon first">
            <div class="atlt-dashboard-addon-l">
                <strong><?php echo esc_html(atlt_get_plugin_display_name('automatic-translations-for-polylang', $text_domain)); ?></strong>
                <span class="addon-desc"><?php _e('Polylang addon to translate webpages.', $text_domain); ?></span>
                <?php if (atlt_is_plugin_installed('automatic-translations-for-polylang')): ?>
                    <span class="installed"><?php _e('Installed', $text_domain); ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url(admin_url('plugin-install.php?s=AI+translation+for+polylang+by+coolplugins&tab=search&type=term')); ?>" class="atlt-dashboard-btn" target="_blank"><?php _e('Install', $text_domain); ?></a>
                <?php endif; ?>
            </div>
            <div class="atlt-dashboard-addon-r">
                <img src="<?php echo esc_url(ATLT_URL . 'admin/atlt-dashboard/images/polylang-addon.png'); ?>" alt="<?php _e('Polylang Addon', $text_domain); ?>">
            </div>
        </div>
        <div class="atlt-dashboard-addon">
            <div class="atlt-dashboard-addon-l">
                <strong><?php echo esc_html(atlt_get_plugin_display_name('automatic-translate-addon-for-translatepress', $text_domain)); ?></strong>
                <span class="addon-desc"><?php _e('TranslatePress addon to translate webpages.', $text_domain); ?></span>
                <?php if (atlt_is_plugin_installed('automatic-translate-addon-for-translatepress')): ?>
                    <span class="installed"><?php _e('Installed', $text_domain); ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url(admin_url('plugin-install.php?s=AI+Translation+for+translatepress+by+coolplugins&tab=search&type=term')); ?>" class="atlt-dashboard-btn" target="_blank"><?php _e('Install', $text_domain); ?></a>
                <?php endif; ?>
            </div>
            <div class="atlt-dashboard-addon-r">
                <img src="<?php echo esc_url(ATLT_URL . 'admin/atlt-dashboard/images/translatepress-addon.png'); ?>" alt="<?php _e('TranslatePress Addon', $text_domain); ?>">
            </div>
        </div>
    </div>

    <div class="atlt-dashboard-rate-us">
        <h3><?php _e('Rate Us ⭐⭐⭐⭐⭐', $text_domain); ?></h3>
        <p><?php _e('We\'d love your feedback! Hope this addon made auto-translations easier for you.', $text_domain); ?></p>
        <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" class="review-link" target="_blank"><?php _e('Submit a Review →', $text_domain); ?></a>
    </div>
</div>

<?php

function atlt_format_time_taken($time_taken, $text_domain) {
    if ($time_taken === 0) return __('0', $text_domain);
    if ($time_taken < 60) return sprintf(__('%d sec', $text_domain), $time_taken);
    if ($time_taken < 3600) {
        $min = floor($time_taken / 60);
        $sec = $time_taken % 60;
        return sprintf(__('%d min %d sec', $text_domain), $min, $sec);
    }
    $hours = floor($time_taken / 3600);
    $min = floor(($time_taken % 3600) / 60);
    return sprintf(__('%d hours %d min', $text_domain), $hours, $min);
}

function atlt_is_plugin_installed($plugin_slug) {
    $plugins = get_plugins();
    
    // Check if the plugin is installed
    if ($plugin_slug === 'automatic-translate-addon-for-translatepress') {
        return isset($plugins['automatic-translate-addon-for-translatepress/automatic-translate-addon-for-translatepress.php']) || isset($plugins['automatic-translate-addon-pro-for-translatepress/automatic-translate-addon-for-translatepress-pro.php']);
    } elseif ($plugin_slug === 'automatic-translations-for-polylang') {
        return isset($plugins['automatic-translations-for-polylang/automatic-translation-for-polylang.php']) ||isset($plugins['automatic-translations-for-polylang-pro/automatic-translation-for-polylang.php']);
    }
    return false; // Return false if no match found
}

function atlt_get_plugin_display_name($plugin_slug, $text_domain) {
    $plugins = get_plugins();

    // Define free and pro plugin paths
    $plugin_paths = [
        'automatic-translations-for-polylang' => [
            'free' => 'automatic-translations-for-polylang/automatic-translation-for-polylang.php',
            'pro'  => 'automatic-translations-for-polylang-pro/automatic-translation-for-polylang.php',
            'free_name' => __('AI Translation for Polylang', $text_domain),
            'pro_name'  => __('AI Translation For Polylang (Pro)', $text_domain),
        ],
        'automatic-translate-addon-for-translatepress' => [
            'free' => 'automatic-translate-addon-for-translatepress/automatic-translate-addon-for-translatepress.php',
            'pro'  => 'automatic-translate-addon-pro-for-translatepress/automatic-translate-addon-for-translatepress-pro.php',
            'free_name' => __('AI Translation for TranslatePress', $text_domain),
            'pro_name'  => __('AI Translation for TranslatePress (Pro)', $text_domain),
        ],
    ];

    // Check if the provided plugin slug exists
    if (!isset($plugin_paths[$plugin_slug])) {
        return $plugin_slug['free_name'];
    }

    $free_installed = isset($plugins[$plugin_paths[$plugin_slug]['free']]);
    $pro_installed = isset($plugins[$plugin_paths[$plugin_slug]['pro']]);

    // Determine which version is installed
    if ($pro_installed) {
        return $plugin_paths[$plugin_slug]['pro_name'];
    } elseif ($free_installed) {
        return $plugin_paths[$plugin_slug]['free_name'];
    } else {
        return $plugin_paths[$plugin_slug]['free_name'];
    }
}

function atlt_format_number($number, $text_domain) {
    $formats = [
        1000000000 => __('B+', $text_domain),
        1000000 => __('M+', $text_domain),  
        1000 => __('K+', $text_domain)
    ];
    
    foreach ($formats as $threshold => $suffix) {
        if ($number >= $threshold) {
            return floor($number / $threshold * 10) / 10 . $suffix;
        }
    }
    return $number;
}

