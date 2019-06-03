<?php
/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 17/08/16
 * Time: 18:38
 */

class EnviopackUpdateCarrierController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        $old_id_carrier	= (int)$params['id_carrier'];
        $new_id_carrier	= (int)$params['carrier']->id;

        $carrier_manager = new EpackCarrierManager();
        $carrier_manager->update_carrier_local($old_id_carrier, $new_id_carrier);
    }
}