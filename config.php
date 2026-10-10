<?php
declare(strict_types=1);

/*
 * Configuración de conexión MySQL.
 * Por seguridad, evita publicar este archivo en repositorios públicos.
 */
const DB_HOST = 'mysql-andry.alwaysdata.net';
const DB_NAME = 'andry_articulosgit';
const DB_USER = 'andry';
const DB_PASS = 'clase1234';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Crear la tabla automáticamente si todavía no existe.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS articulos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            marca VARCHAR(100) NOT NULL,
            unidades INT UNSIGNED NOT NULL DEFAULT 0,
            ubicacion VARCHAR(150) NOT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_articulos_nombre (nombre),
            INDEX idx_articulos_marca (marca)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    return $pdo;
}
