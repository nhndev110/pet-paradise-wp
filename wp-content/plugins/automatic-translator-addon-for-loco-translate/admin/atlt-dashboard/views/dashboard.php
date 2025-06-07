
    <div class="atlt-dashboard-left-section">
        
        <!-- Welcome Section -->
        <div class="atlt-dashboard-welcome">
            <div class="atlt-dashboard-welcome-text">
                <h2><?php echo esc_html__('Welcome To Loco Addon', $text_domain); ?></h2>
                <p><?php echo esc_html__('Translate WordPress plugins or themes instantly with Loco Addon. One-click, thousands of strings - no extra cost!', $text_domain); ?></p>
                <div class="atlt-dashboard-btns-row">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=loco-plugin')); ?>" class="atlt-dashboard-btn primary"><?php echo esc_html__('Translate Plugins', $text_domain); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=loco-theme')); ?>" class="atlt-dashboard-btn"><?php echo esc_html__('Translate Themes', $text_domain); ?></a>
                </div>
                <a class="atlt-dashboard-docs" href="<?php echo esc_url('https://locoaddon.com/docs/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'); ?>" target="_blank"><img src="<?php echo esc_url(ATLT_URL . 'admin/atlt-dashboard/images/document.svg'); ?>" alt="document"> <?php echo esc_html__('Read Plugin Docs', $text_domain); ?></a>
            </div>
            <div class="atlt-dashboard-welcome-video">
                <a href="https://locoaddon.com/docs/translate-plugin-theme-via-yandex-translate/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard_video" target="_blank" class="atlt-dashboard-video-link">
                    <img decoding="async" src="<?php echo ATLT_URL . 'admin/atlt-dashboard/images/video.svg'; ?>" class="play-icon" alt="play-icon">
                    <picture>
                        <source srcset="<?php echo ATLT_URL . 'admin/atlt-dashboard/images/loco-addon-video.avifs'; ?>" type="image/avif">
                        <img src="<?php echo ATLT_URL . 'admin/atlt-dashboard/images/loco-addon-video.jpg'; ?>" class="loco-video" alt="loco translate addon preview">
                    </picture>
                </a>
            </div>
        </div>

        <!-- Translation Providers -->  
        <div class="atlt-dashboard-translation-providers">
            <h3><?php _e('Translation Providers', $text_domain); ?></h3>
            <div class="atlt-dashboard-providers-grid">
                
                <?php

                $providers = [
                    ["AI Translations (Gemini / OpenAI)", "gemini-ai-logo.png", "Pro", ["Unlimited Translations", "Fast Translations via AI", "Gemini API Key Required"], esc_url('https://locoaddon.com/docs/gemini-ai-translations-wordpress/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard_gemini'), esc_url('admin.php?page=loco-atlt-dashboard&tab=settings')],
                    ["ChatGPT Translations", "chatgpt-logo.png", "Pro", ["Copy & Translate in ChatGPT", "Fast Translations via AI", "No API Key Required"], esc_url('https://locoaddon.com/docs/chatgpt-ai-translations-wordpress/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard_chatgpt')],
                    ["Chrome Built-in AI", "chrome-built-in-ai-logo.png", "Pro", ["Fast AI Translations in Browser", "Unlimited Free Translations", "Use Translation Modals"], esc_url('https://locoaddon.com/docs/how-to-use-chrome-ai-auto-translations/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard_chrome')],
                    ["Google Translate", "google-translate-logo.png", "Pro", ["Unlimited Free Translations", "Fast & No API Key Required"], esc_url('https://locoaddon.com/docs/auto-translations-via-google-translate/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard_google')],
                    ["Yandex Translate", "yandex-translate-logo.png", "Free", ["Unlimited Free Translations", "No API & No Extra Cost"], esc_url('https://locoaddon.com/docs/translate-plugin-theme-via-yandex-translate/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard_yandex')],
                    ["DeepL Doc Translator", "deepl-translate-logo.png", "Pro", ["Limited Free Translations / day", "Translate via Doc Translator"], esc_url('https://locoaddon.com/docs/translate-via-deepl-doc-translator/?utm_source=atlt_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard_deepl')]
                ];

                foreach ($providers as $index => $provider) {
                    ?>
                    <div class="atlt-dashboard-provider-card">
                        <div class="atlt-dashboard-provider-header">
                            <a href="<?php echo esc_url($provider[4]); ?>" target="_blank"><img src="<?php echo esc_url(ATLT_URL . 'assets/images/' . $provider[1]); ?>" alt="<?php echo esc_html($provider[0]); ?>"></a>
                            <span class="atlt-dashboard-badge <?php echo strtolower($provider[2]); ?>"><?php echo $provider[2]; ?></span>
                        </div>
                        <h4><?php echo $provider[0]; ?></h4>
                        <ul>
                            <?php foreach ($provider[3] as $feature) { ?>
                                <li>✅ <?php echo $feature; ?></li>
                            <?php } ?>
                        </ul>
                        <div class="atlt-dashboard-provider-buttons">
                            <a href="<?php echo esc_url($provider[4]); ?>" class="atlt-dashboard-btn" target="_blank">Docs</a>
                            <?php if ($index === 0) { ?>
                                <a href="<?php echo esc_url($provider[5]); ?>" class="atlt-dashboard-btn">Settings</a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>

