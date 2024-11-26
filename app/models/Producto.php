<?php

require_once __DIR__ . '/../db/AccesoDatos.php';

class Producto {
    private const TIPOS_PERMITIDOS = ['videojuego', 'pelicula'];
    private const FORMATOS_PERMITIDOS = ['fisico', 'digital'];

    public $id;
    public $titulo;
    public $precio;
    public $tipo;
    public $anioDeSalida;
    public $formato;
    public $stock;
    public $foto;

    public function __construct($id, $titulo, $precio, $tipo, $anioDeSalida, $formato, $stock, $foto) {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->precio = $precio;
        $this->tipo = $tipo;
        $this->anioDeSalida = $anioDeSalida;
        $this->formato = $formato;
        $this->stock = $stock;
        $this->foto = $foto;
    }

    public static function obtenerTodos()
    {
        try {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos");
            $consulta->execute();

            $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
            $productos = [];

            foreach ($resultados as $fila) {
                $producto = new Producto(
                    $fila['id'],
                    $fila['titulo'],
                    $fila['precio'],
                    $fila['tipo'],
                    $fila['anioDeSalida'],
                    $fila['formato'],
                    $fila['stock'],
                    $fila['foto']
                );
                $productos[] = $producto;
            }

            return $productos;
        } catch (PDOException $e) {
            throw new Exception("Error al obtener las mesas: " . $e->getMessage());
        }
    }

    public static function obtenerUno($titulo, $tipo, $formato) {
        try {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta(
                "SELECT * FROM productos WHERE titulo = :titulo AND tipo = :tipo AND formato = :formato"
            );
            $consulta->bindValue(':titulo', $titulo, PDO::PARAM_STR);
            $consulta->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $consulta->bindValue(':formato', $formato, PDO::PARAM_STR);
            $consulta->execute();
    
            $fila = $consulta->fetch(PDO::FETCH_ASSOC);
    
            if ($fila) {
                return new Producto(
                    $fila['id'],
                    $fila['titulo'],
                    $fila['precio'],
                    $fila['tipo'],
                    $fila['anioDeSalida'],
                    $fila['formato'],
                    $fila['stock'],
                    $fila['foto']
                );
            }
            return null;
    
        } catch (PDOException $e) {
            return "Error al verificar el producto: " . $e->getMessage();
        }
    }

    public static function obtenerPorId($id) {
        try {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta(
                "SELECT * FROM productos WHERE id = :id"
            );
            $consulta->bindValue(':id', $id, PDO::PARAM_INT);
            $consulta->execute();
    
            $fila = $consulta->fetch(PDO::FETCH_ASSOC);
    
            if ($fila) {
                return new Producto(
                    $fila['id'],
                    $fila['titulo'],
                    $fila['precio'],
                    $fila['tipo'],
                    $fila['anioDeSalida'],
                    $fila['formato'],
                    $fila['stock'],
                    $fila['foto']
                );
            }
            return null;
    
        } catch (PDOException $e) {
            return "Error al verificar el producto: " . $e->getMessage();
        }
    }

    public function reducirStock($cantidad) {
        if ($this->stock >= $cantidad) {
            $this->stock -= $cantidad;
    
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta(
                "UPDATE productos SET stock = :stock WHERE id = :id"
            );
            $consulta->bindValue(':stock', $this->stock, PDO::PARAM_INT);
            $consulta->bindValue(':id', $this->id, PDO::PARAM_STR);
            $consulta->execute();
    
            return true;
        }
        return false;
    }

    public static function validarTipo($tipo) {
        return in_array($tipo, self::TIPOS_PERMITIDOS);
    }

    public static function validarFormato($formato) {
        return in_array($formato, self::FORMATOS_PERMITIDOS);
    }

    public function crearProducto() {
        if (!self::validarTipo($this->tipo)) {
            throw new Exception("Tipo no permitido");
        }
        if (!self::validarFormato($this->formato)) {
            throw new Exception("Formato no permitido");
        }

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "INSERT INTO productos (titulo, precio, tipo, anioDeSalida, formato, stock, foto) 
            VALUES (:titulo, :precio, :tipo, :anioDeSalida, :formato, :stock, :foto)"
        );

        $consulta->bindValue(':titulo', $this->titulo, PDO::PARAM_STR);
        $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':anioDeSalida', $this->anioDeSalida, PDO::PARAM_STR);
        $consulta->bindValue(':formato', $this->formato, PDO::PARAM_STR);
        $consulta->bindValue(':stock', $this->stock, PDO::PARAM_INT);
        $consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public function crearOActualizarProducto() {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consultaExistente = $objAccesoDatos->prepararConsulta(
            "SELECT id, stock, precio FROM productos WHERE titulo = :titulo AND tipo = :tipo"
        );
        $consultaExistente->bindValue(':titulo', $this->titulo, PDO::PARAM_STR);
        $consultaExistente->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consultaExistente->execute();

        $productoExistente = $consultaExistente->fetch(PDO::FETCH_ASSOC);

        if ($productoExistente) {
            $nuevoStock = $productoExistente['stock'] + $this->stock;
            $consultaActualizar = $objAccesoDatos->prepararConsulta(
                "UPDATE productos 
                 SET precio = :precio, stock = :stock 
                 WHERE id = :id"
            );
            $consultaActualizar->bindValue(':precio', $this->precio, PDO::PARAM_INT);
            $consultaActualizar->bindValue(':stock', $nuevoStock, PDO::PARAM_INT);
            $consultaActualizar->bindValue(':id', $productoExistente['id'], PDO::PARAM_INT);
            $consultaActualizar->execute();

            return "Producto actualizado correctamente";
        } else {
            $consultaInsertar = $objAccesoDatos->prepararConsulta(
                "INSERT INTO productos (titulo, precio, tipo, anioDeSalida, formato, stock, foto) 
                 VALUES (:titulo, :precio, :tipo, :anioDeSalida, :formato, :stock, :foto)"
            );

            $consultaInsertar->bindValue(':titulo', $this->titulo, PDO::PARAM_STR);
            $consultaInsertar->bindValue(':precio', $this->precio, PDO::PARAM_INT);
            $consultaInsertar->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
            $consultaInsertar->bindValue(':anioDeSalida', $this->anioDeSalida, PDO::PARAM_STR);
            $consultaInsertar->bindValue(':formato', $this->formato, PDO::PARAM_STR);
            $consultaInsertar->bindValue(':stock', $this->stock, PDO::PARAM_INT);
            $consultaInsertar->bindValue(':foto', $this->foto, PDO::PARAM_STR);
            $consultaInsertar->execute();

            return "Producto creado con exito";
        }
    }

    public static function verificarProducto($titulo, $tipo, $formato) {
        try {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta(
                "SELECT COUNT(*) FROM productos WHERE titulo = :titulo AND tipo = :tipo AND formato = :formato"
            );
            $consulta->bindValue(':titulo', $titulo, PDO::PARAM_STR);
            $consulta->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $consulta->bindValue(':formato', $formato, PDO::PARAM_STR);
            $consulta->execute();
    
            if ($consulta->fetchColumn() > 0) {
                return "Existe";
            }
    
            $consulta = $objAccesoDatos->prepararConsulta(
                "SELECT COUNT(*) FROM productos WHERE tipo = :tipo"
            );
            $consulta->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $consulta->execute();
    
            if ($consulta->fetchColumn() == 0) {
                return "No hay productos del tipo $tipo";
            }
    
            $consulta = $objAccesoDatos->prepararConsulta(
                "SELECT COUNT(*) FROM productos WHERE titulo = :titulo"
            );
            $consulta->bindValue(':titulo', $titulo, PDO::PARAM_STR);
            $consulta->execute();
    
            if ($consulta->fetchColumn() == 0) {
                return "No hay productos de la marca $titulo";
            }
            return "No existe el producto";
    
        } catch (PDOException $e) {
            return "Error al verificar el producto: " . $e->getMessage();
        }
    }
    
    
}
