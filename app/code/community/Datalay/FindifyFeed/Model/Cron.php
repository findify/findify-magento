<?php
class Datalay_FindifyFeed_Model_Cron{	

    protected $_bundle_children = array();
    public function cronGenerateFeed()
    {
	Mage::log('Findify: starting cron task');

        $mediapath = Mage::getBaseDir('media');
        $urlmediapath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $magentoversion = Mage::getVersion();
        $extensionversion = (string)Mage::getConfig()->getNode()->modules->Datalay_FindifyFeed->version;

	$fileextradata = $mediapath.'/findify/feedextradata.gz';
        $jsonextradata = array();
	$extradata = array(
	    'extension_version' => $extensionversion,
	    'magento_version' => $magentoversion,
	    'feeds' => array()
	);

        $_products_with_children = array('configurable','grouped','bundle');

        /* will process 2 collections, w children n then w/o children */
        $_product_type_filters = array(
            array( 'in' => $_products_with_children),
            array( 'nin' => $_products_with_children)
        );
        
        $jsondata = array();
        
        foreach (Mage::app()->getWebsites() as $website) {

	    foreach ($website->getGroups() as $group) {

	        $stores = $group->getStores();
	        foreach ($stores as $eachStore) {
                    $storeCode = $eachStore->getCode();
                    $storeId = $eachStore->getId();
                    $storeIsActive = $eachStore->getIsActive();

                    Mage::log('Findify: starting feed generation for store '.$storeCode);

                    if(!$storeIsActive){
                      Mage::log('Findify: store '.$storeCode.' is not active');
                      continue; // if this store view is not active, we exit this loop
                    }

                    Mage::app()->setCurrentStore($storeId);

                    // Is feed generation enabled?
                    $feedisenabled = Mage::getStoreConfig('attributes/schedule/isenabled',$storeId);
                    if($feedisenabled){

                        // get filename from system configuration, or use default json_feed-<storeCode> if empty
                        $configfilename = Mage::getStoreConfig('attributes/feedinfo/feedfilename',$storeId);
                        $filename = str_replace("/", "", $configfilename);
                        if(empty($filename)){
                                $filename = 'jsonl_feed-'.$storeCode;
                        }
                        $file = $mediapath.'/findify/'.$filename.'.gz';

                        $starttime = new DateTime('NOW');
                        $today = time(); // time() evaluation should go in the product main loop if a feed generation task could last more than one day (i.e: 1 hour task starting at 23:30)
                        $jsondata = array();                            

                        // Basic attributes which will always be needed for feed generation:
                        $attributesUsed = array('thumbnail','sku','id','visibility','type_id','created_at','news_from_date','name','price','product_url','special_price','special_from_date','special_to_date','description','short_description');

                        // User selected attributes via System / Configuration:
                        $selectedattributes = Mage::getStoreConfig('attributes/general/attributes',$storeId);
                        // Add user selected attributes to array of used attributes for select in collection
                        if ($selectedattributes) {
                            $selectedattributes = unserialize($selectedattributes);
                            if (is_array($selectedattributes)) {
                                foreach($selectedattributes as $selectedattributesRow) {
                                        $attributesUsed[] = $selectedattributesRow['attributename'];
                                }
                            }
                        }

                        foreach ($_product_type_filters as $_type_filter) {
							
							/* for bundle products */
			    			if($_type_filter["in"])
								$attributesUsed[] = "price_type";

							// load product collection, selecting only needed attributes
                            $products = Mage::getResourceModel('catalog/product_collection')
                                ->addAttributeToFilter('status',array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                                    ->addAttributeToSelect($attributesUsed)
                                    ->addAttributeToFilter('type_id', $_type_filter);
                                    /* process products with children first */

                            // We load all category IDs and names as an array to avoid Mage::getModel('catalog/category')->getCollection() in each loop
                            $categoryIdNames = array();
                            $categoryCollection = Mage::getModel('catalog/category')->getCollection() // singleton...
                                    ->setStoreId($storeId)
                                    ->addAttributeToSelect('name')
                                    ->addAttributeToFilter('is_active', 1);
                            $categoryIdNames[1] = 'Root Catalog';
                            foreach($categoryCollection as $category){                
                                    $categoryIdNames[$category->getId()] = $category->getName();
                            }

                            foreach ($products as $product) {

                                $product_data = array();

                                $product_data['sku'] = $product->getSku(); // sku
                                $product_data['id'] = $product->getId(); // id
                                $product_data['visibility'] = $product->getVisibility(); // visibility (1,2,3,4)
                                $product_data['type_id'] = $product->getTypeId(); // type id (simple, configurable...)

                                // we use created_at or news_from_date attribute value, whichever is newer
                                $createdAt = $product->getCreatedAt();
                                $newsFromDate = $product->getNewsFromDate();
                                if($newsFromDate && (strtotime($newsFromDate) > strtotime($createdAt))){
                                    $datetime = new DateTime($newsFromDate);
                                }else{
                                    $datetime = new DateTime($createdAt);
                                }
                                $product_data['created_at'] = $datetime->format(DateTime::ATOM);                      

                                    if ( in_array($product_data['type_id'], $_products_with_children) ) { // if this product has children, set id as item_group_id
                                    $product_data['item_group_id'] = $product_data['id'];            
                                }else{
                                    $product_data['item_group_id'] = '';
                                }

                                // Product categories as breadcrumbs Father > Child > Grandchild
                                $pathArray = array();
                                $productCategories = $product->getCategoryCollection() // this could be faster in big catalogs if we store id - path as an array previously
                                        ->addAttributeToSelect('path');
                                foreach($productCategories as $category){            
                                    $pathIds = explode('/', $category->getPath()); // getPath() returns category IDs path as '1/2/53', we store that as an array ['1','2','53']
                                    $pathByName = array();
                                    foreach($pathIds as $pathId){
                                            $pathByName[] = $categoryIdNames[$pathId];
                                    }
                                    array_splice($pathByName, 0, 2); // remove first two elements in path, which are always: Root Catalog > Default Category
                                    $pathArray[] = implode(' > ', $pathByName);
                                }
                                $product_data['category'] = $pathArray;

                                $product_data['title'] = $product->getName(); // name

                                    if($product_data['type_id']=="bundle"){
                                        $_min_price_info = Mage::getModel('findifyfeed/bundle_price')->getTotalPrices($product, false);
                                        $_bundle_min_price = $_min_price_info["minprice"];

                                        foreach($_min_price_info["products"] as $_child_id ){
                                            $this->_bundle_children[$_child_id][] = $product_data['id'];
                                        }
                                        $product_data['price'] = sprintf('%0.2f',$_bundle_min_price); // price excl tax, two decimal places                                
                                    }else{
                                $product_data['price'] = sprintf('%0.2f',$product->getPrice()); // price excl tax, two decimal places
                                    }

                                $product_data['product_url'] = $product->getProductUrl(); // full url

                                $specialprice = $product->getSpecialPrice();
                                if ($specialprice){
                                    $specialPriceFromDate = $product->getSpecialFromDate();
                                    $specialPriceToDate = $product->getSpecialToDate();
                                    if (Mage::app()->getLocale()->isStoreDateInInterval($product->getStoreId(), $specialPriceFromDate, $specialPriceToDate)){
                                        $product_data['sale_price'] = sprintf('%0.2f',$specialprice);
                                    }
                                }          

                                // Product availability, as Magento calculates it
                                $product_data['availability'] = $product->isSaleable() ? "in stock" : "out of stock";

                                // php CLI php.ini by default sets memory_limit to -1 (unlimited), but a bug in /lib/Varien/Image/Adapter/Gd2.php
                                // in some Magento releases makes it interpret -1 as an actual value for size comparation, so it calculates that
                                // any image size is (of course) bigger than -1 and it does not create the resized image we need. As a workaround
                                // here we set memory_limit to 512M, which is a huge value but remember that by default it had no limits anyway
                                ini_set('memory_limit','512M');
                                // We resize thumbnail or small image - base image sometimes does not work. Note: attribute thumbnail and/or small_image must be enabled in getCollection
                                $product_data['image_url'] = (string)Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(180,180);
                                $product_data['thumbnail_url'] = (string)Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(65,65);

                                // Long and short descriptions
                                $product_data['description'] = $product->getDescription();
                                $product_data['short_description'] = $product->getShortDescription();

                                // User selected attributes via System / Configuration:
                                if (is_array($selectedattributes)) {
                                    foreach($selectedattributes as $selectedattributesRow) {
                                        $attrfilelabel = $selectedattributesRow['attributejson'];
                                        $attrname = $selectedattributesRow['attributename'];
                                        $attributecontent = $product->getAttributeText($attrname);
                                        if (!empty($attributecontent)){
                                          $product_data[$attrfilelabel] = $attributecontent;
                                        }else{
                                          $product_data[$attrfilelabel] = '';
                                        }
                                    }
                                }

                                $jsondata[] = json_encode($product_data)."\n"; // Add this product data to main json array

                                if($product->getTypeId() == "simple"){ // if product is not simple, it can not have parents

                                    $groupParentsIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId()); // does it belong to a grouped product?
                                    if(isset($groupParentsIds[0])){ // it belongs to at least one grouped product
                                        foreach($groupParentsIds as $parentId) {
                                            $groupedProduct = Mage::getModel('catalog/product')->load($parentId);
                                            if($groupedProduct->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED){
                                                continue; // if grouped product is disabled, we do not add it to the feed
                                            } 
                                            $product_data_in_group = $product_data; // we add to the feed a copy of the simple product for each group that it belongs to, modifying item_group_id in each instance
                                            $product_data_in_group['item_group_id'] = $parentId;
                                            $jsondata[] = json_encode($product_data_in_group)."\n";
                                        }
                                    }

                                    $configurableParentsIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId()); // does it belong to a configurable product?
                                    if(isset($configurableParentsIds[0])){ // it belongs to at least one configurable product
                                        foreach($configurableParentsIds as $parentId) {
                                            $configurableProduct = Mage::getModel('catalog/product')->load($parentId); 
                                            if($configurableProduct->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED){
                                                    continue; // if configurable product is disabled, we do not add it to the feed
                                            } 
                                            $product_data_in_configurable = $product_data; // we add to the feed a copy of the simple product for each configurable that it belongs to, modifying item_group_id in each instance
                                            $product_data_in_configurable['item_group_id'] = $parentId; // child products are added once for each parent, setting item_group_id with the parents' ids

                                            // now we will calculate the product's price as part of its configurable parent
                                            $attributes = $configurableProduct->getTypeInstance(true)->getConfigurableAttributes($configurableProduct);
                                            $pricesByAttributeValues = array();
                                            $basePrice = $configurableProduct->getFinalPrice(); // configurable product base price
                                            foreach ($attributes as $attribute){ // check all attributes and get the price adjustments specified in the configurable product admin page
                                                $prices = $attribute->getPrices();
                                                foreach ($prices as $price){
                                                    if ($price['is_percent']){
                                                        $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'] * $basePrice / 100;
                                                    }
                                                    else {
                                                        $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'];
                                                    }
                                                }
                                            }
                                            $fullProduct = Mage::getModel('catalog/product')->load($product->getId()); // Loading products in a loop is generally not recommended because it is resource intensive, but it is the only way to get full product attributes in all possible configurations here
                                            $totalPrice = $basePrice;
                                            // check all configurable attributes
                                            foreach ($attributes as $attribute){
                                                $attributeCode = $attribute->getProductAttribute()->getAttributeCode(); // 'color', 'size', etc
                                                $attributeValue = $fullProduct->getData($attributeCode);
                                                // if the attribute is used in parent and child, add the previously stored price increase to the simple product price
                                                if (isset($pricesByAttributeValues[$attributeValue])){
                                                    $totalPrice += $pricesByAttributeValues[$attributeValue];
                                                    // $attributecontent = $fullProduct->getAttributeText($attributeCode);
                                                    // $product_data_in_configurable['cf_'.$attributeCode] = $attributecontent; // attribute and content for each configurable product's child
                                                }
                                            }
                                            $product_data_in_configurable['price'] = sprintf('%0.2f',$totalPrice);

                                            $jsondata[] = json_encode($product_data_in_configurable)."\n"; // Add this product data to main json array
                                        }
                                    }
                                        if(isset( $this->_bundle_children[$product_data['id']])){ //isset($bundleParentsIds[0])){ // it belongs to at least one configurable product
                                            foreach($this->_bundle_children[$product_data['id']] as $parentId) {

                                                $product_data_in_group = $product_data; // we add to the feed a copy of the simple product for each group that it belongs to, modifying item_group_id in each instance
                                                $product_data_in_group['item_group_id'] = $parentId; //$this->_bundle_children[$product_data['id']][0];
                                                $jsondata[] = json_encode($product_data_in_group)."\n"; // Add this product data to main json array
                                }
                                        }                                    

                                    }/* endif product simple */

                            } // end foreach ($products as $product)
                        } // end foreach ($typefilter)
                    }else{ // feed is not enabled
                        Mage::log('Findify: feed generation for store '.$storeCode.' is disabled in system configuration');
                    } // end if($feedisenabled)

                    Mage::log('Findify: feed generation for store '.$storeCode.' has finished');

                    // Write product feed array to gzipped file
                    file_put_contents("compress.zlib://$file", $jsondata);

                    $endtime = new DateTime('NOW');
                    $runinterval = $starttime->diff($endtime);
                    $elapsed = $runinterval->format('%S'); // elapsed seconds
                    $fileurl = $urlmediapath.'/findify/'.$filename.'.gz';

                    $extradata['feeds'][$storeCode] = array(
                            'feed_url' => $fileurl,
                            'last_generation_duration' => $elapsed,
                            'last_generation_time' => $starttime
                    );
                } // end foreach ($allStores)
	    } // end foreach ($website->getGroups()
        } // end foreach (Mage::app()->getWebsites()

	$jsonextradata[] = json_encode($extradata);
	file_put_contents("compress.zlib://$fileextradata", $jsonextradata);

	Mage::log('Findify: cron task has finished');
	return $this;

    } // end function cronGenerateFeed()
} // end class Datalay_FindifyFeed_Model_Cron
