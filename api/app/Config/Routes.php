<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/test-db-connection', 'testDbConnection::index');
$routes->get('/test-db-connection2', 'testDbConnection2::index');
$routes->get('/test-cors', 'TestCors::index');



// 讓 preflight OPTIONS 不落 404（CORS filter 仍會附加 headers）
$routes->options('/(:any)', 'Home::options');
$routes->post('/admins/add', 'AdminsController::addAdmin');
$routes->post('/admins/update', 'AdminsController::updateAdmin');
$routes->post('/admins/delete', 'AdminsController::deleteAdmin');
$routes->get('/admins/get', 'AdminsController::getAdmins');
$routes->get('/admins/get-by-id', 'AdminsController::getAdminById');
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

// 公司基本資料相關路由
$routes->get('/company-base/get', 'CompanyBaseController::get');
$routes->post('/company-base/save', 'CompanyBaseController::save');

// 關於頁面區塊設定
$routes->get('/appabout/get', 'AppaboutController::get');
$routes->post('/appabout/save', 'AppaboutController::save');
$routes->post('/upload/image', 'UploadController::image');

// 角色相關路由
$routes->get('/role/get', 'RoleController::get');
$routes->get('/role/get-by-id', 'RoleController::getById');
$routes->post('/role/add', 'RoleController::add');
$routes->post('/role/update', 'RoleController::update');
$routes->post('/role/delete', 'RoleController::delete');

// 權限相關路由
$routes->get('/permission/get', 'PermissionController::get');
$routes->get('/permission/get-by-id', 'PermissionController::getById');
$routes->post('/permission/add', 'PermissionController::add');
$routes->post('/permission/update', 'PermissionController::update');
$routes->post('/permission/delete', 'PermissionController::delete');
