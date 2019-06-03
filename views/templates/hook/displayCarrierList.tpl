<script async defer src="https://maps.googleapis.com/maps/api/js?key={$enviopack_maps_api_key}&callback=init_gmap"></script>

<script type="text/javascript">
    relay_selected = false;
    var delivery_options = document.querySelectorAll('input[id^="delivery_option_"]');

    function init_gmap(){
        var input = document.querySelector('input[id="delivery_option_{$enviopack_id_carrier_local}"]');
        if(!input.checked){
            return;
        }
        var row = input.closest('.row.delivery-option');
        createMapElement(row);
    }

    function createMapElement(row){
        map = document.createElement("div");
        map.setAttribute("id", "enviopack-map");
        // Insert after my row
        row.parentNode.insertBefore(map, row.nextSibling);
        map = new google.maps.Map(document.getElementById('enviopack-map'), {
            zoom: 5,
            center: new google.maps.LatLng(-34.6156625, -58.5033378),
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });
        infowindow = new google.maps.InfoWindow();
        gmarkers = [];
        {{foreach from=$enviopack_offices item=location key=index}}
            gmarkers['{$location.id}'] = createMarker(new google.maps.LatLng("{$location.lat}", "{$location.lng}"),
                '<a class="office-select" style="cursor: pointer;background-color: #f83885;border: 1px solid #f83885;color: white;padding: 5px 10px;display: inline-block;border-radius: 4px;font-weight: 600;margin-bottom: 10px;text-align: center;" onclick="enviopack_register_relay(\'' + "{$location.full_address}" + '\',\'' + "{$location.id}" + '\',\'' + "{$location.service}" + '\',\'' + "{$location.price}" + '\',\'' + "{$location.courier}" + '\',\'' + "{$location.name}" + '\')">Seleccionar</a> ' + '<br>' +
                '<strong>Correo:</strong> ' + "{$location.courier}" + '<br>' +
                '<strong>Nombre:</strong> ' + "{$location.name}" + '<br>' +
                '<strong>Tlf:</strong> ' + "{$location.phone}" + '<br>' +
                '<strong>Dirección:</strong> ' + "{$location.full_address}" + '<br>' +
                '<strong>Tiempo de entrega:</strong> ' + "{$location.shipping_time}" + ' Hrs<br>' 
            );
        {{/foreach}}

        var data_to_send = {
            method: 'getRelayPoint',
            'enviopack_cart_id': {$enviopack_cart_id}
        };

        var xhr = new XMLHttpRequest();
        xhr.withCredentials = true;

        xhr.addEventListener("readystatechange", function () {
            if (this.readyState === 4) {
                res = JSON.parse(this.responseText);
                if (res && res.data && 'id_relaypoint' in res.data) {
                    relay_selected = true;
                    // Set the color for the current relay selected
                    gmarkers.forEach(function (marker, id) {
                        if (marker) {
                            if (id == res.data.id_relaypoint) {
                                marker.setIcon('https://maps.google.com/mapfiles/ms/icons/green-dot.png');
                            }
                        }
                    });
                    // Set the name of the current relay in the shipping method
                    row.getElementsByClassName('carrier-delay')[0].innerHTML = 'Sucursal seleccionada: '+res.data.address;
                }else if (res){
                    for (var i = 0; i < delivery_options.length; i++) {
                        // Get the shipping to office method
                        if(delivery_options[i].id === 'delivery_option_{$enviopack_id_carrier_local}'){
                            // Check if there is no office selected
                            if(!relay_selected){
                                delivery_options[i].closest('.delivery-option').getElementsByClassName('carrier-price')[0].innerHTML = 'Desde $' + {$enviopack_branch_price};
                                if(delivery_options[i].checked)
                                    change_cart_method_price('Desde $' + {$enviopack_branch_price} + ' ARS');
                            }
                        }
                    }
                    // Shipping method selected but no relay selected, block order
                    relay_selected = false;
                    ep_allowOrder(false);
                }
            }
        });

        xhr.open("POST", "{$enviopack_ajax_url}");
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

        xhr.send(arrToWww(data_to_send));
    }

    for (var i = 0; i < delivery_options.length; i++) {
        delivery_options[i].addEventListener('click', handleMap);
    }

    function change_cart_method_price(price = 'desde $' + {$enviopack_branch_price}){
        if(document.querySelector('#cart-subtotal-shipping') == null){
            var cart_interval = setInterval(function(){
                if(document.querySelector('#cart-subtotal-shipping') != null){
                    clearInterval(cart_interval);
                    document.querySelector('#cart-subtotal-shipping').querySelector('.value').innerHTML = price;
                }
            }, 1000);
        }else{
            if(!relay_selected)
                document.querySelector('#cart-subtotal-shipping').querySelector('.value').innerHTML = price;
        }
    }

    function handleMap(event) {
        if (this.id === 'delivery_option_{$enviopack_id_carrier_local}') {
            // Check if there is no office selected
            if(!relay_selected){
                this.closest('.delivery-option').getElementsByClassName('carrier-price')[0].innerHTML = 'Desde $' + {$enviopack_branch_price};
                if(this.checked)
                    change_cart_method_price('Desde $' + {$enviopack_branch_price} + ' ARS');
            }
            var row = this.closest('.row.delivery-option');
            if(document.getElementById('enviopack-map')){
                document.getElementById('enviopack-map').style.display = 'block';
                if(!relay_selected){
                    ep_allowOrder(false);
                }else{
                    ep_allowOrder(true);
                }
            }else{
                createMapElement(row);
            }
        } else {
            document.getElementById('enviopack-map').style.display = 'none';
            ep_allowOrder(true);
        }
    }

    function arrToWww(obj, prefix) {
        var str = [],
            p;
        for (p in obj) {
            if (obj.hasOwnProperty(p)) {
            var k = prefix ? prefix + "[" + p + "]" : p,
                v = obj[p];
            str.push((v !== null && typeof v === "object") ?
                serialize(v, k) :
                encodeURIComponent(k) + "=" + encodeURIComponent(v));
            }
        }
        return str.join("&");
    }
    
    function enviopack_register_relay(office_address, office_id, office_service, office_price, office_name) {
        if (office_id) {
            var data_to_send = {
                method: 'setRelayPoint',
                'enviopack_cart_id': {$enviopack_cart_id},
                'office_id': office_id,
                'office_address': office_address,
                'office_service': office_service,
                'office_price': office_price,
                'office_name': office_name
            };

            var xhr = new XMLHttpRequest();
            xhr.withCredentials = true;

            xhr.addEventListener("readystatechange", function () {
            if (this.readyState === 4) {
                res = JSON.parse(this.responseText);
                if (res) {
                    jQuery(gmarkers).each(function (id, marker) {
                        if (marker) {
                            if (id == office_id) {
                                marker.setIcon('https://maps.google.com/mapfiles/ms/icons/green-dot.png');
                            } else {
                                marker.setIcon('https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi2.png');
                            }
                        }
                    });
                    location.reload();
                }
            }
            });

            xhr.open("POST", "{$enviopack_ajax_url}");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

            xhr.send(arrToWww(data_to_send));
        } else {
            ep_allowOrder(false);
        }
    }

    function createMarker(latlng, html) {
        var marker = new google.maps.Marker({
            position: latlng,
            map: map
        });
        
        google.maps.event.addListener(marker, 'click', function () {
            infowindow.setContent(html);
            infowindow.open(map, marker);
        });
        return marker;
    }

    /* Block/Unblock Order button */
    function ep_allowOrder(status) {
        document.querySelector('[name=confirmDeliveryOption]').disabled = !(status);
    }

</script>

<style>
    #enviopack-map {
        position: relative;
        overflow: hidden;
        display: block;
        width: 100%;
        height: 300px;
        margin-bottom: 20px;
    }
</style>