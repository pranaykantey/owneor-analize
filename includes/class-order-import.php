<?php
/**
 * Handles order import functionality
 */

class Owneor_Order_Import {

    public function import_orders_page() {
        echo '<div class="wrap">';
        echo '<h1 style="color: #007cba; margin-bottom: 20px;">📥 Import Orders</h1>';

        if (isset($_POST['import_data'])) {
            if (!empty($_FILES['import_file']['tmp_name'])) {
                $file = $_FILES['import_file']['tmp_name'];
                $ext = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);
                $imported = 0;
                if ($ext == 'csv') {
                    $handle = fopen($file, 'r');
                    $header = fgetcsv($handle, 1000, ',');
                    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                        if (count($header) == count($data)) {
                            $row = array_combine($header, $data);
                            $this->create_order_from_data($row);
                            $imported++;
                        }
                    }
                    fclose($handle);
                } elseif ($ext == 'xml') {
                    $content = file_get_contents($file);
                    $xml = simplexml_load_string($content);
                    if ($xml === false) {
                        echo '<div class="notice notice-error" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;">❌ Failed to load XML file.</div>';
                    } else {
                        foreach ($xml->order as $order) {
                            $row = array();
                            foreach ($order as $key => $value) {
                                $row[(string)$key] = (string)$value;
                            }
                            $this->create_order_from_data($row);
                            $imported++;
                        }
                    }
                }
                echo '<div class="notice notice-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">✅ Imported ' . $imported . ' orders successfully.</div>';
            }

        }

        if (isset($_POST['delete_all_orders'])) {
            $this->delete_all_orders();
            echo '<div class="notice notice-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">✅ All orders deleted successfully.</div>';
        }

        echo '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
        echo '<h3 style="margin-top: 0;">Upload File</h3>';
        echo '<form method="post" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">';
        echo '<input type="file" name="import_file" accept=".csv,.xml" required style="padding: 5px;">';
        echo '<input type="submit" name="import_data" value="📤 Import Data" class="button button-primary" style="background: #007cba; color: white;">';
        echo '</form>';
        echo '<p style="margin-top: 10px; color: #6c757d;">Supported formats: CSV, XML</p>';
        echo '</div>';

        echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px;">';
        echo '<h3 style="color: #856404; margin-top: 0;">⚠️ Danger Zone</h3>';
        echo '<p style="color: #856404;"><strong>Warning:</strong> This will permanently delete all WooCommerce orders. This action cannot be undone.</p>';
        echo '<form method="post" onsubmit="return confirm(\'Are you sure you want to delete all orders? This action cannot be undone.\');">';
        echo '<input type="submit" name="delete_all_orders" value="🗑️ Delete All Orders" class="button" style="background: #dc3545; color: white; border: none; padding: 10px 20px;">';
        echo '</form>';
        echo '</div>';

        echo '</div>';
    }

    private function create_order_from_data($row) {
        // Check for duplicate
        $existing_orders = wc_get_orders(array(
            'meta_key' => '_original_order_id',
            'meta_value' => $row['Order ID'],
            'limit' => 1,
        ));
        if (!empty($existing_orders)) {
            return; // Skip duplicate
        }

        $order = wc_create_order();
        $order->set_customer_id(0); // Guest order

        // Set billing address
        $order->set_billing_first_name($row['Customer Name']);
        $order->set_billing_phone($row['Phone']);
        $order->set_billing_city($row['District']);
        $order->set_billing_state($row['Thana']);

        // Full address
        $full_address = $row['Village'] . ', ' . $row['Post Office'] . ', ' . $row['Union'] . ', ' . $row['Thana'] . ', ' . $row['District'];

        // Set ACF field if ACF is active
        if (function_exists('update_field')) {
            update_field('full_address', $full_address, $order->get_id());
        }

        // Store original order ID
        update_post_meta($order->get_id(), '_original_order_id', $row['Order ID']);

        // Add product - assume a dummy product or find by name
        // For simplicity, create a dummy product
        $product_id = wc_get_product_id_by_sku('dummy'); // Or create one
        if (!$product_id) {
            $product = new WC_Product_Simple();
            $product->set_name('Imported Product - ' . $row['Color'] . ' ' . $row['Size']);
            $product->set_sku('dummy');
            $product->set_price($row['Price']);
            $product->save();
            $product_id = $product->get_id();
        }
        $order->add_product(wc_get_product($product_id), $row['Quantity']);

        // Set status
        $status = strtolower($row['Status']);
        if ($status == 'completed' || $status == 'delivered') {
            $order->set_status('completed');
        } else {
            $order->set_status('pending');
        }

        $order->calculate_totals();
        $order->save();
    }

    private function delete_all_orders() {
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => 'any',
        ));
        foreach ($orders as $order) {
            $order->delete();
        }
    }
}