<?php
/**
 * Handles scripts and styles enqueuing
 */

class Owneor_Scripts {

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
}