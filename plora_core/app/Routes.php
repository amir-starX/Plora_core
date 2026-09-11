<?php

declare(strict_types=1);

$router->get('/', 'HomeController@index');
$router->get('/hello/{name}', 'HomeController@hello');
