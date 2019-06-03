<?php

/**
* 
*/
class AdminCarriersController extends AdminCarriersControllerCore
{
	/*
	Override de la logica del update para que no cree un carrier nuevo sino que modifique el existente. 
	La lógica es la practicamente la misma que la de AdminCarriersControllerCore

	 */
	public function postProcess()
    {
        if (Tools::getValue('action') == 'GetModuleQuickView' && Tools::getValue('ajax') == '1') {
            $this->ajaxProcessGetModuleQuickView();
        }
        if (Tools::getValue('submitAdd'.$this->table)) {
            /* Checking fields validity */
            $this->validateRules();
            if (!count($this->errors)) {
                $id = (int)Tools::getValue('id_'.$this->table);

                /* Object update */
                if (isset($id) && !empty($id)) {
                    try {
                        if ($this->tabAccess['edit'] === '1') {
                            $current_carrier = new Carrier($id);
                            if (!Validate::isLoadedObject($current_carrier)) {
                                throw new PrestaShopException('Cannot load Carrier object');
                            }

                            /** @var Carrier $new_carrier */
                            // Duplicate current Carrier
                            //$new_carrier = $current_carrier->duplicateObject();
                            if (Validate::isLoadedObject($current_carrier)) {
                                // Set flag deteled to true for historization
                                //$current_carrier->deleted = true;
                                $current_carrier->update();

                                /*// Fill the new carrier object
                                $this->copyFromPost($new_carrier, $this->table);
                                $new_carrier->position = $current_carrier->position;
                                $new_carrier->update();

                                $this->updateAssoShop($new_carrier->id);
                                $new_carrier->copyCarrierData((int)$current_carrier->id);
                                $this->changeGroups($new_carrier->id);
                                // Call of hooks
                                Hook::exec('actionCarrierUpdate', array(
                                    'id_carrier' => (int)$current_carrier->id,
                                    'carrier' => $new_carrier
                                ));
                                $this->postImage($new_carrier->id);
                                $this->changeZones($new_carrier->id);
                                $new_carrier->setTaxRulesGroup((int)Tools::getValue('id_tax_rules_group'));*/
                                Tools::redirectAdmin(self::$currentIndex.'&id_'.$this->table.'='.$current_carrier->id.'&conf=4&token='.$this->token);
                            } else {
                                $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b>';
                            }
                        } else {
                            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
                        }
                    } catch (PrestaShopException $e) {
                        $this->errors[] = $e->getMessage();
                    }
                }

                /* Object creation */
                else {
                    if ($this->tabAccess['add'] === '1') {
                        // Create new Carrier
                        $carrier = new Carrier();
                        $this->copyFromPost($carrier, $this->table);
                        $carrier->position = Carrier::getHigherPosition() + 1;
                        if ($carrier->add()) {
                            if (($_POST['id_'.$this->table] = $carrier->id /* voluntary */) && $this->postImage($carrier->id) && $this->_redirect) {
                                $carrier->setTaxRulesGroup((int)Tools::getValue('id_tax_rules_group'), true);
                                $this->changeZones($carrier->id);
                                $this->changeGroups($carrier->id);
                                $this->updateAssoShop($carrier->id);
                                Tools::redirectAdmin(self::$currentIndex.'&id_'.$this->table.'='.$carrier->id.'&conf=3&token='.$this->token);
                            }
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while creating an object.').' <b>'.$this->table.'</b>';
                        }
                    } else {
                        $this->errors[] = Tools::displayError('You do not have permission to add this.');
                    }
                }
            }
            parent::postProcess();
        }
        /*
elseif ((isset($_GET['status'.$this->table]) || isset($_GET['status'])) && Tools::getValue($this->identifier))
        {
            if ($this->tabAccess['edit'] === '1')
            {
                if (Tools::getValue('id_carrier') == Configuration::get('PS_CARRIER_DEFAULT'))
                    $this->errors[] = Tools::displayError('You cannot disable the default carrier, however you can change your default carrier. ');
                else
                    parent::postProcess();
            }
            else
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }
*/
        elseif (isset($_GET['isFree'.$this->table])) {
            $this->processIsFree();
        } else {
            /*
    if ((Tools::isSubmit('submitDel'.$this->table) && in_array(Configuration::get('PS_CARRIER_DEFAULT'), Tools::getValue('carrierBox')))
                || (isset($_GET['delete'.$this->table]) && Tools::getValue('id_carrier') == Configuration::get('PS_CARRIER_DEFAULT')))
                    $this->errors[] = $this->l('Please set another carrier as default before deleting this one.');
            else
            {
*/
                // if deletion : removes the carrier from the warehouse/carrier association
                if (Tools::isSubmit('delete'.$this->table)) {
                    $id = (int)Tools::getValue('id_'.$this->table);
                    // Delete from the reference_id and not from the carrier id
                    $carrier = new Carrier((int)$id);
                    Warehouse::removeCarrier($carrier->id_reference);
                } elseif (Tools::isSubmit($this->table.'Box') && count(Tools::isSubmit($this->table.'Box')) > 0) {
                    $ids = Tools::getValue($this->table.'Box');
                    array_walk($ids, 'intval');
                    foreach ($ids as $id) {
                        // Delete from the reference_id and not from the carrier id
                        $carrier = new Carrier((int)$id);
                        Warehouse::removeCarrier($carrier->id_reference);
                    }
                }
            parent::postProcess();
            Carrier::cleanPositions();
            //}
        }
    }
}