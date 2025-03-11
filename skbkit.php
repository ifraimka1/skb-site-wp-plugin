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
require_once plugin_dir_path(__FILE__) . 'includes/vk.php';
require_once plugin_dir_path(__FILE__) . 'functions.php';
require_once plugin_dir_path(__FILE__) . 'db.php';

function skbkit_init()
{
    skbkit_register_contactform_route();
    skbkit_register_vk_routes();
}

add_action('init', 'skbkit_init');

// Регистрация функции для активации плагина
function plugin_activation()
{
    create_posts_table();
    create_attachments_table();
    get_news_init();
}

// При активации плагина вызываем функцию
register_activation_hook(__FILE__, 'plugin_activation');
