<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 18/08/16
 * Time: 18:01
 */

require_once(dirname(__FILE__) . '/EpackOrderModel.php');

class EpackOrder
{
    private $order_detail;
    private $epack_model;
    private $epack_api;
    private $epack_order_id;
    public $last_response;

    public function __construct($order_detail, $epack_api)
    {
        $this->order_detail = $order_detail;
        $this->epack_model = new EpackOrderModel();
        $this->epack_api = $epack_api;
    }

    public function save()
    {
        $this->last_response = $this->epack_api->make_order($this->order_detail);

        if (key_exists('id', $this->last_response)) {
            $this->epack_order_id = $this->last_response['id'];
        } else {
            return -1;
        }

    }

    public function get_epack_order_id()
    {
        return $this->epack_order_id;
    }
}