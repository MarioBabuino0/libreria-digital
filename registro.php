<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Libro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container-fluid">
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="registro.php">Registro</a></li>
        <li class="nav-item"><a class="nav-link" href="consulta.php">Consulta</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
    <h2>Registrar libro</h2>
    <?php
    $conn = mysqli_connect('db','root','root_password','libreria');
    if(!$conn) { echo "<div class='alert alert-danger'>Error en conexión</div>"; }

    // Procesar formulario
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $titulo = $_POST["titulo"];
        $fecha_pub = $_POST["fecha_pub"];
        $autores_existentes = isset($_POST["autor_id"]) ? $_POST["autor_id"] : [];
        $autores_nuevos = isset($_POST["nuevo_autor"]) ? array_filter(array_map('trim', $_POST["nuevo_autor"])) : [];
        $img_data = null;
        if (isset($_FILES["imagen"]) && $_FILES["imagen"]["tmp_name"] != '') {
            $img_data = addslashes(file_get_contents($_FILES["imagen"]["tmp_name"]));
        }

        // Insertar libro
        $sql_libro = "INSERT INTO libro (titulo, fecha_pub, imagen) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql_libro);
        mysqli_stmt_bind_param($stmt, "sss", $titulo, $fecha_pub, $img_data);
        if(mysqli_stmt_execute($stmt)) {
            $libro_id = mysqli_insert_id($conn);

            // Procesar autores nuevos (insertar solo si no existen con ese nombre)
            foreach($autores_nuevos as $nuevo_autor) {
                if ($nuevo_autor == "") continue;
                // Revisar si ya existe
                $stmt_check = mysqli_prepare($conn, "SELECT id FROM autor WHERE nombre=?");
                mysqli_stmt_bind_param($stmt_check, "s", $nuevo_autor);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_bind_result($stmt_check, $id_existente);
                if(mysqli_stmt_fetch($stmt_check)){
                    // Ya existe, usar ID existente
                    $autores_existentes[] = $id_existente;
                } else {
                    // No existe, insertar y usar nuevo ID
                    $stmt_insert = mysqli_prepare($conn, "INSERT INTO autor (nombre) VALUES (?)");
                    mysqli_stmt_bind_param($stmt_insert, "s", $nuevo_autor);
                    mysqli_stmt_execute($stmt_insert);
                    $autores_existentes[] = mysqli_insert_id($conn);
                }
                mysqli_stmt_close($stmt_check);
            }
            // Insertar vínculos libro-autor, evitando duplicados finales
            $autores_existentes = array_unique($autores_existentes);
            foreach($autores_existentes as $autor_id){
                $sql_la = "INSERT INTO LibroAutor (idLibro, idAutor) VALUES (?, ?)";
                $stmt_link = mysqli_prepare($conn, $sql_la);
                mysqli_stmt_bind_param($stmt_link, "ii", $libro_id, $autor_id);
                mysqli_stmt_execute($stmt_link);
            }
            echo "<div class='alert alert-success'>Libro registrado correctamente.</div>";
        } else {
            echo "<div class='alert alert-danger'>Error al registrar libro.</div>";
        }
    }

    $autores_db = mysqli_query($conn, "SELECT id, nombre FROM autor");
    ?>
    <form method="post" enctype="multipart/form-data" class="card p-4 shadow" style="max-width: 480px">
        <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha de publicación</label>
            <input type="number" min="1000" max="9999" name="fecha_pub" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Autores existentes</label>
            <select name="autor_id[]" class="form-select" multiple size="4">
                <?php while($a = mysqli_fetch_assoc($autores_db)): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
            <small class="form-text text-muted">Ctrl+Click para seleccionar varios</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Nuevos autores <span class="text-muted">(máx. 3)</span></label>
            <?php for($i=0;$i<3;$i++): ?>
                <input type="text" name="nuevo_autor[]" class="form-control my-1" placeholder="Nombre autor nuevo">
            <?php endfor; ?>
            <small class="form-text text-muted">Deja en blanco los campos que no uses</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Imagen de portada</label>
            <input type="file" name="imagen" accept="image/*" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Registrar</button>
    </form>
</div>
</body>
</html>
