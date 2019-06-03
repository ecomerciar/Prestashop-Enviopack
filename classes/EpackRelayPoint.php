<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 19/08/16
 * Time: 00:58
 */

require_once(dirname(__FILE__) . '/EpackRelayPointModel.php');

class EpackRelayPoint
{
    public $id;
    public $id_db_carrier;      /* Enviopack Model Carrier ID */
    public $id_remote_relay;
    public $description;
    public $street;
    public $number;
    public $floor;
    public $department;
    public $postal_code;
    public $locality;
    public $latitude;
    public $longitude;
    public $id_remote_carrier;

    private $relaypoint_model;

    public function __construct($id = null)
    {
        $this->relaypoint_model = new EpackRelayPointModel();

        if (!is_null($id)) {
            $my_relay = $this->relaypoint_model->get_relaypoint("id_relaypoint=".$id);

            if (is_array($my_relay)) {
                $this->id                = $id;
                $this->id_db_carrier     = $my_relay["id_carrier"];
                $this->id_remote_relay   = $my_relay["id_remote_relay"];
                $this->description       = $my_relay["description"];
                $this->street            = $my_relay["street"];
                $this->number            = $my_relay["number"];
                $this->floor             = $my_relay["floor"];
                $this->department        = $my_relay["department"];
                $this->postal_code       = $my_relay["postal_code"];
                $this->locality          = $my_relay["locality"];
                $this->latitude          = $my_relay["latitude"];
                $this->longitude         = $my_relay["longitude"];
                $this->id_remote_carrier = $my_relay["id_remote_carrier"];
            }
        }
    }

    public function add()
    {
        if ($this->id > 0)
            return;

        $relaypoint_detail = array("id_carrier"        => $this->id_db_carrier,
                                   "id_remote_relay"   => $this->id_remote_relay,
                                   "id_remote_carrier" => $this->id_remote_carrier,
                                   "description"       => $this->description,
                                   "street"            => $this->street,
                                   "number"            => $this->number,
                                   "floor"             => $this->floor,
                                   "department"        => $this->department,
                                   "postal_code"       => $this->postal_code,
                                   "locality"          => $this->locality,
                                   "latitude"          => $this->latitude,
                                   "longitude"         => $this->longitude);

        $this->relaypoint_model->add($relaypoint_detail);
    }
}