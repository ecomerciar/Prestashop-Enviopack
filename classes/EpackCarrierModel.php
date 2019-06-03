<?php

/**
 * User: gborgoni@localcode.com.ar
 * Date: 17/08/16
 * Time: 22:53
 */
class EpackCarrierModel
{
    protected $carrier;

    public function __construct()
    {
        $this->carrier = _DB_PREFIX_ . "enviopack_carrier";
        $this->order = _DB_PREFIX_ . "enviopack_order";
    }

    public function get_relay_carrier_id()
    {
        $SQL = "SELECT `id_local_carrier`, `id_carrier` FROM " . $this->carrier . " WHERE `modality` = 'S' AND `active` = '1'";
        $db_id = Db::getInstance()->getValue($SQL);
        return $db_id;
    }

    public function get_active_carriers()
    {
        $carrier_list = array();

        $SQL = "SELECT * FROM " . $this->carrier . " WHERE active=1;";

        if ($results = Db::getInstance()->ExecuteS($SQL)) {
            foreach ($results as $row) {
                $carrier_list[] = $row;
            }
        }

        return $carrier_list;
    }

    public function get_all_carriers()
    {
        $carrier_list = array();

        $SQL = "SELECT * FROM " . $this->carrier;

        if ($results = Db::getInstance()->ExecuteS($SQL)) {
            foreach ($results as $row) {
                $carrier_list[] = $row;
            }
        }

        return $carrier_list;
    }

    public function add_carrier($id_local, $id_remote, $has_relaypoint, $service_type, $modality, $description, $active = 1)
    {
        try {
            Db::getInstance()->insert("enviopack_carrier", array(
                "id_local_carrier" => $id_local,
                "id_remote_carrier" => $id_remote,
                "has_relaypoint" => $has_relaypoint,
                "service_type" => $service_type,
                "modality" => $modality,
                "description" => $description,
                "active" => $active
            ));
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
            return false;
        }

        return true;
    }

    public function get_carrier_id_by_remote($id_remote)
    {
        $SQL = "SELECT id_carrier FROM " . $this->carrier . " WHERE id_remote_carrier='" . $id_remote . "'";
        $db_id = Db::getInstance()->getValue($SQL);

        return $db_id;
    }

    public function get_carrier_id_for_order($order_id)
    {
        $SQL = "SELECT carrier_id FROM " . $this->order . " WHERE id_ps_order='" . $order_id . "'";
        $db_id = Db::getInstance()->getValue($SQL);

        return $db_id;
    }

    public function get_carrier_row($id_carrier)
    {
        $SQL = "SELECT * FROM " . $this->carrier . " WHERE id_carrier='" . $id_carrier . "'";
        $res = false;
        if ($results = Db::getInstance()->ExecuteS($SQL)) {
            foreach ($results as $row) {
                $res[] = $row;
            }
        }

        return $res;
    }

    public function get_value($value, $condition)
    {
        $SQL = "SELECT $value FROM " . $this->carrier . " WHERE " . $condition;
        $val = Db::getInstance()->getValue($SQL);

        return $val;
    }

    public function delete($id_db)
    {
        $data = array("active" => 0);
        $this->update($data, "id_carrier=" . $id_db);
    }

    public function update($data, $where)
    {
        try {
            Db::getInstance()->update("enviopack_carrier", $data, $where);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
        }
    }
}