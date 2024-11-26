<?php

require "./models/Producto.php";

class TiendaController {
    public function CargarUno($request, $response, $args) {
        $parametros = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        $titulo = $parametros['titulo'] ?? null;
        $precio = $parametros['precio'] ?? null;
        $tipo = $parametros['tipo'] ?? null;
        $anio = $parametros['año'] ?? null;
        $formato = $parametros['formato'] ?? null;
        $stock = $parametros['stock'] ?? null;
        $foto = $uploadedFiles['foto'] ?? null;

        if (is_null($titulo) || is_null($precio) || is_null($tipo) || is_null($anio) || is_null($formato) || is_null($stock) || !$foto instanceof \Psr\Http\Message\UploadedFileInterface || $foto->getError() !== UPLOAD_ERR_OK) {
            $msj = json_encode(["mensaje" => "Faltan parametros"]);
            $response->getBody()->write($msj);
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $directorioFotos = "./ImagenesDeProductos/2024";
        if (!is_dir($directorioFotos)) {
            mkdir($directorioFotos, 0755, true);
        }

        $extension = pathinfo($foto->getClientFilename(), PATHINFO_EXTENSION);
        $nombreFoto = uniqid("{$titulo}_{$anio}_{$formato}") . ".{$extension}";
        $rutaFoto = "$directorioFotos/$nombreFoto";

        try {
            $foto->moveTo($rutaFoto);
            $rutaCompleta = realpath($rutaFoto);

            $producto = new Producto(null, $titulo, $precio, $tipo, $anio, $formato, $stock, $rutaCompleta);
            $resultado = $producto->crearOActualizarProducto();

            $msj = json_encode(["mensaje" => $resultado]);
            $response->getBody()->write($msj);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (Exception $e) {
            $msj = json_encode(["error" => $e->getMessage()]);
            $response->getBody()->write($msj);
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    public function VerificarExiste($request, $response, $args){
        $parametros = $request->getParsedBody();

        $titulo = $parametros['titulo'] ?? null;
        $tipo = $parametros['tipo'] ?? null;
        $formato = $parametros['formato'] ?? null;

        if (is_null($titulo) || is_null($tipo) || is_null($formato)) {
            $msj = json_encode(["mensaje" => "Faltan parametros"]);
            $response->getBody()->write($msj);
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $resultado = Producto::verificarProducto($titulo, $tipo, $formato);
            $msj = json_encode(["mensaje" => $resultado]);
            $response->getBody()->write($msj);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (Exception $e) {
            $msj = json_encode(["error" => $e->getMessage()]);
            $response->getBody()->write($msj);
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}


