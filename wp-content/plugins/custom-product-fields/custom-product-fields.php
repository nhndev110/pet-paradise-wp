<?php

/**
 * Plugin Name: Custom Product Fields
 * Description: Thêm custom field vào sản phẩm WooCommerce.
 * Author: NHNDEV110
 * Version: 1.0
 */

add_action('woocommerce_product_write_panel_tabs', 'woo_add_pet_tab');

function woo_add_pet_tab()
{
?>
  <li class="pet_tab">
    <a href="#pet_tab_data">
      <span><?php _e('Thông tin thú cưng', 'woocommerce') ?></span>
    </a>
  </li>
<?php
}

add_action('woocommerce_product_write_panels', 'woo_add_pet_fields');
function woo_add_pet_fields()
{
?>
  <div id="pet_tab_data" class="panel woocommerce_options_panel">
    <div class="options_group">
      <?php
      woocommerce_wp_text_input([
        'id' => '_pet_name',
        'label' => __('Tên thú cưng', 'woocommerce'),
        'placeholder' => 'Nhập tên thú cưng',
        'desc_tip' => true,
        'description' => __('Nhập tên thú cưng của bạn.', 'woocommerce'),
        'custom_attributes' => [
          'autocomplete' => 'off',
        ],
      ]);

      woocommerce_wp_text_input([
        'id' => '_pet_birth_date',
        'label' => __('Ngày sinh', 'woocommerce'),
        'placeholder' => 'dd-mm-yyyy',
        'desc_tip' => true,
        'class' => 'custom-datepicker',
        'description' => __('Ngày sinh của thú cưng.', 'woocommerce'),
        'custom_attributes' => [
          'autocomplete' => 'off',
        ],
      ]);

      woocommerce_wp_select([
        'id' => '_pet_gender',
        'label' => __('Giới tính', 'woocommerce'),
        'options' => [
          '' => __('Chọn giới tính', 'woocommerce'),
          'male' => __('Đực', 'woocommerce'),
          'female' => __('Cái', 'woocommerce'),
        ],
        'desc_tip' => true,
        'description' => __('Giới tính của thú cưng.', 'woocommerce'),
      ]);

      woocommerce_wp_select([
        'id' => '_pet_health',
        'label' => __('Sức khỏe', 'woocommerce'),
        'options' => [
          '' => __('Chọn tình trạng sức khỏe', 'woocommerce'),
          'excellent' => __('Rất tốt', 'woocommerce'),
          'good' => __('Tốt', 'woocommerce'),
          'normal' => __('Bình thường', 'woocommerce'),
          'concerning' => __('Đáng lo ngại', 'woocommerce'),
          'poor' => __('Yếu', 'woocommerce'),
        ],
        'desc_tip' => true,
        'description' => __('Tình trạng sức khỏe của thú cưng.', 'woocommerce'),
      ]);

      woocommerce_wp_text_input([
        'id' => '_pet_vaccination',
        'label' => __('Số mũi tiêm phòng', 'woocommerce'),
        'placeholder' => '0',
        'description' => __('Nhập số mũi tiêm phòng đã tiêm cho thú cưng (0 nếu chưa tiêm).', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => [
          'step' => '1',
          'min' => '0'
        ],
        'desc_tip' => true,
      ]);

      woocommerce_wp_text_input([
        'id' => '_pet_deworming',
        'label' => __('Số lần tẩy giun', 'woocommerce'),
        'placeholder' => '0',
        'description' => __('Nhập số lần đã tẩy giun cho thú cưng (0 nếu chưa tẩy).', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => [
          'step' => '1',
          'min' => '0'
        ],
        'desc_tip' => true,
      ]);
      ?>
    </div>
  </div>
<?php
}

add_action('woocommerce_process_product_meta', 'woo_save_pet_fields');

function woo_save_pet_fields($post_id)
{
  $pet_fields = [
    '_pet_name',
    '_pet_birth_date',
    '_pet_gender',
    '_pet_health',
    '_pet_vaccination',
    '_pet_deworming',
  ];

  foreach ($pet_fields as $field) {
    if (isset($_POST[$field])) {
      $value = sanitize_text_field($_POST[$field]);
      update_post_meta($post_id, $field, $value);
    }
  }
}

add_action('admin_enqueue_scripts', 'enqueue_datepicker_assets');

function enqueue_datepicker_assets($hook_suffix)
{
  if ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php') {
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css');

    wp_add_inline_script('jquery-ui-datepicker', "
      jQuery(document).ready(function($) {
        $('.custom-datepicker').datepicker({
          dateFormat: 'dd-mm-yy',
          changeMonth: true,
          changeYear: true,
        });

        $('#woo_expiry_date').datepicker({
          defaultDate: '',
          dateFormat: 'yy-mm-dd',
          changeMonth: true,
          changeYear: true,
          numberOfMonths: 1,
        });
      });
    ");
  }
}

// add_action('woocommerce_product_options_general_product_data', 'add_expiration_date_field');

// function add_expiration_date_field()
// {
//   echo '<div class="options_group">';

//   woocommerce_wp_text_input([
//     'id' => '_product_expiration_date',
//     'label' => __('Hạn sử dụng', 'woocommerce'),
//     'placeholder' => 'dd-mm-yyyy',
//     'desc_tip' => true,
//     'class' => 'custom-datepicker',
//   ]);

//   echo '</div>';
// }

// add_action('woocommerce_process_product_meta', 'save_expiration_date_field');

// function save_expiration_date_field($post_id)
// {
//   if (isset($_POST['_product_expiration_date'])) {
//     update_post_meta($post_id, '_product_expiration_date', sanitize_text_field($_POST['_product_expiration_date']));
//   }
// }

// add_action('woocommerce_single_product_summary', 'display_expiration_date', 30);

// function display_expiration_date()
// {
//   global $post;
//   $expiration_date = get_post_meta($post->ID, '_product_expiration_date', true);
//   if (!empty($expiration_date)) {
//     echo '<p class="product-expiration-date"><strong>Hạn sử dụng:</strong> ' . esc_html($expiration_date) . '</p>';
//   }
// }
