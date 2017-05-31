<?php
$installer = $this;
$installer->startSetup();

$cmsPageData = array(
    'title' => 'search',
    'root_template' => 'one_column',
    'meta_keywords' => '',
    'meta_description' => '',
    'identifier' => 'search',
    'content_heading' => '',
    'is_active' => 1,
    'stores' => array(0),//available for all store views
    'content' => "<div data-findify-attr=\"findify-search-results\"></div>"
);

$pageAlreadyExists = 0;

foreach (Mage::app()->getWebsites() as $website) {
    foreach ($website->getGroups() as $group) {
        $stores = $group->getStores();
        foreach ($stores as $eachStore) {
            $storeId = $eachStore->getId();
            // loop through all storeIds checking if there is a page with this identifier. This way is usually faster than checking all elements in page collection
            $pageId = Mage::getModel('cms/page')->checkIdentifier($identifier, $storeId);
            if ($pageId) {
                $pageAlreadyExists = 1;
            }
        }
    }
}

if (!$pageAlreadyExists){
// if we have found no page with this identifier, we create it
    Mage::getModel('cms/page')->setData($cmsPageData)->save();
}                               

$installer->endSetup();
