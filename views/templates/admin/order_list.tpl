{if $procesed_ok}
    <div class="alert alert-success">
        {foreach from=$procesed_ok item=process key=key}
            {$process}<br>
        {/foreach}
    </div>
{/if}

{if $procesed_error}
    <div class="alert alert-danger">
        {foreach from=$procesed_error item=process key=key}
            {$process}<br>
        {/foreach}
    </div>
{/if}

<form class="form-inline" method="POST" action="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}" id="form_filter">
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Estado: </label>
            <select class="form-control" id="id_section" name="section">
                {foreach from=$sections item=section key=key}
                    {if $key == $section_selected}
                        <option value="{$key}" selected>{$section}</option>
                    {else}
                        <option value="{$key}">{$section}</option>
                    {/if}
                {/foreach}
            </select>
        </div>
    </div>

    <div class="col-md-8">
        {if $section_selected == "listos"}
            <button class="btn btn-success pull-right" id="btnProcess" name="action" value="process" disabled style="margin-left: 10px;">PROCESAR PEDIDOS SELEC.</button>
            <button class="btn btn-danger pull-right" id="btnDelete" name="action" value="delorder" disabled>ELIMINAR PEDIDOS</button>
        {elseif $section_selected == "procesados"}
            <button type="button" class="btn btn-info pull-right" id="btnGetLabels" name="action" disabled>DESCARGAR ETIQUETAS SELEC.</button>
        {/if}
    </div>
</div>

{if $section_selected == "listos"}
<div class="alert alert-info" role="alert" style="margin: 30px 0 10px 0; line-height: 35px;">
  <strong>Importante:</strong> Recordá que si refrescas esta pagina luego de haber un procesado un envio esta repitiendo toda la operación completa por lo cual el envio va a procesarse nuevamente. Para refrescar la pagina sin correr riesgos podes hacer click aqui: <button type="button" class="btn btn-info" onclick="javascript:location.reload()">Refrescar</button>
</div>
{/if}

<hr>
{if count($order_list) > 0}
<div class="row">
<div class="panel col-md-12">
    <div class="panel-heading">
        Lista de pedidos
    </div>
    <table class="table gender">
        <thead>
        <th class="result-check"><input type="checkbox" id="id_selectall"></th>
        <th>ID</th>
        <th>Referencia</th>
        <th>Nombre y Apeliido</th>
        <th>Localidad y Prov.</th>
        <th>Calle</th>
        <th>Número</th>
        <th>Piso</th>
        <th>Depto</th>
        <th>Monto</th>
        <th>Servicio</th>
        <th>Correo</th>
        </thead>
        <tbody>
        {foreach from=$order_list key=id item=order}
            <tr class="result-tr">
                <td><input type="checkbox" value="{$order.id}" name="selected[]"></td>
                <td>{$order.id}</td>
                <td>{$order.reference}</td>
                <td>{$order.name} {$order.lastname}</td>
                <td>{$order.locality}<br>{$order.state}</td>

                {if $order.modality == 'A Sucursal'}
                <td colspan="4">&nbsp;</td>
                {else}
                <td><input type="text" value="{$order.street}" style="max-width: 100px!important;" data-key="street" data-id="{$order.id}"></td>
                <td><input type="text" value="{$order.number}" style="max-width: 50px!important;" data-key="number" data-id="{$order.id}"></td>
                <td><input type="text" value="{$order.floor}" style="max-width: 50px!important;" data-key="floor" data-id="{$order.id}"></td>
                <td><input type="text" value="{$order.department}" style="max-width: 50px!important;" data-key="department" data-id="{$order.id}"></td>
                {/if}

                <td>${$order.price}</td>
                <td>{$order.service}</td>
                {if $order.prices }
                <td width="270">
                    <div class="dropdown" style="width: 270px" onselectstart="return false;">
                        <div data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor:pointer; height: 40px; padding: 10px 8px; background-color: #F5F8F9; border: 1px solid #C7D6DB; border-radius: 3px;" id="selected">
                            {if $order.selected_carrier != ""}
                                <div class="col-md-11" style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;"><img class="carrier_logo" style="max-height: 17px;"  src="https://www.enviopack.com/imgs/{$order.selected_carrier.correo.id}.png"> ${$order.selected_carrier.valor} | {$order.selected_carrier.servicio} - {$order.selected_carrier.horas_entrega} hs.</div>
                            {else}
                                <div class="col-md-11" style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">Asignación automática vía reglas en EnvioPack</div>
                            {/if}
                            <div class="col-md-1" style="padding-top: 2px;"><i class="icon-arrow-down"></i></div>
                        </div>

                        <div class="dropdown-menu" aria-labelledby="dLabel" style="overflow-y: scroll; max-height: 200px">
                            <div class="dropdown-item"  onclick="set_carrier({$order.id}, '-1', this);" style="cursor:pointer">
                                <table class="table">
                                    <tr>
                                        <td>Asignación automática vía reglas en EnvioPack</td>
                                    </tr>
                                </table>
                            </div>

                            {foreach from=$order.prices key=id item=carrier}
                                <div class="dropdown-item" onclick="set_carrier({$order.id}, {$carrier.correo.id_carrier}, this);" style="cursor:pointer">
                                    <table class="table" style="height: 40px;">
                                        <tr>
                                            <td><img class="carrier_logo" style="max-height: 17px;" src="https://www.enviopack.com/imgs/{$carrier.correo.id}.png">
                                            $ {$carrier.valor} | {$carrier.servicio} - {$carrier.horas_entrega} hs.</td>
                                        </tr>
                                    </table>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </td>
                {elseif $order.modality == 'A Sucursal'}
                        <td>A sucursal
                {else}
                        <td>No se pudo cotizar el envio. Verifique el código postal.</td>
                {/if}
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
</div>

    <div class="col-md-12" style="padding: 5px;">

        {if $total_pages_listos > 1}
            <div class="form-group pull-right">
                <label>Página: </label>
                <select class="form-control pageSelect" name="page">
                    {for $page=1 to $total_pages_listos}
                        {if $page == $current_pages_listos}
                            <option value="{$page}" selected>{$page}</option>
                        {else}
                            <option value="{$page}">{$page}</option>
                        {/if}
                    {/for}
                </select>
            </div>
        {/if}
    </div>

{elseif count($results) > 0}
<div class="row">
<div class="panel col-md-12">
    <div class="panel-heading">
        Lista de pedidos
        <button type="submit" class="btn btn-success pull-right" style="margin-top: 2px; ">ACTUALIZAR LISTADO</button>
    </div>
    <table class="table gender">
        <thead>
        {if $section_selected == "procesados"}
            <th class="result-check"><input type="checkbox" id="id_selectall"></th>
        {/if}
        <th>N°</th>
        <th>F de Alta</th>
        <th>Nomre y Apeliido</th>
        <th>Localidad y Prov.</th>
        <th>Monto</th>
        <th>Pagado</th>
        <th>Correo</th>
        <th></th>
        </thead>
        <tbody>
        {foreach from=$results key=id item=result}
            <tr class="result-tr">
                {if $section_selected === "procesados"}
                    <td><input type="checkbox" value="{$result.id}" name="ep_order_id"></td>
                {/if}
                <td>{$result.id_externo}</td>
                <td>{$result.fecha_alta|date_format:"%d-%m-%Y %H:%M"}</td>
                <td>{$result.nombre} {$result.apellido}</td>
                <td>{$result.provincia}<br>{$result.localidad}</td>
                {if $section_selected === "procesados"}
                {/if}
                <td>{$result.monto}</td>
                {if $result.pagado }
                    <td><span class="icon-check paid"></span></td>
                {else}
                    <td><span class="icon-remove pending"></span></td>
                {/if}
                
                {if $section_selected == "procesados"}
                    <td><a href="https://app.enviopack.com/pedidos/procesados/todos?id={$result.id}" target="_blank" class="btn btn-default"><i class="icon-search-plus"></i> Ver</a></td>
                {elseif $section_selected == "borradores"}
                    <td><a href="https://app.enviopack.com/pedidos/borradores/todos?id={$result.id}" target="_blank" class="btn btn-default"><i class="icon-search-plus"></i> Ver</a></td>
                {elseif $section_selected == "por-confirmar"}
                    <td><a href="https://app.enviopack.com/pedidos/por-confirmar/todos?id={$result.id}" target="_blank" class="btn btn-default"><i class="icon-search-plus"></i> Ver</a></td>
                {elseif $section_selected == "en-proceso"}
                    <td><a href="https://app.enviopack.com/pedidos/en-proceso/todos?id={$result.id}" target="_blank" class="btn btn-default"><i class="icon-search-plus"></i> Ver</a></td>
                {/if}
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
</div>

    <div class="col-md-12" style="padding: 5px;">
        {if $total_pages > 1}
            <div class="form-group pull-right">
                <label>Página: </label>
                <select class="form-control pageSelect" name="page">
                    {for $page=1 to $total_pages}
                        {if $page == $actual_page}
                            <option value="{$page}" selected>{$page}</option>
                        {else}
                            <option value="{$page}">{$page}</option>
                        {/if}
                    {/for}
                </select>
            </div>
        {/if}
    </div>
{else}
    <div class="alert alert-info">
        <p>No se registraron pedidos en este estado.</p>
    </div>
{/if}


</form>


<script>
    function set_carrier(order_id, carrier_id, element)
    {
        var $order_id = order_id;
        var $carrier_id = carrier_id;

        $.ajax({
            type: "post",
            url: "{$enviopack_ajax_url}",
            datatype: "json",
            data: {
                method: 'setCarrier',
                order: $order_id,
                carrier: $carrier_id
            },

            success: function (response) {
                response = JSON.parse(response);

                if (response.status === "ok") {
                    set_selected(element);
                }
            }
        });

    }

    function set_selected(element) {
        lista = $(element).parent().html();
        selected = $(element).children().children().children().children().html();

        $(element).parent().parent().children().children().first().html(selected);
        $(element).parent().html(lista);
    }

    $(document).ready(function() {
        $(".pageSelect").on("change", function() {
            $("#form_filter").submit();
        });

        $("#btnGetLabels").on("click", function() {

            var orders_ids = $("input[name=ep_order_id]:checked").map(function () {
                return this.value;
            }).get();
            orders_ids = orders_ids.join();
            
            var req = new XMLHttpRequest();
            req.open("GET", "{$enviopack_ajax_url}?method=getOrderLabel&selected=" + orders_ids, true);
            req.responseType = "blob";

            req.onload = function (event) {
                var blob = req.response;
                var link=document.createElement('a');
                link.href=window.URL.createObjectURL(blob);
                link.download="Enviopack_etiquetas" + new Date() + ".pdf";
                //link.click();
            };

            req.send();
        });

        $("#id_section").on("change", function() {
            $("#form_filter .pageSelect").val(1);
            $("#form_filter").submit();
        });

        $("#id_selectall").on("click", function() {
            var checked_status = this.checked;

            $("#btnGetLabels").prop("disabled", !this.checked);
            $("#btnDelete").prop("disabled", !this.checked);
            $("#btnProcess").prop("disabled", !this.checked);

            $("input[type=checkbox]").each(function(){
                this.checked = checked_status;
            });
        });

        $("input[type=checkbox]").each(function(){
            $(this).on("click", function() {
                some_checked = false;

                $("input[type=checkbox]").each(function(){
                    if (this.checked == true && this.id != "id_selectall") {
                        some_checked = true;
                    }
                });

                if (some_checked == false) {
                    $("#id_selectall").prop("checked", false);
                }

                if ($("#id_selectall").checked == false || some_checked == false || this.checked) {
                    $("#btnGetLabels").prop("disabled", !this.checked);
                    $("#btnDelete").prop("disabled", !this.checked);
                    $("#btnProcess").prop("disabled", !this.checked);
                }
            });
        });

        $("input[type=text]").each(function() {
            $(this).on("blur", function() {
                $.ajax({
                    type: "post",
                    url: "{$enviopack_ajax_url}",
                    datatype: "json",
                    data: {
                        method: 'updateOrder',
                        key: $(this).data('key'),
                        val: $(this).val(),
                        id: $(this).data('id')
                    },

                    success: function (response) {

                    }
                });
            });
        });

        $("select[name=select_carrier]").each(function () {
            $(this).on("change", function() {
                $.ajax({
                    type: "post",
                    url: "{$enviopack_ajax_url}",
                    datatype: "json",
                    data: {
                        method: 'setOrderCarrier',
                        carrier: $(this).val(),
                        id: $(this).data('id')
                    },

                    success: function (response) {

                    }
                });

            });
        });

    });
</script>