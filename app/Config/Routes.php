<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// 1. Pengalihan Utama
$routes->addRedirect('/', 'stock');

// 2. Dashboard & Public Data
$routes->group('stock', function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('get_live_data', 'DashboardController::get_live_data');
    $routes->get('history/(:any)/(:any)', 'ChartController::get_history/$1/$2');
    $routes->get('detail/(:segment)', 'StockDetailController::detail/$1');
});

$routes->group('stock', ['filter' => 'auth'], function ($routes) {
    // Sistem Token
    $routes->get('token', 'TokenController::index');
    $routes->get('token/buy/(:num)', 'TokenController::buy/$1');
    $routes->get('token/history', 'TokenController::history');
});

// 3. Autentikasi (Login & Register)
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('register', 'AuthController::register');
$routes->post('register', 'AuthController::attemptRegister');
$routes->get('logout', 'AuthController::logout');

// 4. Fitur Berbayar & AI (Gunakan Filter Auth agar harus login)
// Catatan: Pastikan kamu sudah membuat Filter 'auth' di app/Config/Filters.php
$routes->group('api', function ($routes) {
    // Analisis AI
    $routes->get('analyze/(:any)', 'AIAnalysisController::analyze/$1');
});