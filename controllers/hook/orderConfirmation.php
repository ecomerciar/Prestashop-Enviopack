<?php
/*
 *
 */
require_once(dirname(__FILE__) . '/../../classes/EpackRelayManager.php');
require_once(dirname(__FILE__) . '/../../classes/EpackCarrier.php');


class EnviopackOrderConfirmationController
{
    private $file;
    private $module;
    private $context;
    private $_path;

    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        $relay_manager = new EpackRelayManager();

        $carrier = new EpackCarrier($params['order']->id_carrier);

        if ($carrier->modality === 'D') {
            $relay_manager->delete_shipping_relaypoint($params['order']->id_cart);
        }

    }
}
