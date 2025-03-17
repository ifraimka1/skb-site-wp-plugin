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
require_once plugin_dir_path(__FILE__) . 'includes/vk_callback.php';
require_once plugin_dir_path(__FILE__) . 'includes/vk_cron.php';
require_once plugin_dir_path(__FILE__) . 'db.php';
require_once plugin_dir_path(__FILE__) . 'functions.php';

function plugin_activation()
{
    create_posts_table();
    create_attachments_table();
    get_news_init();

    wp_clear_scheduled_hook('skbkit_hourly_event');
    wp_schedule_event(time(), 'hourly', 'skbkit_hourly_event');
}

add_action('rest_api_init', 'skbkit_register_contactform_route');
add_action('rest_api_init', 'skbkit_register_vk_routes');
add_action('skbkit_hourly_event', 'skbkit_check_posts');

register_activation_hook(__FILE__, 'plugin_activation');

function plugin_deactivation()
{
    wp_clear_scheduled_hook('skbkit_hourly_event');
}

register_deactivation_hook(__FILE__, 'plugin_deactivation');
