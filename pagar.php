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
<script src="https://www.paypal.com/sdk/js?client-id=sb&currency=USD" data-sdk-integration-source="button-factory"></script>

<div class="jumbotron text-center">
    <h1 class="display-4">¡Paso Final!</h1>
    <hr class="my-4">
    <p class="lead">Estas a punto de pagar con PayPal la cantidad de:
        <h4>$<?php echo number_format($total, 2); ?></h4>
        <div id="smart-button-container">
            <div style="text-align: center;">
                <div id="paypal-button-container"></div>
            </div>
        </div>
    </p>
    <p>Los productos podrán ser descargados una vez que se procese el pago<br/>
        <strong>(Para aclaraciones :app_distribuidas@gmail.com)</strong>
    </p>
</div>

<script src="https://www.paypal.com/sdk/js?client-id=sb"></script>
<script>paypal.Buttons().render('body');</script>

<script>
function initPayPalButton() {
    paypal.Buttons({
    style: {
        shape: 'rect',
        color: 'gold',
        layout: 'vertical',
        label: 'paypal',
        
    },

    createOrder: function(data, actions) {
        return actions.order.create({
        purchase_units: [{"amount":{"currency_code":"USD","value":1}}]
        });
    },

    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
        alert('Transaction completed by ' + details.payer.name.given_name + '!');
        });
    },

    onError: function(err) {
        console.log(err);
    }
    }).render('#paypal-button-container');
}
initPayPalButton();
</script>

<?php
include 'templates/pie.php';
?>