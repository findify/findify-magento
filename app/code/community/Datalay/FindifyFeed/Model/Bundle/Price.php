<?php

class Datalay_FindifyFeed_Model_Bundle_Price extends Mage_Bundle_Model_Product_Price
{

    public function getTotalPrices($product,
        $takeTierPrice = true) {
        $this->_isPricesCalculatedByIndex = $product->getData('min_price'); //&& $product->getData('max_price'));
        $taxHelper = $this->_getHelperData('tax');
        $includeTax = $taxHelper->displayPriceIncludingTax();

        if ($this->_isPricesCalculatedByIndex) {
            $minimalPrice = $taxHelper->getPrice($product, $product->getData('min_price'), $includeTax, null, null, null, null, null, false);
        } else {
            $isPriceFixedType = ($product->getPriceType() == self::PRICE_TYPE_FIXED);
            /**
             * Check if product price is fixed
             */
            //$product->setCustomerGroupId(0);

            $finalPrice = $product->getFinalPrice();
            if ($isPriceFixedType) {
                $minimalPrice = $taxHelper->getPrice($product, $finalPrice, $includeTax, null, null, null, null, null, false);
            } else { // PRICE_TYPE_DYNAMIC
                $minimalPrice = 0;
            }

            $min_price = $this->_getMinimalBundleOptionsPrice($product, $includeTax, $takeTierPrice);
            $minimalPrice += $min_price['minprice'];

            $customOptions = $product->getOptions();
            if ($isPriceFixedType && $customOptions) {
                foreach ($customOptions as $customOption) {
                    /* @var $customOption Mage_Catalog_Model_Product_Option */
                    $minimalPrice += $taxHelper->getPrice(
                        $product, $this->_getMinimalCustomOptionPrice($customOption), $includeTax);
                }
            }
        }

        $minimalPrice = $product->getStore()->roundPrice($minimalPrice);
        return array('minprice' => $minimalPrice, 'products' => $min_price['products']);
    }

    protected function _getCustomerGroupId($product)
    {
        return 0;
    }
    

    protected function _getMinimalBundleOptionsPrice($product,
        $includeTax,
        $takeTierPrice) {

        $options = $this->getOptions($product);
        $minimalPrice = 0;
        $minimalPriceWithTax = 0;
        $hasRequiredOptions = $this->_hasRequiredOptions($product);
        $selectionMinimalPrices = array();
        $selectionMinimalPricesWithTax = array();

        /* added to keep key */
        $_min_price_products = array();
        $_min_prices = array();

        if (!$options) {
            return $minimalPrice;
        }

        foreach ($options as $option) {
            /* @var $option Mage_Bundle_Model_Option */
            $selectionPrices = $this->_getSelectionPrices($product, $option, $takeTierPrice, $includeTax);
            $selectionPricesWithTax = $this->_getSelectionPrices($product, $option, $takeTierPrice, true);

            if (count($selectionPrices)) {
                $selectionMinPrice = is_array($selectionPrices) ? min($selectionPrices) : $selectionPrices;
                $selectMinPriceWithTax = is_array($selectionPricesWithTax) ?
                    min($selectionPricesWithTax) : $selectionPricesWithTax;

                if ($option->getRequired()) {
                    $minimalPrice += $selectionMinPrice;
                    $minimalPriceWithTax += $selectMinPriceWithTax;

                    $_k = array_keys($selectionPrices, min($selectionPrices));
                    if (count($_k))
                        $_min_price_products[] = $_k[0];
                } elseif (!$hasRequiredOptions) {
                    $selectionMinimalPrices[] = $selectionMinPrice;
                    $selectionMinimalPricesWithTax[] = $selectMinPriceWithTax;

                    $_k = array_keys($selectionPrices, min($selectionPrices));
                    if (count($_k) > 0) {
                        $_min_prices[$_k[0]] = $selectionMinPrice;
                    }
                }
            }
        }
        // condition is TRUE when all product options are NOT required
        if (!$hasRequiredOptions) {
            $minimalPrice = min($selectionMinimalPrices);
            $minimalPriceWithTax = min($selectionMinimalPricesWithTax);
            $_k = array_keys($_min_prices, min($selectionMinimalPrices));
            if (count($_k) > 0) {
                $_min_price_products[] = $_k[0]; //$minimalPrice;
            }
        }

        $taxConfig = $this->_getHelperData('tax')->getConfig();

        //In the case of total base calculation we round the tax first and
        //deduct the tax from the price including tax
        if ($taxConfig->priceIncludesTax($product->getStore()) && Mage_Tax_Model_Calculation::CALC_TOTAL_BASE ==
            $taxConfig->getAlgorithm($product->getStore()) && ($minimalPriceWithTax > $minimalPrice)
        ) {
            //We convert the value to string to maintain the precision
            $tax = (String) ($minimalPriceWithTax - $minimalPrice);
            $roundedTax = $this->_getApp()->getStore()->roundPrice($tax);
            $minimalPrice = $minimalPriceWithTax - $roundedTax;
        }
        return array('minprice' => $minimalPrice, 'products' => $_min_price_products);
    }

    
    protected function _getSelectionPrices($product,
        $option,
        $takeTierPrice,
        $includeTax) {

        $selectionPrices = array();
        $taxHelper = $this->_getHelperData('tax');
        $taxCalcMethod = $taxHelper->getConfig()->getAlgorithm($product->getStore());
        $isPriceFixedType = ($product->getPriceType() == self::PRICE_TYPE_FIXED);

        $selections = $option->getSelections();
        if (!$selections) {
            return $selectionPrices;
        }

        foreach ($selections as $selection) {
            /* @var $selection Mage_Bundle_Model_Selection */
            if (!$selection->isSalable()) {
                /**
                 * @todo CatalogInventory Show out of stock Products
                 */
                continue;
            }

            $item = $isPriceFixedType ? $product : $selection;

            $selectionUnitPrice = $this->getSelectionFinalTotalPrice(
                $product, $selection, 1, null, false, $takeTierPrice);
            $selectionQty = $selection->getSelectionQty();
            /* min price for option selection */
            if ($isPriceFixedType) {
                $selectionPrice = $selectionQty * $taxHelper->getPrice($item, $selectionUnitPrice, $includeTax, null, null, null, null, null, false);
                if(isset($selectionPrices[$item->getId()])){
                    $selectionPrices[$item->getId()] = min(  $selectionPrice , $selectionPrices[$item->getId()] );
                }else{
                    $selectionPrices[$item->getId()] = $selectionPrice;
                }
            } else if ($taxCalcMethod == Mage_Tax_Model_Calculation::CALC_TOTAL_BASE) {
                $selectionPrice = $selectionQty * $taxHelper->getPrice($item, $selectionUnitPrice, $includeTax, null, null, null, null, null, false);
                $selectionPrices[$item->getId()] = $selectionPrice;
            } else if ($taxCalcMethod == Mage_Tax_Model_Calculation::CALC_ROW_BASE) {
                $selectionPrice = $taxHelper->getPrice($item, $selectionUnitPrice * $selectionQty, $includeTax);
                $selectionPrices[$item->getId()] = $selectionPrice;
            } else { //dynamic price and Mage_Tax_Model_Calculation::CALC_UNIT_BASE
                $selectionPrice = $taxHelper->getPrice($item, $selectionUnitPrice, $includeTax) * $selectionQty;
                $selectionPrices[$item->getId()] = $selectionPrice;
            }
        }
        return $selectionPrices;
    }

    
    public function getOptions($product) {
        $product->getTypeInstance(true)
            ->setStoreFilter($product->getStoreId(), $product);

        $optionCollection = $product->getTypeInstance(true)
            ->getOptionsCollection($product);

        $selectionCollection = $product->getTypeInstance(true)
            ->getSelectionsCollection(
            $product->getTypeInstance(true)->getOptionsIds($product), $product
        );

        return $optionCollection->appendSelections($selectionCollection, false, false);
    }

    
    public function getSelectionPrice($bundleProduct,
        $selectionProduct,
        $selectionQty = null,
        $multiplyQty = true) {
        return $this->getSelectionFinalTotalPrice($bundleProduct, $selectionProduct, 0, $selectionQty, $multiplyQty);
    }

    
    public function getSelectionPreFinalPrice($bundleProduct,
        $selectionProduct,
        $qty = null) {
        return $this->getSelectionPrice($bundleProduct, $selectionProduct, $qty);
    }


    public function getSelectionFinalPrice($bundleProduct,
        $selectionProduct,
        $bundleQty,
        $selectionQty = null,
        $multiplyQty = true) {
        return $this->getSelectionFinalTotalPrice($bundleProduct, $selectionProduct, $bundleQty, $selectionQty, $multiplyQty);
    }

    
    public function getSelectionFinalTotalPrice($bundleProduct,
        $selectionProduct,
        $bundleQty,
        $selectionQty,
        $multiplyQty = true,
        $takeTierPrice = true) {
        if (is_null($selectionQty)) {
            $selectionQty = $selectionProduct->getSelectionQty();
        }

        if ($bundleProduct->getPriceType() == self::PRICE_TYPE_DYNAMIC) {
            $price = $selectionProduct->getFinalPrice($takeTierPrice ? $selectionQty : 1);
        } else {
            if ($selectionProduct->getSelectionPriceType()) { // percent
                $product = clone $bundleProduct;
                $product->setFinalPrice($this->getPrice($product));
                Mage::dispatchEvent(
                    'catalog_product_get_final_price', array('product' => $product, 'qty' => $bundleQty)
                );
                $price = $product->getData('final_price') * ($selectionProduct->getSelectionPriceValue() / 100);
            } else { // fixed
                $price = $selectionProduct->getSelectionPriceValue();
            }
        }

        $price = $this->getLowestPrice($bundleProduct, $price, $bundleQty);

        if ($multiplyQty) {
            $price *= $selectionQty;
        }

        return $price;
    }

    
    public function getLowestPrice($bundleProduct,
        $price,
        $bundleQty = 1) {
        $price *= 1;
        return min($this->_getApp()->getStore()->roundPrice($price), $this->_applyGroupPrice($bundleProduct, $price), $this->_applyTierPrice($bundleProduct, $bundleQty, $price), $this->_applySpecialPrice($bundleProduct, $price)
        );
    }

    protected function _applyGroupPrice($product,
        $finalPrice) {
        $result = $finalPrice;
         $groupPrice = $product->getGroupPrice();

          if (is_numeric($groupPrice)) {
          $groupPrice = $finalPrice - ($finalPrice * ($groupPrice / 100));
          $groupPrice = $this->_getApp()->getStore()->roundPrice($groupPrice);
          $result = min($finalPrice, $groupPrice);
          } 

        return $result;
    }

        public function getGroupPrice($product) {
        $groupPrices = $product->getData('group_price');

        if (is_null($groupPrices)) {
            $attribute = $product->getResource()->getAttribute('group_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $groupPrices = $product->getData('group_price');
            }
        }

        if (is_null($groupPrices) || !is_array($groupPrices)) {
            return null;
        }

        $customerGroup = $this->_getCustomerGroupId($product);

        $matchedPrice = 0;

        foreach ($groupPrices as $groupPrice) {
            if ($groupPrice['cust_group'] == $customerGroup && $groupPrice['website_price'] > $matchedPrice) {
                $matchedPrice = $groupPrice['website_price'];
                break;
            }
        }

        return $matchedPrice;
    }

    
    protected function _applyTierPrice($product,
        $qty,
        $finalPrice) {
        if (is_null($qty)) {
            return $finalPrice;
        }

        $tierPrice = $product->getTierPrice($qty);

        if (is_numeric($tierPrice)) {
            $tierPrice = $finalPrice - ($finalPrice * ($tierPrice / 100));
            $tierPrice = $this->_getApp()->getStore()->roundPrice($tierPrice);
            $finalPrice = min($finalPrice, $tierPrice);
        }

        return $finalPrice;
    }

    public function getTierPrice($qty = null,
        $product) {
        $allGroups = Mage_Customer_Model_Group::CUST_GROUP_ALL;
        $prices = $product->getData('tier_price');

        if (is_null($prices)) {
            $attribute = $product->getResource()->getAttribute('tier_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $prices = $product->getData('tier_price');
            }
        }

        if (is_null($prices) || !is_array($prices)) {
            if (!is_null($qty)) {
                return 0;
            }
            return array(array(
                    'price' => 0,
                    'website_price' => 0,
                    'price_qty' => 1,
                    'cust_group' => $allGroups
            ));
        }

        $custGroup = $this->_getCustomerGroupId($product);
        if ($qty) {
            $prevQty = 1;
            $prevPrice = 0;
            $prevGroup = $allGroups;

            foreach ($prices as $price) {
                if ($price['cust_group'] != $custGroup && $price['cust_group'] != $allGroups) {
                    // tier not for current customer group nor is for all groups
                    continue;
                }
                if ($qty < $price['price_qty']) {
                    // tier is higher than product qty
                    continue;
                }
                if ($price['price_qty'] < $prevQty) {
                    // higher tier qty already found
                    continue;
                }
                if ($price['price_qty'] == $prevQty && $prevGroup != $allGroups && $price['cust_group'] == $allGroups) {
                    // found tier qty is same as current tier qty but current tier group is ALL_GROUPS
                    continue;
                }

                if ($price['website_price'] > $prevPrice) {
                    $prevPrice = $price['website_price'];
                    $prevQty = $price['price_qty'];
                    $prevGroup = $price['cust_group'];
                }
            }

            return $prevPrice;
        } else {
            $qtyCache = array();
            foreach ($prices as $i => $price) {
                if ($price['cust_group'] != $custGroup && $price['cust_group'] != $allGroups) {
                    unset($prices[$i]);
                } else if (isset($qtyCache[$price['price_qty']])) {
                    $j = $qtyCache[$price['price_qty']];
                    if ($prices[$j]['website_price'] < $price['website_price']) {
                        unset($prices[$j]);
                        $qtyCache[$price['price_qty']] = $i;
                    } else {
                        unset($prices[$i]);
                    }
                } else {
                    $qtyCache[$price['price_qty']] = $i;
                }
            }
        }

        return ($prices) ? $prices : array();
    }

    public static function calculatePrice($basePrice,
        $specialPrice,
        $specialPriceFrom,
        $specialPriceTo,
        $rulePrice = false,
        $wId = null,
        $gId = null,
        $productId = null) {
        $resource = Mage::getResourceSingleton('bundle/bundle');
        $selectionResource = Mage::getResourceSingleton('bundle/selection');
        $productPriceTypeId = Mage::getSingleton('eav/entity_attribute')->getIdByCode(
            Mage_Catalog_Model_Product::ENTITY, 'price_type'
        );

        if ($wId instanceof Mage_Core_Model_Store) {
            $store = $wId->getId();
            $wId = $wId->getWebsiteId();
        } else {
            $store = Mage::app()->getStore($wId)->getId();
            $wId = Mage::app()->getStore($wId)->getWebsiteId();
        }

        if (!$gId) {
            $gId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        } else if ($gId instanceof Mage_Customer_Model_Group) {
            $gId = $gId->getId();
        }

        if (!isset(self::$attributeCache[$productId]['price_type'])) {
            $attributes = $resource->getAttributeData($productId, $productPriceTypeId, $store);
            self::$attributeCache[$productId]['price_type'] = $attributes;
        } else {
            $attributes = self::$attributeCache[$productId]['price_type'];
        }

        $options = array(0);
        $results = $resource->getSelectionsData($productId);

        if (!$attributes || !$attributes[0]['value']) { //dynamic
            foreach ($results as $result) {
                if (!$result['product_id']) {
                    continue;
                }

                if ($result['selection_can_change_qty'] && $result['type'] != 'multi' && $result['type'] != 'checkbox'
                ) {
                    $qty = 1;
                } else {
                    $qty = $result['selection_qty'];
                }

                $result['final_price'] = $selectionResource->getPriceFromIndex($result['product_id'], $qty, $store, $gId);

                $selectionPrice = $result['final_price'] * $qty;

                if (isset($options[$result['option_id']])) {
                    $options[$result['option_id']] = min($options[$result['option_id']], $selectionPrice);
                } else {
                    $options[$result['option_id']] = $selectionPrice;
                }
            }
            $basePrice = array_sum($options);
        } else {
            foreach ($results as $result) {
                if (!$result['product_id']) {
                    continue;
                }
                if ($result['selection_price_type']) {
                    $selectionPrice = $basePrice * $result['selection_price_value'] / 100;
                } else {
                    $selectionPrice = $result['selection_price_value'];
                }

                if ($result['selection_can_change_qty'] && $result['type'] != 'multi' && $result['type'] != 'checkbox'
                ) {
                    $qty = 1;
                } else {
                    $qty = $result['selection_qty'];
                }

                $selectionPrice = $selectionPrice * $qty;

                if (isset($options[$result['option_id']])) {
                    $options[$result['option_id']] = min($options[$result['option_id']], $selectionPrice);
                } else {
                    $options[$result['option_id']] = $selectionPrice;
                }
            }

            $basePrice = $basePrice + array_sum($options);
        }

        $finalPrice = self::calculateSpecialPrice($basePrice, $specialPrice, $specialPriceFrom, $specialPriceTo, $store);

        /**
         * adding customer defined options price
         */
        $customOptions = Mage::getResourceSingleton('catalog/product_option_collection')->reset();
        $customOptions->addFieldToFilter('is_require', '1')
            ->addProductToFilter($productId)
            ->addPriceToResult($store, 'price')
            ->addValuesToResult();

        foreach ($customOptions as $customOption) {
            $values = $customOption->getValues();
            if ($values) {
                $prices = array();
                foreach ($values as $value) {
                    $prices[] = $value->getPrice();
                }
                if (count($prices)) {
                    $finalPrice += min($prices);
                }
            } else {
                $finalPrice += $customOption->getPrice();
            }
        }

        if ($rulePrice === false) {
            $rulePrice = Mage::getResourceModel('catalogrule/rule')
                ->getRulePrice(Mage::app()->getLocale()->storeTimeStamp($store), $wId, $gId, $productId);
        }

        if ($rulePrice !== null && $rulePrice !== false) {
            $finalPrice = min($finalPrice, $rulePrice);
        }

        $finalPrice = max($finalPrice, 0);

        return $finalPrice;
    }


    public static function calculateSpecialPrice($finalPrice,
        $specialPrice,
        $specialPriceFrom,
        $specialPriceTo,
        $store = null) {
        if (!is_null($specialPrice) && $specialPrice != false) {
            if (Mage::app()->getLocale()->isStoreDateInInterval($store, $specialPriceFrom, $specialPriceTo)) {
                $specialPrice = Mage::app()->getStore()->roundPrice($finalPrice * $specialPrice / 100);
                $finalPrice = min($finalPrice, $specialPrice);
            }
        }

        return $finalPrice;
    }


    public function isGroupPriceFixed() {
        return false;
    }

    protected function _getHelperData($name) {
        return Mage::helper($name);
    }

    protected function _getApp() {
        return Mage::app();
    }

    protected function _hasRequiredOptions($product) {
        $options = $this->getOptions($product);
        if ($options) {
            foreach ($options as $option) {
                if ($option->getRequired()) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function _getMinimalCustomOptionPrice($option) {
        $prices = $this->_getCustomOptionValuesPrices($option);
        $minimalOptionPrice = ($prices) ? min($prices) : (float) $option->getPrice(true);
        $minimalPrice = ($option->getIsRequire()) ? $minimalOptionPrice : 0;
        return $minimalPrice;
    }

    protected function _getCustomOptionValuesPrices($option) {
        $values = $option->getValues();
        $prices = array();
        if ($values) {
            foreach ($values as $value) {
                /* @var $value Mage_Catalog_Model_Product_Option_Value */
                $prices[] = $value->getPrice(true);
            }
        }
        return $prices;
    }
    
}
