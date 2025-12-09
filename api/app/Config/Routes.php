<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/test-db-connection', 'testDbConnection::index');
$routes->get('/test-db-connection2', 'testDbConnection2::index');

