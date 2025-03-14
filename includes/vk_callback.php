<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/vk_parser.php';
require_once __DIR__ . '/../db.php';

use VK\Client\VKApiClient;

$config_path = plugin_dir_path(__FILE__) . 'config.php';
if (!file_exists($config_path)) {
    wp_die('Файл config.php отсутствует.');
}
$config = require $config_path;

// Конфигурация
define('VK_API_VERSION', '5.131'); // Версия API VK
define('VK_SECRET_KEY', $config['vk_callback_secret']); // Секретный ключ из настроек Callback API
define('VK_CONFIRMATION_CODE', $config['vk_callback_confirm']); // Код подтверждения из настроек Callback API

$vk = new VKApiClient(VK_API_VERSION);

function vk_callback(WP_REST_Request $request)
{
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data['secret'] !== VK_SECRET_KEY) {
        wp_die('Invalid secret key', '', ['response' => 403]);
    }

    if ($data['type'] === 'confirmation') {
        echo VK_CONFIRMATION_CODE;
        exit;
    }

    if ($data['type'] === 'wall_post_new') {
        $new_post = parse_post($data['object']);
        $successful_insert = insert_posts([$new_post]);
        if ($successful_insert) {
            return new WP_REST_Response('ok', 200);
        } else {
            return new WP_REST_Response('Znaesh li ti, vdol night dorog', 500);
        }
    }

    echo 'ok';
    exit;
};

function skbkit_register_vk_callback_routes()
{
    register_rest_route(
        'skbkit/v1',
        '/vkcallback',
        array(
            'methods' => 'POST',
            'callback' => 'vk_callback',
            'permission_callback' => function () {
                return true;
            }
        )
    );
}

add_action('rest_api_init', 'skbkit_register_vk_callback_routes');
