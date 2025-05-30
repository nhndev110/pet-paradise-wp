<?php

function search_by_title_only($where, $query)
{
  global $wpdb;

  if ($query->is_search() && ! is_admin()) {
    $search_term = $query->get('s');
    if ($search_term) {
      $search_term = esc_sql($wpdb->esc_like($search_term));
      $where = " AND ({$wpdb->posts}.post_title LIKE '%{$search_term}%')";
    }
  }

  return $where;
}
add_filter('posts_where', 'search_by_title_only', 10, 2);

function count_expired_products_by_period($period = 'this_month')
{
  $meta_query = array();
  $today = date('Y-m-d');
  if ($period == 'this_month') {
    $last_day_this_month  = date('Y-m-t');
    $meta_query[] = array(
      'key'     => 'woo_expiry_date',
      'value'   => array($today, $last_day_this_month),
      'type'    => 'DATE',
      'compare' => 'BETWEEN',
    );
  }
  if ($period == 'next_month') {
    $next_month_start = date("Y-m-01", strtotime('+1 month'));
    $next_month_end = date("Y-m-t", strtotime('+1 month'));
    $meta_query[] = array(
      'key'     => 'woo_expiry_date',
      'value'   => array($next_month_start, $next_month_end),
      'type'    => 'DATE',
      'compare' => 'BETWEEN',
    );
  }
  if ($period == 'three_months') {
    $third_month_end = date("Y-m-t", strtotime('+3 month'));
    $meta_query[] = array(
      'key'     => 'woo_expiry_date',
      'value'   => array($today, $third_month_end),
      'type'    => 'DATE',
      'compare' => 'BETWEEN',
    );
  }
  if ($period == 'expired') {
    $meta_query[] = array(
      'key'     => 'woo_expiry_date',
      'value'   => $today,
      'type'    => 'DATE',
      'compare' => '<=',
    );
  }

  $args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => $meta_query,
  );
  $query = new WP_Query($args);
  return $query->found_posts;
}

function display_expiry_products_dashboard_widget()
{
?>
  <div class="expiry-dashboard-widget">
    <style>
      .expiry-dashboard-widget .stats-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 10px;
      }

      .expiry-dashboard-widget .stat-box {
        background: #fff;
        border-left: 4px solid #0073aa;
        padding: 12px 15px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        text-decoration: none;
        transition: all 0.3s;
        color: #333;
      }

      .expiry-dashboard-widget .stat-box:hover {
        background: #f9f9f9;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
      }

      .expiry-dashboard-widget .stat-box.warning {
        border-left-color: #ffba00;
      }

      .expiry-dashboard-widget .stat-box.danger {
        border-left-color: #dc3232;
      }

      .expiry-dashboard-widget .stat-box h3 {
        margin: 0 0 5px;
        font-size: 14px;
      }

      .expiry-dashboard-widget .stat-count {
        font-size: 24px;
        font-weight: 600;
      }
    </style>

    <div class="stats-container">
      <a href="./edit.php?s&post_status=all&post_type=product&action=-1&expiry_period=this_month" class="stat-box">
        <h3>Tháng này</h3>
        <div class="stat-count"><?= count_expired_products_by_period('this_month') ?></div>
      </a>
      <a href="./edit.php?s&post_status=all&post_type=product&action=-1&expiry_period=next_month" class="stat-box">
        <h3>Tháng tới</h3>
        <div class="stat-count"><?= count_expired_products_by_period('next_month') ?></div>
      </a>
      <a href="./edit.php?s&post_status=all&post_type=product&action=-1&expiry_period=three_months" class="stat-box warning">
        <h3>3 tháng tới</h3>
        <div class="stat-count"><?= count_expired_products_by_period('three_months') ?></div>
      </a>
      <a href="./edit.php?s&post_status=all&post_type=product&action=-1&expiry_period=expired" class="stat-box danger">
        <h3>Đã hết hạn</h3>
        <div class="stat-count"><?= count_expired_products_by_period('expired') ?></div>
      </a>
    </div>
  </div>
<?php
}

function register_expiry_products_dashboard_widget()
{
  wp_add_dashboard_widget(
    'expiry_products_dashboard_widget',
    'Các sản phẩm sắp hết hạn sử dụng',
    'display_expiry_products_dashboard_widget'
  );
}

add_action('wp_dashboard_setup', 'register_expiry_products_dashboard_widget');
