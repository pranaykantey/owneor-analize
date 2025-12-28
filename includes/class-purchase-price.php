<?php
/**
 * Handles purchase price functionality for products
 */

class Owneor_Purchase_Price {

    public function add_purchase_price_field() {
        woocommerce_wp_text_input(array(
            'id' => '_purchase_price',
            'label' => __('Purchase Price', 'owneor-analize'),
            'desc_tip' => 'true',
            'description' => __('Enter the purchase price for this product.', 'owneor-analize'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            )
        ));
    }

    public function save_purchase_price_field($post_id) {
        $purchase_price = isset($_POST['_purchase_price']) ? sanitize_text_field($_POST['_purchase_price']) : '';
        update_post_meta($post_id, '_purchase_price', $purchase_price);
    }

    public function purchase_prices_page() {
        echo '<div class="wrap">';
        echo '<h1 style="color: #007cba; margin-bottom: 20px;">💰 Purchase Prices</h1>';

        if (isset($_POST['save_prices'])) {
            foreach ($_POST['purchase_prices'] as $product_id => $price) {
                update_post_meta($product_id, '_purchase_price', sanitize_text_field($price));
            }
            echo '<div class="notice notice-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">✅ Purchase prices updated successfully.</div>';
        }

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
        );
        $products = get_posts($args);

        echo '<form method="post">';
        echo '<div style="overflow-x: auto;">';
        echo '<table class="wp-list-table widefat fixed striped" style="border-collapse: collapse; width: 100%;">';
        echo '<thead>';
        echo '<tr style="background: #007cba; color: white;">';
        echo '<th style="padding: 10px;">Product</th>';
        echo '<th style="padding: 10px;">Purchase Price</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($products as $product) {
            $price = get_post_meta($product->ID, '_purchase_price', true);
            echo '<tr>';
            echo '<td style="padding: 10px;">' . esc_html($product->post_title) . '</td>';
            echo '<td style="padding: 10px;"><input type="number" step="0.01" min="0" name="purchase_prices[' . $product->ID . ']" value="' . esc_attr($price) . '" style="width: 100%; padding: 5px;"></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '<div style="margin-top: 20px;">';
        echo '<input type="submit" name="save_prices" value="💾 Save Prices" class="button button-primary" style="background: #28a745; color: white; padding: 10px 20px; font-size: 16px;">';
        echo '</div>';
        echo '</form>';

        echo '</div>';
    }
}