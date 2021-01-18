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
<script src="https://www.paypalobjects.com/api/checkout.js"></script>
<div class="jumbotron text-center">
    <h1 class="display-4">¡Paso Final!</h1>
    <hr class="my-4">
    <p class="lead">Estas a punto de pagar con PayPal la cantidad de:
        <h4>$<?php echo number_format($total, 2); ?></h4>
        <div id="paypal-button"></div>
    </p>
    <p>Los productos podrán ser descargados una vez que se procese el pago<br/>
        <strong>(Para aclaraciones :app_distribuidas@gmail.com)</strong>
    </p>
</div>

<script>
  paypal.Button.render({
    // Configure environment
    env: 'sandbox',
    client: {
      sandbox: 'AVqEp2apcdJbJo2yOuQqo2_TYMS4pSJKTQ4XClMw1g1xlz3j8kxLfpNcwGXtNK26p1khAQ_vG3y7lZn2',
      production: 'AcHIaATMy5sESWZl68hsQJYubpHAh0I2TjAXWRlZF0QvTxu863d3rRGR1VtkkBCG7zFl6xrQmYX1NkdM'
    },
    // Customize button (optional)
    locale: 'es_EC',
    style: {
      size: 'responsive',
      color: 'gold',
      shape: 'pill',
    },

    // Enable Pay Now checkout flow (optional)
    commit: true,

    // Set up a payment
    payment: function(data, actions) {
      return actions.payment.create({
        transactions: [{
          amount: {
            total: '<?php echo $total;?>',
            currency: 'USD'
          },
            description: "Compra de productos Vision GM:$<?php echo number_format($total, 2);?>",
            custom: "<?php echo $S_ID;?>#<?php echo openssl_encrypt($idVenta, COD, KEY);?>"
        }]
      });
    },
    // Execute the payment
    onAuthorize: function(data, actions) {
      return actions.payment.execute().then(function() {
        // Show a confirmation message to the buyer
        console.log(data);
        window.location = "verificador.php?paymentToken=" + data.paymentToken + "&paymentID=" + data.paymentID;
      });
    }
  }, '#paypal-button');

</script>

<?php
include 'templates/pie.php';
?>