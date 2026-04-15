<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('stock', 'StockController::index');
$routes->get('stock/get_live_data', 'StockController::get_live_data');