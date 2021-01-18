<?php
    include 'global/config.php';
    include 'global/conexion.php';
    include 'carrito.php';
?>
<?php 
    print_r($_POST);
    if ($_POST) {
        $IDVenta = openssl_decrypt($_POST['IDVenta'], COD, KEY);
        $IDProducto = openssl_decrypt($_POST['IDProducto'], COD, KEY);

        print_r($IDVenta);
        print_r($IDProducto);
        $sentencia = $pdo->prepare("SELECT * FROM `tbldetalleventa` 
                                    WHERE IDVenta = :IDVenta 
                                    AND IDProducto = :IDProducto
                                    AND Descargado <" . DESCARGAS_PERMITIDAS);
                    $sentencia->bindParam(":IDVenta", $IDVenta);
                    $sentencia->bindParam(":IDProducto", $IDProducto);
                    $sentencia->execute();
        
        $listaProductos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        //print_r($listaProductos);
        if ($sentencia->rowCount()>0) {
            echo "Archivo en descarga...";
            $nombreArchivo = "archivos/" . $listaProductos[0]['IDProducto'] . ".pdf";
            $nuevoNombreArchivo = $_POST['IDVenta'] . $_POST['IDProducto'] . ".pdf";
            echo $nuevoNombreArchivo;

            header("Content-Transfer-Enconding: binary");
            header("Content-type: application/force-download");
            header("Content-Disposition: attachment; filename = $nuevoNombreArchivo");
            readfile("$nombreArchivo");

            $sentencia = $pdo->prepare("UPDATE `tbldetalleventa` set Descargado=Descargado + 1
                                        WHERE IDVenta = :IDVenta
                                        AND IDProducto = :IDProducto");
                        $sentencia->bindParam(":IDVenta", $IDVenta);
                        $sentencia->bindParam(":IDProducto", $IDProducto);
                        $sentencia->execute();
                        
        } else {
            include 'templates/cabecera.php';
            echo "<br><br><br><br><br><br><h2>Tus descargas se agotaron</h2>";
            include 'templates/pie.php';
        }
    } 

?>