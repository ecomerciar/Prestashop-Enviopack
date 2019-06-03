<?php

require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackOrderModel.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackCarrier.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackAddress.php');

class EnviopackActionPaymentConfirmationController
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
        $order_model = new EpackOrderModel();

        $order_id = $params['id_order'];
        $order = new Order($order_id);


        $carrier = new EpackCarrier($order->id_carrier);

        if ($carrier->id_db > 0) {

            $address = new Address($order->id_address_delivery);

            $splitAddress = EpackAddress::getInstance()->splitAddress($address);

            $street = $splitAddress['calle'];
            $number = $splitAddress['numero'];
            $floor = $splitAddress['piso'];
            $depto = $splitAddress['depto'];

            $order_check = $order_model->get("id_ps_order=" . $order_id);

            if (empty($order_check)) {
                $order_model->add($order_id, $street, $number, $floor, $depto);
            }
        }
    }
}
