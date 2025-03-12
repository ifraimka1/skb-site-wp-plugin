<?php

function create_posts_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'skbkit_vk_posts';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id BIGINT(20) NOT NULL,
                heading VARCHAR(255) NOT NULL,
                text TEXT NOT NULL,
                date VARCHAR(10) NOT NULL,
                views BIGINT(20) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

        // Используем dbDelta для создания таблицы
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

function create_attachments_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'skbkit_vk_posts_attachments';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

        $charset_collate = $wpdb->get_charset_collate();
        $reference = $wpdb->prefix . "skbkit_vk_posts(id)";

        $sql = "CREATE TABLE $table_name (
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
    global $wpdb;

    $table_posts = $wpdb->prefix . 'skbkit_vk_posts';
    $table_att = $wpdb->prefix . 'skbkit_vk_posts_attachments';

    foreach ($wall as $post) {
        if (!$wpdb->get_row("SELECT * FROM $table_posts WHERE id = $post->id")) {
            $wpdb->insert($table_posts, [
                "id" => $post->id,
                "heading" => $post->heading,
                "text" => $post->text,
                "date" => $post->date,
                "views" => $post->views,
            ]);
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
    global $wpdb;

    $table_posts = $wpdb->prefix . 'skbkit_vk_posts';
    $result = $wpdb->query("DELETE FROM $table_posts");

    return $result;
}
