<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 16/08/16
 * Time: 13:17
 */

require_once(dirname(__FILE__) . '/EpackCarrierModel.php');

class EpackCarrier
{
    public $id_local;       /* ID de Obj Carrier en PS */
    public $id_remote;      /* ID de Carrier en Enviopack */
    public $has_relaypoint; /* Indica si tiene sucursales */
    public $id_db;          /* ID en tabla: enviopack_carrier */
    public $service_type;   /* Tipo de servicio: N/P/R/C */
    public $modality;       /* Modalidad: D/S */
    public $description;    /* Nombre a mostrar en el combo del admin */

    private $model;

    public function __construct($id_local_carrier = null, $id_db = null)
    {
        $this->model = new EpackCarrierModel();

        if ($id_local_carrier > 0) {
            $this->id_local = $id_local_carrier;
            $this->id_remote = $this->model->get_value("id_remote_carrier", "id_local_carrier=" . $id_local_carrier);
            $this->id_db = $this->model->get_value("id_carrier", "id_local_carrier=" . $id_local_carrier);

            if ($this->id_db <= 0)
                return;

            $this->modality = $this->model->get_value("modality", "id_carrier=" . $this->id_db);
            $this->service_type = $this->model->get_value("service_type", "id_carrier=" . $this->id_db);
            $this->description = $this->model->get_value("description", "id_carrier=" . $this->id_db);
        } elseif ($id_db > 0) {
            $this->id_db = $id_db;
            $this->id_remote = $this->model->get_value("id_remote_carrier", "id_carrier=" . $id_db);
            $this->id_local = $this->model->get_value("id_local_carrier", "id_carrier=" . $id_db);

            $this->modality = $this->model->get_value("modality", "id_carrier=" . $this->id_db);
            $this->service_type = $this->model->get_value("service_type", "id_carrier=" . $this->id_db);
            $this->description = $this->model->get_value("description", "id_carrier=" . $this->id_db);
        }
    }

    public function add($active = 0)
    {
        if (isset($this->id_local) && isset($this->id_remote) &&
            isset($this->has_relaypoint) && isset($this->service_type) && isset($this->modality)) {

            $this->model->add_carrier(
                $this->id_local,
                $this->id_remote,
                $this->has_relaypoint,
                $this->service_type,
                $this->modality,
                $this->description,
                $active
            );

            return true;
        }

        return false;
    }

    public function update()
    {
        $data = array(
            "id_local_carrier" => $this->id_local,
            "id_remote_carrier" => $this->id_remote,
            "has_relaypoint" => $this->has_relaypoint,
            "service_type" => $this->service_type,
            "modality" => $this->modality
        );

        $this->model->update($data, "id_carrier=" . $this->id_db);
    }

    public function delete()
    {
        $this->model->delete($this->id_db);
    }

    public function has_relay_points()
    {
        $has_relay = $this->model->get_value("has_relaypoint", "id_carrier=" . $this->id_db);
        return $has_relay;
    }

    public function get_carrier_id_for_order($order_id)
    {
        $carrier_id = $this->model->get_carrier_id_for_order($order_id);
        return $carrier_id;
    }

    public function get_carrier_row($carrier_id)
    {
        $carrier_row = $this->model->get_carrier_row($carrier_id);
        return $carrier_row;
    }

    public function get_relay_carrier()
    {
        $relay = $this->model->get_relay_carrier_id();
        return $relay;
    }

    public function activate()
    {
        $data = array("active" => 1);
        $this->model->update($data, "id_carrier=" . $this->id_db);
    }
}