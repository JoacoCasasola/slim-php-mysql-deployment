<?php

require_once __DIR__ . '/../db/AccesoDatos.php';
require_once __DIR__ . '/../models/Producto.php';

class Venta {
    public $id;
    public $codigo;
    public $emailUsuario;
    public $id_producto;
    public $titulo;
    public $tipo;
    public $formato;
    public $stock;
    public $fecha;
    public $imagen;

    public function __construct($id, $codigo, $emailUsuario, $id_producto, $titulo, $tipo, $formato, $stock, $fecha, $imagen) {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->emailUsuario = $emailUsuario;
        $this->id_producto = $id_producto;
        $this->titulo = $titulo;
        $this->tipo = $tipo;
        $this->formato = $formato;
        $this->stock = $stock;
        $this->fecha = $fecha;
        $this->imagen = $imagen;
    }

    public static function obtenerTodos()
    {
        try {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM ventas");
            $consulta->execute();

            $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
            $ventas = [];

            foreach ($resultados as $fila) {
                $venta = new Venta(
                    $fila['id'],
                    $fila['codigo'],
                    $fila['email_usuario'],
                    $fila['id_producto'],
                    $fila['titulo'],
                    $fila['tipo'],
                    $fila['formato'],
                    $fila['stock'],
                    $fila['fecha'],
                    $fila['imagen']
                );
                $ventas[] = $venta;
            }

            return $ventas;
        } catch (PDOException $e) {
            throw new Exception("Error al obtener las ventas: " . $e->getMessage());
        }
    }

    public static function obtenerPorCodigo($codigo) {
        try {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM ventas WHERE codigo = :codigo");
            $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
            $consulta->execute();
    
            $fila = $consulta->fetch(PDO::FETCH_ASSOC);
    
            if ($fila) {
                return new Venta(
                    $fila['id'],
                    $fila['codigo'],
                    $fila['email_usuario'],
                    $fila['id_producto'],
                    $fila['titulo'],
                    $fila['tipo'],
                    $fila['formato'],
                    $fila['stock'],
                    $fila['fecha'],
                    $fila['imagen'],
                );
            }
            return null;
        } catch (PDOException $e) {
            throw new Exception("Error al obtener la venta: " . $e->getMessage());
        }
    }

    public function validarEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function crearVenta()
    {
        if (!Producto::validarFormato($this->formato)) {
            throw new Exception("Formato no permitido");
        }

        if (!Producto::validarTipo($this->tipo)) {
            throw new Exception("Tipo no permitido");
        }

        if (!$this->validarEmail($this->emailUsuario)) {
            throw new Exception("Email no permitido");
        }

        if ($this->stock<1) {
            throw new Exception("El stock no es valido");
        }

        if (!Producto::obtenerUno($this->titulo, $this->tipo, $this->formato)) {
            throw new Exception("El producto no existe");
        }

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta(
            "INSERT INTO ventas (codigo, email_usuario, id_producto, titulo, tipo, formato, stock, fecha, imagen) 
            VALUES (:codigo, :email_usuario, :id_producto, :titulo, :tipo, :formato, :stock, :fecha, :imagen)"
        );

        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':email_usuario', $this->emailUsuario, PDO::PARAM_STR);
        $consulta->bindValue(':id_producto', $this->id_producto, PDO::PARAM_INT);
        $consulta->bindValue(':titulo', $this->titulo, PDO::PARAM_STR);
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':formato', $this->formato, PDO::PARAM_STR);
        $consulta->bindValue(':stock', $this->stock, PDO::PARAM_INT);
        $consulta->bindValue(':fecha', $this->fecha, PDO::PARAM_INT);
        $consulta->bindValue(':imagen', $this->imagen, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function modificarVenta($codigo, $email, $titulo, $tipo, $formato, $stock)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consultaSQL = "UPDATE ventas SET ";
        $parametros = [];

        if (!is_null($email)) {
            $consultaSQL .= "email_usuario = :email_usuario, ";
            $parametros[':email_usuario'] = $email;
        }
        if (!is_null($titulo)) {
            $consultaSQL .= "titulo = :titulo, ";
            $parametros[':titulo'] = $titulo;
        }
        if (!is_null($tipo) && Producto::validarTipo($tipo)) {
            $consultaSQL .= "tipo = :tipo, ";
            $parametros[':tipo'] = $tipo;
        }
        if (!is_null($formato) && Producto::validarFormato($formato)) {
            $consultaSQL .= "formato = :formato, ";
            $parametros[':formato'] = $formato;
        }
        if (!is_null($stock)) {
            $consultaSQL .= "stock = :stock, ";
            $parametros[':stock'] = $stock;
        }

        $consultaSQL = rtrim($consultaSQL, ', ') . " WHERE codigo = :codigo";
        $parametros[':codigo'] = $codigo;

        $consulta = $objAccesoDatos->prepararConsulta($consultaSQL);

        foreach ($parametros as $key => $value) {
            $consulta->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $consulta->execute();
    }
}