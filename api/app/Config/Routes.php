<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/test-db-connection', 'testDbConnection::index');
$routes->get('/test-db-connection2', 'testDbConnection2::index');
$routes->get('/api/test-cors', 'TestCors::index');



// 讓 preflight OPTIONS 不落 404（CORS filter 仍會附加 headers）
$routes->options('/(:any)', 'Home::options');
$routes->post('/api/admins/add', 'Admins::addAdmin');
$routes->get('/api/admins/get', 'Admins::getAdmins');


