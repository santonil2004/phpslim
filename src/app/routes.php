<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Models\User;
use App\Application\Settings\SettingsInterface as Settings;
use App\Domain\User\UserRepository;
use Illuminate\Database\Capsule\Manager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        try {
          $users = User::query()->get()->first();
          $users->name =  uniqid();
          dump($users);
          $users->refresh();
          $users->save();
           dd($users->name);
        } catch(Throwable $t){
            dd($t->getTraceAsString());
        }

       

        
        $response->getBody()->write('Hello world!'.uniqid());
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->get('/settings', function (Settings $settings) {
        var_dump($settings);
    });
};
