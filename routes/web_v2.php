<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'api/v2', 'namespace' => 'V2'], function ($router) {
    $router->group(['middleware' => 'auth'], function ($router) {
        $router->group(["prefix" => "user"], function ($router) {
            $router->post('dashboard', 'UserController@index');
        });
    });
});
