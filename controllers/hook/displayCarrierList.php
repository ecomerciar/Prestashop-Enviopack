<?php

require_once(dirname(__FILE__) . '/../../classes/EpackCarrier.php');
require_once(dirname(__FILE__) . '/../../classes/EpackRelayManager.php');

class EnviopackDisplayCarrierListController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
        $this->epack_relay_manager = new EpackRelayManager();
    }

    public function run($params)
    {

        $address = new Address($params['cart']->id_address_delivery);
        $state = new State($address->id_state);
        $id_carrier = new EpackCarrier();
        $id_carrier = $id_carrier->get_relay_carrier();

        $weight = $this->getCartWeight($params['cart'], $params['cart']->id_carrier);
        $dimensions = EpackCartHelper::getCartDimensions($params['cart']);

        $offices = $this->module->EpackApi->getCotizacionSucursal($state->iso_code, $address->postcode, $weight, $dimensions, $params['cart']->getOrderTotal(true, 4));

        $ajax_url = _PS_BASE_URL_ . __PS_BASE_URI__;
        $ajax_url = rtrim($ajax_url, '/') . '/modules/enviopack/ajax.php';

        $this->context->smarty->assign('enviopack_offices', $offices);
        $this->context->smarty->assign('enviopack_cart_id', $params['cart']->id);
        $this->context->smarty->assign('enviopack_postcode', $address->postcode);
        $this->context->smarty->assign('enviopack_weight', $weight);
        $this->context->smarty->assign('enviopack_id_carrier_local', $id_carrier);
        $this->context->smarty->assign('enviopack_select_relay', true);
        $this->context->smarty->assign('enviopack_maps_api_key', 'AIzaSyDuhF23s4P90AFdaW-ffxcAAMgbu-oKDCQ');
        $this->context->smarty->assign('enviopack_branch_price', Configuration::get("ENVIOPACK_BRANCH_PRICE"));
        $this->context->smarty->assign('enviopack_ajax_url', $ajax_url);

        return $this->module->display($this->file, 'displayCarrierList.tpl');

    }

    public function getCartWeight($cart, $id_carrier)
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

}
