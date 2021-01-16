<?php
include 'global/config.php';
include 'global/conexion.php';
include 'carrito.php';
include 'templates/cabecera.php';
?>

<?php
if ($_POST){
    $total = 0;
    $S_ID=session_id();
    $correo=$_POST['email'];

    foreach($_SESSION['CARRITO'] as $indice=>$producto){
        $total = $total+($producto['PRECIO']*$producto['CANTIDAD']);
    }
    $sentencia = $pdo->prepare("INSERT INTO `tblventas` (`ID`, `ClaveTransaccion`, `PaypalDatos`, `Fecha`, `Correo`, `Total`, `Estado`) 
                                VALUES (NULL, :ClaveTransaccion, '', NOW(), :Correo, :Total, 'pendiente');");
    
    $sentencia->bindParam(":ClaveTransaccion", $S_ID);
    $sentencia->bindParam(":Correo", $correo);
    $sentencia->bindParam(":Total", $total);
    $sentencia->execute();
    $idVenta = $pdo->lastInsertId();

    foreach($_SESSION['CARRITO'] as $indice=>$producto){
        $sentencia = $pdo->prepare("INSERT INTO `tbldetalleventa` (`ID`, `IDVenta`, `IDProducto`, `PrecioUnitario`, `Cantidad`, `Descargado`) 
                                    VALUES (NULL, :IDVenta, :IDProducto, :PrecioUnitario, :Cantidad, '0');");

        $sentencia->bindParam(":IDVenta", $idVenta);
        $sentencia->bindParam(":IDProducto", $producto['ID']);
        $sentencia->bindParam(":PrecioUnitario", $producto['PRECIO']);
        $sentencia->bindParam(":Cantidad", $producto['CANTIDAD']);
        $sentencia->execute();
    }
}
?>


<?php
include 'templates/pie.php';
?>