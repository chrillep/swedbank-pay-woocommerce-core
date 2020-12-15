<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    spl_autoload_register(function ($className) {
        $autoLoadPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        $classFile = $autoLoadPath . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
            return true;
        }
        return false;
    });
}

require_once __DIR__ . '/Adapter.php';
require_once __DIR__ . '/Gateway.php';
require_once __DIR__ . '/MobilePayGateway.php';
require_once __DIR__ . '/TestCase.php';

// phpcs:disable
if (getenv('ACCESS_TOKEN') && getenv('PAYEE_ID')) {
    define('ACCESS_TOKEN', getenv('ACCESS_TOKEN'));
    define('PAYEE_ID', getenv('PAYEE_ID'));
    define('VERSION', getenv('VERSION'));

    if (getenv('ACCESS_TOKEN_MOBILEPAY') && getenv('PAYEE_ID_MOBILEPAY')) {
        define('ACCESS_TOKEN_MOBILEPAY', getenv('ACCESS_TOKEN_MOBILEPAY'));
        define('PAYEE_ID_MOBILEPAY', getenv('PAYEE_ID_MOBILEPAY'));
    }
    // phpcs:enable
} else {
    // Load config
    if (file_exists(__DIR__ . '/config.local.ini')) {
        $config = parse_ini_file(__DIR__ . '/config.local.ini', true);
    } else {
        $config = parse_ini_file(__DIR__ . '/config.ini', true);
    }

    define('ACCESS_TOKEN', $config['access_token']);
    define('PAYEE_ID', $config['payee_id']);
    define('ACCESS_TOKEN_MOBILEPAY', $config['access_token_mobilepay']);
    define('PAYEE_ID_MOBILEPAY', $config['payee_id_mobilepay']);
}
