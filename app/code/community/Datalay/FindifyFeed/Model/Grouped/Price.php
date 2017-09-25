<?php

class Datalay_FindifyFeed_Model_Grouped_Price extends Mage_Catalog_Model_Product_Type_Grouped_Price
{

    public function getPriceInfo($product) {
        $_child_products = array();
        $_prices = array();
        $typeInstance = $product->getTypeInstance(true);
        $associatedProducts = $typeInstance->setStoreFilter($product->getStore(), $product)
            ->getAssociatedProducts($product);
        foreach ($associatedProducts as $childProduct) {
            $_child_products[] = $childProduct->getId();
            $_prices[] = $childProduct->getFinalPrice(1);
        }
        $finalPrice = min($_prices);
        $product->setFinalPrice($finalPrice);
        $_price = max(0, $product->getData('final_price'));
        return array('price' => $_price, 'products' => $_child_products);
    }

}
