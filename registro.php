<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Libro</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navegación superior de la aplicación -->

<nav class="navbar  navbar-expand-lg bg-dark navbar-dark justify-content-center p-3">
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" href="index.php">Inicio</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="registro.php">Registro</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="consulta.php">Consulta</a>
    </li>
  </ul>
</nav>
<div class="container" style="max-width: 540px;">
    <h2 class="my-4">Registrar libro</h2>
    <?php
    // Conexión a la base de datos
    $conn = mysqli_connect('db','root','root_password','libreria');
    if(!$conn) { echo "<div class='alert alert-danger'>Error en conexión</div>"; exit(); }

    // Si el formulario se envió...
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Obtén y valida campos principales
        $titulo = trim($_POST["titulo"]);
        $fecha_pub = $_POST["fecha_pub"];
        if($fecha_pub < 1000 || $fecha_pub > 2155){
            echo "<div class='alert alert-warning'>El año debe estar entre 1000 y 2155.</div>";
            exit();
        }

        // Obtiene arrays de autores (IDs de existentes y nombres de nuevos)
        $autores_existentes = isset($_POST["autor_id"]) ? $_POST["autor_id"] : [];
        $autores_nuevos = isset($_POST["nuevo_autor"]) ? array_filter(array_map('trim', $_POST["nuevo_autor"])) : [];

        // Procesa la imagen si se subió archivo (puede ser NULL)
        $img_data = null;
        if (isset($_FILES["imagen"]) && $_FILES["imagen"]["tmp_name"] !== '') {
            $img_data = file_get_contents($_FILES["imagen"]["tmp_name"]);
        }

        // Inserta el nuevo libro usando statement preparado (con tipos correctos)
        $sql_libro = "INSERT INTO libro (titulo, fecha_pub, imagen) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql_libro);
        mysqli_stmt_bind_param($stmt, "sis", $titulo, $fecha_pub, $img_data);

        if(mysqli_stmt_execute($stmt)) {
            // Si tuvo éxito, recupera el ID del nuevo libro
            $libro_id = mysqli_insert_id($conn);

            // Inserta nuevos autores únicamente si no existen y agrega sus IDs al arreglo global
            foreach($autores_nuevos as $nuevo_autor) {
                if ($nuevo_autor == "") continue;
                $stmt_check = mysqli_prepare($conn, "SELECT id FROM autor WHERE nombre=?");
                mysqli_stmt_bind_param($stmt_check, "s", $nuevo_autor);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_bind_result($stmt_check, $id_existente);
                if(mysqli_stmt_fetch($stmt_check)){
                    $autores_existentes[] = $id_existente;
                } else {
                    $stmt_insert = mysqli_prepare($conn, "INSERT INTO autor (nombre) VALUES (?)");
                    mysqli_stmt_bind_param($stmt_insert, "s", $nuevo_autor);
                    mysqli_stmt_execute($stmt_insert);
                    $autores_existentes[] = mysqli_insert_id($conn);
                }
                mysqli_stmt_close($stmt_check);
            }

            // Quita posibles autores repetidos
            $autores_existentes = array_unique($autores_existentes);

            // Inserta las relaciones libro-autor
            foreach($autores_existentes as $autor_id){
                $sql_la = "INSERT INTO LibroAutor (idLibro, idAutor) VALUES (?, ?)";
                $stmt_link = mysqli_prepare($conn, $sql_la);
                mysqli_stmt_bind_param($stmt_link, "ii", $libro_id, $autor_id);
                mysqli_stmt_execute($stmt_link);
            }
            echo "<div class='alert alert-success'>Libro registrado correctamente.</div>";
        } else {
            // En caso de error, despliega el mensaje real
            echo "<div class='alert alert-danger'>Error al registrar libro: " . mysqli_stmt_error($stmt) . "</div>";
        }
    }

    // Consulta los autores existentes para mostrarlos en el select múltiple
    $autores_db = mysqli_query($conn, "SELECT id, nombre FROM autor");
    ?>
    <!-- Tarjeta de Bootstrap para el formulario -->
    <div class="card shadow p-4">
    <form method="post" enctype="multipart/form-data">
        <!-- Titulo -->
        <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" class="form-control" required maxlength="100">
        </div>
        <!-- Año de publicación -->
        <div class="mb-3">
            <label class="form-label">Año de publicación</label>
            <input type="number" min="0" max="2155" name="fecha_pub" class="form-control" required>
        </div>
        <!-- Select para elegir (varios) autores existentes -->
        <div class="mb-3">
            <label class="form-label">Autores existentes</label>
            <select name="autor_id[]" class="form-select" multiple size="4">
                <?php while($a = mysqli_fetch_assoc($autores_db)): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
            <small class="form-text text-muted">Ctrl+Click para seleccionar varios</small>
        </div>
        <!-- Entrada de texto para crear hasta 3 nuevos autores -->
        <div class="mb-3">
            <label class="form-label">Nuevos autores <span class="text-muted">(máx. 3)</span></label>
            <?php for($i=0;$i<3;$i++): ?>
                <input type="text" name="nuevo_autor[]" class="form-control my-1" maxlength="50" placeholder="Nombre autor nuevo">
            <?php endfor; ?>
            <small class="form-text text-muted">Deja en blanco los campos que no uses</small>
        </div>
        <!-- Subida de portada -->
        <div class="mb-3">
            <label class="form-label">Imagen de portada</label>
            <input type="file" name="imagen" accept="image/*" class="form-control">
        </div>
        <!-- Botón de registrar -->
        <button type="submit" class="btn btn-secondary w-100">Registrar</button>
    </form>
    </div>
</div>
</body>
</html>
