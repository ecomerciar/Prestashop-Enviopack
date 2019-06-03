<?php

/**
 * @author    Gustavo Borgoni <gborgoni@localcode.com.ar>
 * Date: 07/08/16
 */

class EpackConfig
{

    private $module;
    private $EpackCarrierManager;
    private $EpackRelayManager;

    const ESTIMATION_SUM_DIMS = 1;
    const ESTIMATION_MAX_DIMS = 2;
    const ESTIMATION_DEFAULT_PACKET = 3; // Se referencia en enviopack-config.js de forma directa

    public function __construct($module)
    {
        $this->module = $module;
        $this->EpackCarrierManager = new EpackCarrierManager($module);
        $this->EpackRelayManager = new EpackRelayManager();
    }

    public function processConfiguration()
    {
        $output = null;

        if (Tools::isSubmit('enviopackAuth')) {
            $output .= $this->process_auth();
        } elseif (Tools::isSubmit('enviopackGeneral')) {
            $output .= $this->process_config();
        } elseif (Tools::isSubmit('enviopackFail')) {
            $output .= $this->process_fail();
        }

        return $output;
    }

    public function displayConfigurationForm()
    {

        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form[0] = $this->auth_form();

        if ((Configuration::get('ENVIOPACK_APIKEY') && Configuration::get('ENVIOPACK_SECRETKEY'))
            || (defined(ENVIOPACK_DEFAULT_APIKEY) && defined(ENVIOPACK_DEFAULT_SECRETKEY)
            && (!empty(ENVIOPACK_DEFAULT_APIKEY) && !empty(ENVIOPACK_DEFAULT_SECRETKEY)))
            && $this->module->EpackApi->credentialsCheck()) {

            $fields_form[1] = $this->general_config();
            $fields_form[2] = $this->fail_config();

        }

        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->module->name;

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $this->module->displayName;
        $helper->show_toolbar = false;
        $helper->toolbar_scroll = false;
        $helper->submit_action = 'submit' . $this->module->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                'desc' => $this->module->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->module->name . '&save' . $this->module->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            )
        );

        // Load current value
        $helper->fields_value['ENVIOPACK_APIKEY'] = Configuration::get('ENVIOPACK_APIKEY') ? Configuration::get('ENVIOPACK_APIKEY') : ENVIOPACK_DEFAULT_APIKEY;
        $helper->fields_value['ENVIOPACK_SECRETKEY'] = Configuration::get('ENVIOPACK_SECRETKEY') ? Configuration::get('ENVIOPACK_SECRETKEY') : ENVIOPACK_DEFAULT_SECRETKEY;
        $helper->fields_value['ENVIOPACK_HOOK_URL'] = Configuration::get('ENVIOPACK_HOOK_URL');
        $helper->fields_value['ENVIOPACK_SOURCEADDR'] = Configuration::get('ENVIOPACK_SOURCEADDR');
        $helper->fields_value['ENVIOPACK_BRANCH_PRICE'] = Configuration::get('ENVIOPACK_BRANCH_PRICE');
        $helper->fields_value['ENVIOPACK_DEF_STATE'] = Configuration::get('ENVIOPACK_DEF_STATE');
        $helper->fields_value['ENVIOPACK_PAID_STATE'] = Configuration::get('ENVIOPACK_PAID_STATE');
        $helper->fields_value['ENVIOPACK_DEF_WEIGHT'] = Configuration::get('ENVIOPACK_DEF_WEIGHT');
        $helper->fields_value['ENVIOPACK_DEF_DEPTH'] = Configuration::get('ENVIOPACK_DEF_DEPTH');
        $helper->fields_value['ENVIOPACK_DEF_WIDTH'] = Configuration::get('ENVIOPACK_DEF_WIDTH');
        $helper->fields_value['ENVIOPACK_DEF_HEIGHT'] = Configuration::get('ENVIOPACK_DEF_HEIGHT');
        $helper->fields_value['ENVIOPACK_DEF_PRICE'] = Configuration::get('ENVIOPACK_DEF_PRICE');
        $helper->fields_value['ENVIOPACK_PACKET_ESTIMATION_METHOD'] = Configuration::get('ENVIOPACK_PACKET_ESTIMATION_METHOD');
        $helper->fields_value['ENVIOPACK_PACKET_ESTIMATION_DEFAULT'] = Configuration::get('ENVIOPACK_PACKET_ESTIMATION_DEFAULT');

        return $helper->generateForm($fields_form);
    }

    private function general_config()
    {

        $source_address_list = $this->module->EpackApi->getSourceAddress();
        $select_address_options = array();
        $select_address_options[] = array(
            'id_option' => '0',
            'name' => 'Seleccione una dirección'
        );

        foreach ($source_address_list as $source_address) {
            $select_address_options[] = array(
                'id_option' => $source_address['id'],
                'name' => $source_address['calle'] . " " . $source_address['numero']
            );
        }

        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $objState = new OrderStateCore();
        $state_list = $objState->getOrderStates($default_lang);

        foreach ($state_list as $state) {
            $status_list[] = array(
                'id_option' => $state['id_order_state'],
                'name' => $state['name']
            );
        }

        $packet_estimation_options = array(
            array('id_option' => self::ESTIMATION_SUM_DIMS, 'name' => 'Estimar dimensiones en base a la sumatoria de las dimensiones de los productos'),
            array('id_option' => self::ESTIMATION_MAX_DIMS, 'name' => 'Estimar en base a la dimension mas alta de cada producto'),
        );

        $packet_types = $this->module->EpackApi->getTiposDePaquete();

        if (count($packet_types) > 0) {
            $packet_estimation_options[] = array('id_option' => self::ESTIMATION_DEFAULT_PACKET, 'name' => 'Paquete default');

            $default_packets = array(array());
            foreach ($packet_types as $packet) {
                $size = $packet['alto'] . 'x' . $packet['ancho'] . 'x' . $packet['largo'];
                $default_packets[] = array('id_option' => $size, 'name' => $size);
            }

            $default_packet_select = array(
                'type' => 'select',
                'label' => $this->module->l('Paquete default para los envíos'),
                'name' => 'ENVIOPACK_PACKET_ESTIMATION_DEFAULT',
                'desc' => 'Tamaño del paquete que se utilizará para los envíos. <br><strong>Solo aplica si escoje la opción "Paquete default" para la estimación del tamaño del paquete.</strong>',
                'options' => array(
                    'query' => $default_packets,
                    'id' => 'id_option',
                    'name' => 'name',
                ),
                'required' => false
            );
        }

        $config_options = array(
            "ENVIOPACK_SOURCEADDR" => $select_address_options,
            "ENVIOPACK_DEF_STATE" => $status_list,
            "ENVIOPACK_PAID_STATE" => $status_list,
            "ENVIOPACK_PACKET_ESTIMATION_METHOD" => $packet_estimation_options,
        );
        // Guardo los valores default si no los tiene guardados
        $this->saveDefaultConfigValues($config_options);

        $form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->module->l('Configuración general'),
                    'icon' => 'icon-envelope'
                ),
                'description' => $this->module->l('Para configurar tus preferencias de envío ingresá a tu cuenta de EnvíoPack haciendo ') .
                    '<a href="https://app.enviopack.com/correos-y-tarifas" target="_new">click aquí</a>',
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => '<strong>' . $this->module->l('URL para EnvíoPack') . '</strong>',
                        'name' => 'ENVIOPACK_HOOK_URL',
                        'desc' => 'Pegar este URL en tus <a href="https://app.enviopack.com/configuraciones-api" target="_new">configuraciones de API de EnvíoPack</a>',
                        'disabled' => true,
                        'required' => false
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Dirección de origen'),
                        'name' => 'ENVIOPACK_SOURCEADDR',
                        'desc' => 'La dirección de origen de los pedidos',
                        'options' => array(
                            'query' => $select_address_options,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Valor de envío a sucursal ($)'),
                        'name' => 'ENVIOPACK_BRANCH_PRICE',
                        'desc' => 'En caso de dejar vacío, el valor predeterminado será $120',
                        'class' => 'fixed-width-xl',
                        'required' => false
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Estado'),
                        'name' => 'ENVIOPACK_DEF_STATE',
                        'desc' => 'Estado que será asignado al pedido una vez que haya sido procesado por EnvioPack',
                        'options' => array(
                            'query' => $status_list,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Estado pagado'),
                        'name' => 'ENVIOPACK_PAID_STATE',
                        'desc' => 'Estado que se utiliza para indicar que un pedido se encuentra pago y listo para ser enviado',
                        'options' => array(
                            'query' => $status_list,
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                        'required' => true
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->module->l('Estimacion de tamaño del paquete'),
                        'name' => 'ENVIOPACK_PACKET_ESTIMATION_METHOD',
                        'desc' => 'Método de estimación usada para el armado del paquete en base a las dimensiones de los productos',
                        'options' => array(
                            'query' => $packet_estimation_options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                        'required' => true
                    )
                ),
                'submit' => array(
                    'name' => 'enviopackGeneral',
                    'title' => $this->module->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        if ($default_packet_select) {
            $form['form']['input'][] = $default_packet_select;
        }

        return $form;
    }

    private function getURLSite()
    {
        $url = Tools::htmlentitiesutf8(
            ((bool)Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://')
                . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__
        );
        return $url;
    }

    /**
     * Este metodo guarda los valores default la primera vez cuando no estan inicializados
     */
    private function saveDefaultConfigValues($config_options)
    {
        $enviopack = $this->module;
        $protocol_link = (Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()) ? 'https://' : 'http://';
        $useSSL = ((Configuration::get('PS_SSL_ENABLED')) || Tools::usingSecureMode()) ? true : false;
        $protocol_content = ($useSSL) ? 'https://' : 'http://';
        $link = new Link($protocol_link, $protocol_content);

        if (!Configuration::get('ENVIOPACK_HOOK_URL')) {
            $ipn_url = $link->getModuleLink(
                'enviopack',
                'notification',
                array(),
                null,
                null,
                Configuration::get('PS_SHOP_DEFAULT')
            );
            Configuration::updateValue('ENVIOPACK_HOOK_URL', $ipn_url);
        }

        if (!Configuration::get('ENVIOPACK_SOURCEADDR')) {
            foreach ($config_options['ENVIOPACK_SOURCEADDR'] as $values) {
                if ($values['id_option'] != 0) {
                    // Asigno la primer direccin de origen que tenga cargada
                    Configuration::updateValue('ENVIOPACK_SOURCEADDR', $values['id_option']);
                    break;
                }
            }
        }
        if (!Configuration::get('ENVIOPACK_BRANCH_PRICE')) {
            Configuration::updateValue('ENVIOPACK_BRANCH_PRICE', 120);
        }
        if (!Configuration::get('ENVIOPACK_DEF_STATE')) {
            foreach ($config_options['ENVIOPACK_DEF_STATE'] as $values) {
                if ($values['id_option'] == 3) {
                    // Preparación en proceso
                    Configuration::updateValue('ENVIOPACK_DEF_STATE', 3);
                    break;
                }
            }
        }
        if (!Configuration::get('ENVIOPACK_PAID_STATE')) {
            foreach ($config_options['ENVIOPACK_PAID_STATE'] as $values) {
                if ($values['id_option'] == 2) {
                    // Pago aceptado
                    Configuration::updateValue('ENVIOPACK_PAID_STATE', 2);
                    break;
                }
            }
        }

        if (!Configuration::get('ENVIOPACK_PACKET_ESTIMATION_METHOD')) {
            Configuration::updateValue('ENVIOPACK_PACKET_ESTIMATION_METHOD', self::ESTIMATION_SUM_DIMS);
        }
    }

    private function process_config()
    {
        $output = null;

        $default_state = strval(Tools::getValue('ENVIOPACK_DEF_STATE'));
        $paid_state = strval(Tools::getValue('ENVIOPACK_PAID_STATE'));
        $source_addr = strval(Tools::getValue('ENVIOPACK_SOURCEADDR'));
        $packet_estimation = strval(Tools::getValue('ENVIOPACK_PACKET_ESTIMATION_METHOD'));
        $packet_default = strval(Tools::getValue('ENVIOPACK_PACKET_ESTIMATION_DEFAULT'));
        $branch_price = strval(Tools::getValue('ENVIOPACK_BRANCH_PRICE'));


        if (empty($source_addr) || $source_addr == 0) {
            $output .= $this->module->displayError($this->module->l('Debe elegir una dirección de origen'));
        } elseif (!$default_state) {
            $output .= $this->module->displayError($this->module->l('Debe elegir un estado para los pedidos procesados por EnvioPack'));
        } elseif (!$paid_state) {
            $output .= $this->module->displayError($this->module->l('Debe elegir un estado para indicar que el pedido está pagado y listo para ser enviado'));
        } elseif ($default_state == $paid_state) {
            $output .= $this->module->displayError($this->module->l('El estado pagado no debe ser igual al estado de pedidos procesados por EnvioPack'));
        } elseif ($packet_estimation == self::ESTIMATION_DEFAULT_PACKET && !$packet_default) {
            $output .= $this->module->displayError($this->module->l('Debe elegir el tamaño del paquete para estimar con la modalidad "Paquete default"'));
        } elseif (!preg_match('/^\d*$/', $branch_price, $res)) {
            $output .= $this->module->displayError($this->module->l('Debe ingresar solo caracteres numéricos para el valor de envío a sucursal'));
        } else {
            Configuration::updateValue('ENVIOPACK_DEF_STATE', $default_state);
            Configuration::updateValue('ENVIOPACK_PAID_STATE', $paid_state);
            Configuration::updateValue('ENVIOPACK_SOURCEADDR', $source_addr);
            Configuration::updateValue('ENVIOPACK_PACKET_ESTIMATION_METHOD', $packet_estimation);
            Configuration::updateValue('ENVIOPACK_PACKET_ESTIMATION_DEFAULT', $packet_default);
            Configuration::updateValue('ENVIOPACK_BRANCH_PRICE', $branch_price);
        }

        return $output;
    }

    private function auth_form()
    {
        $form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->module->l('Datos de acceso'),
                    'icon' => 'icon-lock'
                ),
                'description' => $this->module->l('Para configurar tus preferencias de envío ingresá a tu cuenta de EnvíoPack haciendo ') .
                    '<a href="https://app.enviopack.com/correos-y-tarifas" target="_new">click aquí</a>',
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('API Key'),
                        'name' => 'ENVIOPACK_APIKEY',
                        'size' => 40,
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Secret Key'),
                        'name' => 'ENVIOPACK_SECRETKEY',
                        'size' => 40,
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'name' => 'enviopackAuth',
                    'title' => $this->module->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        return $form;
    }

    private function process_auth()
    {
        $output = null;

        $api_key = strval(Tools::getValue('ENVIOPACK_APIKEY'));
        $secret_key = strval(Tools::getValue('ENVIOPACK_SECRETKEY'));

        if (!$api_key || empty($api_key) || !Validate::isGenericName($api_key) || !$secret_key
            || empty($secret_key) || !Validate::isGenericName($secret_key)) {

            $output .= $this->module->displayError($this->module->l('Los datos proporcionados no son válidos'));
        } else {
            Configuration::updateValue('ENVIOPACK_APIKEY', $api_key);
            Configuration::updateValue('ENVIOPACK_SECRETKEY', $secret_key);

            $this->module->EpackApi->setApiKey($api_key);
            $this->module->EpackApi->setSecretKey($secret_key);

            if ($this->module->EpackApi->credentialsCheck()) {

                $output .= $this->module->displayConfirmation($this->module->l('Credenciales guardadas correctamente'));

                $this->module->EpackApi->set_config($_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . "enviopack/ajax.php?method=webhook");

                $dbinstance = Db::getInstance();
                $results = $dbinstance->ExecuteS("SELECT `id_local_carrier` FROM `" . _DB_PREFIX_ . "enviopack_carrier` WHERE `modality` = 'D'");
                foreach ($results as $row) {
                    $id_local_carrier = $row['id_local_carrier'];
                    $dbinstance->delete("carrier", "`id_carrier` = '$id_local_carrier'", 1);
                    $dbinstance->delete("enviopack_carrier", "`id_local_carrier` = '$id_local_carrier'", 1);
                }
                
                // Desscargo la lista de correos habilitados en EnvioPack
                $carriers_list = $this->module->EpackApi->getCorreos();
                $this->EpackCarrierManager->install_remote_carriers($carriers_list);

                // Creo los carriers Genericos
                $generic_carriers = array(
                    array('ps_id' => '', 'remote_id' => '', 'has_relay' => 0, 'service' => 'N', 'modality' => 'D', 'name' => '', 'active' => 0),
                    array('ps_id' => '', 'remote_id' => '', 'has_relay' => 0, 'service' => 'P', 'modality' => 'D', 'name' => '', 'active' => 0),
                    array('ps_id' => '', 'remote_id' => '', 'has_relay' => 0, 'service' => 'X', 'modality' => 'D', 'name' => '', 'active' => 0),
                );

                // Instalo el carrier de sucursales que está siempre visible
                $ps_carrier_id = 0;

                if (!$this->EpackCarrierManager->get_generic_carrier_id('N', 'S')) {
                    $ps_carrier_id = $this->EpackCarrierManager->add_ps_carrier(
                        "Envio a sucursal",
                        $this->module->name,
                        "Elija una sucursal para retirar su pedido"
                    );

                    $generic_carriers[] = array(
                        'ps_id' => $ps_carrier_id,
                        'remote_id' => '',
                        'has_relay' => 1,
                        'service' => 'N',
                        'modality' => 'S',
                        'name' => '',
                        'active' => 1
                    );
                }

                foreach ($generic_carriers as $generic_carrier) {
                    if (!$this->EpackCarrierManager->get_generic_carrier_id($generic_carrier['service'], $generic_carrier['modality'])) {
                        $this->EpackCarrierManager->add_ep_carrier(
                            $generic_carrier['ps_id'],
                            $generic_carrier['remote_id'],
                            $generic_carrier['has_relay'],
                            $generic_carrier['service'],
                            $generic_carrier['modality'],
                            $generic_carrier['name'],
                            $generic_carrier['active']
                        );
                    }
                }
                
                /* if ($ps_carrier_id > 0) {
                    $this->EpackCarrierManager->copy_logo(dirname(__FILE__) . '/../sucursal.png', $ps_carrier_id, ".jpg");
                    $relay_points = $this->module->EpackApi->getSucursales();
                    $this->EpackRelayManager->install_relay_points($relay_points, $ps_carrier_id);
                } */

                $this->EpackCarrierManager->active_generic_carriers();

                $source_address_list = $this->module->EpackApi->getSourceAddress();
                Configuration::updateValue('ENVIOPACK_SOURCEADDR', $source_address_list[0]['id']);

                /* if (!$this->installTab('AdminParentOrders', 'AdminEnviopack', 'EnvioPack - Envios'))
                    return false; */

            } else {
                $output .= $this->module->displayError($this->module->l('Los datos proporcionados no son válidos'));
            }
        }

        return $output;
    }

    private function fail_config()
    {
        // Guardo valores default de los parametros de peso y dimensiones
        if (!Configuration::get('ENVIOPACK_DEF_WEIGHT')) {
            Configuration::updateValue('ENVIOPACK_DEF_WEIGHT', 2); //2Kg default
        }
        $dim_configs = array('ENVIOPACK_DEF_HEIGHT', 'ENVIOPACK_DEF_WIDTH', 'ENVIOPACK_DEF_DEPTH');
        foreach ($dim_configs as $name) {
            if (!Configuration::get($name)) {
                Configuration::updateValue($name, 50); // 50 cm por default en cada dimensions
            }
        }

        $form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->module->l('Cofiguración ante fallas')
                ),
                'description' => $this->module->l('Configure los valores que serán utilizados en caso de fallas en la configuración de los productos.
                    Recuerde que para lograr una correcta cotización del servicio de envío es muy importante que todos los productos estén correctamente configurados.
                '),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Peso'),
                        'name' => 'ENVIOPACK_DEF_WEIGHT',
                        'size' => 40,
                        'desc' => $this->module->l('Peso que se usará para productos que no tengan peso registrado en la tienda, en kg'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Alto'),
                        'name' => 'ENVIOPACK_DEF_HEIGHT',
                        'size' => 40,
                        'desc' => $this->module->l('Alto que se usará para productos que no tengan medidas registradas, en cm.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Ancho'),
                        'name' => 'ENVIOPACK_DEF_WIDTH',
                        'size' => 40,
                        'desc' => $this->module->l('Ancho que se usará para productos que no tengan medidas registradas, en cm.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Largo'),
                        'name' => 'ENVIOPACK_DEF_DEPTH',
                        'size' => 40,
                        'desc' => $this->module->l('Largo que se usará para productos que no tengan medidas registradas, en cm.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->module->l('Precio'),
                        'name' => 'ENVIOPACK_DEF_PRICE',
                        'size' => 40,
                        'desc' => $this->module->l('Costo de envío que se usará en el poco probable caso que el servicio de Enviopack esté caído y no podamos obtener una cotización online'),
                        'required' => true
                    ),
                ),
                'submit' => array(
                    'name' => 'enviopackFail',
                    'title' => $this->module->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        return $form;
    }

    private function process_fail()
    {
        $output = null;

        $default_weight = strval(Tools::getValue('ENVIOPACK_DEF_WEIGHT'));
        $default_depth = strval(Tools::getValue('ENVIOPACK_DEF_DEPTH'));
        $default_width = strval(Tools::getValue('ENVIOPACK_DEF_WIDTH'));
        $default_height = strval(Tools::getValue('ENVIOPACK_DEF_HEIGHT'));
        $default_price = strval(Tools::getValue('ENVIOPACK_DEF_PRICE'));

        if (!$default_price || !$default_weight || !$default_depth || !$default_width || !$default_height) {
            $output .= $this->module->displayError($this->module->l('Los datos proporcionados no son válidos'));
        } else {
            Configuration::updateValue('ENVIOPACK_DEF_WEIGHT', $default_weight);
            Configuration::updateValue('ENVIOPACK_DEF_DEPTH', $default_depth);
            Configuration::updateValue('ENVIOPACK_DEF_WIDTH', $default_width);
            Configuration::updateValue('ENVIOPACK_DEF_HEIGHT', $default_height);
            Configuration::updateValue('ENVIOPACK_DEF_PRICE', $default_price);

            $output .= $this->module->displayConfirmation($this->module->l('La configuración fue guardada satisfactoriamente'));
        }
        return $output;
    }

    public function installTab($parent, $class_name, $name)
    {
        if (!Configuration::get('ENVIOPACK_TABINSTALLED')) {
            $tab = new Tab();
            $tab->id_parent = (int)Tab::getIdFromClassName($parent);
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang)
                $tab->name[$lang['id_lang']] = $name;
            $tab->class_name = $class_name;
            $tab->module = 'enviopack';
            $tab->active = 1;

            $all_tabs = $tab->getTabs(false, $tab->id_parent);
            foreach ($all_tabs as $index => $value) {
                if ($value['class_name'] === $class_name) {
                    Configuration::updateValue('ENVIOPACK_TABINSTALLED', true);
                    break;
                }
            }

            $dbinstance = Db::getInstance();
            $dbinstance->delete("authorization_role", "`slug` = 'ROLE_MOD_TAB_ADMINENVIOPACK_CREATE'", 1);
            $dbinstance->delete("authorization_role", "`slug` = 'ROLE_MOD_TAB_ADMINENVIOPACK_DELETE'", 1);
            $dbinstance->delete("authorization_role", "`slug` = 'ROLE_MOD_TAB_ADMINENVIOPACK_READ'", 1);
            $dbinstance->delete("authorization_role", "`slug` = 'ROLE_MOD_TAB_ADMINENVIOPACK_UPDATE'", 1);

            if (!Configuration::get('ENVIOPACK_TABINSTALLED')) {
                if (!$tab->save()) {
                    return false;
                } else {
                    Configuration::updateValue('ENVIOPACK_TABINSTALLED', true);
                }
            }
        }
        return true;
    }
}