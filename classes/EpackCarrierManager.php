<?php

require_once(dirname(__FILE__) . '/EpackCarrierModel.php');
require_once(dirname(__FILE__) . '/EpackCarrier.php');

class EpackCarrierManager
{
    private $carrier_model;

    private $services = array(
        "N" => "Estándar",
        "P" => "Prioritario",
        "X" => "Express"
    );

    public function __construct($module = null)
    {
        $this->module = $module;
        $this->carrier_model = new EpackCarrierModel();
    }

    /* Elimina el carrier de enviopack */
    public function delete_ep_carrier($id_ps_carrier)
    {
        $ep_carrier = new EpackCarrier($id_ps_carrier);

        // Si el carrier es utilizado para elegir sucursales
        // No lo elimino, ya que es comun a ambos metodos de
        // cotizacón: Correo y Provincia
        if ($ep_carrier->modality != 'S') {
            $this->delete_ps_carrier($id_ps_carrier);
            $ep_carrier->delete();
        }
    }

    /* Elinia el carreir de prestashop */
    public function delete_ps_carrier($id_local_carrier)
    {
        $ps_carrier = new Carrier($id_local_carrier);
        $ps_carrier->delete();
        $this->delete_logo($id_local_carrier . ".jpg");
    }

    /* Elimina todos los carrier registrados */
    public function inactive_all_active_carriers()
    {
        $carrier_list = $this->carrier_model->get_active_carriers();

        foreach ($carrier_list as $carrier) {
            $this->delete_ep_carrier($carrier['id_local_carrier']);
        }
    }

    /* Desinstala todos los carriers, sin expeciones */
    public function uninstall()
    {
        $carrier_list = $this->carrier_model->get_all_carriers();

        foreach ($carrier_list as $carrier) {
            $ep_carrier = new EpackCarrier($carrier['id_local_carrier']);
            $ps_carrier = new Carrier($carrier['id_local_carrier']);

            $ep_carrier->delete();
            $ps_carrier->delete();

            $this->delete_logo($carrier['id_local_carrier'] . ".jpg");
        }

    }

    /* Agrega un carrier en la estrcutura de prestashop */
    public function add_ps_carrier($name, $module_name, $delay)
    {

        if (!$name || !$module_name)
            return false;

        $carrier = new Carrier();
        $carrier->name = $name;
        $carrier->id_tax_rules_group = 0;
        $carrier->active = 1;
        $carrier->deleted = 0;
        $carrier->url = "https://seguimiento.enviopack.com/@";

        foreach (Language::getLanguages(true) as $language)
            $carrier->delay[(int)$language['id_lang']] = $delay;

        $carrier->shipping_handling = false;
        $carrier->range_behavior = 0;
        $carrier->is_module = true;
        $carrier->shipping_external = true;
        $carrier->external_module_name = $module_name;
        $carrier->need_range = true;

        if (!$carrier->add())
            return false;

        $groups = Group::getGroups(true);
        foreach ($groups as $group)
            Db::getInstance()->insert('carrier_group', array(
            'id_carrier' => (int)$carrier->id,
            'id_group' => (int)$group['id_group']
        ));

        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier->id;
        $rangePrice->delimiter1 = 0;
        $rangePrice->delimiter2 = 1000;
        $rangePrice->add();

        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier->id;
        $rangeWeight->delimiter1 = 0;
        $rangeWeight->delimiter2 = 1000;
        $rangeWeight->add();


        $zones = Zone::getZones(true);
        foreach ($zones as $zone) {
            Db::getInstance()->insert('carrier_zone', array('id_carrier' =>
                (int)$carrier->id, 'id_zone' => (int)$zone['id_zone']));
            Db::getInstance()->insert('delivery', array(
                'id_carrier' =>
                    (int)$carrier->id, 'id_range_price' => (int)$rangePrice->id,
                'id_range_weight' => null, 'id_zone' => (int)$zone['id_zone'], 'price'
                    => '0'
            ));
            Db::getInstance()->insert('delivery', array('id_carrier' =>
                (int)$carrier->id, 'id_range_price' => null, 'id_range_weight' =>
                (int)$rangeWeight->id, 'id_zone' => (int)$zone['id_zone'], 'price' =>
                '0'));
        }

        return $carrier->id;
    }

    /* Crea los carriers que están en la API */
    public function install_remote_carriers($carrier_list)
    {
        foreach ($carrier_list as $remote) {

            $services = $this->module->EpackApi->getServices($remote['id']);

            foreach ($services as $service) {
                if ($service->modalidad == "D") {
                    if (!array_key_exists($service->servicio, $this->services))
                        continue;

                    if (!$this->carrier_ep_exists($remote['id'], $service->servicio)) {
                        $this->add_ep_carrier("", $remote['id'], 0, $service->servicio, "D", $remote['nombre']);
                    }
                }
            }
        }

        return true;
    }

    /* Verifica si el carrier de enviopack existe */
    public function carrier_ep_exists($ep_carrier_id, $service)
    {
        $id = $this->carrier_model->get_value("id_carrier", "id_remote_carrier='$ep_carrier_id' and service_type='$service'");
        $res = false;

        if ($id > 0) {
            $res = true;
        }

        return $res;
    }

    /* Verifica si el carrier generico existe */
    public function carrier_generic_exists($service, $modality)
    {
        $id = $this->carrier_model->get_value(
            "id_carrier",
            "id_remote_carrier='' and service_type='" . $service .
                "' and modality='" . $modality . "'"
        );
        $res = false;

        if ($id > 0) {
            $res = true;
        }

        return $res;
    }

    /* Devuelve el ID del carrier generico */
    public function get_generic_carrier_id($service, $modality)
    {
        $id = $this->carrier_model->get_value(
            "id_local_carrier",
            "id_remote_carrier='' and service_type='" . $service .
                "' and modality='" . $modality . "'"
        );

        return $id;
    }

    /* Agrega un carrier de enviopack */
    public function add_ep_carrier($id_local, $id_remote, $has_relaypoits, $service_type, $modality, $name, $active = 0)
    {
        $epack_carrier = new EpackCarrier();
        $epack_carrier->id_local = $id_local;
        $epack_carrier->id_remote = $id_remote;
        $epack_carrier->has_relaypoint = $has_relaypoits;
        $epack_carrier->service_type = $service_type;
        $epack_carrier->modality = $modality;
        $epack_carrier->description = trim($name . " " . $this->services[$service_type]);
        $epack_carrier->add($active);

        return $epack_carrier;
    }

    /* Descarga el logo del carrier */
    public function copy_logo($src_path, $carrier_id, $extension)
    {
        copy($src_path, _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier_id . $extension);
    }

    /* Elinia el logo del carrier*/
    public function delete_logo($file_name)
    {
        if (file_exists(_PS_SHIP_IMG_DIR_ . $file_name))
            unlink(_PS_SHIP_IMG_DIR_ . $file_name);
    }

    public function get_carrier_by_remoteid($remote_id)
    {
        $id_db = $this->carrier_model->get_value("id_carrier", "id_remote_carrier='$remote_id'");

        return new EpackCarrier(null, $id_db);
    }

    public function get_remote_carriers()
    {
        $carrier_list = array();

        $result = $this->carrier_model->get_all_carriers();
        foreach ($result as $carrier) {
            if ($carrier['id_remote_carrier'] != "") {
                $carrier_list[] = $carrier;
            }
        }

        return $carrier_list;
    }

    public function get_carrier_local_id($id_carrier)
    {
        return $this->carrier_model->get_value("id_local_carrier", "id_carrier=" . $id_carrier);
    }

    public function update_carrier_local($old, $new)
    {
        $model = new EpackCarrierModel();
        $model->update("id_local_carrier=$new", "id_local_carrier=$old");
    }

    /* Activa los carriers remotos */
    public function active_remote_carriers()
    {
        $carriers = $this->get_remote_carriers();

        foreach ($carriers as $carrier) {
            $my_carrier = new EpackCarrier(null, $carrier['id_carrier']);

            $ps_carrier_id = $this->add_ps_carrier($carrier['description'], $this->module->name, $this->services[$carrier['service_type']]);
            $my_carrier->id_local = $ps_carrier_id;
            $my_carrier->update();

            $this->copy_logo("https://www.enviopack.com/imgs/" . $carrier['id_remote_carrier'] . ".png", $ps_carrier_id, ".jpg");

            $my_carrier->activate();
        }


    }

    /* Devuelve todos los carriers genericos inactivos */
    public function get_generic_carriers()
    {
        $carrier_list = array();
        $result = $this->carrier_model->get_all_carriers();
        foreach ($result as $carrier) {
            if (!$carrier['id_remote_carrier'] and !$carrier['has_relaypoint']) {
                $carrier_list[] = $carrier;
            }
        }

        return $carrier_list;
    }

    /* Activa los carriers genericos */
    public function active_generic_carriers()
    {
        $carriers = $this->get_generic_carriers();

        foreach ($carriers as $carrier) {
            $my_carrier = new EpackCarrier(null, $carrier['id_carrier']);

            $ps_carrier_id = $this->add_ps_carrier(
                "Envio a domicilio",
                $this->module->name,
                $this->services[$carrier['service_type']]
            );
            $this->copy_logo(dirname(__FILE__) . '/../truck.png', $ps_carrier_id, ".jpg");

            $my_carrier->id_local = $ps_carrier_id;
            $my_carrier->update();
            $my_carrier->activate();
        }
    }
}