<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 19/08/16
 * Time: 00:59
 */
class EpackRelayPointModel
{

    protected $relaypoint;
    protected $relay_temp;

    public function __construct()
    {
        $this->relaypoint = _DB_PREFIX_ . "enviopack_relaypoint";
        $this->relay_temp = _DB_PREFIX_ . "enviopack_shipping_relaypoint";
    }

    public function add($relaypoint_detail)
    {
        try {
            Db::getInstance()->insert("enviopack_relaypoint", $relaypoint_detail);
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e");
        }
    }

    public function get_relaypoint_list($id_db_carrier)
    {
        $list = array();

        $SQL = "SELECT * FROM " . $this->relaypoint . " WHERE id_carrier=" . $id_db_carrier . " ORDER BY description";

        if ($result = Db::getInstance()->executeS($SQL)) {
            foreach ($result as $relay_point) {
                $list[] = $relay_point;
            }
        }

        return $list;
    }

    public function add_shipping_relaypoint($cart_id, $relay)
    {
        try {
            Db::getInstance()->insert(
                "enviopack_shipping_relaypoint",
                array(
                    "id_relaypoint" => $relay["office_id"],
                    "id_cart" => $cart_id,
                    "name" => $relay['office_name'],
                    "service" => $relay['office_service'],
                    "address" => $relay['office_address'],
                    "price" => $relay['office_price'],
                )
            );
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e ");
        }
    }


    public function update_shipping_relaypoint($cart_id, $relay)
    {
        try {
            Db::getInstance()->update(
                "enviopack_shipping_relaypoint",
                array(
                    "id_relaypoint" => $relay["office_id"],
                    "name" => $relay['office_name'],
                    "service" => $relay['office_service'],
                    "address" => $relay['office_address'],
                    "price" => $relay['office_price'],
                ),
                "id_cart=" . $cart_id
            );
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e ");
        }
    }

    public function delete_shipping_relaypoint($cart_id)
    {
        Db::getInstance()->delete("enviopack_shipping_relaypoint", "id_cart=" . $cart_id);
    }

    public function get_shipping_relaypoint($id_cart)
    {
        $SQL = "SELECT * FROM " . $this->relay_temp . " WHERE id_cart=" . $id_cart;
        $relay_point = Db::getInstance()->getRow($SQL);

        return $relay_point;
    }

    public function get_relaypoint($condition)
    {
        $SQL = "SELECT * FROM " . $this->relaypoint . " WHERE " . $condition;

        $relay_point = Db::getInstance()->getRow($SQL);

        return $relay_point;
    }

    public function get_relaypoints($condition)
    {
        $SQL = "SELECT * FROM " . $this->relaypoint . " WHERE " . $condition;

        $relay_point = Db::getInstance()->executeS($SQL);

        return $relay_point;
    }

    public function get_column($column, $condition)
    {
        $SQL = "SELECT $column FROM " . $this->relaypoint . " WHERE " . $condition;

        $relay_point = Db::getInstance()->getValue($SQL);

        return $relay_point;
    }
}