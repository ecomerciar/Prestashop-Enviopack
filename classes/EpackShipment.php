<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 18/08/16
 * Time: 23:18
 */

require_once(dirname(__FILE__) . '/EpackShipmentModel.php');
require_once(dirname(__FILE__) . '/EpackRelayManager.php');
require_once(dirname(__FILE__) . '/EpackPackage.php');
require_once(dirname(__FILE__) . '/EpackCarrier.php');
require_once(dirname(__FILE__) . '/EpackRelayPoint.php');

class EpackShipment
{
    public $epack_api;

    /* Datos generales del envio */
    public $pedido;
    public $direccion_envio;
    public $destinatario;
    public $observaciones;
    public $servicio;
    public $correo;
    public $confirmado;
    public $paquetes;
    public $modalidad;

    /* Envio a domicilio */
    public $calle;
    public $piso;
    public $numero;
    public $depto;
    public $referencia_domicilio;
    public $codigo_postal;
    public $provincia;
    public $localidad;

    /* Envio a sucursal */
    public $sucursal;

    public function __construct($EpackApi)
    {
        $this->epack_api = $EpackApi;
    }

    private function general_data()
    {
        $data = array(
            "pedido" => $this->pedido,
            "direccion_envio" => $this->direccion_envio,
            "destinatario" => $this->destinatario,
            "observaciones" => $this->observaciones,
            "servicio" => $this->servicio,
            "correo" => $this->correo,
            "confirmado" => $this->confirmado,
            "paquetes" => $this->paquetes,
            "modalidad" => $this->modalidad
        );

        if (empty($data['pedido'])) {
            $data['pedido'] = "";
        }
        if (strlen($data['destinatario']) > 50) {
            $data['destinatario'] = "";
        }
        if (empty($data['modalidad'])) {
            $data['modalidad'] = "";
        }

        return $data;
    }

    private function home_data()
    {
        $general_data = $this->general_data();

        $data = array(
            "calle" => $this->calle,
            "numero" => $this->numero,
            "piso" => $this->piso,
            "depto" => $this->depto,
            "referencia_domicilio" => $this->referencia_domicilio,
            "codigo_postal" => filter_var($this->codigo_postal, FILTER_SANITIZE_NUMBER_INT),
            "provincia" => $this->provincia,
            "localidad" => $this->localidad
        );

        if (empty($data['calle']) || strlen($data['calle']) > 30) {
            $data['calle'] = "";
        }
        if (empty($data['numero']) || strlen($data['numero']) > 5) {
            $data['numero'] = "";
        }
        if (strlen($data['piso']) > 6) {
            $data['piso'] = "";
        }
        if (strlen($data['depto']) > 4) {
            $data['depto'] = "";
        }
        if (strlen($data['referencia_domicilio']) > 30) {
            $data['referencia_domicilio'] = "";
        }
        if (!preg_match('/^\d{4}$/', $data['codigo_postal'], $res)) {
            $data['codigo_postal'] = "";
        }
        if (empty($data['provincia'])) {
            $data['provincia'] = "";
        }
        if (empty($data['localidad']) || strlen($data['localidad']) > 50) {
            $data['localidad'] = "";
        }

        return array_merge($general_data, $data);
    }

    private function relaypoint_data()
    {
        $general_data = $this->general_data();

        $data = array("sucursal" => (int)$this->sucursal);

        return array_merge($general_data, $data);
    }

    public function send()
    {
        if ($this->modalidad == "S") {
            $data = $this->relaypoint_data();
        } else {
            $data = $this->home_data();
        }

        return $this->epack_api->create_shipment($data);
    }

}