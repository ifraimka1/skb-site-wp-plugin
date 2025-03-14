<?php

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_posts = $wpdb->prefix . 'skbkit_vk_posts';
$table_att = $wpdb->prefix . 'skbkit_vk_posts_attachments';

function create_posts_table()
{
    global $wpdb, $table_posts;

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_posts'") != $table_posts) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_posts (
                id BIGINT(20) NOT NULL,
                heading VARCHAR(255) NOT NULL,
                text TEXT NOT NULL,
                date VARCHAR(10) NOT NULL,
                hash VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

        // Используем dbDelta для создания таблицы
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

function create_attachments_table()
{
    global $wpdb, $table_posts, $table_att;

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_att'") != $table_att) {

        $charset_collate = $wpdb->get_charset_collate();
        $reference = $table_posts . "(id)";

        $sql = "CREATE TABLE $table_att (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                postid BIGINT(20) NOT NULL,
                type ENUM('video', 'photo') NOT NULL,
                url TEXT NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (postid) REFERENCES $reference
            ) $charset_collate;";

        // Используем dbDelta для создания таблицы
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

function insert_posts($wall)
{
    global $wpdb, $table_posts, $table_att;

    foreach ($wall as $post) {
        if (!$wpdb->get_row("SELECT * FROM $table_posts WHERE id = $post->id")) {
            $status = $wpdb->insert($table_posts, [
                "id" => $post->id,
                "heading" => $post->heading,
                "text" => $post->text,
                "date" => $post->date,
                "hash" => $post->hash,
            ]);
            return $status;
        }

        foreach ($post->photos as $photo) {
            if (!$wpdb->get_row("SELECT * FROM $table_att WHERE url LIKE '$photo'")) {
                $wpdb->insert($table_att, [
                    "postid" => $post->id,
                    "url" => $photo,
                    "type" => 'photo',
                ]);
            }
        }
        foreach ($post->videos as $video) {
            if (!$wpdb->get_row("SELECT * FROM $table_att WHERE url LIKE '$video->preview'")) {
                $wpdb->insert($table_att, [
                    "postid" => $post->id,
                    "url" => $video->preview,
                    "type" => 'video',
                ]);
            }
        }
    }
}

function truncate_posts()
{
    global $wpdb, $table_posts;

    $result = $wpdb->query("DELETE FROM $table_posts");

    return $result;
}

function get_fresh_posts()
{
    global $wpdb, $table_posts;

    $last24h = time() - 24 * 60 * 60;

    $sql = "SELECT id, hash
            FROM $table_posts
            WHERE date >= $last24h";
    $result = $wpdb->get_results($sql, OBJECT_K);

    return $result;
}

function update_post($post)
{
    global $wpdb, $table_posts;

    $wpdb->update(
        $table_posts,
        [
            "heading" => $post->heading,
            "text" => $post->text,
            "hash" => $post->hash,
        ],
        ['id' => $post->id]
    );
}
