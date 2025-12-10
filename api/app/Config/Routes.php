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
$routes->post('/api/admins/add', 'AdminsController::addAdmin');
$routes->post('/api/admins/update', 'AdminsController::updateAdmin');
$routes->post('/api/admins/delete', 'AdminsController::deleteAdmin');
$routes->get('/api/admins/get', 'AdminsController::getAdmins');
$routes->post('/api/admins/login', 'AuthController::login');
$routes->get('/api/admins/me', 'AuthController::me');
$routes->post('/api/admins/logout', 'AuthController::logout');

// 系統架構層級相關路由
$routes->post('/api/structure/add', 'StructureController::addLevel');
$routes->post('/api/structure/update', 'StructureController::updateLevel');
$routes->post('/api/structure/update-sort-order', 'StructureController::updateSortOrder');
$routes->post('/api/structure/delete', 'StructureController::deleteLevel');
$routes->get('/api/structure/get', 'StructureController::getLevels');


