<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Psr\Log\LoggerInterface;
use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Application\Settings\SettingsInterface;

require __DIR__ . '/../vendor/autoload.php';

// Define Default set.
defined('BASE_URL_PATH') || define('BASE_URL_PATH', '/');
defined('ENV_DIR_PATH') || define('ENV_DIR_PATH', __DIR__ . '/../../');
defined('SETTINGS_DIR_PATH') || define('SETTINGS_DIR_PATH', __DIR__ . '/../../');
defined('TEMPLATES_DIR_PATH') || define('TEMPLATES_DIR_PATH', __DIR__ . '/../../');
defined('ENV_IDENTIFY') || define('ENV_IDENTIFY', '');

// SESSION Name.
session_name('MAILERID');

// Set up Dotenv
$env = '.env' . ( ENV_IDENTIFY ? '.' .ENV_IDENTIFY: '' );
if ( file_exists( rtrim(ENV_DIR_PATH, '/') . '/' . $env ) ) {
  \Dotenv\Dotenv::createImmutable( rtrim(ENV_DIR_PATH, '/') . '/', $env )->load();
} else {
  die('環境設定ファイルがありません。');
}

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Should be set to true in production
if (false) {
  $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Set up repositories
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

/** @var SettingsInterface $settings */
$settings = $container->get(SettingsInterface::class);

// タイムゾーン.
date_default_timezone_set($settings->get('timeZone'));

// ベースパス.
if (BASE_URL_PATH !== '/') {
  $app->setBasePath( '/' . trim(BASE_URL_PATH, '/') );
}

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

// Error details.
$displayErrorDetails = $settings->get('displayErrorDetails');
$logError = $settings->get('logError');
$logErrorDetails = $settings->get('logErrorDetails');

// エラーハンドルにLoggerを追加.
$errorLogger = $container->get(LoggerInterface::class);

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory, $errorLogger);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Run App & Emit Response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
