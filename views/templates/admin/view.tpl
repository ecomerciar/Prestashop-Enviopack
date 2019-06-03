<div class="row">
    <form class="form-inline" method="POST" action="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}" id="form_filter">
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
        {if $section_selected == "borradores" || $section_selected == "en-proceso"}
            <button class="btn btn-success pull-right" id="id_process" disabled>Procesar seleccionados</button>
        {/if}
        <button class="btn btn-danger pull-right" id="id_delete" disabled>Eliminar seleccionados</button>
    </div>
    <div class="col-md-12" style="padding: 5px;">
        {if $total_pages > 1}
        <div class="form-group pull-right">
        <label>Página: </label>
        <select class="form-control" id="id_page" name="page">
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
    </form>
</div>
<div class="row">
    <div class="panel col-md-12">
        <div class="panel-heading">
            Lista de pedidos
        </div>
        <table class="table gender">
            <thead>
            <th class="result-check"><input type="checkbox" id="id_selectall"></th>
            <th>N°</th>
            <th>F de Alta</th>
            <th>Nomre y Apeliido</th>
            <th>Localidad y Prov.</th>
            <th>Monto</th>
            <th>Modalidad</th>
            <th>Aforo</th>
            <th>Pagado</th>
            <th>Correo</th>
            </thead>
            <tbody>
            {foreach from=$results key=id item=result}
                <tr class="result-tr">
                    <td><input type="checkbox" value="{$result.id}" name="selected[]"></td>
                    <td>{$result.id_externo}</td>
                    <td>{$result.fecha_alta|date_format:"%d-%m-%Y %H:%M"}</td>
                    <td>{$result.nombre} {$result.apellido}</td>
                    <td>{$result.provincia} {$result.localidad}</td>
                    <td>{$result.monto}</td>
                    <td>{$modality[$result.ultimo_envio.modalidad]}</td>
                    <td>{$result.ultimo_envio.peso_aforado}</td>
                    {if $result.pagado }
                        <td><span class="icon-check paid"></span></td>
                    {else}
                        <td><span class="icon-remove pending"></span></td>
                    {/if}
                    {if $result.ultimo_envio.correo}
                        <td><img class="carrier_logo" src="https://www.enviopack.com/imgs/{$result.ultimo_envio.correo}.png"></td>
                    {else}
                        <td></td>
                    {/if}
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#id_section").on("change", function() {
            $("#form_filter").submit();
        });

        $("#id_page").on("change", function() {
            $("#form_filter").submit();
        });

        $("#id_selectall").on("click", function() {
            var checked_status = this.checked;

            $("#id_process").prop("disabled", !this.checked);
            $("#id_delete").prop("disabled", !this.checked);

            $("input[type=checkbox]").each(function(){
                this.checked = checked_status;
            });
        });
    });
</script>