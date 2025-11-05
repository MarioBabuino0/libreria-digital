<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Libros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <h3>Libros Registrados</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Título</th>
                <th>Autor(es)</th>
                <th>Fecha de publicación</th>
                <th>Portada</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $conn = mysqli_connect("db", "root", "root_password", "libreria");
            $sql = "SELECT libro.titulo, libro.fecha_pub, libro.imagen,
                           GROUP_CONCAT(autor.nombre SEPARATOR ', ') AS autores
                    FROM libro
                    JOIN LibroAutor ON libro.id = LibroAutor.idLibro
                    JOIN autor ON LibroAutor.idAutor = autor.id
                    GROUP BY libro.id";
            $res = mysqli_query($conn, $sql);
            while($row = mysqli_fetch_assoc($res)){
                $img = $row['imagen'] ? base64_encode($row['imagen']) : '';
                echo "<tr>
                        <td>" . htmlspecialchars($row['titulo']) . "</td>
                        <td>" . htmlspecialchars($row['autores']) . "</td>
                        <td>" . htmlspecialchars($row['fecha_pub']) . "</td>
                        <td>";
                if($img){
                    echo "<img src='data:image/jpeg;base64,$img' style='max-width:80px;max-height:120px' />";
                }else{
                    echo "<span class='text-muted'>Sin imagen</span>";
                }
                echo    "</td>
                    </tr>";
            }
            mysqli_close($conn);
            ?>
        </tbody>
    </table>
</div>
</body>
</html>
