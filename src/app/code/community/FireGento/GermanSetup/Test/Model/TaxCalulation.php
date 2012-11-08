<?php

class Firegento_Germansetup_Test_Model_TaxCalulation extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    public function setUp()
    {
        $this->assertTrue( false );
    }

    protected function setUpQuote()
    {
        $this->_quote = Mage::getModel('sales/quote');

    }

    public function testBruttopreis()
    {
        $this->fail();
    }

    public function testMwst()
    {
        $this->fail();
    }

    public function testSumme()
    {
        $this->fail();
    }


}