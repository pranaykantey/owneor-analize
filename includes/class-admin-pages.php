<?php
/**
 * Handles admin pages rendering
 */

class Owneor_Admin_Pages {

    private $analysis;

    public function __construct($analysis) {
        $this->analysis = $analysis;
    }

    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1 style="color: #007cba; margin-bottom: 20px;">📊 Owneor Analysis</h1>';

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

        // Form with better styling
        echo '<div style="background: #f1f1f1; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
        echo '<form method="post" style="display: flex; gap: 10px; align-items: center;">';
        echo '<label for="start_date" style="font-weight: bold;">Start Date (optional):</label>';
        echo '<input type="date" name="start_date" value="' . esc_attr($start_date) . '" style="padding: 5px;">';
        echo '<label for="end_date" style="font-weight: bold;">End Date (optional):</label>';
        echo '<input type="date" name="end_date" value="' . esc_attr($end_date) . '" style="padding: 5px;">';
        echo '<input type="submit" name="calculate_analysis" value="Calculate Analysis" class="button button-primary" style="background: #007cba; color: white;">';
        echo '</form>';
        echo '</div>';

        // Get analysis data
        $analysis = $this->analysis->calculate_analysis($start_date, $end_date);

        // Download buttons with icons
        echo '<div style="margin-bottom: 20px;">';
        echo '<button id="download-pdf" class="button" style="background: #dc3545; color: white; margin-right: 10px;">📄 Download as PDF</button>';
        echo '<button id="download-image" class="button" style="background: #28a745; color: white;">🖼️ Download as Image</button>';
        echo '</div>';

        // Display table with better styling
        echo '<div style="overflow-x: auto;">';
        echo '<table id="analysis-table" class="wp-list-table widefat fixed striped" style="border-collapse: collapse; width: 100%;">';
        echo '<thead>';
        echo '<tr style="background: #007cba; color: white;">';
        echo '<th style="padding: 10px;">Product</th>';
        echo '<th style="padding: 10px;">Quantity Sold</th>';
        echo '<th style="padding: 10px;">Revenue</th>';
        echo '<th style="padding: 10px;">Cost</th>';
        echo '<th style="padding: 10px;">Profit</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($analysis['products'] as $product) {
            echo '<tr>';
            echo '<td style="padding: 10px;">' . esc_html($product['name']) . '</td>';
            echo '<td style="padding: 10px; text-align: center;">' . esc_html($product['quantity']) . '</td>';
            echo '<td style="padding: 10px; text-align: right;">' . wc_price($product['revenue']) . '</td>';
            echo '<td style="padding: 10px; text-align: right;">' . wc_price($product['cost']) . '</td>';
            echo '<td style="padding: 10px; text-align: right; font-weight: bold; color: ' . ($product['profit'] >= 0 ? 'green' : 'red') . ';">' . wc_price($product['profit']) . '</td>';
            echo '</tr>';
        }
        echo '<tr style="background: #f1f1f1; font-weight: bold; font-size: 1.1em;">';
        echo '<td style="padding: 10px;">Total</td>';
        echo '<td style="padding: 10px; text-align: center;">' . esc_html($analysis['totals']['quantity']) . '</td>';
        echo '<td style="padding: 10px; text-align: right;">' . wc_price($analysis['totals']['revenue']) . '</td>';
        echo '<td style="padding: 10px; text-align: right;">' . wc_price($analysis['totals']['cost']) . '</td>';
        echo '<td style="padding: 10px; text-align: right; color: ' . ($analysis['totals']['profit'] >= 0 ? 'green' : 'red') . ';">' . wc_price($analysis['totals']['profit']) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        echo '</div>';
    }

    public function detailed_analysis_page() {
        echo '<div class="wrap">';
        echo '<h1 style="color: #007cba; margin-bottom: 20px;">🔍 Detailed Analysis</h1>';

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $selected_statuses = isset($_POST['statuses']) ? $_POST['statuses'] : array('completed');

        // Form with better styling
        echo '<div style="background: #f1f1f1; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
        echo '<form method="post">';
        echo '<div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">';
        echo '<label for="start_date" style="font-weight: bold;">Start Date (optional):</label>';
        echo '<input type="date" name="start_date" value="' . esc_attr($start_date) . '" style="padding: 5px;">';
        echo '<label for="end_date" style="font-weight: bold;">End Date (optional):</label>';
        echo '<input type="date" name="end_date" value="' . esc_attr($end_date) . '" style="padding: 5px;">';
        echo '</div>';
        echo '<div style="margin-bottom: 10px;">';
        echo '<label style="font-weight: bold;">Order Statuses:</label><br>';
        $statuses = array('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed');
        foreach ($statuses as $status) {
            $checked = in_array($status, $selected_statuses) ? 'checked' : '';
            echo '<label style="margin-right: 10px;"><input type="checkbox" name="statuses[]" value="' . $status . '" ' . $checked . '> ' . ucfirst($status) . '</label>';
        }
        echo '</div>';
        echo '<input type="submit" name="calculate_detailed" value="Calculate Detailed Analysis" class="button button-primary" style="background: #007cba; color: white;">';
        echo '</form>';
        echo '</div>';

        // Download buttons with icons
        echo '<div style="margin-bottom: 20px;">';
        echo '<button id="download-pdf" class="button" style="background: #dc3545; color: white; margin-right: 10px;">📄 Download as PDF</button>';
        echo '<button id="download-image" class="button" style="background: #28a745; color: white;">🖼️ Download as Image</button>';
        echo '</div>';

        // Get analysis data
        $analysis = $this->analysis->calculate_detailed_analysis($start_date, $end_date, $selected_statuses);

        // Display table with orders and better styling
        echo '<div style="overflow-x: auto;">';
        echo '<table id="analysis-table" class="wp-list-table widefat fixed striped" style="border-collapse: collapse; width: 100%;">';
        echo '<thead>';
        echo '<tr style="background: #007cba; color: white;">';
        echo '<th style="padding: 10px;">Image</th>';
        echo '<th style="padding: 10px;">Order ID</th>';
        echo '<th style="padding: 10px;">Status</th>';
        echo '<th style="padding: 10px;">Product</th>';
        echo '<th style="padding: 10px;">Quantity</th>';
        echo '<th style="padding: 10px;">Revenue</th>';
        echo '<th style="padding: 10px;">Cost</th>';
        echo '<th style="padding: 10px;">Profit</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($analysis['orders'] as $order_item) {
            echo '<tr>';
            echo '<td style="padding: 10px; text-align: center;"><a href="' . esc_url($order_item['image_url']) . '" data-lightbox="product-images"><img src="' . esc_url($order_item['image_url']) . '" style="width: 50px; height: auto; border-radius: 4px;"></a></td>';
            echo '<td style="padding: 10px; text-align: center;">' . esc_html($order_item['order_id']) . '</td>';
            echo '<td style="padding: 10px; text-align: center;"><span style="background: ' . $this->get_status_color($order_item['status']) . '; color: white; padding: 2px 6px; border-radius: 4px;">' . esc_html(ucfirst($order_item['status'])) . '</span></td>';
            echo '<td style="padding: 10px;">' . esc_html($order_item['product_name']) . '</td>';
            echo '<td style="padding: 10px; text-align: center;">' . esc_html($order_item['quantity']) . '</td>';
            echo '<td style="padding: 10px; text-align: right;">' . wc_price($order_item['revenue']) . '</td>';
            echo '<td style="padding: 10px; text-align: right;">' . wc_price($order_item['cost']) . '</td>';
            echo '<td style="padding: 10px; text-align: right; font-weight: bold; color: ' . ($order_item['profit'] >= 0 ? 'green' : 'red') . ';">' . wc_price($order_item['profit']) . '</td>';
            echo '</tr>';
        }
        echo '<tr style="background: #f1f1f1; font-weight: bold; font-size: 1.1em;">';
        echo '<td style="padding: 10px;" colspan="3">Total</td>';
        echo '<td style="padding: 10px; text-align: center;">' . esc_html($analysis['totals']['quantity']) . '</td>';
        echo '<td style="padding: 10px; text-align: right;">' . wc_price($analysis['totals']['revenue']) . '</td>';
        echo '<td style="padding: 10px; text-align: right;">' . wc_price($analysis['totals']['cost']) . '</td>';
        echo '<td style="padding: 10px; text-align: right; color: ' . ($analysis['totals']['profit'] >= 0 ? 'green' : 'red') . ';">' . wc_price($analysis['totals']['profit']) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        echo '</div>';
    }

    public function delivery_charge_analysis_page() {
        echo '<div class="wrap">';
        echo '<h1 style="color: #007cba; margin-bottom: 20px;">🚚 Delivery Charge Analysis</h1>';

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

        // Form with better styling
        echo '<div style="background: #f1f1f1; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
        echo '<form method="post" style="display: flex; gap: 10px; align-items: center;">';
        echo '<label for="start_date" style="font-weight: bold;">Start Date (optional):</label>';
        echo '<input type="date" name="start_date" value="' . esc_attr($start_date) . '" style="padding: 5px;">';
        echo '<label for="end_date" style="font-weight: bold;">End Date (optional):</label>';
        echo '<input type="date" name="end_date" value="' . esc_attr($end_date) . '" style="padding: 5px;">';
        echo '<input type="submit" name="calculate_delivery" value="Calculate Delivery Analysis" class="button button-primary" style="background: #007cba; color: white;">';
        echo '</form>';
        echo '</div>';

        // Get analysis data
        $analysis = $this->analysis->calculate_delivery_analysis($start_date, $end_date);

        // Display cancelled loss box with attractive styling
        if (isset($analysis['totals']['cancelled_loss']) && $analysis['totals']['cancelled_loss'] > 0) {
            echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center;">';
            echo '<span style="font-size: 24px; margin-right: 10px;">⚠️</span>';
            echo '<div>';
            echo '<strong>Total Loss from Cancelled Orders (Owneor Delivery):</strong> <span style="color: #856404; font-size: 1.2em;">' . wc_price($analysis['totals']['cancelled_loss']) . '</span>';
            echo '</div>';
            echo '</div>';
        }

        // Display table with better styling
        echo '<div style="overflow-x: auto;">';
        echo '<table class="wp-list-table widefat fixed striped" style="border-collapse: collapse; width: 100%;">';
        echo '<thead>';
        echo '<tr style="background: #007cba; color: white;">';
        echo '<th style="padding: 10px;">Order ID</th>';
        echo '<th style="padding: 10px;">Customer</th>';
        echo '<th style="padding: 10px;">Profit</th>';
        echo '<th style="padding: 10px;">Delivery Charge</th>';
        echo '<th style="padding: 10px;">Net Profit</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($analysis['orders'] as $order_data) {
            echo '<tr>';
            echo '<td style="padding: 10px; text-align: center;">' . esc_html($order_data['id']) . '</td>';
            echo '<td style="padding: 10px;">' . esc_html($order_data['customer']) . '</td>';
            echo '<td style="padding: 10px; text-align: right;">' . wc_price($order_data['profit']) . '</td>';
            echo '<td style="padding: 10px; text-align: right;">' . wc_price($order_data['delivery_charge']) . '</td>';
            echo '<td style="padding: 10px; text-align: right; font-weight: bold; color: ' . ($order_data['net_profit'] >= 0 ? 'green' : 'red') . ';">' . wc_price($order_data['net_profit']) . '</td>';
            echo '</tr>';
        }
        echo '<tr style="background: #f1f1f1; font-weight: bold; font-size: 1.1em;">';
        echo '<td style="padding: 10px;">Total</td>';
        echo '<td style="padding: 10px;"></td>';
        echo '<td style="padding: 10px; text-align: right;">' . wc_price($analysis['totals']['profit']) . '</td>';
        echo '<td style="padding: 10px; text-align: right;">' . wc_price($analysis['totals']['delivery_charge']) . '</td>';
        echo '<td style="padding: 10px; text-align: right; color: ' . ($analysis['totals']['net_profit'] >= 0 ? 'green' : 'red') . ';">' . wc_price($analysis['totals']['net_profit']) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        echo '</div>';
    }

    private function get_status_color($status) {
        $colors = array(
            'pending' => '#ffc107',
            'processing' => '#17a2b8',
            'on-hold' => '#fd7e14',
            'completed' => '#28a745',
            'cancelled' => '#dc3545',
            'refunded' => '#6c757d',
            'failed' => '#dc3545',
        );
        return isset($colors[$status]) ? $colors[$status] : '#6c757d';
    }
}