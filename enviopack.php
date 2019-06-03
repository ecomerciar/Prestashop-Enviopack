<?php

/**
 * @author    Gustavo Borgoni <gborgoni@localcode.com.ar>
 * Date: 05/08/16
 */

if (!defined('_PS_VERSION_'))
    exit;

// Define default api keys
define('ENVIOPACK_DEFAULT_APIKEY', '');
define('ENVIOPACK_DEFAULT_SECRETKEY', '');

class EnvioPack extends CarrierModule
{
    public $id_carrier;
    private $_html = '';
    private $_postErrors = array();
    private $_moduleName = 'enviopack';
    private $EpackConfig;
    public $EpackApi;
    protected $context;

    public function __construct()
    {
        $this->name = 'enviopack';
        $this->tab = 'shipping_logistics';
        $this->version = '1.7.4.2';
        $this->author = 'EnvioPack';
        $this->need_instance = 0;
        $this->limited_countries = array('ar');
        $this->bootstrap = true;

        include_once _PS_MODULE_DIR_ . $this->name . "/classes/EpackApi.php";
        include_once _PS_MODULE_DIR_ . $this->name . "/classes/EpackConfig.php";
        include_once _PS_MODULE_DIR_ . $this->name . "/classes/EpackCarrierManager.php";
        include_once _PS_MODULE_DIR_ . $this->name . "/classes/EpackRelayManager.php";

        $this->EpackConfig = new EpackConfig($this);
        $this->EpackApi = EpackApi::getInstance();

        if ((Configuration::get('ENVIOPACK_APIKEY') && Configuration::get('ENVIOPACK_SECRETKEY'))
            || (defined(ENVIOPACK_DEFAULT_APIKEY) && defined(ENVIOPACK_DEFAULT_SECRETKEY)
            && (!empty(ENVIOPACK_DEFAULT_APIKEY) && !empty(ENVIOPACK_DEFAULT_SECRETKEY)))) {
            $this->EpackApi->setApiKey(Configuration::get('ENVIOPACK_APIKEY'));
            $this->EpackApi->setSecretKey(Configuration::get('ENVIOPACK_SECRETKEY'));
        }

        parent::__construct();

        $this->displayName = $this->l('Envio pack');
        $this->description = $this->l('EnvioPack te conecta con los principales operadores logísticos,
                                        todos en un mismo lugar y de forma más fácil.');
    }

    /**
     * Install methods
     */
    public function loadSQLFile($sql_file)
    {
        $sql_content = file_get_contents($sql_file);

        $sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
        $sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);

        $result = true;
        foreach ($sql_requests as $request)
            if (!empty($request))
            $result &= Db::getInstance()->execute(trim($request));

        return $result;
    }

    public function install()
    {
        if (!parent::install())
            return false;

        // Install SQL tables
        $sql_file = dirname(__FILE__) . '/install/install.sql';
        if (!$this->loadSQLFile($sql_file))
            return false;

        // Hook register
        if (!$this->registerHook('actionCarrierUpdate')
            || !$this->registerHook('displayAfterCarrier')
            || !$this->registerHook('displayAdminOrder')
            || !$this->registerHook('orderConfirmation')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('actionValidateOrder')
            || !$this->registerHook('actionPaymentConfirmation'))
            return false;

        return true;
    }

    public function uninstallTab($class_name)
    {
        $id_tab = (int)Tab::getIdFromClassName($class_name);
        $tab = new Tab((int)$id_tab);
        return $tab->delete();
    }

    public function uninstall()
    {
        if (!parent::uninstall())
            return false;

        $carrier_manager = new EpackCarrierManager();
        $carrier_manager->uninstall();

        $sql_file = dirname(__FILE__) . '/install/uninstall.sql';
        if (!$this->loadSQLFile($sql_file))
            return false;

        Configuration::deleteByName('ENVIOPACK_APIKEY');
        Configuration::deleteByName('ENVIOPACK_SECRETKEY');
        Configuration::deleteByName('ENVIOPACK_HOOK_URL');
        Configuration::deleteByName('ENVIOPACK_CARRIER');
        Configuration::deleteByName('ENVIOPACK_SOURCEADDR');
        Configuration::deleteByName('ENVIOPACK_BRANCH_PRICE');
        Configuration::deleteByName('ENVIOPACK_DEF_STATE');
        Configuration::deleteByName('ENVIOPACK_DEF_WEIGHT');
        Configuration::deleteByName('ENVIOPACK_DEF_PRICE');
        Configuration::deleteByName('ENVIOPACK_DEF_DEPTH');
        Configuration::deleteByName('ENVIOPACK_DEF_WIDTH');
        Configuration::deleteByName('ENVIOPACK_DEF_HEIGHT');
        Configuration::deleteByName('ENVIOPACK_TABINSTALLED');
        Configuration::deleteByName('ENVIOPACK_PACKET_ESTIMATION_METHOD');
        Configuration::deleteByName('ENVIOPACK_PACKET_ESTIMATION_DEFAULT');

        if (!$this->uninstallTab('AdminEnviopack'))
            return false;

        return true;
    }

    public function getContent()
    {
        $output = $this->EpackConfig->processConfiguration();
        $output .= $this->EpackConfig->displayConfigurationForm();
        $this->context->controller->addJS(($this->_path) . 'views/js/enviopack-config.js');
        return $output;
    }

    public function getHookController($hook_name)
    {
        require_once(dirname(__FILE__) . '/controllers/hook/' . $hook_name . '.php');
        $controller_name = $this->name . $hook_name . 'Controller';
        $controller = new $controller_name($this, __FILE__, $this->_path);
        return $controller;
    }

    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        $controller = $this->getHookController('getOrderShippingCost');
        return $controller->run($params, $shipping_cost);
    }

    public function hookUpdateCarrier($params)
    {
        $controller = $this->getHookController('updateCarrier');
        return $controller->run($params);
    }

    public function hookDisplayAfterCarrier($params)
    {
        $controller = $this->getHookController('displayCarrierList');
        return $controller->run($params);
    }

    function hookOrderConfirmation($params)
    {
        $controller = $this->getHookController('orderConfirmation');
        return $controller->run($params);
    }

    function hookActionPaymentConfirmation($params)
    {
        $controller = $this->getHookController('actionPaymentConfirmation');
        return $controller->run($params);
    }

    function hookActionOrderStatusPostUpdate($params)
    {
        $controller = $this->getHookController('actionOrderStatusPostUpdate');
        return $controller->run($params);
    }

    function hookActionValidateOrder($params)
    {
        $controller = $this->getHookController('actionValidateOrder');
        return $controller->run($params);
    }

}