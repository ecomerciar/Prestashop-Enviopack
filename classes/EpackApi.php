<?php


require_once(dirname(__FILE__) . '/EpackPackage.php');

class EpackApi
{
    static protected $instance;

    protected $idDireccionEnvio;
    protected $url = "https://api.enviopack.com/";
    protected $apiKey;
    protected $secretKey;
    protected $token;

    public function __construct()
    {
    }

    /* Singleton */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EpackApi();
        }

        return self::$instance;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function getSourceAddress()
    {
        $response = $this->get('direcciones-de-envio');
        return json_decode($response, true);
    }

    public function credentialsCheck()
    {
        try {
            $at = $this->getAccessToken();
            if (!$at) return false;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getCorreos()
    {
        $request = array(
            'filtrar_activos' => true
        );

        $response = $this->get('correos', $request);
        $response = json_decode($response, true);

        return $response;
    }

    public function getSucursales($correo = "")
    {
        if ($correo != "") {
            $request = array(
                "id_correo" => $correo
            );
        } else {
            $request = array();
        }

        $response = $this->get('sucursales', $request);
        return json_decode($response, true);
    }

    public function getServices($correo)
    {
        $response = $this->get('correos/' . $correo . '/servicios');

        return json_decode($response);
    }

    /* Devuelve el precio que abonona el cliente por el envio */
    public function get_carrier_price($codigoPostal, $correo, $peso, $modalidad = 'D', $service = 'N', $volumen = null)
    {
        if (!$codigoPostal || !$peso) {
            PrestaShopLogger::AddLog("Enviopack: No es posible cotizar un envio sin codigo postal o peso", 2);
            return false;
        }

        $request = array(
            'codigo_postal' => $this->cleanPostcode($codigoPostal),
            'correo' => $correo,
            'peso' => $peso,
            'servicio' => $service,
            'modalidad' => $modalidad,
        );

        if ($volumen) {
            $request['volumen'] = $volumen;
        }

        $response = $this->get('cotizar/precio/por-correo', $request);
        $response = json_decode($response, true);

        if (empty($response))
            return false;

        if (array_key_exists('valor', $response[0]))
            return $response[0]['valor'];

        return false;
    }

    /* Devuelve el costo del envio que paga el vendedor */
    public function get_carrier_cost($codigoPostal, $peso, $servicio = 'N', $dimensiones)
    {
        if (!$codigoPostal || !$peso) {
            PrestaShopLogger::AddLog("Enviopack: No es posible cotizar un envio sin codigo postal o peso", 2);
            return false;
        }

        $paquete = EpackPackage::getPacketEstimatedSize($dimensiones);

        $request = array(
            'codigo_postal' => $this->cleanPostcode($codigoPostal),
            'peso' => $peso,
            'modalidad' => 'D',
            'servicio' => $servicio,
            'paquetes' => $paquete,
        );

        $response = $this->get('cotizar/costo', $request);
        $response = json_decode($response, true);

        return $response;
    }

    /* Devuelve la lista de localidades de una provincia */
    public function get_localities($id_province)
    {
        if (trim($id_province) == "") {
            $id_province = "C";
        }

        $request = array('id_provincia' => $id_province);
        $response = $this->get('localidades', $request);
        return json_decode($response, true);
    }

    /* Devuelve el costo de envio a una sucursal */
    public function getCotizacionSucursal($province_id, $postcode, $weight, $dimensiones, $order_total)
    {
        $paquete = EpackPackage::getPacketEstimatedSize($dimensiones);

        $request = array(
            "provincia" => $province_id,
            "codigo_postal" => $postcode,
            "peso" => $weight,
            "paquetes" => $paquete,
            "monto_pedido" => $order_total,
            "plataforma" => "prestashop"
            //"localidad" => $locality_id,
            //"correo" => $carrier_id,
            //"modalidad" => "S"
        );

        $response = $this->get('cotizar/precio/a-sucursal', $request);
        $response = json_decode($response, true);

        $new_offices = array();
        foreach ($response as $office) {
            $new_office = array();
            if (isset($office['servicio'])) {
                switch ($office['servicio']) {
                    case 'N':
                    default:
                        $new_office['service_name'] = 'estándar';
                        break;
                    case 'P':
                        $new_office['service_name'] = 'prioritario';
                        break;
                    case 'X':
                        $new_office['service_name'] = 'express';
                        break;
                    case 'R':
                        $new_office['service_name'] = 'devolución';
                        break;
                }
            } else {
                $new_office['service_name'] = '';
            }
            $new_office['shipping_time'] = (isset($office['horas_entrega']) ? $office['horas_entrega'] : '');
            $new_office['service'] = (isset($office['servicio']) ? $office['servicio'] : '');
            $new_office['address'] = (isset($office['sucursal']['calle']) ? $office['sucursal']['calle'] : '') . ' ' . (isset($office['sucursal']['numero']) ? $office['sucursal']['numero'] : '');
            $new_office['id'] = (isset($office['sucursal']['id']) ? $office['sucursal']['id'] : '');
            $new_office['name'] = (isset($office['sucursal']['nombre']) ? $office['sucursal']['nombre'] : '');
            $new_office['lat'] = (isset($office['sucursal']['latitud']) ? $office['sucursal']['latitud'] : '');
            $new_office['lng'] = (isset($office['sucursal']['longitud']) ? $office['sucursal']['longitud'] : '');
            $new_office['full_address'] = (isset($office['sucursal']['calle']) ? $office['sucursal']['calle'] : '') . ' ' . (isset($office['sucursal']['numero']) ? $office['sucursal']['numero'] . ', ' : '') . (isset($office['sucursal']['localidad']['nombre']) ? $office['sucursal']['localidad']['nombre'] : '');
            $new_office['phone'] = (isset($office['sucursal']['telefono']) ? $office['sucursal']['telefono'] : '');
            $new_office['zone_id'] = (isset($office['sucursal']['localidad']['id']) ? $office['sucursal']['localidad']['id'] : '');
            $new_office['zone_name'] = (isset($office['sucursal']['localidad']['nombre']) ? $office['sucursal']['localidad']['nombre'] : '');
            $new_office['price'] = (isset($office['valor']) ? $office['valor'] : '');
            $new_office['courier'] = (isset($office['sucursal']['correo']['nombre']) ? $office['sucursal']['correo']['nombre'] : '');
            $new_offices[] = $new_office;
        }
        return $new_offices;
    }

    /* Devuelve un pdf con las etiquetas solicitadas */
    public function get_labels($ids)
    {
        $url = $this->url . "envios/etiquetas?access_token=" . $this->getAccessToken() . "&ids=" . $ids;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /* Devuelve el precio que paga el cliente por un envio a provincia */
    public function getCotizacionADomicilio($codigoPostal, $provincia, $peso, $dimensiones, $service = 'N', $order_total)
    {
        if (!$codigoPostal || !$provincia || !$peso || !$dimensiones) {
            PrestaShopLogger::AddLog("Enviopack: No es posible cotizar un envio sin codigo postal, provincia, dimensiones o peso", 2);
            return false;
        }

        $request = array(
            'codigo_postal' => $this->cleanPostcode($codigoPostal),
            'provincia' => $provincia,
            'peso' => $peso,
            'servicio' => $service,
            'paquetes' => EpackPackage::getPacketEstimatedSize($dimensiones),
            "monto_pedido" => $order_total,
            "plataforma" => "prestashop"
        );

        $response = $this->get('cotizar/precio/a-domicilio', $request);

        $response = json_decode($response, true);

        if (empty($response))
            return false;

        if (isset($response[0]) && isset($response[0]['valor']))
            return $response[0]['valor'];

        return false;
    }

    public function get_orders($parameters)
    {
        $response = $this->get('pedidos', $parameters);
        $response = json_decode($response, true);

        return $response;
    }


    public function make_order($order_detail)
    {
        $request = array(
            "id_externo" => $this->sanitize($order_detail['order_id']),
            "plataforma" => "prestashop",
            "nombre" => $this->sanitize($order_detail['name']),
            "apellido" => $this->sanitize($order_detail['lastname']),
            "email" => $this->sanitize($order_detail['email']),
            "telefono" => $this->sanitize($order_detail['phone']),
            "celular" => $this->sanitize($order_detail['mobile']),
            "monto" => $this->sanitize($order_detail['price']),
            "fecha_alta" => date("c"),
            "pagado" => $order_detail['paid_out'],
            "provincia" => $this->sanitize($order_detail['state']),
            "localidad" => $this->sanitize($order_detail['locality'])
        );

        $response = $this->post('/pedidos', $request);
        $response = json_decode($response, true);

        return $response;
    }

    public function create_shipment($request)
    {
        if (!empty($request['codigo_postal'])) {
            $request['codigo_postal'] = $this->cleanPostcode($request['codigo_postal']);
        }

        $response = $this->post('envios', $request);
        $response = json_decode($response, true);

        return $response;
    }

    public function get_order($id)
    {
        $response = $this->get('pedidos/' . $id);
        return json_decode($response, true);
    }

    public function get_shipment($id)
    {
        $response = $this->get('envios/' . $id);
        return json_decode($response, true);
    }

    public function set_process($request)
    {
        $response = $this->post('/envios/procesar', $request);
        return json_decode($response, true);
    }

    public function set_config($url)
    {
        $request = array("webhook_url" => $url);

        $response = $this->post('/configuraciones-api', $request);
        return json_encode($response);
    }

    protected function get($method, $request = array())
    {
        $url = rtrim($this->url, '/') . '/' . ltrim($method, '/') . '?' . http_build_query($request);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->getAccessToken()));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    protected function sanitize($text)
    {
        return trim($text);
    }

    protected function post($method, $request = array())
    {
        $url = rtrim($this->url, '/') . '/' . ltrim($method, '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->getAccessToken()));

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    protected function getAccessToken()
    {
        if ($this->token) {
            return $this->token;
        }

        $url = $this->url . "auth";

        $request = array(
            'api-key' => $this->apiKey,
            'secret-key' => $this->secretKey
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);
        if (!empty($response['token'])) {
            $this->token = $response['token'];
            return $response['token'];
        } else {
            return false;
        }

    }

    public static function cleanPostcode($postcode)
    {
        return preg_replace("/[^0-9]/", "", $postcode);
    }

    public function getTiposDePaquete()
    {
        return json_decode($this->get('tipos-de-paquetes'), true);
    }

}
