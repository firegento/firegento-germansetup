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
 * @since     0.2.0
 */
/**
 * Observer class
 *
 * @category  FireGento
 * @package   FireGento_GermanSetup
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2012 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version   $Id:$
 * @since     0.2.0
 */
class FireGento_GermanSetup_Model_Observer
{
    /**
     * Add "Visible on Checkout Review on Front-end" Option to Attribute Settings
     *
     * @param Varien_Event_Observer $observer Observer
     * @event adminhtml_catalog_product_attribute_edit_prepare_form
     * @return FireGento_GermanSetup_Model_Observer
     */
    public function addIsVisibleOnCheckoutOption(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $form = $event->getForm();

        $fieldset = $form->getElement('front_fieldset');
        $source = Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray();
        $fieldset->addField(
            'is_visible_on_checkout',
            'select',
            array(
                'name' => 'is_visible_on_checkout',
                'label' => Mage::helper('germansetup')->__('Visible in Checkout'),
                'title' => Mage::helper('germansetup')->__('Visible in Checkout'),
                'values' => $source,
            )
        );

        return $this;
    }

    /**
     * Filters all agreements
     *
     * Filters all agreements against the Magento template filter. This enables the Magento
     * administrator define a cms static block as the content of the checkout agreements..
     *
     * @param Varien_Event_Observer $observer Observer
     * @event core_block_abstract_to_html_before
     * @return FireGento_GermanSetup_Model_Observer Self.
     */
    public function filterAgreements(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block->getType() == 'checkout/agreements') {
            if ($agreements = $block->getAgreements()) {
                $collection = new Varien_Data_Collection();
                foreach ($agreements as $agreement) {
                    $agreement->setData('content', $this->_filterString($agreement->getData('content')));
                    $agreement->setData('checkbox_text', $this->_filterString($agreement->getData('checkbox_text')));
                    $collection->addItem($agreement);
                }
                $observer->getEvent()->getBlock()->setAgreements($collection);
            }
        }

        return $this;
    }

    /**
     * Calls the Magento template filter to transform {{block type="cms/block" block_id="xyz"}}
     * into the specific html code
     *
     * @param  string $string Agreement to filter
     * @return string Processed String
     */
    protected function _filterString($string)
    {
        $processor = Mage::getModel('cms/template_filter');
        $string = $processor->filter($string);

        return $string;
    }

    /**
     * Auto-Generates the meta information of a product.
     *
     * @param Varien_Event_Observer $observer Observer
     * @event catalog_product_save_before
     * @return FireGento_GermanSetup_Model_Observer Self.
     */
    public function autogenerateMetaInformation(Varien_Event_Observer $observer)
    {
        /* @var $product Mage_Catalog_Model_Product */
        $product = $observer->getEvent()->getProduct();

        if ($product->getData('meta_autogenerate') == 1) {
            // Set Meta Title
            $product->setMetaTitle($product->getName());

            // Set Meta Keywords
            $keywords = $this->_getCategoryKeywords($product);
            if (!empty($keywords)) {
                if (mb_strlen($keywords) > 255) {
                    $remainder = '';
                    $keywords = Mage::helper('core/string')->truncate($keywords, 255, '', $remainder, false);
                }
                $product->setMetaKeyword($keywords);
            }

            // Set Meta Description
            $description = $product->getShortDescription();
            if (empty($description)) {
                $description = $product->getDescription();
            }
            if (empty($description)) {
                $description = $keywords;
            }
            if (mb_strlen($description) > 255) {
                $remainder = '';
                $description = Mage::helper('core/string')->truncate($description, 255, '...', $remainder, false);
            }
            $product->setMetaDescription($description);
        }

        return $this;
    }

    /**
     * Get the categories of the current product
     *
     * @param  Mage_Catalog_Model_Product $product Product
     * @return string                              Keywords
     */
    protected function _getCategoryKeywords($product)
    {
        $categories = $product->getCategoryIds();

        //Check for empty categories
        if (true === empty($categories)) {
            return array();
        }

        //If product categories were given fetch their names and build the keyword string
        $categoryArr = $this->_fetchCategoryNames($categories);
        $keywords = $this->_buildKeywords($categoryArr);

        return $keywords;
    }

    /**
     * Fetches all category names via category path; adds first the assigned
     * categories and second all categories via path.
     *
     * @param  array $categories Category Ids
     * @return array Categories
     */
    protected function _fetchCategoryNames($categories)
    {
        $return = array(
            'assigned' => array(),
            'path' => array()
        );

        foreach ($categories as $categoryId) {
            // Check if category was already added
            if (array_key_exists($categoryId, $return['assigned'])
                || array_key_exists($categoryId, $return['path'])
            ) {
                return;
            }

            /* @var $category Mage_Catalog_Model_Category */
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $return['assigned'][$categoryId] = $category->getName();

            // Fetch path ids and remove the first two (base and root category)
            $path = $category->getPath();
            $pathIds = explode('/', $path);
            array_shift($pathIds);
            array_shift($pathIds);

            // Fetch the names from path categories
            if (count($pathIds) > 0) {
                foreach ($pathIds as $pathId) {
                    if (!array_key_exists($pathId, $return['assigned'])
                        && !array_key_exists($pathId, $return['path'])
                    ) {
                        /* @var $pathCategory Mage_Catalog_Model_Category */
                        $pathCategory = Mage::getModel('catalog/category')->load($pathId);
                        $return['path'][$pathId] = $pathCategory->getName();
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Processes the category array and generates a string
     *
     * @param  array  $categoryTypes Categories
     * @return string Keywords
     */
    protected function _buildKeywords($categoryTypes)
    {
        $keywords = '';

        //Check for empty category types
        if (true === empty($categoryTypes)) {
            return $keywords;
        }

        foreach ($categoryTypes as $categories) {
            $keywords .= implode(', ', $categories);
        }

        return $keywords;
    }

    /**
     * Add "Required" and "Visible on Custom Creation" Option to Checkout Agreements
     *
     * @param Varien_Event_Observer $observer Observer
     * @event adminhtml_block_html_before
     * @return FireGento_GermanSetup_Model_Observer
     */
    public function addOptionsForAgreements(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Checkout_Agreement_Edit_Form) {
            $helper = Mage::helper('germansetup');
            $form = $block->getForm();

            $fieldset = $form->getElement('base_fieldset');
            $fieldset->addField('is_required', 'select', array(
                'label' => $helper->__('Required'),
                'title' => $helper->__('Required'),
                'note' => $helper->__('Display Checkbox on Frontend'),
                'name' => 'is_required',
                'required' => true,
                'options' => array(
                    '1' => $helper->__('Yes'),
                    '0' => $helper->__('No'),
                ),
            ));

            $fieldset->addField('agreement_type', 'select', array(
                'label' => $helper->__('Display on'),
                'title' => $helper->__('Display on'),
                'note' => $helper->__('Require Confirmation on Customer Registration and/or Checkout'),
                'name' => 'agreement_type',
                'required' => true,
                'options' => Mage::getSingleton('germansetup/source_agreementType')->getOptionArray(),
            ));

            Mage::dispatchEvent('germansetup_adminhtml_checkout_agreement_edit_form', array(
                'form' => $form,
                'fieldset' => $fieldset,
            ));

            $model = Mage::registry('checkout_agreement');
            $form->setValues($model->getData());
            $block->setForm($form);
        }

        return $this;
    }

    /**
     * After updating the quantities of cart items, it might be needed to recalculate the shipping tax
     *
     * @param  Varien_Event_Observer $observer
     * @event  checkout_cart_update_items_after
     * @return void
     */
    public function recollectAfterQuoteItemUpdate(Varien_Event_Observer $observer)
    {
        $store = Mage::app()->getStore();
        if (Mage::getStoreConfig(FireGento_GermanSetup_Model_Tax_Config::XML_PATH_SHIPPING_TAX_ON_PRODUCT_TAX, $store)
            == FireGento_GermanSetup_Model_Tax_Config::USE_TAX_DEPENDING_ON_PRODUCT_VALUES
        ) {
            Mage::getSingleton('checkout/session')
                ->getQuote()
                ->setTotalsCollectedFlag(false)
                ->collectTotals();
        }
    }

    /**
     * Get required agreements on custom registration
     *
     * @return array
     */
    protected function _getCustomerCreateAgreements()
    {
        $ids = Mage::getModel('checkout/agreement')->getCollection()
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('agreement_type', array('in' => array(
                FireGento_GermanSetup_Model_Source_AgreementType::AGREEMENT_TYPE_CUSTOMER,
                FireGento_GermanSetup_Model_Source_AgreementType::AGREEMENT_TYPE_BOTH,
            ))) // Only get Required Elements
            ->getAllIds();

        return $ids;
    }

    /**
     * Check if there are required agreements for the customer registration
     * and validate them if applicable.
     *
     * @param  Varien_Event_Observer $observer
     * @event  controller_action_predispatch_customer_account_createpost
     * @return void
     */
    public function customerCreatePreDispatch(Varien_Event_Observer $observer)
    {
        $requiredAgreements = $this->_getCustomerCreateAgreements();
        $controller = $observer->getEvent()->getControllerAction();
        $postedAgreements = array_keys($controller->getRequest()->getPost('agreement', array()));

        if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
            $session = Mage::getSingleton('customer/session');
            $session->addException(
                new Mage_Customer_Exception('Cannot create customer: agreements not confirmed'),
                Mage::helper('germansetup')->__('Agreements not confirmed.')
            );

            $controller->getResponse()->setRedirect( Mage::getUrl('*/*/create', array('_secure' => true)) );
            $controller->setFlag(
                $controller->getRequest()->getActionName(),
                Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH,
                true
            );
        }
    }
}
