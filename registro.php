<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Libro</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-dark navbar-dark justify-content-center p-3">
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
    // Configura mysqli para lanzar excepciones si hay error con try catch ejecutando primero el try para revisar si hay errores en la conexion
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = mysqli_connect('db','root','root_password','libreria');
    } catch (mysqli_sql_exception $e) {
        // Mostrar la alerta inteligente en Bootstrap
        echo '<div class="container mt-4">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> No se pudo conectar a la base de datos.<br>
                    <span style="font-size:0.91em;">' . htmlspecialchars($e->getMessage()) . '</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
              </div>';
        exit();
    }

    // Consulta los autores existentes para mostrarlos en el select múltiple
    $autores_db = mysqli_query($conn, "SELECT id, nombre FROM autor ORDER BY nombre ASC");

    // PROCESAMIENTO EN EL POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Obtén y valida campos principales
        $titulo = trim($_POST["titulo"]); // Elimina espacios inecesarios
        $fecha_pub = $_POST["fecha_pub"];
        if($fecha_pub < 1000 || $fecha_pub > 2155){
            echo "<div class='alert alert-warning alert-dismissible fade show mt-3' role='alert'><strong>Aviso:</strong> El año debe estar entre 1000 y 2155.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Cerrar'></button></div>";
            mysqli_close($conn);
            exit();
        }

        // Obtiene arrays de autores (IDs de existentes y nombres de nuevos)
        $autores_existentes = isset($_POST["autor_id"]) ? $_POST["autor_id"] : [];
        $autores_nuevos = isset($_POST["nuevo_autor"]) ? array_filter(array_map('trim', $_POST["nuevo_autor"])) : [];

        // Procesa la imagen si se subió archivo (puede ser NULL)
        // Si no se sube imagen, $img_data se mantiene en null
        $img_data = null;
        if (isset($_FILES["imagen"]) && $_FILES["imagen"]["tmp_name"] !== '') {
            $img_data = file_get_contents($_FILES["imagen"]["tmp_name"]);
        }

        try {
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
                echo "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>Libro registrado correctamente.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Cerrar'></button></div>";
            } else {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        } catch (Exception $e) {
            // Muestra error en registro (por ejemplo, si la imagen es NULL, si hay problemas con algún dato, etc)
            echo "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                    <strong>Error:</strong> No se pudo registrar el libro. " . htmlspecialchars($e->getMessage()) . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Cerrar'></button>
                  </div>";
        }
        mysqli_close($conn);
    }
    ?>

    <!-- Tarjeta de Bootstrap para el formulario -->
    <div class="card shadow p-4">
    <form method="post" enctype="multipart/form-data"> 
        <!-- Titulo -->
        <div class="mb-2">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" class="form-control" required maxlength="100">
        </div>
        <!-- Año de publicación -->
        <div class="mb-2">
            <label class="form-label">Año de publicación</label>
            <input type="number" min="0" max="2155" name="fecha_pub" class="form-control" required>
        </div>
        <!-- Select para elegir (varios) autores existentes -->
        <div class="mb-2">
            <label class="form-label">Autores existentes</label>
            <select name="autor_id[]" class="form-select" multiple size="3">
                <!-- mysqli_fetch_assoc me regresa por cada fila de la tabla una asociasion id-nombre de cada autor -->
                <?php while($a = mysqli_fetch_assoc($autores_db)): ?>
                    <!-- Recorro la lista de autores que consulte desde $autores_db el valor de $a sera el id pero se imprime el nombre 
                     al ser un bucle tengo tantas opciones como filas haya-->
                    <option value="<?= $a['id'] ?>"><?= $a['nombre'] ?></option> 
                <?php endwhile; ?>
            </select>
            <small class="form-text text-muted">Ctrl+Click para seleccionar varios</small>
        </div>

        <!-- Entrada de texto para crear hasta 3 nuevos autores -->
        <div class="mb-2">
            <label class="form-label">Ingresar autores nuevos <span class="text-muted">(máx. 3)</span></label>
            <?php for($i=0;$i<3;$i++): ?>
                <input type="text" name="nuevo_autor[]" class="form-control my-1" maxlength="50" placeholder="Nombre autor nuevo">
            <?php endfor; ?>
            <small class="form-text text-muted">Deja en blanco los campos que no uses</small>
        </div>
        <!-- Subida de portada -->
        <div class="mb-2">
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

