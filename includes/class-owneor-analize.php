<?php
/**
 * Main plugin class for Owneor Analyze
 */

class Owneor_Analize {

    private $analysis;
    private $purchase_price;
    private $admin_pages;
    private $admin_menu;
    private $order_import;
    private $scripts;

    public function __construct() {
        $this->analysis = new Owneor_Analysis();
        $this->purchase_price = new Owneor_Purchase_Price();
        $this->admin_pages = new Owneor_Admin_Pages($this->analysis);
        $this->order_import = new Owneor_Order_Import();
        $this->admin_menu = new Owneor_Admin_Menu($this->admin_pages, $this->purchase_price, $this->order_import);
        $this->scripts = new Owneor_Scripts();
    }

    public function run() {
        add_action('admin_menu', array($this->admin_menu, 'add_admin_menu'));
        add_action('woocommerce_product_options_pricing', array($this->purchase_price, 'add_purchase_price_field'));
        add_action('woocommerce_process_product_meta', array($this->purchase_price, 'save_purchase_price_field'));
        add_action('admin_enqueue_scripts', array($this->scripts, 'enqueue_scripts'));
    }
}