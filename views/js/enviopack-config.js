$(document).ready(function() {

    const ESTIMATION_SUM_PACKET = 1;
    const ESTIMATION_MAX_PACKET = 2;
    const ESTIMATION_DEFAULT_PACKET = 3;

    texts = {};
    texts[ESTIMATION_SUM_PACKET] = 'Se estimará un solo paquete de la siguiente forma:<br>\
    <ul>\
        <li style="margin:5px 0">Si hay un sólo producto, se enviará un paquete con dichas dimensiones</li>\
        <li style="margin:5px 0">Si hay varios productos y todos son del mismo tamaño, se estimará un paquete con los productos apilados (tomando el lado más chico para apilar). \
        <br>Ejemplo: Tenemos 2 productos de 5x20x30. Se estimará un paquete de 10x20x30 (se calcula 2 veces la menor dimensión, el de lado 5)</li>\
        <li style="margin:5px 0">Si hay varios productos de dimensiones diferentes, se suman los volúmenes totales y se calculará una caja en forma de cubo con  el volumen total.\
        <br>Ejemplo: Tenemos 2 productos, uno de 10x15x20, y otro de 20x20x5. El volumen total es 10x15x20 + 20x20x5 = 5000 cm3, tomamos la raíz cúbica y obtenemos un valor redondeado de 18cm. Se estima un paquete de 18x18x18</li>\
    </ul><br>\
    <p><strong>Recomendamos esta opción cuando envías la mayoría de tus paquetes con dimensiones similares.</strong></p>';
    texts[ESTIMATION_MAX_PACKET] = 'Se estimará un solo paquete tomando los lados más grandes de todos los productos\
        <br>Ejemplo: Tenemos un producto de 10x10x20 y otros de 20x5x30. Los lados más grandes son 30 (del segundo), 20 (del segundo) y 20 (del primero), entonces estimamos 20x20x30.<br><br>\
        <p><strong>Recomendamos esta opción  cuando envías una producto grande en una caja con espacio donde podés agregar más productos de menores dimensiones.</strong></p>';
    texts[ESTIMATION_DEFAULT_PACKET] = 'Se estimará un solo paquete que previamente se haya cargado en la plataforma de EnvioPack elegido por usted.<br>\
    * Configurar mis paquetes predeterminados <a href="https://app.enviopack.com/configuracion/mis-paquetes ">https://app.enviopack.com/configuracion/mis-paquetes</a><br><br>\
    <p><strong>Recomendamos esta opción  cuando utilizas una misma caja para todos tus envíos.</strong></p>';
    
    let html = "<div class='form-group' id='estimation-explain' style='margin-top:-15px'> \
        <div class='col-lg-3'></div> \
        <div class='col-lg-9'> \
            <p class='text' style='font-size:15px;'> \
            </p>\
        </div>\
    </div>";

    $('#fieldset_1_1 .form-wrapper').append(html);

    $(document).on('change', 'select#ENVIOPACK_PACKET_ESTIMATION_METHOD', function() {
        if($("select#ENVIOPACK_PACKET_ESTIMATION_METHOD").val() == ESTIMATION_DEFAULT_PACKET){
            $("select#ENVIOPACK_PACKET_ESTIMATION_DEFAULT").parent().parent().show();
        } else {
            $("select#ENVIOPACK_PACKET_ESTIMATION_DEFAULT").parent().parent().hide();
        }

        $("#estimation-explain .text").html(texts[$("select#ENVIOPACK_PACKET_ESTIMATION_METHOD").val()]);
    });


    $( "select#ENVIOPACK_PACKET_ESTIMATION_METHOD").trigger( "change" );

});
