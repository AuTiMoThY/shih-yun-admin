<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/test-db-connection', 'testDbConnection::index');
$routes->get('/test-db-connection2', 'testDbConnection2::index');
// 注意：因為 RewriteBase /api/ 已設定，路由定義中不需要包含 /api/ 前綴
$routes->get('/test-cors', 'TestCors::index');



// 讓 preflight OPTIONS 不落 404（CORS filter 仍會附加 headers）
$routes->options('/(:any)', 'Home::options');
$routes->post('/admins/add', 'AdminsController::addAdmin');
$routes->post('/admins/update', 'AdminsController::updateAdmin');
$routes->post('/admins/delete', 'AdminsController::deleteAdmin');
$routes->get('/admins/get', 'AdminsController::getAdmins');
$routes->post('/admins/login', 'AuthController::login');
$routes->get('/admins/me', 'AuthController::me');
$routes->post('/admins/logout', 'AuthController::logout');

// 系統架構層級相關路由
$routes->post('/structure/add', 'StructureController::add');
$routes->post('/structure/update', 'StructureController::update');
$routes->post('/structure/update-sort-order', 'StructureController::updateSortOrder');
$routes->post('/structure/delete', 'StructureController::delete');
$routes->get('/structure/get', 'StructureController::get');

// 模組相關路由
$routes->post('/module/add', 'ModuleController::add');
$routes->post('/module/update', 'ModuleController::update');
$routes->post('/module/delete', 'ModuleController::delete');
$routes->get('/module/get', 'ModuleController::get');
