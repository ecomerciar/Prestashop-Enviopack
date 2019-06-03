<?php

require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackApi.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackOrder.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackOrderModel.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackCarrier.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackAddress.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackRelayManager.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackShipment.php');


class EnviopackActionValidateOrderController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        $id_carrier = $params['order']->id_carrier;
        $carrier = new EpackCarrier($id_carrier);
        if ($carrier->id_db > 0 && $carrier->modality === 'S') {
            $order = $params['order'];
            $cart = $params['cart'];

            // Retrieve default order address and fetch its ID
            $address = new Address($cart->id_address_delivery);
            $id_address_delivery = (int)$address->id;

            // Retrieve DPD Pickup point selection
            $relay_manager = new EpackRelayManager();
            $relay_address = $relay_manager->get_shipping_relaypoint($cart->id);

            // DPD Pickup address will become one of customer's
            if (!empty($relay_address)) {
                $new_address = new Address();
                $new_address->id_customer = $address->id_customer;
                $new_address->lastname = $address->lastname;
                $new_address->firstname = $address->firstname;
                $new_address->company = $relay_address['name'];
                $new_address->address1 = $relay_address['address'];
                $new_address->address2 = '';
                $new_address->postcode = $address->postcode;
                $new_address->city = $address->city;
                $new_address->phone = $address->phone;
                $new_address->phone_mobile = $address->phone_mobile;
                $new_address->id_country = $address->id_country;
                $new_address->alias = 'Sucursal de envío';
                $new_address->deleted = 1;
                $new_address->add();
                $id_address_delivery = (int)$new_address->id;
            }

            // Update order
            $order->id_address_delivery = $id_address_delivery;
            $order->update();
        }
    }
}
