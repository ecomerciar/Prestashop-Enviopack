<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 11/09/16
 * Time: 22:58
 */

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/classes/EpackRelayManager.php');
require_once(dirname(__FILE__) . '/classes/EpackApi.php');
require_once(dirname(__FILE__) . '/classes/EpackOrderModel.php');
require_once(dirname(__FILE__) . '/classes/EpackCartHelper.php');

$relay_manager = new EpackRelayManager();

$EpackApi = EpackApi::getInstance();

if (Configuration::get('ENVIOPACK_APIKEY') && Configuration::get('ENVIOPACK_SECRETKEY')) {
    $EpackApi->setApiKey(Configuration::get('ENVIOPACK_APIKEY'));
    $EpackApi->setSecretKey(Configuration::get('ENVIOPACK_SECRETKEY'));
} else {
    die();
}

switch (tools::getValue('method')) {
    /* Devuelve la lista de sucursales, dependiendo de la localidad */
    case 'getRelayPoints':
        $locality = tools::getValue('locality');
        $id_carrier = tools::getValue('id_carrier');
        $weight = tools::getValue('weight');

        $cart = Context::getContext()->cart;
        $dimesions = EpackCartHelper::getCartDimensions($cart);

        $address = new Address($cart->id_address_delivery);
        $state = new State($address->id_state);
        $province = $state->iso_code;

        $relaypoints = $relay_manager->get_relay_point_by_locality($locality, $id_carrier);
        $relaypoint_list = array();
        foreach ($relaypoints as $relaypoint) {
            $response = $EpackApi->getCotizacionSucursal($relaypoint['id_remote_carrier'], $province, $locality, $weight, $dimesions, $cart->getOrderTotal(true, 4));

            if (!empty($response)) {
                if (array_key_exists('valor', $response[0])) {
                    $cost = $response[0]['valor'];
                    $time = $response[0]['horas_entrega'];

                    $relaypoint['cost'] = $cost;
                    $relaypoint['time'] = $time;
                    $relaypoint_list[] = $relaypoint;

                }
            }
        }

        echo Tools::jsonEncode($relaypoint_list);
        break;

    /* Devuelve la sucursal (temporal) a donde realizar el envio */
    case 'getRelayPoint':
        $id_cart = tools::getValue('enviopack_cart_id');

        $relay = $relay_manager->get_shipping_relaypoint($id_cart);

        echo tools::jsonEncode(array("status" => "ok", "data" => $relay));

        break;

    /* Establece la sucursal a donde realizar el envio */
    case 'setRelayPoint':
        $id_cart = tools::getValue('enviopack_cart_id');
        $relay = array(
            'office_id' => tools::getValue('office_id'),
            'office_address' => tools::getValue('office_address'),
            'office_service' => tools::getValue('office_service'),
            'office_price' => tools::getValue('office_price'),
            'office_name' => tools::getValue('office_name')
        );

        $relay_manager->set_shipping_relaypoint($id_cart, $relay);

        echo tools::jsonEncode(array("status" => "ok"));

        break;
    /* Actualiza la direccion de un pedido */
    case 'updateOrder':
        $orderModel = new EpackOrderModel();

        $key = tools::getValue("key");
        $val = tools::getValue("val");
        $id = tools::getValue("id");

        $data = array($key => "$val");

        $orderModel->update($data, "id_ps_order=" . $id);

        break;
    /* Actualiza la direccion de un pedido */
    case 'setOrderCarrier':
        $orderModel = new EpackOrderModel();

        $carrier = tools::getValue("carrier");
        $id = tools::getValue("id");

        $data = array("carrier_id" => $carrier);
        $orderModel->update($data, "id_order=" . $id);

        break;

    /* Obtiene el la etiqueta de un pedido */
    case 'getOrderLabel':

        $ids = Tools::getValue('selected');
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="downloaded.pdf"');

        echo $EpackApi->get_labels($ids);

        break;

    /* Webhook para cambiar el estado */
    /* DEPRECADO */
    case 'shipmentProcessed':
        $id = Tools::getValue('id');

        if ($id > 0) {
            $orderModel = new EpackOrderModel();

            $order_row = $orderModel->get("id_shipment=" . $id);
            $order = new Order($order_row['id_order']);
            $order->setCurrentState((int)Configuration::get('ENVIOPACK_DEF_STATE'));

            $response = $EpackApi->get_shipment($id);
            if (is_array($response)) {
                $order->setWsShippingNumber($response['tracking_number']);
            } else {
                echo $response;
            }
        }
        break;

    // https://www.enviopack.com/documentacion/notificaciones
    case 'webhook':
        $id = Tools::getValue('id');
        $tipo = Tools::getValue('tipo');

        if ($id > 0 && ($tipo == 'envio-procesado' || $tipo == 'envio-cambio-condicion')) {
            $orderModel = new EpackOrderModel();

            $order_row = $orderModel->get("id_shipment=" . $id);
            $order_id = isset($order_row['id_ps_order']) ? $order_row['id_ps_order'] : $order_row['id_order'];
            $order = new Order($order_id);
            if ($tipo == 'envio-procesado') {
                $order->setCurrentState((int)Configuration::get('ENVIOPACK_DEF_STATE'));
            }

            $response = $EpackApi->get_shipment($id);
            if (is_array($response)) {
                $order->setWsShippingNumber($response['tracking_number']);
            } else {
                echo $response;
            }
        }

        break;


    /* Establece el carrier a un pedido */
    case 'setCarrier':
        $orderModel = new EpackOrderModel();

        $id_order = Tools::getValue('order');
        $id_carrier = Tools::getValue('carrier');

        $data = array("carrier_id" => $id_carrier);

        $orderModel->update($data, "id_ps_order=" . $id_order);

        echo json_encode(array("status" => "ok"));
        break;

    /* Set a relay to an order */
    case 'RegisterRelay':
        echo json_encode(array("status" => "cagate"));
        break;


    default:
        break;
}


function getCartWeight($cart, $id_carrier)
{
    $defWeight = Configuration::get("ENVIOPACK_DEF_WEIGHT");

    $products = $cart->getProducts();
    $weight = 0;

    switch (Configuration::get('PS_WEIGHT_UNIT')) {
        case 'lb':
            $multiplier = 0.453592;
            break;
        case 'g':
            $multiplier = 0.001;
            break;
        case 'kg':
        default:
            $multiplier = 1;
            break;
    }
    foreach ($products as $product) {
        $productObj = new Product($product['id_product']);
        $carriers = $productObj->getCarriers();
        $isProductCarrier = false;

        foreach ($carriers as $carrier) {
            if (!$id_carrier || $carrier['id_carrier'] == $id_carrier) {
                $isProductCarrier = true;
                continue;
            }
        }

        if ($product['is_virtual'] or (count($carriers) && !$isProductCarrier))
            continue;

        $weight += ($product['weight'] > 0 ? ($product['weight'] * $multiplier) : $defWeight) * $product['cart_quantity'];
    }

    return $weight;
}

/**
 * Devuelve un array con las dimensiones de cada productos del carrito
 * @param  [Cart ] $cart
 * @return [array]
 */
function getCartDimensions($cart)
{
    $products = $cart->getProducts();
    $dimensions = array();
    foreach ($products as $product) {
        for ($i = 0; $i < $product['cart_quantity']; $i++) {
            if (min($product['width'], $product['height'], $product['depth']) <= 0) {
                $dimensions[] = array(
                    'width' => Configuration::get("ENVIOPACK_DEF_WIDTH"),
                    'height' => Configuration::get("ENVIOPACK_DEF_HEIGHT"),
                    'depth' => Configuration::get("ENVIOPACK_DEF_DEPTH"),
                );
            } else {
                $dimensions[] = array(
                    'width' => $product['width'],
                    'height' => $product['height'],
                    'depth' => $product['depth'],
                );
            }

        }
    }

    return $dimensions;
}
exit;