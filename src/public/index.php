<?php

declare(strict_types=1);

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\VarDumper\VarDumper;

require __DIR__ . '/../vendor/autoload.php';


function dd(mixed ...$vars): never
{
	if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && !headers_sent()) {
		header('HTTP/1.1 500 Internal Server Error');
	}

	$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
	$file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : null;
	$line = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : null;

	echo  "<div style='background-color: #212529; color: #df1c1c; margin-bottom:-12px; padding:8px;'><b>Trace: </b><em>{$file} on line {$line}</em></div>";

	if (array_key_exists(0, $vars) && 1 === count($vars)) {
		VarDumper::dump($vars[0]);
	} else {
		foreach ($vars as $k => $v) {
			VarDumper::dump($v, is_int($k) ? 1 + $k : $k);
		}
	}

	exit(1);
}

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

if (false) { // Should be set to true in production
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

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

/** @var SettingsInterface $settings */
$settings = $container->get(SettingsInterface::class);

$displayErrorDetails = $settings->get('displayErrorDetails');
$logError = $settings->get('logError');
$logErrorDetails = $settings->get('logErrorDetails');

$capsule = new Capsule();
$capsule->addConnection($settings->get('db'));
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container->set('db', $capsule);

/*
$t = $capsule->getConnection()->getPdo();

dd($t);
*/

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Run App & Emit Response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
