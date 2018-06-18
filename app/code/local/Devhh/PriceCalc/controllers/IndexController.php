<?php

/**
 * @package   Devhh_PriceCalc
 * @author    Osiozekhai Aliu <aliu@dev-hh.de>
 * @copyright 2018 Devhh
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Devhh_PriceCalc_IndexController extends Mage_Core_Controller_Front_Action
{

    /**
     * @return Mage_Core_Model_Session
     */
    protected function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * @return bool|Zend_Controller_Response_Abstract
     */
    public function indexAction()
    {
        if (
            !$this->_validateFormKey() &&
            !$this->getRequest()->isXmlHttpRequest() &&
            !$this->getRequest()->isPost()
        ) {
            return false;
        }

        $block = $this->getLayout()->createBlock('price_calc/content')->setTemplate('devhh/price_calc.phtml');
        return $this->getResponse()->setBody($block->toHtml());
    }
}