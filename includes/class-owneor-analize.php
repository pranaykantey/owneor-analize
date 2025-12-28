<?php
/**
 * Main plugin class for Owneor Analyze
 */

class Owneor_Analize {

    public function run() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('woocommerce_product_options_pricing', array($this, 'add_purchase_price_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_purchase_price_field'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Owneor Analysis',
            'Owneor Analysis',
            'manage_options',
            'owneor-analysis',
            array($this, 'admin_page')
        );
        add_submenu_page(
            'owneor-analysis',
            'Purchase Prices',
            'Purchase Prices',
            'manage_options',
            'owneor-purchase-prices',
            array($this, 'purchase_prices_page')
        );
        add_submenu_page(
            'owneor-analysis',
            'Detailed Analysis',
            'Detailed Analysis',
            'manage_options',
            'owneor-detailed-analysis',
            array($this, 'detailed_analysis_page')
        );
        add_submenu_page(
            'owneor-analysis',
            'Delivery Charge Analysis',
            'Delivery Charge Analysis',
            'manage_options',
            'owneor-delivery-charge-analysis',
            array($this, 'delivery_charge_analysis_page')
        );

    }

    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>Owneor Analysis</h1>';

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

        // Form
        echo '<form method="post">';
        echo '<label for="start_date">Start Date (optional):</label>';
        echo '<input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
        echo '<label for="end_date">End Date (optional):</label>';
        echo '<input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
        echo '<input type="submit" name="calculate_analysis" value="Calculate Analysis">';
        echo '</form>';

        // Get analysis data
        $analysis = $this->calculate_analysis($start_date, $end_date);

        // Download buttons
        echo '<p>';
        echo '<button id="download-pdf" class="button">Download as PDF</button> ';
        echo '<button id="download-image" class="button">Download as Image</button>';
        echo '</p>';

        // Display table
        echo '<table id="analysis-table" class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Product</th>';
        echo '<th>Quantity Sold</th>';
        echo '<th>Revenue</th>';
        echo '<th>Cost</th>';
        echo '<th>Profit</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($analysis['products'] as $product) {
            echo '<tr>';
            echo '<td>' . esc_html($product['name']) . '</td>';
            echo '<td>' . esc_html($product['quantity']) . '</td>';
            echo '<td>' . wc_price($product['revenue']) . '</td>';
            echo '<td>' . wc_price($product['cost']) . '</td>';
            echo '<td>' . wc_price($product['profit']) . '</td>';
            echo '</tr>';
        }
        echo '<tr style="font-weight: bold;">';
        echo '<td>Total</td>';
        echo '<td>' . esc_html($analysis['totals']['quantity']) . '</td>';
        echo '<td>' . wc_price($analysis['totals']['revenue']) . '</td>';
        echo '<td>' . wc_price($analysis['totals']['cost']) . '</td>';
        echo '<td>' . wc_price($analysis['totals']['profit']) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';

        echo '</div>';
    }

    private function calculate_analysis($start_date, $end_date, $status = 'completed') {
        // Query orders
        $args = array(
            'limit' => -1,
        );
        if ($status != 'all') {
            $args['status'] = $status;
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

    public function enqueue_scripts($hook) {
        if ($hook === 'toplevel_page_owneor-analysis') {
            wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array(), '1.4.1', true);
            wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), '2.5.1', true);
            wp_enqueue_script('owneor-admin-js', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery', 'html2canvas', 'jspdf'), '1.0.0', true);
        }
        if ($hook === 'owneor-analysis_page_owneor-detailed-analysis') {
            wp_enqueue_style('lightbox-css', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css');
            wp_enqueue_script('lightbox-js', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js', array('jquery'), '2.11.4', true);
            wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array(), '1.4.1', true);
            wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), '2.5.1', true);
            wp_enqueue_script('owneor-admin-js', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery', 'html2canvas', 'jspdf'), '1.0.0', true);
        }
    
        
    }
    public function purchase_prices_page() {
        echo '<div class="wrap">';
        echo '<h1>Purchase Prices</h1>';

        if (isset($_POST['save_prices'])) {
            foreach ($_POST['purchase_prices'] as $product_id => $price) {
                update_post_meta($product_id, '_purchase_price', sanitize_text_field($price));
            }
            echo '<div class="notice notice-success"><p>Purchase prices updated.</p></div>';
        }

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
        );
        $products = get_posts($args);

        echo '<form method="post">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Product</th>';
        echo '<th>Purchase Price</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($products as $product) {
            $price = get_post_meta($product->ID, '_purchase_price', true);
            echo '<tr>';
            echo '<td>' . esc_html($product->post_title) . '</td>';
            echo '<td><input type="number" step="0.01" min="0" name="purchase_prices[' . $product->ID . ']" value="' . esc_attr($price) . '"></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '<input type="submit" name="save_prices" value="Save Prices" class="button button-primary">';
        echo '</form>';

        echo '</div>';
    }

    public function detailed_analysis_page() {
        echo '<div class="wrap">';
        echo '<h1>Detailed Analysis</h1>';

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'completed';

        // Form
        echo '<form method="post">';
        echo '<label for="start_date">Start Date (optional):</label>';
        echo '<input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
        echo '<label for="end_date">End Date (optional):</label>';
        echo '<input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
        echo '<input type="submit" name="calculate_detailed" value="Calculate Detailed Analysis">';
        echo '</form>';

        // Download buttons
        echo '<p>';
        echo '<button id="download-pdf" class="button">Download as PDF</button> ';
        echo '<button id="download-image" class="button">Download as Image</button>';
        echo '</p>';

        // Get analysis data
        $analysis = $this->calculate_analysis($start_date, $end_date);

        // Display table with images
        echo '<table id="analysis-table" class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Image</th>';
        echo '<th>Product</th>';
        echo '<th>Quantity Sold</th>';
        echo '<th>Revenue</th>';
        echo '<th>Cost</th>';
        echo '<th>Profit</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($analysis['products'] as $product_id => $product) {
            $wc_product = wc_get_product($product_id);
            if (isset($product['image']) && $product['image']) {
                $image_url = $product['image'];
            } else {
                $image_id = $wc_product->get_image_id();
                $image_url = wp_get_attachment_image_url($image_id, 'thumbnail') ?: wc_placeholder_img_src();
            }
            echo '<tr>';
            echo '<td><a href="' . esc_url($image_url) . '" data-lightbox="product-images"><img src="' . esc_url($image_url) . '" style="width: 50px; height: auto;"></a></td>';
            echo '<td>' . esc_html($product['name']) . '</td>';
            echo '<td>' . esc_html($product['quantity']) . '</td>';
            echo '<td>' . wc_price($product['revenue']) . '</td>';
            echo '<td>' . wc_price($product['cost']) . '</td>';
            echo '<td>' . wc_price($product['profit']) . '</td>';
            echo '</tr>';
        }
        echo '<tr style="font-weight: bold;">';
        echo '<td>Total</td>';
        echo '<td></td>';
        echo '<td>' . esc_html($analysis['totals']['quantity']) . '</td>';
        echo '<td>' . wc_price($analysis['totals']['revenue']) . '</td>';
        echo '<td>' . wc_price($analysis['totals']['cost']) . '</td>';
        echo '<td>' . wc_price($analysis['totals']['profit']) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';

        echo '</div>';
    }

    public function delivery_charge_analysis_page() {
        echo '<div class="wrap">';
        echo '<h1>Delivery Charge Analysis</h1>';

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

        // Form
        echo '<form method="post">';
        echo '<label for="start_date">Start Date (optional):</label>';
        echo '<input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
        echo '<label for="end_date">End Date (optional):</label>';
        echo '<input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
        echo '<input type="submit" name="calculate_delivery" value="Calculate Delivery Analysis">';
        echo '</form>';

        // Get analysis data
        $analysis = $this->calculate_delivery_analysis($start_date, $end_date);

        // Display table
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Order ID</th>';
        echo '<th>Customer</th>';
        echo '<th>Profit</th>';
        echo '<th>Delivery Charge</th>';
        echo '<th>Net Profit</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($analysis['orders'] as $order_data) {
            echo '<tr>';
            echo '<td>' . esc_html($order_data['id']) . '</td>';
            echo '<td>' . esc_html($order_data['customer']) . '</td>';
            echo '<td>' . wc_price($order_data['profit']) . '</td>';
            echo '<td>' . wc_price($order_data['delivery_charge']) . '</td>';
            echo '<td>' . wc_price($order_data['net_profit']) . '</td>';
            echo '</tr>';
        }
        echo '<tr style="font-weight: bold;">';
        echo '<td>Total</td>';
        echo '<td></td>';
        echo '<td>' . wc_price($analysis['totals']['profit']) . '</td>';
        echo '<td>' . wc_price($analysis['totals']['delivery_charge']) . '</td>';
        echo '<td>' . wc_price($analysis['totals']['net_profit']) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';

        echo '</div>';
    }

    public function import_orders_page() {
        echo '<div class="wrap">';
        echo '<h1>Import Orders</h1>';

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
                        echo '<div class="notice notice-error"><p>Failed to load XML file.</p></div>';
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
                echo '<div class="notice notice-success"><p>Imported ' . $imported . ' orders.</p></div>';
            }
        
        }

        if (isset($_POST['delete_all_orders'])) {
            $this->delete_all_orders();
            echo '<div class="notice notice-success"><p>All orders deleted.</p></div>';
        }

        echo '<form method="post" enctype="multipart/form-data">';
        echo '<input type="file" name="import_file" accept=".csv,.xml" required>';
        echo '<input type="submit" name="import_data" value="Import Data" class="button button-primary">';
        echo '</form>';

        echo '<hr>';
        echo '<h3>Delete All Orders</h3>';
        echo '<p><strong>Warning:</strong> This will permanently delete all WooCommerce orders. This action cannot be undone.</p>';
        echo '<form method="post" onsubmit="return confirm(\'Are you sure you want to delete all orders? This action cannot be undone.\');">';
        echo '<input type="submit" name="delete_all_orders" value="Delete All Orders" class="button button-danger">';
        echo '</form>';

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

    private function calculate_delivery_analysis($start_date, $end_date) {
            $args = array(
                'status' => 'completed',
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
                if (function_exists('get_field')) {
                    $delivery_type = get_field('delivery_charge', $order->get_id());
                    if ($delivery_type == 'Owneor') {
                        $delivery_charge = get_field('delivery_charge_amount', $order->get_id()) ?: 0;
                        $net_profit = $profit - $delivery_charge;
                    }
                }
    
                $order_data[] = array(
                    'id' => $order->get_id(),
                    'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'profit' => $profit,
                    'delivery_charge' => $delivery_charge,
                    'net_profit' => $net_profit,
                );
    
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
                )
            );
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