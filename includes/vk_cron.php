<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/vk_parser.php';
require_once __DIR__ . '/../db.php';

use VK\Client\VKApiClient;

global $config, $token;

$config_path = plugin_dir_path(__FILE__) . 'config.php';
if (!file_exists($config_path)) {
    wp_die('Файл config.php отсутствует.');
}
$config = require $config_path;

function skbkit_check_posts() {
    global $config;

    $posts_to_check = get_fresh_posts();

    if (empty($posts_to_check)) return;

    $posts_to_check_ids = [];
    foreach ($posts_to_check as $post) {
        array_push($posts_to_check_ids, $config['test_vk_wall_id'].$post->id);
    }
    
    $token = $config['vk_access_token'];
    $args = ['posts' => implode(',', $posts_to_check_ids)];

    $vk = new VKApiClient();
    $response = $vk->wall()->getById($token, $args);

    foreach ($response as $post) {
        $old_hash = $posts_to_check[$post['id']]->hash;
        $new_text = $post['text'];
        $new_hash = is_hash_changed($old_hash, $new_text);

        if ($new_hash) {
            update_post(parse_post($post));
        }
    }
}


function is_hash_changed($old_hash, $new_text)
{
    $new_hash = get_post_hash($new_text);

    if ($old_hash !== $new_hash) {
        return $new_hash;
    }

    return false;
}
