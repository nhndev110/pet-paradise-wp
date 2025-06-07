<?php

/**
 * Plugin Name: Custom WooCommerce
 * Description: Tùy chỉnh WooCommerce.
 * Author: NHNDEV110
 * Version: 1.0
 */

add_filter('woocommerce_account_menu_items', 'custom_woocommerce_account_menu_items');
function custom_woocommerce_account_menu_items($items)
{
  unset($items['downloads']);

  add_filter('woocommerce_get_endpoint_url', function ($url, $endpoint) {
    if ($endpoint === 'customer-logout') {
      return wp_logout_url(wc_get_page_permalink('myaccount'));
    }
    return $url;
  }, 10, 4);

  return $items;
}

add_filter('wc_order_statuses', 'custom_woocommerce_order_statuses');
function custom_woocommerce_order_statuses($order_statuses)
{
  // Xóa trạng thái processing
  unset($order_statuses['wc-processing']);

  // Đổi tên trạng thái "Tạm giữ" thành "Đang xử lý"
  $order_statuses['wc-on-hold'] = "Đang xử lý";

  return $order_statuses;
}

// Đặt trạng thái cho đơn hàng theo phương thức thanh toán
add_action('woocommerce_checkout_order_processed', 'set_order_status_by_payment_method', 20, 1);
function set_order_status_by_payment_method($order_id)
{
  $order = wc_get_order($order_id);

  if ($order) {
    $payment_method = $order->get_payment_method();

    
    // Nếu thanh toán khi nhận hàng (COD) hoặc các phương thức offline khác
    if ($payment_method === 'cod') {
      $order->update_status('on-hold', 'Đơn hàng thanh toán khi nhận hàng - đang xử lý.');
    }
    
    // Nếu thanh toán VNPay hoặc các phương thức online khác
    if ($payment_method === 'vnpay_pay_default') {
      $order->update_status('pending', 'Đơn hàng chờ thanh toán online.');
    }
  }
}

// Hook bổ sung để đảm bảo COD luôn có trạng thái on-hold
add_action('woocommerce_thankyou', 'ensure_cod_status', 10, 1);
function ensure_cod_status($order_id)
{
  if (!$order_id) return;

  $order = wc_get_order($order_id);

  if ($order && $order->get_payment_method() === 'cod') {
    // Nếu đơn COD vẫn ở trạng thái pending, chuyển sang on-hold
    if ($order->get_status() === 'pending') {
      $order->update_status('on-hold', 'Đơn hàng COD tự động chuyển sang đang xử lý.');
    }
  }

  if ($order && $order->get_payment_method() === 'vnpay_pay_default') {
    // Chỉ set pending nếu chưa thanh toán
    if ($order->get_status() !== 'completed' && $order->get_status() !== 'processing') {
      $order->update_status('on-hold', 'Đơn hàng VNPay chờ thanh toán online.');
    }
  }
}

// add_action('woocommerce_order_status_changed', 'debug_status_change', 10, 4);
// function debug_status_change($order_id, $old_status, $new_status, $order)
// {
//   if ($order->get_payment_method() === 'vnpay_pay_default') {
//     error_log("Order $order_id: $old_status → $new_status");
//   }
// }

add_filter('woocommerce_payment_successful_result', 'control_vnpay_status', 10, 2);
function control_vnpay_status($result, $order_id)
{
  $order = wc_get_order($order_id);

  if ($order && $order->get_payment_method() === 'vnpay_pay_default') {
    if (!isset($_GET['vnp_ResponseCode']) || $_GET['vnp_ResponseCode'] !== '00') {
      $order->update_status('pending', 'VNPay - Chờ thanh toán online.');
    }
  }

  return $result;
}