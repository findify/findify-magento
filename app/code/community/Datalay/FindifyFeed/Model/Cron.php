<?php
class Datalay_FindifyFeed_Model_Cron{	

    public function cronGenerateFeed()
    {
	Mage::log('Findify: starting cron task');

        $mediapath = Mage::getBaseDir('media');

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

			$today = time(); // time() evaluation should go in the main loop if a feed generation task could last more than one day (i.e: 1 hour task starting at 23:30)
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

                        // load product collection, selecting only needed attributes
			$products = Mage::getResourceModel('catalog/product_collection')
			    ->addAttributeToSelect($attributesUsed);
			    //->addAttributeToFilter('sku', array('like' => 'm%'))

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
                        
                            // set item_group_id
                            // TEMP
                            //$parentId = '';
                            //if ($parentId) {
                            //    $product_data['item_group_id'] = $parentId; // if product has a parent, set parent id as item_group_id
                            //} else
                            if ($product_data['type_id'] == "configurable" || $product_data['type_id'] == "grouped") { // if this product has children, set id as item_group_id
                                $product_data['item_group_id'] = $product_data['id'];            
                            }else{
                                $product_data['item_group_id'] = '';
                            }
                        
                            // Product categories as breadcrumbs Father > Child > Grandchild
                            $pathArray = array();
                            $productCategories = $product->getCategoryCollection() // this could be faster in big catalogs if we store id - path as an array previously
                                    ->addAttributeToSelect('path');
                                    //->addAttributeToSelect('is_active')
                                    //->setStoreId($storeId)
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
                            $product_data['price'] = sprintf('%0.2f',$product->getPrice()); // price excl tax, two decimal places
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
                                
                            if($product->getTypeId() != "simple"){ // if product is not simple, it can not have parents
                                $jsondata[] = json_encode($product_data)."\n"; // Add this product data to main json array
                            }else{ // if product is simple, it can belong to a grouped or configurable product
                                $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId()); // does it belong to a grouped product?
                                if(!$parentIds) {
                                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId()); // does it belong to a configurable product?
                                }
                                if(isset($parentIds[0])){ // product is simple and has parents
                                    foreach($parentIds as $parentId) {
                                        $product_data['item_group_id'] = $parentId; // child products are added once for each parent, setting item_group_id with the parents' ids
                                        $jsondata[] = json_encode($product_data)."\n"; // Add this product data to main json array
                                    }
                                }else{ // product is simple and has no parents
                                    $jsondata[] = json_encode($product_data)."\n"; // Add this product data to main json array
                                }
                            }

                        } // end foreach ($products as $product)

		        // Write product feed array to gzipped file
		        file_put_contents("compress.zlib://$file", $jsondata);

    	            }else{ // feed is not enabled
		        Mage::log('Findify: feed generation for store '.$storeCode.' is disabled in system configuration');
                    } // end if($feedisenabled)

		    Mage::log('Findify: feed generation for store '.$storeCode.' has finished');

                } // end foreach ($allStores)
	    } // end foreach ($website->getGroups()
        } // end foreach (Mage::app()->getWebsites()

	Mage::log('Findify: cron task has finished');
	return $this;

    } // end function cronGenerateFeed()
} // end class Datalay_FindifyFeed_Model_Cron
