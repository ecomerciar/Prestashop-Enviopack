<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 15/08/16
 * Time: 14:58
 */

require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackApi.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackOrder.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackShipment.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackOrderModel.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackCarrier.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackRelayManager.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackCarrierManager.php');
require_once(_PS_MODULE_DIR_ . '/enviopack/classes/EpackCartHelper.php');

class AdminEnviopackController extends ModuleAdminController
{

    private $sections = array(
        "listos" => "Pendientes de informar a EnvioPack",
        "borradores" => "EnvioPack - Borradores",
        "por-confirmar" => "EnvioPack - Por Confirmar",
        "en-proceso" => "EnvioPack - En Proceso",
        "procesados" => "EnvioPack - Procesados"
    );

    private $modality = array(
        "D" => "A domicilio",
        "" => "A domicilio",
        "S" => "A sucursal"
    );

    private $service = array(
        "N" => "Estándar",
        "P" => "Prioritario",
        "X" => "Express"
    );

    public function __construct()
    {
        $this->EpackApi = EpackApi::getInstance();

        parent::__construct();

        $this->EpackApi->setApiKey(Configuration::get('ENVIOPACK_APIKEY'));
        $this->EpackApi->setSecretKey(Configuration::get('ENVIOPACK_SECRETKEY'));

        $this->bootstrap = true;
        $this->carrier_manager = new EpackCarrierManager();
    }

    public function renderList()
    {

        $section = Tools::getValue('section');

        if (!isset($section) || empty($section))
            $section = "listos";

        $epack_ordermodel = new EpackOrderModel();
        $epack_relaymanager = new EpackRelayManager();

        $action = Tools::getValue('action');

        $this->context->smarty->assign('procesed_ok', "");
        $this->context->smarty->assign('procesed_error', "");

        switch ($action) {
            case "delorder":
                $selected_orders = Tools::getValue('selected');

                if (is_array($selected_orders)) {
                    foreach ($selected_orders as $order_id) {
                        $epack_ordermodel->delete("id_ps_order=" . $order_id);
                    }
                }

                break;

            // Tomo del listado de pedidos los seleccionados para procesar
            case "process":
                $selected_orders = Tools::getValue('selected');

                if (is_array($selected_orders)) {
                    $process_ok = array();
                    $process_error = array();

                    foreach ($selected_orders as $order_id) {
                        $order_extradata = $epack_ordermodel->get("id_ps_order=" . $order_id);

                        $order = new Order($order_id);
                        $cart = new Cart($order->id_cart);
                        $delivery_address = new Address($order->id_address_delivery);
                        $customer = new Customer($cart->id_customer);
                        $delivery_state = new State($delivery_address->id_state);

                        if ($order_extradata['id_ep_order'] < 1) {
                            $order_detail = array(
                                "order_id" => $order_id,
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

                            $id_ep_order = $enviopack_order->get_epack_order_id();
                            $epack_ordermodel->update(array("id_ep_order" => $id_ep_order), "id_ps_order=" . $order->id);
                            if ($result == -1) {
                                $message = "El pedido #" . $order_id . " no pudo ser procesado: ";

                                if (isset($enviopack_order->last_response['errors']['global'])) {
                                    foreach ($enviopack_order->last_response['errors']['global'] as $key => $value) {
                                        $message .= "$value<br>";
                                    }
                                }

                                foreach ($enviopack_order->last_response['errors']['campos'] as $key => $value) {
                                    $message .= "$key - $value<br>";
                                }

                                $process_error[] = $message;
                                continue;
                            }
                        } else {
                            $id_ep_order = $order_extradata['id_ep_order'];
                        }

                        $epack_carrier = new EpackCarrier($order->id_carrier);

                        /* if ($order_extradata['carrier_id'] > 0) {
                            $id_local = $this->carrier_manager->get_carrier_local_id($order_extradata['carrier_id']);

                            if ($id_local > 0) {
                                $epack_carrier = new EpackCarrier($id_local);
                                $correo = $epack_carrier->id_remote;
                            }
                        } elseif ($order_extradata['carrier_id'] == -1) {
                            $correo = "";
                        } */

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
                            $sucursal = $epack_relaymanager->get_shipping_relaypoint($cart->id);
                            $shipment->modalidad = "S";
                            $shipment->sucursal = $sucursal['id_relaypoint'];
                            $shipment->confirmado = false;
                            //$nuevo_correo = new EpackRelayPoint($sucursal['id_relaypoint']);
                            //$shipment->correo = $nuevo_correo['id_remote_carrier'];
                        } else {
                            $carrier_id = $epack_carrier->get_carrier_id_for_order($order->id);
                            $correo = $epack_carrier->get_carrier_row($carrier_id);
                            if ($correo) {
                                $correo = $correo[0];
                                $shipment->correo = $correo['id_remote_carrier'];
                                $shipment->confirmado = false;
                            }
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
                                $process_ok[] = "El pedido: " . $order_id . " Se registró en EnvioPack correctamente";

                                $epack_ordermodel->update(array("id_shipment" => $result['id']), "id_ps_order=" . $order->id);
                                $order = new Order($order_id);
                                $order->setWsShippingNumber($result['tracking_number']);
                            } else {
                                $message = "El pedido #" . $order_id . " no pudo ser procesado: ";

                                if (isset($result['errors']['global'])) {
                                    foreach ($result['errors']['global'] as $key => $value) {
                                        $message .= "$value<br>";
                                    }
                                }

                                foreach ($result['errors']['campos'] as $key => $value) {
                                    $message .= "$key - $value<br>";
                                }

                                $process_error[] = $message;
                            }
                        }

                    }
                    $this->context->smarty->assign('procesed_ok', $process_ok);
                    $this->context->smarty->assign('procesed_error', $process_error);
                }

                break;
            default:

                break;
        }

        // =====================================================================

        switch ($section) {
            // Pendientes de informar a EnvioPack
            case "listos":
                $order_list = array();

                $current_pages_listos = (Tools::getValue('page')) ? Tools::getValue('page') : 1;

                if ($action === 'process' && is_array($selected_orders) && count($selected_orders) && count($process_error) == 0) {
                    $current_pages_listos = 1;
                }

                $rpp = 20;

                $limit = $rpp;
                $offset = ($current_pages_listos - 1) * $rpp;

                $total_listos = $epack_ordermodel->count_all();
                $listos = $epack_ordermodel->get_all($limit, $offset);

                $total_pages_listos = ceil($total_listos / $rpp);

                foreach ($listos as $key => $value) {
                    if (!$value['id_shipment']) {
                        $order = new Order($value['id_ps_order']);
                        $cart = new Cart($order->id_cart);
                        $address = new Address($order->id_address_delivery);
                        $customer = new Customer($cart->id_customer);
                        $state = new State($address->id_state);


                        if ($order->id_carrier > 0) {
                            $carrier = new EpackCarrier($order->id_carrier);
                        }

                        $detail = array(
                            "id" => $order->id,
                            "name" => $address->firstname,
                            "lastname" => $address->lastname,
                            "email" => $customer->email,
                            "phone" => $address->phone,
                            "mobile" => $address->phone_mobile,
                            "price" => round($order->total_paid, 2),
                            "state" => $state->name,
                            "street" => $value['street'],
                            "number" => $value['number'],
                            "floor" => $value['floor'],
                            "department" => $value['department'],
                            "locality" => $address->city,
                            "reference" => $order->reference,
                        );

                        $detail["carrier"] = "";
                        $detail["service"] = '-';
                        $detail["carrier_remote"] = "";
                        $detail["modality"] = "-";

                        if ($carrier->id_db > 0) {
                            $detail["service"] = $carrier->service_type;

                            if ($carrier->modality === 'D') {
                                $detail["carrier"] = $carrier->id_db;
                                $detail["modality"] = "A Domicilio";
                            } elseif ($carrier->modality === 'S') {
                                $relay_point_row = $epack_relaymanager->get_shipping_relaypoint($order->id_cart);
                                if (!empty($relay_point_row)) {
                                    /* $detail["relay_id"] = $relay_point["id_relaypoint"];
                                    $detail["prices"][] = $relay_point["price"]; */
                                    $relay_point = new EpackRelayPoint($relay_point_row['id_relaypoint']);
                                    $detail["carrier"] = $relay_point->id_db_carrier;
                                    $detail["modality"] = "A Sucursal";
                                    $detail["carrier_remote"] = $relay_point->id_remote_carrier;
                                }
                            }
                        } else {
                            continue;
                        }

                        $detail['prices'] = array();
                        $detail['selected_carrier'] = "";

                        // costo del envio
                        $dimensiones = EpackCartHelper::getCartDimensions($cart);
                        if ($carrier->modality !== "S") {
                            $cost_list = $this->EpackApi->get_carrier_cost(
                                $address->postcode,
                                $this->getCartWeight($cart, $order->id_carrier),
                                $detail['service'],
                                $dimensiones
                            );
                            foreach ($cost_list as $cost) {
                                $epack_carrier_tmp = $this->carrier_manager->get_carrier_by_remoteid($cost['correo']['id']);
                                $cost['correo']['id_carrier'] = $epack_carrier_tmp->id_db;
                                $cost['servicio'] = $this->service[$cost['servicio']];
                                $detail['prices'][] = $cost;

                                if ($detail["carrier"] == $epack_carrier_tmp->id_db && $epack_carrier_tmp->id_db != "") {
                                    $detail['selected_carrier'] = $cost;
                                }
                            }
                        }/*  else {
                            $detail['prices'][] = $relay_point_row['price'];
                            $detail['selected_carrier'][] = $relay_point_row['price'];
                        } */



                        if (isset($this->service[$detail['service']])) {
                            $detail['service'] = $this->service[$detail['service']];
                        }

                        $order_list[] = $detail;
                    }
                }

                $this->context->smarty->assign('current_pages_listos', $current_pages_listos);
                $this->context->smarty->assign('total_pages_listos', $total_pages_listos);

                $this->context->smarty->assign('order_list', $order_list);
                $this->context->smarty->assign('results', array());
                break;
            default:
                $page = Tools::getValue('page');

                if (!isset($page) || empty($page))
                    $page = 1;

                $parameters = array(
                    "orden_columna" => "fecha_alta",
                    "orden_sentido" => "desc",
                    "pagina" => $page,
                    "ppp" => "20",
                    "seccion" => $section,
                    "subseccion" => "todos"
                );

                $result = $this->EpackApi->get_orders($parameters);

                $this->context->smarty->assign('modality', $this->modality);
                $this->context->smarty->assign('results', $result['pedidos']);
                $this->context->smarty->assign('total_pages', $result['total_paginas']);
                $this->context->smarty->assign('actual_page', $result['pagina']);
                $this->context->smarty->assign('order_list', array());
        }

        $carriers = $this->carrier_manager->get_remote_carriers();
        $ajax_url = _PS_BASE_URL_ . __PS_BASE_URI__;
        $ajax_url = rtrim($ajax_url, '/') . '/modules/enviopack/ajax.php';

        $this->context->smarty->assign('carriers', $carriers);
        $this->context->smarty->assign('section_selected', $section);
        $this->context->smarty->assign('sections', $this->sections);
        $this->context->smarty->assign('enviopack_ajax_url', $ajax_url);

        $this->context->controller->addCSS(_PS_MODULE_DIR_ . '/enviopack/views/css/displayCarrierList.css');

        $return = $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/enviopack/views/templates/admin/order_list.tpl');

        return $return;
    }

    public function initContent()
    {

        parent::initContent();

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
            /* if (strlen($shipment->referencia_domicilio) > 30) {
                $shipment->referencia_domicilio = "";
            } */
            if (!preg_match('/^\d{4}$/', $shipment->codigo_postal, $res)) {
                $shipment->codigo_postal = "";
            }
            /* if (empty($shipment->provincia)) {
                $shipment->provincia = "";
            } */
            if (empty($shipment->localidad) || strlen($shipment->localidad) > 50) {
                $shipment->localidad = "";
            }
        }
        return $shipment;
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