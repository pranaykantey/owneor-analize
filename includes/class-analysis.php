<?php
/**
 * Handles analysis calculations for orders and products
 */

class Owneor_Analysis {

    public function calculate_analysis($start_date, $end_date, $status = 'all') {
        // Query orders
        $args = array(
            'limit' => -1,
        );
        if ($status != 'all') {
            if (is_array($status)) {
                $args['status'] = $status;
            } else {
                $args['status'] = $status;
            }
        }
        if (!empty($start_date) && !empty($end_date)) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        $orders = wc_get_orders($args);

        $products = array();
        $total_quantity = 0;
        $total_revenue = 0;
        $total_cost = 0;
        $total_profit = 0;

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();
                $revenue = $item->get_total();
                $purchase_price = get_post_meta($product_id, '_purchase_price', true);
                $cost = $purchase_price ? $purchase_price * $quantity : 0;
                $profit = $revenue - $cost;

                if (!isset($products[$product_id])) {
                    $product = wc_get_product($product_id);
                    $products[$product_id] = array(
                        'name' => $product->get_name(),
                        'quantity' => 0,
                        'revenue' => 0,
                        'cost' => 0,
                        'profit' => 0,
                        'image' => null,
                    );
                }

                $products[$product_id]['quantity'] += $quantity;
                $products[$product_id]['revenue'] += $revenue;
                $products[$product_id]['cost'] += $cost;
                $products[$product_id]['profit'] += $profit;

                $total_quantity += $quantity;
                $total_revenue += $revenue;
                $total_cost += $cost;
                $total_profit += $profit;
            }
            if (function_exists('get_field')) {
                $order_image = get_field('order_image_raw', $order->get_id());
                if ($order_image) {
                    foreach ($order->get_items() as $item) {
                        $product_id = $item->get_product_id();
                        if ($products[$product_id]['image'] === null) {
                            $products[$product_id]['image'] = $order_image;
                        }
                    }
                }
            }
        }

        return array(
            'products' => $products,
            'totals' => array(
                'quantity' => $total_quantity,
                'revenue' => $total_revenue,
                'cost' => $total_cost,
                'profit' => $total_profit,
            )
        );
    }

    public function calculate_detailed_analysis($start_date, $end_date, $status = 'all') {
        $args = array(
            'limit' => -1,
        );
        if ($status != 'all') {
            if (is_array($status)) {
                $args['status'] = $status;
            } else {
                $args['status'] = $status;
            }
        }
        if (!empty($start_date) && !empty($end_date)) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        $orders = wc_get_orders($args);

        $order_data = array();
        $total_quantity = 0;
        $total_revenue = 0;
        $total_cost = 0;
        $total_profit = 0;

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();
                $revenue = $item->get_total();
                $purchase_price = get_post_meta($product_id, '_purchase_price', true);
                $cost = $purchase_price ? $purchase_price * $quantity : 0;
                $profit = $revenue - $cost;

                $product = wc_get_product($product_id);
                $image_id = $product->get_image_id();
                $image_url = wp_get_attachment_image_url($image_id, 'thumbnail') ?: wc_placeholder_img_src();
                $order_data[] = array(
                    'order_id' => $order->get_id(),
                    'status' => $order->get_status(),
                    'product_name' => $product->get_name(),
                    'image_url' => $image_url,
                    'quantity' => $quantity,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $profit,
                );

                $total_quantity += $quantity;
                $total_revenue += $revenue;
                $total_cost += $cost;
                $total_profit += $profit;
            }
        }

        return array(
            'orders' => $order_data,
            'totals' => array(
                'quantity' => $total_quantity,
                'revenue' => $total_revenue,
                'cost' => $total_cost,
                'profit' => $total_profit,
            )
        );
    }

    public function calculate_delivery_analysis($start_date, $end_date) {
        $args = array(
            'status' => 'any',
            'limit' => -1,
        );
        if (!empty($start_date) && !empty($end_date)) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        $orders = wc_get_orders($args);

        $order_data = array();
        $total_profit = 0;
        $total_delivery = 0;
        $total_net = 0;
        $cancelled_loss = 0;

        foreach ($orders as $order) {
            $profit = 0;
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();
                $revenue = $item->get_total();
                $purchase_price = get_post_meta($product_id, '_purchase_price', true);
                $cost = $purchase_price ? $purchase_price * $quantity : 0;
                $profit += $revenue - $cost;
            }

            $delivery_charge = 0;
            $net_profit = $profit;
            $is_cancelled = $order->get_status() == 'cancelled';
            if (function_exists('get_field')) {
                $delivery_type = get_field('delivery_charge', $order->get_id());
                if ($delivery_type == 'Owneor') {
                    $delivery_charge = get_field('delivery_charge_amount', $order->get_id()) ?: 0;
                    $net_profit = $profit - $delivery_charge;
                    if ($is_cancelled) {
                        $cancelled_loss += $delivery_charge;
                    }
                }
            }

            if (!$is_cancelled) {
                $order_data[] = array(
                    'id' => $order->get_id(),
                    'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'profit' => $profit,
                    'delivery_charge' => $delivery_charge,
                    'net_profit' => $net_profit,
                );
            }

            $total_profit += $profit;
            $total_delivery += $delivery_charge;
            $total_net += $net_profit;
        }

        return array(
            'orders' => $order_data,
            'totals' => array(
                'profit' => $total_profit,
                'delivery_charge' => $total_delivery,
                'net_profit' => $total_net,
                'cancelled_loss' => $cancelled_loss,
            )
        );
    }
}