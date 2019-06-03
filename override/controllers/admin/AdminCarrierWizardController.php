<?php
class AdminCarrierWizardController extends AdminCarrierWizardControllerCore
{
 
    /*
     *  Override del edit de carriers para que al editar, solamente edite y no cree un carrier nuevo.
     */
    public function ajaxProcessFinishStep()
    {
        $return = array('has_error' => false);
        if (!$this->tabAccess['edit']) {
            $return = array(
                'has_error' =>  true,
                $return['errors'][] = Tools::displayError('You do not have permission to use this wizard.')
            );
        } else {
            $this->validateForm(false);
            if ($id_carrier = Tools::getValue('id_carrier')) {
                $current_carrier = new Carrier((int)$id_carrier);
                
                if (Validate::isLoadedObject($current_carrier)) {
                    $this->copyFromPost($current_carrier, $this->table);
                    $current_carrier->update();
                    $carrier = $current_carrier;
                }
            } else {
                $carrier = new Carrier();
                $this->copyFromPost($carrier, $this->table);
                if (!$carrier->add()) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving this carrier.');
                }
            }
            if ($carrier->is_free) {
                $carrier->deleteDeliveryPrice('range_weight');
                $carrier->deleteDeliveryPrice('range_price');
            }
            if (Validate::isLoadedObject($carrier)) {
                if (!$this->changeGroups((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving carrier groups.');
                }
                if (!$this->changeZones((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving carrier zones.');
                }
                if (!$carrier->is_free) {
                    if (!$this->processRanges((int)$carrier->id)) {
                        $return['has_error'] = true;
                        $return['errors'][] = $this->l('An error occurred while saving carrier ranges.');
                    }
                }
                if (Shop::isFeatureActive() && !$this->updateAssoShop((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving associations of shops.');
                }
                if (!$carrier->setTaxRulesGroup((int)Tools::getValue('id_tax_rules_group'))) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving the tax rules group.');
                }
                if (Tools::getValue('logo')) {
                    if (Tools::getValue('logo') == 'null' && file_exists(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg')) {
                        unlink(_PS_SHIP_IMG_DIR_.$carrier->id.'.jpg');
                    } else {
                        $logo = basename(Tools::getValue('logo'));
                        if (!file_exists(_PS_TMP_IMG_DIR_.$logo) || !copy(_PS_TMP_IMG_DIR_.$logo, _PS_SHIP_IMG_DIR_.$carrier->id.'.jpg')) {
                            $return['has_error'] = true;
                            $return['errors'][] = $this->l('An error occurred while saving carrier logo.');
                        }
                    }
                }
                $return['id_carrier'] = $carrier->id;
            }
        }
        die(Tools::jsonEncode($return));
    }
}
