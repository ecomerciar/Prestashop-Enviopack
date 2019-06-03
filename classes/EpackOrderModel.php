<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 18/08/16
 * Time: 18:07
 */
class EpackOrderModel
{

    protected $remote_order;

    public function __construct()
    {
        $this->remote_order = _DB_PREFIX_ . "enviopack_order";
    }

    public function add($id_ps_order, $street = null, $number = null, $floor = null, $department = null, $id_shipment = null, $id_ep_order = null)
    {
        try {
            Db::getInstance()->insert("enviopack_order", array(
                "id_ps_order" => $id_ps_order,
                "id_ep_order" => $id_ep_order,
                "id_shipment" => $id_shipment,
                "street" => $street,
                "number" => $number,
                "floor" => $floor,
                "department" => $department
            ));
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
        }
    }

    public function get($condition)
    {
        $SQL = "SELECT * FROM " . $this->remote_order . " WHERE " . $condition;
        $order = Db::getInstance()->getRow($SQL, true);

        return $order;
    }

    public function get_all($limit = null, $offset = null)
    {
        $SQL = "SELECT * FROM " . $this->remote_order . " as epo INNER JOIN " . _DB_PREFIX_ . "orders pso ON pso.id_order = epo.id_ps_order INNER JOIN " . _DB_PREFIX_ . "enviopack_carrier epc ON pso.id_carrier = epc.id_local_carrier WHERE id_shipment = 0";


        if ($limit) {
            $SQL .= " LIMIT " . $limit;
        }

        if ($offset) {
            $SQL .= " OFFSET " . $offset;
        }

        $orders = Db::getInstance()->executeS($SQL);

        return $orders;
    }

    public function count_all()
    {
        $SQL = "SELECT count(*) FROM " . $this->remote_order . " as epo INNER JOIN " . _DB_PREFIX_ . "orders pso ON pso.id_order = epo.id_ps_order INNER JOIN " . _DB_PREFIX_ . "enviopack_carrier epc ON pso.id_carrier = epc.id_local_carrier WHERE id_shipment = 0";

        return Db::getInstance()->getValue($SQL, false);
    }

    public function delete($condition)
    {
        try {
            Db::getInstance()->delete("enviopack_order", $condition);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
        }
    }

    public function update($data, $condition)
    {
        try {
            Db::getInstance()->update("enviopack_order", $data, $condition);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
        }
    }

/*    public function get_tracking_numbers($epack_orders_ids){
        if (count($epack_orders_ids) == 0) {
            return array();
        }

        foreach ($epack_orders_ids as $key => $value) {
            $epack_orders_ids[$key] = (int) $value;
        }
        $ids = implode(',', $epack_orders_ids);

        $SQL = "SELECT epack_orders.id_ep_order , poc.tracking_numbers FROM ".$this->remote_order." epack_orders INNER JOIN " . _DB_PREFIX_ . "ps_order_carrier poc ON pso.id_order = epo.id_ps_order WHERE id_remote_order IN (".$ids .')';
        $tracking_numbers = Db::getInstance()->getRow($SQL);

        return $tracking_numbers;
    }*/

}