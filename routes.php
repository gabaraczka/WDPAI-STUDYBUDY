<?php

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($request) {

    case '/login':
    case '/login.html':
        require 'login.html';
        break;
    case '/register':
    case '/register.html':
        require 'register.html';
        break;
    case '/home':
    case '/home-page.php':
        require 'home-page.php';
        break;
    case '/generate':
    case '/generate.php':
        require 'generate.php';
        break;
    case '/study-cards':
    case '/study-cards.php':
        require 'study-cards.php';
        break;

    case '/logout':
    case '/logout.php':
        require 'logout.php';
        break;
    case '/create-folder':
    case '/create_folder.php':
        require 'create_folder.php';
        break;
    default:

    $ext = pathinfo($request, PATHINFO_EXTENSION);
        if (in_array($ext, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico'])) {
            $file = __DIR__ . $request;
            if (file_exists($file)) {
                $mimeTypes = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon',
                ];
                header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
                readfile($file);
                exit;
            }
        }
        http_response_code(404);
        echo "404 Not Found";
        break;
}