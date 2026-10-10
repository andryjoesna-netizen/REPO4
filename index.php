<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$error = '';
$mensaje = '';
$editar = null;

function e(?string $valor): string {
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string)($_POST['csrf_token'] ?? '');
        if (!hash_equals($csrf, $token)) {
            throw new RuntimeException('La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.');
        }

        $accion = (string)($_POST['accion'] ?? '');
        if ($accion === 'guardar') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $marca = trim((string)($_POST['marca'] ?? ''));
            $unidadesRaw = trim((string)($_POST['unidades'] ?? ''));
            $ubicacion = trim((string)($_POST['ubicacion'] ?? ''));

            if ($nombre === '' || $marca === '' || $ubicacion === '' || $unidadesRaw === '') {
                throw new RuntimeException('Completa todos los campos.');
            }
            if (mb_strlen($nombre) > 150 || mb_strlen($marca) > 100 || mb_strlen($ubicacion) > 150) {
                throw new RuntimeException('Uno de los campos supera la longitud permitida.');
            }
            if (!ctype_digit($unidadesRaw) || (int)$unidadesRaw > 4294967295) {
                throw new RuntimeException('Las unidades deben ser un número entero igual o mayor que cero.');
            }
            $unidades = (int)$unidadesRaw;

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE articulos SET nombre=?, marca=?, unidades=?, ubicacion=? WHERE id=?');
                $stmt->execute([$nombre, $marca, $unidades, $ubicacion, $id]);
                $_SESSION['flash'] = 'Artículo actualizado correctamente.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO articulos (nombre, marca, unidades, ubicacion) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nombre, $marca, $unidades, $ubicacion]);
                $_SESSION['flash'] = 'Artículo registrado correctamente.';
            }
            header('Location: index.php');
            exit;
        }

        if ($accion === 'eliminar') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                throw new RuntimeException('El artículo seleccionado no es válido.');
            }
            $stmt = $pdo->prepare('DELETE FROM articulos WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['flash'] = $stmt->rowCount() ? 'Artículo eliminado.' : 'El artículo ya no existía.';
            header('Location: index.php');
            exit;
        }
    }

    if (isset($_GET['editar'])) {
        $idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
        if ($idEditar && $idEditar > 0) {
            $stmt = $pdo->prepare('SELECT * FROM articulos WHERE id = ?');
            $stmt->execute([$idEditar]);
            $editar = $stmt->fetch() ?: null;
        }
    }

    $busqueda = trim((string)($_GET['buscar'] ?? ''));
    if ($busqueda !== '') {
        $stmt = $pdo->prepare('SELECT * FROM articulos WHERE nombre LIKE ? OR marca LIKE ? OR ubicacion LIKE ? ORDER BY id DESC');
        $like = '%' . $busqueda . '%';
        $stmt->execute([$like, $like, $like]);
        $articulos = $stmt->fetchAll();
    } else {
        $articulos = $pdo->query('SELECT * FROM articulos ORDER BY id DESC')->fetchAll();
    }
    $totalArticulos = (int)$pdo->query('SELECT COUNT(*) FROM articulos')->fetchColumn();
    $totalUnidades = (int)$pdo->query('SELECT COALESCE(SUM(unidades), 0) FROM articulos')->fetchColumn();
} catch (Throwable $ex) {
    $error = $ex instanceof PDOException
        ? 'No se pudo conectar con la base de datos. Verifica las credenciales y que la base de datos exista en el servidor.'
        : $ex->getMessage();
    $articulos = $articulos ?? [];
    $busqueda = $busqueda ?? '';
    $totalArticulos = $totalArticulos ?? 0;
    $totalUnidades = $totalUnidades ?? 0;
}

if (!empty($_SESSION['flash'])) {
    $mensaje = (string)$_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestor de artículos</title>
    <style>
        :root{--navy:#15263f;--blue:#2864dc;--bg:#f3f6fb;--text:#1f2937;--muted:#64748b;--line:#e2e8f0}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Arial,Helvetica,sans-serif;color:var(--text)}
        header{background:linear-gradient(120deg,#15263f,#244d83);color:white;padding:28px 20px}
        .wrap{max-width:1150px;margin:auto;padding:0 18px}.head{display:flex;gap:12px;align-items:center}
        .logo{font-size:26px;background:#ffffff20;border-radius:12px;padding:12px}.head h1{margin:0;font-size:25px}.head p{margin:6px 0 0;color:#dbeafe}
        main{padding:24px 0 45px}.stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:20px}
        .stat,.panel{background:white;border:1px solid var(--line);border-radius:14px;box-shadow:0 5px 20px #17255408}
        .stat{padding:18px 20px}.stat span{color:var(--muted);font-size:13px}.stat strong{display:block;font-size:27px;margin-top:6px}
        .layout{display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start}.panel{padding:21px}
        h2{font-size:18px;margin:0 0 18px}.field{margin-bottom:14px}label{display:block;font-size:13px;font-weight:700;margin-bottom:7px}
        input{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:white}
        input:focus{outline:2px solid #bfdbfe;border-color:var(--blue)}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:0;border-radius:8px;padding:10px 13px;font-weight:700;font-size:13px;text-decoration:none;cursor:pointer}
        .primary{background:var(--blue);color:white}.secondary{background:#eaf0fa;color:#1e3a8a}.danger{background:#fee2e2;color:#991b1b}
        .actions{display:flex;gap:7px;flex-wrap:wrap}.form-actions{display:flex;gap:8px}.form-actions .btn{flex:1}
        .notice{padding:12px 14px;border-radius:9px;margin-bottom:16px;font-size:14px}.ok{background:#dcfce7;color:#166534}.err{background:#fee2e2;color:#991b1b}
        .tabletop{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:15px}.tabletop h2{margin:0}
        .search{display:flex;gap:7px;max-width:390px;width:100%}.search input{min-width:0}
        .tablewrap{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:13px}th{text-align:left;background:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.04em}
        th,td{padding:13px 11px;border-bottom:1px solid var(--line);vertical-align:middle}tbody tr:hover{background:#fafcff}.pill{display:inline-block;padding:4px 8px;background:#eaf0fa;color:#1e40af;border-radius:20px;font-weight:700}
        .empty{text-align:center;padding:35px 10px;color:var(--muted)}small{color:var(--muted)}
        @media(max-width:850px){.layout{grid-template-columns:1fr}.stats{grid-template-columns:1fr 1fr}}
        @media(max-width:520px){.stats{grid-template-columns:1fr 1fr}.stat strong{font-size:22px}.tabletop{align-items:stretch;flex-direction:column}.search{max-width:none}.panel{padding:16px}.head h1{font-size:21px}}
    </style>
</head>
<body>
<header>
    <div class="wrap head">
        <div class="logo" aria-hidden="true">▦</div>
        <div><h1>Gestor de artículos</h1><p>Control sencillo de inventario y ubicaciones</p></div>
    </div>
</header>
<main class="wrap">
    <?php if ($mensaje): ?><div class="notice ok"><?= e($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="notice err"><?= e($error) ?></div><?php endif; ?>

    <section class="stats">
        <div class="stat"><span>Artículos registrados</span><strong><?= number_format($totalArticulos, 0, ',', '.') ?></strong></div>
        <div class="stat"><span>Total de unidades</span><strong><?= number_format($totalUnidades, 0, ',', '.') ?></strong></div>
    </section>

    <div class="layout">
        <section class="panel">
            <h2><?= $editar ? 'Editar artículo' : 'Registrar artículo' ?></h2>
            <form method="post" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" value="<?= e((string)($editar['id'] ?? '')) ?>">
                <div class="field"><label for="nombre">Nombre del artículo</label><input id="nombre" name="nombre" maxlength="150" required value="<?= e($editar['nombre'] ?? '') ?>" placeholder="Ej. Teclado inalámbrico"></div>
                <div class="field"><label for="marca">Marca</label><input id="marca" name="marca" maxlength="100" required value="<?= e($editar['marca'] ?? '') ?>" placeholder="Ej. Logitech"></div>
                <div class="field"><label for="unidades">Unidades</label><input id="unidades" name="unidades" type="number" min="0" step="1" required value="<?= e(isset($editar['unidades']) ? (string)$editar['unidades'] : '0') ?>"></div>
                <div class="field"><label for="ubicacion">Ubicación</label><input id="ubicacion" name="ubicacion" maxlength="150" required value="<?= e($editar['ubicacion'] ?? '') ?>" placeholder="Ej. Estante A - Nivel 2"></div>
                <div class="form-actions">
                    <button class="btn primary" type="submit"><?= $editar ? 'Guardar cambios' : '＋ Registrar artículo' ?></button>
                    <?php if ($editar): ?><a class="btn secondary" href="index.php">Cancelar</a><?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="tabletop">
                <h2>Inventario</h2>
                <form class="search" method="get" action="index.php">
                    <input type="search" name="buscar" value="<?= e($busqueda) ?>" placeholder="Buscar nombre, marca o ubicación">
                    <button class="btn primary" type="submit">Buscar</button>
                    <?php if ($busqueda !== ''): ?><a class="btn secondary" href="index.php">Limpiar</a><?php endif; ?>
                </form>
            </div>
            <div class="tablewrap">
                <table>
                    <thead><tr><th>Artículo</th><th>Marca</th><th>Unidades</th><th>Ubicación</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php if (!$articulos): ?>
                        <tr><td colspan="5" class="empty"><?= $busqueda !== '' ? 'No se encontraron resultados.' : 'Todavía no hay artículos. Registra el primero usando el formulario.' ?></td></tr>
                    <?php else: foreach ($articulos as $a): ?>
                        <tr>
                            <td><strong><?= e($a['nombre']) ?></strong><br><small>ID #<?= (int)$a['id'] ?></small></td>
                            <td><?= e($a['marca']) ?></td>
                            <td><span class="pill"><?= number_format((int)$a['unidades'], 0, ',', '.') ?></span></td>
                            <td><?= e($a['ubicacion']) ?></td>
                            <td><div class="actions">
                                <a class="btn secondary" href="index.php?editar=<?= (int)$a['id'] ?>">Editar</a>
                                <form method="post" action="index.php" onsubmit="return confirm('¿Seguro que deseas eliminar este artículo?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button class="btn danger" type="submit">Eliminar</button>
                                </form>
                            </div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
</body>
</html>
