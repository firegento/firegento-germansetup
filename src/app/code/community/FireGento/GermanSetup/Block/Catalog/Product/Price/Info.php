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


class FireGento_GermanSetup_Block_Catalog_Product_Price_Info extends Mage_Core_Block_Template
{
    /**
     * Return used texts
     *
     * @return array
     */
    public function getInfoTexts()
    {
        $shippingCostUrl = Mage::helper('germansetup')->getShippingCostUrl();

        $texts = array();

        if (Mage::getStoreConfigFlag('catalog/price/show_tax_info')) {
            if ($this->getIsIncludingTax() == Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX) {
                $texts['tax-details'] = $this->__('excl. %s Tax', $this->getFormattedTaxRate());
            } else {
                $texts['tax-details'] = $this->__('incl. %s Tax', $this->getFormattedTaxRate());
            }
        }

        if (!empty($shippingCostUrl) && $this->getIsShowShippingLink()) {
            if ($this->getIsIncludingShippingCosts()) {
                $texts['shipping-cost-details'] = $this->__('incl. <a href="%s">Shipping Cost</a>', $shippingCostUrl);
            } else {
                $texts['shipping-cost-details'] = $this->__('excl. <a href="%s">Shipping Cost</a>', $shippingCostUrl);
            }
        }

        return $texts;
    }

    /**
     * Return info line
     *
     * @return string
     */
    public function getInfoLine()
    {
        $texts = $this->getInfoTexts();
        $line = implode(', ', $texts);
        return ucfirst($line);
    }

}