<?php

/**
 * @package   Devhh_PriceCalc
 * @author    Osiozekhai Aliu <aliu@dev-hh.de>
 * @copyright 2018 Devhh
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class Sam_PriceCalc_Block_Content
 */
class Devhh_PriceCalc_Block_Content extends Mage_Core_Block_Template
{
    /** @var int */
    protected $productId;

    /** @var int */
    protected $currentQty = 1;

    /** @var Mage_Catalog_Model_Product */
    protected $productModel;

    protected $totalSumOptions;
    protected $optionPrice;

    /** @var array */
    protected $options;

    /**
     * Sam_PriceCalc_Block_Content constructor.
     */
    public function __construct()
    {
        $this->currentQty = (int)$this->getRequest()->getParam('qty');
        $this->productId = (int)$this->getRequest()->getParam('product');
        $this->options = (array)$this->getRequest()->getParam('options');
        $this->productModel = Mage::getModel('catalog/product');
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return $this->productModel->load($this->productId);
    }

    /**
     * Returns Product Quantity
     *
     * @return int
     */
    public function getCurrentQty()
    {
        return $this->currentQty;
    }

    public function getProductFinalPrice()
    {
        if ($this->getTierPrice() || $this->getProductSpecialPriceForQty()) {
            if ($this->getProductSpecialPriceForQty() < ($this->getTierPrice() * $this->getCurrentQty())) {
                if ($this->getProductSpecialPriceForQty() == 0) {
                    $finalPrice = $this->getTierPrice() * $this->getCurrentQty();
                } else {
                    $finalPrice = $this->getProductSpecialPriceForQty();
                }
            } else {
                $finalPrice = $this->getTierPrice() * $this->getCurrentQty();
            }
        } else {
            $finalPrice = $this->getProduct()->getFinalPrice($this->getCurrentQty());
        }

        return $finalPrice;
    }

    public function getProductSpecialPriceForQty()
    {
        return $this->getProduct()->getSpecialPrice() * $this->getCurrentQty();
    }

    public function getTaxPercent()
    {
        $store = Mage::app()->getStore(Mage::app()->getStore()->getId());
        $taxCalculation = Mage::getModel('tax/calculation');
        $request = $taxCalculation->getRateRequest(null, null, null, $store);
        $taxClassId = $this->getProduct()->getTaxClassId();
        $percent = $taxCalculation->getRate(
            $request->setProductClassId($taxClassId)
        );

        return $percent;
    }

    public function getOptionQuantity($optionId = 0, $value = 0)
    {
        $result = $this->getCurrentQty();
        if ($this->getRequest()->getParam("options_{$optionId}_{$value}_qty")) {
            $result = $this->getRequest()->getParam("options_{$optionId}_{$value}_qty") * $this->getCurrentQty();
        }

        return $result;
    }

    public function getPrice($price)
    {
        $this->totalSumOptions += $price;
        return $this->getCurrentQty() * $price;
    }

    public function getOptionsItems()
    {

        $result = array();
        if (!$this->options) {
            return $result;
        }

        foreach ($this->getSelectedOpts($this->options) as $selectedOpt) {
            if (is_numeric($selectedOpt['value'])) {
                $result[] = $this->getProduct()
                    ->getProductOptionsCollection()
                    ->getItemById($selectedOpt['option_id'])
                    ->getValues()[$selectedOpt['value']];
            } elseif (is_array($selectedOpt['value'])) {
                foreach ($selectedOpt['value'] as $value) {
                    $result[] = $this->getProduct()
                        ->getProductOptionsCollection()
                        ->getItemById($selectedOpt['option_id'])
                        ->getValues()[$value];
                }
            }
        }

        return $result;
    }

    /**
     * @param $options
     * @return array
     */
    protected function getSelectedOpts(array $options)
    {
        $result = array();

        foreach ($options as $option => $value) {
            if ($value) {
                $result[] = ['option_id' => $option, 'value' => $value];
            }
        }

        return $result;
    }

    /**
     * @return float
     */
    public function getOptionsPrice()
    {
        $selectedOpts = array();
        foreach ($this->options as $option => $value) {
            if ($value) {
                $selectedOpts[] = ['option_id' => $option, 'value' => $value];
            } elseif (is_array($value)) {
                foreach ($value as $newValue) {
                    $selectedOpts[] = ['option_id' => $option, 'value' => $newValue];
                }
            }
        }

        return $this->getTotalOptionsPrice($selectedOpts);
    }

    /**
     * Get Total Price for selected Options
     *
     * @param array $selectedOpts selected Options
     *
     * @return float
     */
    protected function getTotalOptionsPrice(array $selectedOpts)
    {
        $totalOptionsPrice = 0;

        foreach ($selectedOpts as $selectedOpt) {
            if (is_numeric($selectedOpt['value'])) {
                $totalOptionsPrice += (float)$this->getProductOptionsPrice(
                    $selectedOpt['option_id'], $selectedOpt['value']
                );
            } elseif (is_array($selectedOpt['value'])) {
                foreach ($selectedOpt['value'] as $value) {
                    $totalOptionsPrice += (float)$this->getProductOptionsPrice(
                        $selectedOpt['option_id'], $value
                    );
                }
            }
        }

        return $totalOptionsPrice;
    }

    /**
     * @param $optionId
     * @param $value
     * @return mixed
     * @throws Exception
     */
    public function getProductOptionsPrice($optionId, $value)
    {
        $optionValue = $this->getProduct()
            ->getProductOptionsCollection()
            ->getItemById($optionId)
            ->getValues()[$value];

        if ($optionValue->getTiers()) {
            $result = $this->getOptionTierPrice($optionValue, $optionId, $value);
        } else {
            $result = $optionValue->getPrice();
        }

        return $result;
    }

    /**
     * @param $optionValue
     * @param $optionId
     * @param $value
     * @return mixed
     * @throws Exception
     */
    protected function getOptionTierPrice($optionValue, $optionId, $value)
    {
        $result = false;
        foreach ($optionValue->getTiers() as $tier) {
            if ($tier['qty'] <= $this->getOptionQuantity($optionId, $value)) {
                $result = $tier['price'];
            }
        }

        if (!$result) {
            $result = $optionValue->getPrice();
        }

        return $result;
    }

    /**
     * @return mixed
     */
    protected function getTierPrice()
    {
        return $this->getProduct()->getTierPrice($this->getCurrentQty());
    }

}