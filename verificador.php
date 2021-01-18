<?php
    include 'global/config.php';
    include 'global/conexion.php';
    include 'carrito.php';
    include 'templates/cabecera.php';
?>

<?php
    //print_r($_GET);

    $Client_ID = "AVqEp2apcdJbJo2yOuQqo2_TYMS4pSJKTQ4XClMw1g1xlz3j8kxLfpNcwGXtNK26p1khAQ_vG3y7lZn2";
    $Secret = "EJWzLeCPVrSWWlV-0OkZO7lP6E6Wac7Yu1TV5toWUZSuBYCAuD0jc7FtfCELIXOmmxnMK47YzA4buezC";

    $login = curl_init("https://api-m.sandbox.paypal.com/v1/oauth2/token");
    curl_setopt($login, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($login, CURLOPT_USERPWD, $Client_ID.":".$Secret);
    curl_setopt($login, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $respuesta = curl_exec($login);

    $objRespuesta = json_decode($respuesta);
    $accesToken = $objRespuesta->access_token;
    //print_r($accesToken);

    $venta = curl_init("https://api-m.sandbox.paypal.com/v1/payments/payment/".$_GET['paymentID']);
    curl_setopt($venta, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer " . $accesToken));
    curl_setopt($venta, CURLOPT_RETURNTRANSFER, TRUE);
    $respuestaVenta = curl_exec($venta);
    //print_r($respuestaVenta);

    $objDatosTransaccion = json_decode($respuestaVenta);
    //print_r($objDatosTransaccion->payer->payer_info->email);
    $state = $objDatosTransaccion->state;
    $email = $objDatosTransaccion->payer->payer_info->email;
    
    $total = $objDatosTransaccion->transactions[0]->amount->total;
    $currency = $objDatosTransaccion->transactions[0]->amount->currency;
    $custom = $objDatosTransaccion->transactions[0]->custom;

    $clave = explode("#", $custom);
    $SID = $clave[0];
    $claveVenta = openssl_decrypt($clave[1], COD, KEY);

    curl_close($venta);
    curl_close($login);

    if($state == "approved") {
        $mensajePaypal = "<h3>Pago aprobado</h3>";
        $sentencia = $pdo->prepare("UPDATE `tblventas` 
                                    SET `PaypalDatos` = :PaypalDatos, 
                                    `Estado` = 'aprobado' 
                                    WHERE `tblventas`.`ID` = :ID;");
        $sentencia->bindParam(":ID", $claveVenta);
        $sentencia->bindParam(":PaypalDatos", $respuestaVenta);
        $sentencia->execute();

        $sentencia = $pdo->prepare("UPDATE tblventas SET `Estado` = 'completo'
                                    WHERE ClaveTransaccion = :ClaveTransaccion
                                    AND Total = :TOTAL
                                    AND ID = :ID");
                    $sentencia->bindParam(':ClaveTransaccion', $SID);
                    $sentencia->bindParam(':TOTAL', $total);
                    $sentencia->bindParam(':ID', $claveVenta);
                    $sentencia->execute();
        
                    //Contar cuantos registros fueron modificados
                    $completado = $sentencia->rowCount();
    }else {
        $mensajePaypal = "<h3>Hay un problema con el pago de PayPal</h3>";
    }
?>

<div class="jumbotron">
    <h1 class="display-4">¡Listo!</h1>
    <hr class="my-4">
    <p class="lead"><?php echo $mensajePaypal?></p>
    <p>
        <?php 
            if ($completado>=1) {
                $sentencia = $pdo->prepare("SELECT * FROM `tbldetalleventa`, `tblproductos` 
                                            WHERE tbldetalleventa.IDProducto=tblproductos.ID 
                                            AND tbldetalleventa.IDVenta=:ID");
                $sentencia->bindParam(':ID', $claveVenta);
                $sentencia->execute();

                $listaProductos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                //print_r($listaProductos);
            }
        ?>
        <div class="row">
            <?php foreach($listaProductos as $producto) {?>
            <div class="col-2">
                <div class="card">
                    <img class="card-img-top" src="<?php echo $producto['Imagen'];?>">
                    <div class="card-body">
                        <p class="card-text"><?php echo $producto['Nombre'];?></p>
                        <?php if($producto['Descargado']<DESCARGAS_PERMITIDAS) {?>
                        <form method="post" action="descargas.php">
                            <input type="hidden" name="IDVenta" id="" value="<?php echo openssl_encrypt($claveVenta, COD, KEY); ?>">
                            <input type="hidden" name="IDProducto" id="" value="<?php echo openssl_encrypt($producto['IDProducto'], COD, KEY); ?>">
                            <button class="btn btn-success" type="submit">Descargar</button>
                        </form>
                        <?php } else {?>
                            <button class="btn btn-success" type="button" disabled>Descargar</button>
                        <?php }?>
                    </div>
                </div>
            </div>
            <?php }?>
        </div>
    </p>
</div>
<?php include 'templates/pie.php'; ?>