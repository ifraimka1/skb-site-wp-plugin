<?php
/**
 * Plugin Name: SKB KIT Plugin
 * Description: Плагин для обеспечения работы сайта СКБ.
 * Version: 1.0
 * Author: Соломонов Ифраим, СКБ "КИТ"
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/contact-form.php';

function skbkit_init() {
    skbkit_register_contactform_route();
}

add_action('init', 'skbkit_init');