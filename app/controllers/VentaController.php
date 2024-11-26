<?php

require_once "./models/Venta.php";
require_once "./models/Producto.php";

class VentaController{
    public function CargarUno($request, $response, $args) {
        $parametros = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
    
        $email = $parametros['email'] ?? null;
        $titulo = $parametros['titulo'] ?? null;
        $tipo = $parametros['tipo'] ?? null;
        $formato = $parametros['formato'] ?? null;
        $stock = $parametros['stock'] ?? null;
        $imagen = $uploadedFiles['imagen'] ?? null;
    
        if (is_null($email) || is_null($titulo) || is_null($tipo) || is_null($formato) || is_null($stock) || is_null($imagen)) {
            $msj = json_encode(["mensaje" => "Faltan parametros"]);
            $response->getBody()->write($msj);
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        $codigoAleatorio = substr(uniqid(), -5);
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $fecha = date('Y-m-d_H-i-s');
    
        $extension = pathinfo($imagen->getClientFilename(), PATHINFO_EXTENSION);
        
        $directorioFotos = './ImagenesDeVentas/2024/';
        if (!file_exists($directorioFotos)) {
            mkdir($directorioFotos, 0777, true);
        }
    
        $nombreUsuario = strtok($email, '@');
        $nombreArchivo = "{$titulo}_{$tipo}_{$formato}_{$nombreUsuario}_{$fecha}.{$extension}";
        $rutaFoto = $directorioFotos . $nombreArchivo;
        
        
        
        try {
            if (!is_writable(dirname($rutaFoto))) {
                throw new Exception("El directorio no tiene permisos de escritura");
            }
            $imagen->moveTo($rutaFoto);
            $producto = Producto::obtenerUno($titulo, $tipo, $formato);

            $venta = new Venta(null, $codigoAleatorio, $email, $producto->id, $titulo, $tipo, $formato, $stock, $fecha, $nombreArchivo);
            if(!$producto->reducirStock($stock)){
                $msj = json_encode(["mensaje" => "Stock insuficiente"]);
                $response->getBody()->write($msj);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            }
            
            $venta->crearVenta();
            $msj = json_encode(["mensaje" => "Venta creada con exito"]);
            $response->getBody()->write($msj);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    
        } catch (Exception $e) {
            if (file_exists($rutaFoto)) {
                unlink($rutaFoto);
            }
            
            $msj = json_encode(["error" => $e->getMessage()]);
            $response->getBody()->write($msj);
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    public function VendidosEnUnDia($request, $response, $args) {
        $params = $request->getQueryParams();
        $dia = $params['dia'] ?? null;
    
        if (is_null($dia)) {
            $response->getBody()->write(json_encode(["error" => "Fecha no proporcionada"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $ventas = Venta::obtenerTodos();
            $cantidadVentas = 0;
    
            foreach ($ventas as $venta) {
                $dia = (new DateTime($dia))->format('Y-m-d');
                if (substr($venta->fecha, 0, 10) == $dia) {
                    $cantidadVentas++;
                }
            }
    
            $mensaje = $cantidadVentas > 0 
                ? ["mensaje" => "Cantidad de ventas el $dia: $cantidadVentas"] 
                : ["mensaje" => "No hubo ventas ese dia"];
    
            $response->getBody()->write(json_encode($mensaje));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["error" => "Error al obtener las ventas: " . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function obtenerVentasPorUsuario($request, $response, $args){
        $params = $request->getQueryParams();
        $email = $params['email'] ?? null;
        
        if (is_null($email)) {
            $response->getBody()->write(json_encode(["error" => "Email no proporcionado"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $ventas = Venta::obtenerTodos();
            $cantidadVentas = 0;
    
            foreach ($ventas as $venta) {
                if ($venta->emailUsuario == $email) {
                    $cantidadVentas++;
                }
            }
    
            $mensaje = $cantidadVentas > 0 
                ? ["mensaje" => "Cantidad de ventas del usuario '$email': $cantidadVentas"] 
                : ["mensaje" => "No hubo ventas de ese usuario"];
    
            $response->getBody()->write(json_encode($mensaje));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["error" => "Error al obtener las ventas: " . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function obtenerVentasPorProducto($request, $response, $args){
        $params = $request->getQueryParams();
        $tipo = $params['tipo'] ?? null;
        
        if (is_null($tipo)) {
            $response->getBody()->write(json_encode(["error" => "Tipo de producto no proporcionado"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $ventas = Venta::obtenerTodos();
            $cantidadVentas = 0;
    
            foreach ($ventas as $venta) {
                if ($venta->tipo == $tipo) {
                    $cantidadVentas++;
                }
            }
    
            $mensaje = $cantidadVentas > 0 
                ? ["mensaje" => "Cantidad de ventas de tipo '$tipo': $cantidadVentas"] 
                : ["mensaje" => "No hubo ventas del tipo '$tipo'"];
    
            $response->getBody()->write(json_encode($mensaje));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["error" => "Error al obtener las ventas: " . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function obtenerVentasEntreValores($request, $response, $args) {
        $params = $request->getQueryParams();
        $precioMin = $params['precio_min'] ?? null;
        $precioMax = $params['precio_max'] ?? null;
        
        if (is_null($precioMin) || is_null($precioMax)) {
            $response->getBody()->write(json_encode(["error" => "Rango de precio no proporcionado"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
        if (!is_numeric($precioMin) || !is_numeric($precioMax)) {
            $response->getBody()->write(json_encode(["error" => "Los valores de precio deben ser numericos"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
        try {
            $productos = Producto::obtenerTodos();
            $productosEnRango = [];
    
            foreach ($productos as $producto) {
                if ($producto->precio >= $precioMin && $producto->precio <= $precioMax) {
                    $productosEnRango[] = [
                        'titulo' => $producto->titulo,
                        'anioDeSalida' => $producto->anioDeSalida,
                        'tipo' => $producto->tipo,
                        'formato' => $producto->formato,
                        'precio' => $producto->precio
                    ];
                }
            }
    
            $mensaje = count($productosEnRango) > 0 
                ? ["productos" => $productosEnRango] 
                : ["mensaje" => "No hay productos entre $precioMin y $precioMax pesos"];
    
            $response->getBody()->write(json_encode($mensaje));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["error" => "Error al obtener los productos: " . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function obtenerProductosPorAnioDeSalida($request, $response, $args) {
        try {
            $productos = Producto::obtenerTodos();
            usort($productos, function($a, $b) {
                return $a->anioDeSalida <=> $b->anioDeSalida;
            });
    
            $productosOrdenados = array_map(function($producto) {
                return [
                    'titulo' => $producto->titulo,
                    'anioDeSalida' => $producto->anioDeSalida,
                    'tipo' => $producto->tipo,
                    'formato' => $producto->formato
                ];
            }, $productos);
    
            $response->getBody()->write(json_encode([
                "productos" => $productosOrdenados
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["error" => "Error al obtener los productos: " . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function obtenerProductoMasVendido($request, $response, $args) {
        try {
            $ventas = Venta::obtenerTodos();
            $ventasPorProducto = [];

            foreach ($ventas as $venta) {
                $productoId = $venta->id_producto;
                $cantidadVendida = $venta->stock;

                if (isset($ventasPorProducto[$productoId])) {
                    $ventasPorProducto[$productoId] += $cantidadVendida;
                } else {
                    $ventasPorProducto[$productoId] = $cantidadVendida;
                }
            }
            $productoMasVendidoId = array_search(max($ventasPorProducto), $ventasPorProducto);
            $productoMasVendidoCantidad = $ventasPorProducto[$productoMasVendidoId];

            $producto = Producto::obtenerPorId($productoMasVendidoId);
    
            $response->getBody()->write(json_encode("El producto mas vendido es: $producto->titulo-$producto->tipo-$producto->formato  con " . $productoMasVendidoCantidad . " ventas"));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(["error" => "Error al obtener los productos: " . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function ModificarUno($request, $response, $args)
    {
        $body = (string)$request->getBody();
        $parametros = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $msj = json_encode(["mensaje" => "Error al decodificar."]);
            $response->getBody()->write($msj);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $codigo = $parametros['codigo'] ?? null;
        $email = $parametros['email'] ?? null;
        $titulo = $parametros['titulo'] ?? null;
        $tipo = $parametros['tipo'] ?? null;
        $formato = $parametros['formato'] ?? null;
        $stock = $parametros['stock'] ?? null;

        try {
            if(!Venta::obtenerPorCodigo($codigo)){
                $msj = json_encode(["mensaje" => "El codigo de la venta es incorrecto"]);
                $response->getBody()->write($msj);
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            Venta::modificarVenta($codigo, $email, $titulo, $tipo, $formato, $stock);

            $msj = json_encode(["mensaje" => "Venta modificada con exito"]);
            $response->getBody()->write($msj);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $msj = json_encode(["mensaje" => $e->getMessage()]);
            $response->getBody()->write($msj);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
}