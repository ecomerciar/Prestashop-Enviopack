<?php

require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackOrderModel.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackCarrier.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackAddress.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackApi.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackOrder.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackRelayManager.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackShipment.php');

class EnviopackActionOrderStatusPostUpdateController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
        $this->palabraClavesPrevias = array('de', 'calle', 'pje', 'pje.', 'pasaje', 'prov', 'prov.', 'provincial', 'interprovincial', 'diag', 'diag.', 'diagonal', 'ruta', 'av', 'av.', 'avenida', 'peat', 'peat.', 'peatonal', 'entre', 'y', 'regimiento', 'esquina', 'esq', 'esq.');
        $this->palabraClavesPosteriores = array('de', 'km', 'y');
        $this->EpackApi = EpackApi::getInstance();
        $this->EpackApi->setApiKey(Configuration::get('ENVIOPACK_APIKEY'));
        $this->EpackApi->setSecretKey(Configuration::get('ENVIOPACK_SECRETKEY'));
    }

    public function run($params)
    {
        $config_state = Configuration::get('ENVIOPACK_PAID_STATE');

        if (trim($params['newOrderStatus']->id) == $config_state) {

            $epack_ordermodel = new EpackOrderModel();

            $order_id = $params['id_order'];
            $order = new Order($order_id);

            $carrier = new EpackCarrier($order->id_carrier);

            if ($carrier->id_db > 0) {

                $delivery_address = new Address($order->id_address_delivery);

                $splitAddress = EpackAddress::getInstance()->splitAddress($delivery_address);

                $street = $splitAddress['calle'];
                $number = $splitAddress['numero'];
                $floor = $splitAddress['piso'];
                $depto = $splitAddress['depto'];

                $order_check = $epack_ordermodel->get("id_ps_order=" . $order_id);

                if (empty($order_check)) {
                    $epack_ordermodel->add($order_id, $street, $number, $floor, $depto);
                }

            }


             // Now we process this order
            $order_extradata = $epack_ordermodel->get("id_ps_order=" . $order->id);
            $cart = new Cart($order->id_cart);
            $customer = new Customer($cart->id_customer);
            $delivery_state = new State($delivery_address->id_state);

            if ($order_extradata['id_ep_order'] < 1) {
                $order_detail = array(
                    "order_id" => 'PS-' . $order->id,
                    "name" => $delivery_address->firstname,
                    "lastname" => $delivery_address->lastname,
                    "email" => $customer->email,
                    "phone" => $delivery_address->phone,
                    "mobile" => $delivery_address->phone_mobile,
                    "price" => round($order->total_paid, 2),
                    "paid_out" => true,
                    "state" => $delivery_state->iso_code,
                    "locality" => $delivery_address->city
                );

                // Creo la orden
                $enviopack_order = new EpackOrder($order_detail, $this->EpackApi);
                $result = $enviopack_order->save();
                if ($result === -1) return false;

                $id_ep_order = $enviopack_order->get_epack_order_id();
                $epack_ordermodel->update(array("id_ep_order" => $id_ep_order), "id_ps_order=" . $order->id);
            } else {
                $id_ep_order = $order_extradata['id_ep_order'];
            }

            $epack_carrier = new EpackCarrier($order->id_carrier);
            $package = new EpackPackage($order->getProducts());
            $shipment = new EpackShipment($this->EpackApi);

            $shipment->pedido = $id_ep_order;
            $shipment->direccion_envio = Configuration::get("ENVIOPACK_SOURCEADDR");
            $shipment->servicio = $epack_carrier->service_type;

            if (!trim($delivery_address->firstname) or !trim($delivery_address->lastname)) {
                $shipment->destinatario = $delivery_address->firstname . " " . $delivery_address->lastname;
            } else {
                $shipment->destinatario = $customer->firstname . " " . $customer->lastname;
            }

            $shipment->confirmado = false;
            $shipment->paquetes = $package->get_sizes();
            $shipment->modalidad = $epack_carrier->modality;

            if ($epack_carrier->modality === 'S') {
                $epack_relaymanager = new EpackRelayManager();
                $sucursal = $epack_relaymanager->get_shipping_relaypoint($cart->id);
                $shipment->modalidad = "S";
                $shipment->sucursal = $sucursal['id_relaypoint'];
                $shipment->confirmado = false;
                //$nuevo_correo = new EpackRelayPoint($sucursal['id_relaypoint']);
                //$shipment->correo = $nuevo_correo['id_remote_carrier'];
            } else {
                /* $carrier_id = $epack_carrier->get_carrier_id_for_order($order->id);
                $correo = $epack_carrier->get_carrier_row($carrier_id);
                if ($correo) {
                    $correo = $correo[0];
                    $shipment->correo = $correo['id_remote_carrier'];
                    $shipment->correo = '';
                    $shipment->confirmado = false;
                } */
                $shipment->correo = '';
                $shipment->confirmado = false;
                $shipment->modalidad = "D";
                $shipment->calle = $order_extradata['street'];
                $shipment->numero = $order_extradata['number'];
                $shipment->piso = $order_extradata['floor'];
                $shipment->depto = $order_extradata['department'];
                $shipment->codigo_postal = $delivery_address->postcode;
                $shipment->provincia = $delivery_state->iso_code;
                $shipment->localidad = $delivery_address->city;
            }

            $shipment = $this->check_shipment_values($shipment);

            if (!$shipment) {
                PrestaShopLogger::addLog('Error al intentar enviar shipment, alguno de los datos no son válidos para ser enviados: ' . print_r($shipment, true), 2);
                $process_error[] = "El pedido #" . $order_id . " no pudo ser procesado, datos inválidos";
            } else {
                $result = $shipment->send();

                if (isset($result['id'])) {
                    $epack_ordermodel->update(array("id_shipment" => $result['id']), "id_ps_order=" . $order->id);
                    $order = new Order($order_id);
                    $order->setWsShippingNumber($result['tracking_number']);
                }

                if ($result) {
                    $tracking_number = $result['id'];
                    $tracking_number_sql = pSQL($tracking_number);
                    $table_name = _DB_PREFIX_ . "order_carrier";
                    $query = "UPDATE `$table_name` SET tracking_number=$tracking_number_sql WHERE id_order=" . pSQL($order_id) . ";";
                    Db::getInstance()->Execute($query);
                }
            }
        }
    }


    public function check_shipment_values($shipment)
    {
        $shipment->pedido = filter_var($shipment->pedido, FILTER_SANITIZE_STRING);
        $shipment->direccion_envio = filter_var($shipment->direccion_envio, FILTER_SANITIZE_NUMBER_INT);
        //if (strlen($shipment->destinatario) > 50) $shipment->destinatario = substr($shipment->destinatario, 50);
        if (strlen($shipment->destinatario) > 50) $shipment->destinatario = "";
        if (!($shipment->modalidad === 'S' || $shipment->modalidad === 'D')) return false;
        if (!in_array($shipment->servicio, array('N', 'P', 'X', 'R'))) return false;
        if ($shipment->modalidad === 'D') {
            /* $shipment->calle = substr($shipment->calle, 0, 30);
            $shipment->numero = substr($shipment->numero, 0, 5);
            $shipment->piso = substr($shipment->piso, 0, 6);
            $shipment->depto = substr($shipment->depto, 0, 4);
            $shipment->codigo_postal = (int)substr((string)filter_var($shipment->codigo_postal, FILTER_SANITIZE_NUMBER_INT), 0, 4);
            $shipment->localidad = substr($shipment->localidad, 0, 50); */

            $shipment->codigo_postal = filter_var($shipment->codigo_postal, FILTER_SANITIZE_NUMBER_INT);

            if (empty($shipment->calle) || strlen($shipment->calle) > 30) {
                $shipment->calle = "";
            }
            if (empty($shipment->numero) || strlen($shipment->numero) > 5) {
                $shipment->numero = "";
            }
            if (strlen($shipment->piso) > 6) {
                $shipment->piso = "";
            }
            if (strlen($shipment->depto) > 4) {
                $shipment->depto = "";
            }
            if (!preg_match('/^\d{4}$/', $shipment->codigo_postal, $res)) {
                $shipment->codigo_postal = "";
            }
            if (empty($shipment->localidad) || strlen($shipment->localidad) > 50) {
                $shipment->localidad = "";
            }
        }
        return $shipment;
    }

}
