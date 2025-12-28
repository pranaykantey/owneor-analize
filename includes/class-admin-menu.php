<?php
/**
 * Handles admin menu setup
 */

class Owneor_Admin_Menu {

    private $admin_pages;
    private $purchase_price;
    private $order_import;

    public function __construct($admin_pages, $purchase_price, $order_import) {
        $this->admin_pages = $admin_pages;
        $this->purchase_price = $purchase_price;
        $this->order_import = $order_import;
    }

    public function add_admin_menu() {
        add_menu_page(
            'Owneor Analysis',
            'Owneor Analysis',
            'manage_options',
            'owneor-analysis',
            array($this->admin_pages, 'admin_page')
        );
        add_submenu_page(
            'owneor-analysis',
            'Purchase Prices',
            'Purchase Prices',
            'manage_options',
            'owneor-purchase-prices',
            array($this->purchase_price, 'purchase_prices_page')
        );
        add_submenu_page(
            'owneor-analysis',
            'Detailed Analysis',
            'Detailed Analysis',
            'manage_options',
            'owneor-detailed-analysis',
            array($this->admin_pages, 'detailed_analysis_page')
        );
        add_submenu_page(
            'owneor-analysis',
            'Delivery Charge Analysis',
            'Delivery Charge Analysis',
            'manage_options',
            'owneor-delivery-charge-analysis',
            array($this->admin_pages, 'delivery_charge_analysis_page')
        );
        add_submenu_page(
            'owneor-analysis',
            'Import Orders',
            'Import Orders',
            'manage_options',
            'owneor-import-orders',
            array($this->order_import, 'import_orders_page')
        );
    }
}