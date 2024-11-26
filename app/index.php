<?php
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

require '../vendor/autoload.php';
use Dotenv\Dotenv;
use Middleware\ClienteMiddleware;
use Middleware\EmpleadoMiddleware;
use Middleware\SocioMiddleware;

require "./controllers/TiendaController.php";
require "./controllers/VentaController.php";

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$app = AppFactory::create();

$app->post('/tienda/alta', \TiendaController::class . ':CargarUno');
$app->post('/tienda/consultar', \TiendaController::class . ':VerificarExiste');
$app->post('/ventas/alta', \VentaController::class . ':CargarUno');

$app->group('/ventas/consultar', function (RouteCollectorProxy $group) {
    $group->get('/productos/vendidos', \VentaController::class . ':vendidosEnUnDia');
    $group->get('/productos/entreValores', \VentaController::class . ':obtenerVentasEntreValores');
    $group->get('/productos/masVendido', \VentaController::class . ':obtenerProductoMasVendido');
    $group->get('/ventas/porUsuario', \VentaController::class . ':obtenerVentasPorUsuario');
    $group->get('/ventas/porProducto', \VentaController::class . ':obtenerVentasPorProducto');
    $group->get('/ventas/ingresos', \VentaController::class . ':obtenerIngresos');
});

$app->put('/ventas/modificar', \VentaController::class . ':ModificarUno');

$app->run();
