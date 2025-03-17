<?php

if (!defined('ABSPATH')) {
    exit;
}

$config_path = plugin_dir_path(__FILE__) . 'config.php';
if (file_exists($config_path)) {
	$config = require $config_path;
    $smartcaptcha_server_key = $config['sc_server_key'];
	$max_file_size = $config['max_file_size'];
} else {
    wp_die('Файл config.php отсутствует.');
}
define('SMARTCAPTCHA_SERVER_KEY', $smartcaptcha_server_key);
define('MAX_FILE_SIZE', $max_file_size);

function is_valid_size($size) {
	if ($size > MAX_FILE_SIZE) {
		return false;
	}

	return true;
}

function is_valid_ext($name) {	
	$ext = pathinfo($name, PATHINFO_EXTENSION);
	switch ($ext) {
		case 'doc':
		case 'docx':
		case 'pdf':
		case 'txt':
		case 'pptx':
		case 'ppt':
			return true;
		default:
		  	return false;
	}
}

function check_captcha($token) {
    $ch = curl_init("https://smartcaptcha.yandexcloud.net/validate");
    $args = [
        "secret" => SMARTCAPTCHA_SERVER_KEY,
        "token" => $token,
    ];
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_setopt($ch, CURLOPT_POST, true);    
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch); 
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) {
        echo "Allow access due to an error: code=$httpcode; message=$server_output\n";
        return true;
    }
 
    $resp = json_decode($server_output);
    return $resp->status === "ok";
}

function send_contact_form(WP_REST_Request $request) {
	$captcha = sanitize_text_field($request->get_param('captcha'));
	if (!check_captcha($captcha)) {
        return new WP_REST_Response('Ошибка капчи. Пожалуйста, попробуйте снова.', 403);
    }
	
	$name = sanitize_text_field($request->get_param('name'));
    $email = sanitize_email($request->get_param('email'));
    $message = sanitize_textarea_field($request->get_param('message'));
	$file = $request->get_file_params()['file'];

	// Проверка, что файл был загружен
	if ($file && $file['error'] === UPLOAD_ERR_OK) {
		$tmp_file_path = $file['tmp_name'];
		$original_file_name = $file['name'];

		if (!is_valid_ext($original_file_name)) {
			return new WP_REST_Response('Ошибка: неверный формат файла.', 403);
		}
		if (!is_valid_size($file['size'])) {
			return new WP_REST_Response('Ошибка: файл слишком большой.', 403);
		}

		$new_file_path = sys_get_temp_dir() . '/' . $original_file_name;
		move_uploaded_file($tmp_file_path, $new_file_path);
		$attachments = [$new_file_path];
	} else {
        $attachments = [];
    }
  
	// Отправка письма
	$to = 'mr.ifraim@yandex.ru';
	$subject = 'Сообщение с контактной формы';
	$body = "Имя: $name".PHP_EOL."Email: $email".PHP_EOL."Сообщение:".PHP_EOL."$message";  
	$headers = ['Content-Type: text/plain; charset=UTF-8'];
  
	$sent = wp_mail($to, $subject, $body, $headers, $attachments);

	// Удаляем временный файл после отправки письма
    if ($sent && !empty($attachments)) {
        unlink($new_file_path);
    }
  
	if ($sent) {
	  	return new WP_REST_Response('Сообщение отправлено.', 200);
	} else {
	  	return new WP_REST_Response('Ошибка отправки сообщения.', 500);
	}
}

function skbkit_register_contactform_route() {
	register_rest_route(
		'skbkit/v1',
		'/sendcontactform',
		array(
			'methods' => 'POST',
			'callback' => 'send_contact_form',
			'permission_callback' => function () {
				return true;
			}
		)
	);
}

//add_action('rest_api_init', 'skbkit_register_contactform_route');