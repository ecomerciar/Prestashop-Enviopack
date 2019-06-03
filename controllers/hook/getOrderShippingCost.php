<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 15/08/16
 * Time: 23:27
 */
require_once(dirname(__FILE__) . '/../../classes/EpackCarrier.php');
require_once(dirname(__FILE__) . '/../../classes/EpackRelayManager.php');
require_once(dirname(__FILE__) . '/../../classes/EpackCartHelper.php');

class EnviopackGetOrderShippingCostController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($cart, $shipping_cost)
    {
        $address = new Address($cart->id_address_delivery);
        $carrier = new EpackCarrier($this->module->id_carrier);

        $cart_weight = $this->getCartWeight($cart, $carrier->id_local);
        $dimensions = EpackCartHelper::getCartDimensions($cart);
        $state = new State($address->id_state);

        if ($carrier->modality === 'S') {
            // !! TODO: ver como pasarle el id de localidad.
            $cost = $this->get_current_relaycost($cart->id);
            //$cost = $this->get_relaycost($cart, $state->iso_code, $cart_weight, $dimensions);
        } else {
            $cost = $this->module->EpackApi->getCotizacionADomicilio(
                $address->postcode,
                $state->iso_code,
                $cart_weight,
                $dimensions,
                $carrier->service_type,
                $cart->getOrderTotal(true, 4)
            );
        }

        if ($shipping_cost > 0) {
            return $shipping_cost + $cost;
        }

        return $cost;
    }

    private function get_current_relaycost($id_cart)
    {
        $relay_manager = new EpackRelayManager();
        $cost = $relay_manager->get_shipping_relaypoint($id_cart);
        return isset($cost['price']) ? $cost['price'] : Configuration::get("ENVIOPACK_BRANCH_PRICE");
    }

    private function get_relaycost($cart, $province_id, $cart_weight, $products_dimensions)
    {
        $relay_manager = new EpackRelayManager();
        $relaypoint = $relay_manager->get_shipping_relaypoint($cart->id);

        // Se establece un valor alto para que el envio a sucursal siempre quede ultimo
        if ($this->context->controller->php_self === 'order') {
            $cost = 0;
        } else {
            $cost = false;
        }

        if (!empty($relaypoint->id)) {
            $cost = Configuration::get('ENVIOPACK_DEF_PRICE');
            // TODO: Agregar locality_id
            $response = $this->module->EpackApi->getCotizacionSucursal(
                $relaypoint->id_remote_carrier,
                //$relaypoint->postal_code,
                $province_id,
                $locality_id,
                $cart_weight,
                $products_dimensions,
                $cart->getOrderTotal(true, 4)
            );

            if (!empty($response)) {
                if (array_key_exists('valor', $response[0])) {
                    $cost = $response[0]['valor'];
                }
            }
        }

        return $cost;
    }

    public static function getCartWeight($cart, $id_carrier)
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
    /*public static function getCartDimensions($cart)
    {
        $products = $cart->getProducts();
        $dimensions = array();

        foreach ($products as $product) {
            if (min($product['width'], $product['height'], $product['depth']) <= 0) {
                $dimensions[] = array(
                    'width'  =>  Configuration::get("ENVIOPACK_DEF_WIDTH"), 
                    'height' =>  Configuration::get("ENVIOPACK_DEF_HEIGHT"), 
                    'depth'  =>  Configuration::get("ENVIOPACK_DEF_DEPTH"),
                );
            } else {
                $dimensions[] = array(
                    'width'  => $product['width'], 
                    'height' => $product['height'], 
                    'depth'  => $product['depth'],
                );
            }
        }

        return $dimensions;
    }*/
}