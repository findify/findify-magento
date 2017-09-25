<?php
class Datalay_FindifyFeed_Model_Configurable_Price extends Mage_Catalog_Block_Product_View_Type_Configurable
{
    /**
     * gets simple products prices
     *
     * @return string
     */
    public function getPriceInfo($store) {
        $options = array();
        $currentProduct = $this->getProduct();
        $_product_prices_info = array();
        $productStock = array();
        foreach ($this->getAllowProducts() as $product) {
            $productId = $product->getId();
            $productStock[$productId] = $product->getStockItem()->getIsInStock();
            $_product_prices_info[$product->getId()] = 0;
            foreach ($this->getAllowAttributes() as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());
                if (!isset($options[$productAttributeId])) {
                    $options[$productAttributeId] = array();
                }
                if (!isset($options[$productAttributeId][$attributeValue])) {
                    $options[$productAttributeId][$attributeValue] = array();
                }
                $options[$productAttributeId][$attributeValue][] = $productId;
            }
        }
        $this->_resPrices = array(
            $this->_preparePrice($currentProduct->getFinalPrice())
        );
        foreach ($this->getAllowAttributes() as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $attributeId = $productAttribute->getId();
            $optionPrices = array();
            $prices = $attribute->getPrices();
            if (is_array($prices)) {
                foreach ($prices as $value) {
                    if (!$this->_validateAttributeValue($attributeId, $value, $options)) {
                        continue;
                    }
                    $currentProduct->setConfigurablePrice(
                        $this->_preparePrice($value['pricing_value'], $value['is_percent'])
                    );
                    $currentProduct->setParentId(true);
                    $configurablePrice = $currentProduct->getConfigurablePrice();
                    if (isset($options[$attributeId][$value['value_index']])) {
                        $productsIndexOptions = $options[$attributeId][$value['value_index']];
                        $productsIndex = array();
                        foreach ($productsIndexOptions as $productIndex) {
                            if ($productStock[$productIndex]) {
                                $productsIndex[] = $productIndex;
                            }
                        }
                    } else {
                        $productsIndex = array();
                    }
                    foreach ($productsIndex as $_simple_product_id) {
                        if (isset($_product_prices_info[$_simple_product_id])) {
                            $_product_prices_info[$_simple_product_id]+= $configurablePrice;
                        }
                    }
                    $optionPrices[] = $configurablePrice;
                }
            }
            /**
             * Prepare formated values for options choose
             */
            foreach ($optionPrices as $optionPrice) {
                foreach ($optionPrices as $additional) {
                    $this->_preparePrice(abs($additional - $optionPrice));
                }
            }
        }
        foreach ($_product_prices_info as $_id => $_price) {
            $_product_prices_info[$_id] = $_price + $this->_convertPrice($currentProduct->getFinalPrice());
        }
        return $_product_prices_info;
    }
}
