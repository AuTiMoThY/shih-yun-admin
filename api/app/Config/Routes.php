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

// 上傳圖片相關路由
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

// 公司基本資料相關路由
$routes->get('/company-base/get', 'CompanyBaseController::get');
$routes->post('/company-base/save', 'CompanyBaseController::save');

// 關於我們相關路由
$routes->get('/app-about/get', 'AppAboutController::get');
$routes->post('/app-about/save', 'AppAboutController::save');

// 最新消息相關路由
$routes->get('/app-news/get', 'AppNewsController::get');
$routes->get('/app-news/get-by-id', 'AppNewsController::getById');
$routes->post('/app-news/add', 'AppNewsController::add');
$routes->post('/app-news/update', 'AppNewsController::update');
$routes->post('/app-news/delete', 'AppNewsController::delete');

// 聯絡表單相關路由
$routes->post('/app-contact/submit', 'AppContactController::submit'); // 前台提交表單
$routes->get('/app-contact/get', 'AppContactController::get'); // 後台取得列表
$routes->get('/app-contact/get-by-id', 'AppContactController::getById'); // 後台取得單筆
$routes->post('/app-contact/update-status', 'AppContactController::updateStatus'); // 後台更新狀態
$routes->post('/app-contact/update-reply', 'AppContactController::updateReply'); // 後台更新回信
$routes->post('/app-contact/send-email', 'AppContactController::sendEmail'); // 後台發送郵件
$routes->post('/app-contact/delete', 'AppContactController::delete'); // 後台刪除

// 建案相關路由
$routes->get('/app-case/get', 'AppCaseController::get');
$routes->get('/app-case/get-by-id', 'AppCaseController::getById');
$routes->post('/app-case/add', 'AppCaseController::add');
$routes->post('/app-case/update', 'AppCaseController::update');
$routes->post('/app-case/delete', 'AppCaseController::delete');

// 工程進度相關路由
$routes->get('/app-progress/get', 'AppProgressController::get');
$routes->get('/app-progress/get-by-id', 'AppProgressController::getById');
$routes->post('/app-progress/add', 'AppProgressController::add');
$routes->post('/app-progress/update', 'AppProgressController::update');
$routes->post('/app-progress/delete', 'AppProgressController::delete');