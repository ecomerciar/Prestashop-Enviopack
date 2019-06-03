<?php

class EnvioPackNotificationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $enviopack_order_number = htmlspecialchars(Tools::getValue("id"));
        $enviopack_order_number = filter_var($enviopack_order_number, FILTER_SANITIZE_NUMBER_INT);

        $status_to_set = Configuration::get("ENVIOPACK_DEF_STATE");
        $table_name = _DB_PREFIX_ . "order_carrier";
        $enviopack_order_number_psql = pSQL($enviopack_order_number);

        $sql = "SELECT id_order FROM `$table_name` WHERE tracking_number = '$enviopack_order_number_psql';";
        $id_order = Db::getInstance()->getValue($sql);

        if (!$id_order) return false;
        $order = new Order((int)$id_order);
        $order->setCurrentState($status_to_set);
        exit;
    }
}