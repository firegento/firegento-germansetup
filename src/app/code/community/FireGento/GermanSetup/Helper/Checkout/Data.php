<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_GermanSetup is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * PHP version 5
 *
 * @category  FireGento
 * @package   FireGento_GermanSetup
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
/**
 * Rewrite to fetch required agreement ids.
 *
 * @category  FireGento
 * @package   FireGento_GermanSetup
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.1.0
 */
class FireGento_GermanSetup_Helper_Checkout_Data
    extends Mage_Checkout_Helper_Data
{
    /**
     * get all Required Agreement Ids
     *
     * @return array Agreement Ids
     **/
    public function getRequiredAgreementIds()
    {
        if (is_null($this->_agreements)) {
            if (!Mage::getStoreConfigFlag('checkout/options/enable_agreements')) {
                $this->_agreements = array();
            } else {
                $this->_agreements = Mage::getModel('checkout/agreement')->getCollection()
                    ->addStoreFilter(Mage::app()->getStore()->getId())
                    ->addFieldToFilter('is_active', 1)
                    ->addFieldToFilter('is_required', 1) // Only get Required Elements
                    ->getAllIds();
            }
        }

        return $this->_agreements;
    }

    /**
     * Get all attributes which are marked as is_visible_on_checkout
     *
     * @return Mage_Eav_Model_Resource_Attribute_Collection
     */
    public function getAddtionalAttributes()
    {
        /* @var $checkoutAttributes Mage_Eav_Model_Resource_Attribute_Collection */
        $checkoutAttributes = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
        $checkoutAttributes->addFieldToFilter('is_visible_on_checkout', 1);
        return $checkoutAttributes;
    }

    /**
     * Get the attribute frontend value.
     *
     * @param $_item Mage_Sales_Model_Quote_Item
     * @param $_attribute Mage_Eav_Model_Attribute
     * @return mixed
     */
    public function getAttributeValue($_item, $_attribute)
    {
        $attribute_value = "";
        switch ($_attribute->getFrontendInput()) {
            case 'select':
            case 'multiselect':
                $attribute_value = $_item->getProduct()->getAttributeText($_attribute->getAttributeCode());
                break;
            default:
                $attribute_value = $_item->getProduct()->getData($_attribute->getAttributeCode());
                break;
        }
        return $attribute_value;
    }
}
