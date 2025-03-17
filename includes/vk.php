<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/vk_parser.php';
require_once __DIR__ . '/../db.php';

use VK\Client\VKApiClient;

global $config;

$config_path = plugin_dir_path(__FILE__) . 'config.php';
if (!file_exists($config_path)) {
    wp_die('Файл config.php отсутствует.');
}
$config = require $config_path;

define('VK_ACCESS_TOKEN', $config['vk_access_token']);
define('VK_WALL_ID', $config['vk_wall_id']);

function get_news_init(WP_REST_Request $request = null)
{
    global $config;

    if ($request) {
        $password = sanitize_text_field($request->get_param('truncate'));
        $truncate = $password === $config['truncatepassword'];

        if ($truncate) {
            truncate_posts();
        } else if ($password) {
            return new WP_REST_Response("Не, такой базар ты не вывозишь.", 403);
        }
    }

    $wall_id = $config['vk_wall_id'];
    $count = 50;
    $total = $count;
    $args = ['owner_id' => $wall_id, 'count' => $count, 'offset' => 0];
    
    $vk = new VKApiClient();

    while ($args['offset'] < $total) {
        $response = $vk->wall()->get(VK_ACCESS_TOKEN, $args);
        parse_wall($response);

        if ($total !== $response['count']) $total = $response['count'];

        $args['offset'] += $count;
        sleep(1);
    }


    return $args['offset'];
}

function get_news(WP_REST_Request $request)
{
    global $wpdb;

    $table_posts = $wpdb->prefix . 'skbkit_vk_posts';
    $table_att = $wpdb->prefix . 'skbkit_vk_posts_attachments';
    $count = $request->get_param('count');
    $offset = $request->get_param('offset');

    $sql = "SELECT
                posts.id AS id,
                posts.heading AS heading,
                MIN(att.id) AS attid,
                att.url AS preview
            FROM $table_posts AS posts
            LEFT JOIN $table_att AS att ON posts.id = att.postid
            GROUP BY posts.id
            ORDER BY posts.id DESC";
    $sql .= $count ? " LIMIT $count" : "";
    $sql .= $offset ? " OFFSET $offset" : "";
    $wall = $wpdb->get_results($sql);

    return $wall;
}

function get_news_by_id(WP_REST_Request $request)
{
    global $wpdb;


    $table_posts = $wpdb->prefix . 'skbkit_vk_posts';
    $table_att = $wpdb->prefix . 'skbkit_vk_posts_attachments';

    $id = $request->get_param('id');

    $sql = "SELECT
                posts.*,
                att.url AS att_url,
                att.type As att_type
            FROM $table_posts AS posts
            LEFT JOIN $table_att AS att ON posts.id = att.postid
            WHERE posts.id = $id
            ORDER BY posts.id";
    $rows = $wpdb->get_results($sql);


    $vk = new VKApiClient('5.199');
    $args = ['posts' => VK_WALL_ID . '_' . $id];
    $args = ['posts' => VK_WALL_ID . '_' . $id];
    $response = $vk->wall()->getById(VK_ACCESS_TOKEN, $args);
    $views = $response['items'][0]['views']['count'];
    if (!$views) {
        $views = 0;
    }

    $text = explode("\n", $rows[0]->text);
    $text = array_filter($text, function ($value) {
        return !empty(trim($value));
    });

    $post = new stdClass();
    $post->id = $rows[0]->id;
    $post->heading = $rows[0]->heading;
    $post->text = array_values($text);
    $post->date = $rows[0]->date;
    $post->views = $views;
    $post->photos = [];
    $post->videos = [];

    foreach ($rows as $row) {
        if ($row->att_type == "photo") {
            $new_attachment = $row->att_url;
            array_push($post->photos, $new_attachment);
        } else {
            $new_attachment = new stdClass();
            $new_attachment->preview = $row->att_url;
            array_push($post->videos, $new_attachment);
        }
    }

    return $post;
}

function skbkit_register_vk_routes()
{
    register_rest_route(
        'skbkit/v1',
        '/getnews',
        array(
            'methods' => 'GET',
            'callback' => 'get_news',
            'permission_callback' => function () {
                return true;
            }
        )
    );

    register_rest_route(
        'skbkit/v1',
        '/getnewsbyid',
        array(
            'methods' => 'GET',
            'callback' => 'get_news_by_id',
            'permission_callback' => function () {
                return true;
            }
        )
    );

    register_rest_route(
        'skbkit/v1',
        '/getnewsinit',
        array(
            'methods' => 'GET',
            'callback' => 'get_news_init',
            'permission_callback' => function () {
                return true;
            }
        )
    );

    register_rest_route(
        'skbkit/v1',
        '/getevents',
        array(
            'methods' => 'GET',
            'callback' => 'get_events',
            'permission_callback' => function () {
                return true;
            }
        )
    );
}

// add_action('rest_api_init', 'skbkit_register_vk_routes');
// add_action('rest_api_init', 'skbkit_register_vk_routes');
