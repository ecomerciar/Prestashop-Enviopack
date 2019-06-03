Recomendamos esta opción cuando envías la mayoría de tus paquetes con dimensiones similares.$(document).ready(function () {
    $("#uniform-id_locality").children("span").hide();
    $("#uniform-id_locality").removeAttr("class");

    $(".resume").each(function () {

        var x = $(this).find("strong").html();

        if (x == "Envio a sucursal") {
            $(this).find(".delivery_option_price").html("");
        }

        var m = $(this).find("strong").parent().html().replace("Tiempo de entrega:&nbsp;", "");
        $(this).find("strong").parent().html(m);
    });

    $('input.delivery_option_radio').each(function () {
        if ($(this).prop("checked") == true) {
            var title = $(this).parent().parent().parent().parent().find("strong").html();

            if (title == "Envio a sucursal") {
                $("button[name=processCarrier]").prop("disabled", true);
                $(".cart-prices").hide();
            }
        }
    });

    $(document).on('change', 'input.delivery_option_radio', function () {
        var title = $(this).parent().parent().parent().parent().children("td").find("strong").html();

        if (title == "Envio a sucursal") {
            $("button[name=processCarrier]").prop("disabled", true);
            $(this).parent().parent().parent().parent().find(".delivery_option_price").html('<span style=\"color: #208931;\" id="loading_relay">Cargando sucursales, por favor espere...</span>');
        } else {
            $("#loading_relay").hide();
            $("button[name=processCarrier]").prop("disabled", false);
            $("#delivery-options").hide();
        }
    });

});
