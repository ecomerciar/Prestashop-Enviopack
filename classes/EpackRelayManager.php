<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 18/08/16
 * Time: 00:12
 */

require_once(dirname(__FILE__) . '/EpackRelayPointModel.php');
require_once(dirname(__FILE__) . '/EpackCarrierManager.php');
require_once(dirname(__FILE__) . '/EpackRelayPoint.php');

class EpackRelayManager
{
    private $relaypoint_model;

    public function __construct()
    {
        $this->relaypoint_model = new EpackRelayPointModel();
    }

    /* Crea las sucursales */
    public function install_relay_points($relay_points, $force_carrier_id)
    {
        foreach ($relay_points as $remote_relaypoint) {
            $carrier = new EpackCarrier($force_carrier_id);

            if (!$this->relay_exist($remote_relaypoint['id'])) {

                $relay_point = new EpackRelayPoint();
                $relay_point->id_db_carrier = $carrier->id_db;
                $relay_point->id_remote_relay = $remote_relaypoint['id'];
                $relay_point->id_remote_carrier = $remote_relaypoint['correo']['id'];
                $relay_point->description = $remote_relaypoint['nombre'];
                $relay_point->street = $remote_relaypoint['calle'];
                $relay_point->number = $remote_relaypoint['numero'];
                $relay_point->floor = $remote_relaypoint['piso'];
                $relay_point->department = $remote_relaypoint['depto'];
                $relay_point->postal_code = $remote_relaypoint['codigo_postal'];
                $relay_point->locality = $remote_relaypoint['localidad']['id'];
                $relay_point->latitude = $remote_relaypoint['latitud'];
                $relay_point->longitude = $remote_relaypoint['longitud'];

                $relay_point->add();
            }

        }
    }

    /* Verifica la existencia de un relaypoint localmente */
    private function relay_exist($id_remote_relay)
    {
        $relay_point = $this->relaypoint_model->get_relaypoint("id_remote_relay=" . $id_remote_relay);

        if (is_array($relay_point)) {
            return true;
        }

        return false;
    }

    /* Devuelve los relaypoint de un carrier */
    public function get_relay_points($carrier)
    {
        return $this->relaypoint_model->get_relaypoint_list($carrier->id_db);
    }

    /* Guarda temporalmente la relación del carrito con el punto de relay*/
    public function set_shipping_relaypoint($id_cart, $relay)
    {
        if ($relay['office_id'] === -1) {
            $this->relaypoint_model->delete_shipping_relaypoint($id_cart);
        } else {
            if (is_array($this->relaypoint_model->get_shipping_relaypoint($id_cart))) {
                $this->relaypoint_model->update_shipping_relaypoint($id_cart, $relay);
            } else {
                $this->relaypoint_model->add_shipping_relaypoint($id_cart, $relay);
            }
        }
    }

    /* Elimina la relacion */
    public function delete_shipping_relaypoint($id_cart)
    {
        $this->relaypoint_model->delete_shipping_relaypoint($id_cart);
    }

    /* Devuelve el relaypoint temporal */
    public function get_shipping_relaypoint($id_cart)
    {
        $relaypoint_row = $this->relaypoint_model->get_shipping_relaypoint($id_cart);

        if (!empty($relaypoint_row)) {
            //$relaypoint = new EpackRelayPoint($relaypoint_row['id_relaypoint']);

            return $relaypoint_row;
        }

        return null;
    }

    public function get_relay_point_by_id($id)
    {
        return $this->relaypoint_model->get_relaypoint("id_relaypoint=" . $id);
    }

    /* Devuelve las sucursales de una localidad */
    public function get_relay_point_by_locality($locality_id, $id_carrier)
    {
        return $this->relaypoint_model->get_relaypoints("locality=" . $locality_id . " and id_carrier=" . $id_carrier);
    }

    public function get_remote_carrier_id($id_relaypoint)
    {
        return $this->relaypoint_model->get_column("id_remote_carrier", "id_relaypoint=" . $id_relaypoint);
    }
}