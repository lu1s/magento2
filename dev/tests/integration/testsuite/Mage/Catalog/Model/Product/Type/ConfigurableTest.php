<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Catalog
 * @subpackage  integration_tests
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @magentoDataFixture Mage/Catalog/_files/product_configurable.php
 */
class Mage_Catalog_Model_Product_Type_ConfigurableTest extends PHPUnit_Framework_TestCase
{
    /**
     * Object under test
     *
     * @var Mage_Catalog_Model_Product_Type_Configurable
     */
    protected $_model;

    /**
     * @var Mage_Catalog_Model_Product
     */
    protected $_product;

    protected function setUp()
    {
        $this->_product = Mage::getModel('Mage_Catalog_Model_Product');
        $this->_product->load(1); // fixture

        $this->_model = Mage::getModel('Mage_Catalog_Model_Product_Type_Configurable');
        // prevent fatal errors by assigning proper "singleton" of type instance to the product
        $this->_product->setTypeInstance($this->_model);
    }

    public function testGetRelationInfo()
    {
        $info = $this->_model->getRelationInfo();
        $this->assertInstanceOf('Varien_Object', $info);
        $this->assertEquals('catalog_product_super_link', $info->getTable());
        $this->assertEquals('parent_id', $info->getParentFieldName());
        $this->assertEquals('product_id', $info->getChildFieldName());
    }

    public function testGetChildrenIds()
    {
        $ids = $this->_model->getChildrenIds(1); // fixture
        $this->assertArrayHasKey(0, $ids);
        $this->assertTrue(2 === count($ids[0]));

        $ids = $this->_model->getChildrenIds(1, false);
        $this->assertArrayHasKey(0, $ids);
        $this->assertTrue(2 === count($ids[0]));
    }

    public function testCanUseAttribute()
    {
        $this->assertFalse($this->_model->canUseAttribute($this->_getAttributeByCode('sku')));
        $this->assertTrue($this->_model->canUseAttribute($this->_getAttributeByCode('test_configurable')));
    }

    public function testSetGetUsedProductAttributeIds()
    {
        $testConfigurable = $this->_getAttributeByCode('test_configurable');
        $actual = $this->_model->getUsedProductAttributeIds($this->_product);
        $expected = array($testConfigurable->getId());
        $this->assertEquals($expected, $actual);
    }

    public function testSetUsedProductAttributeIds()
    {
        $testConfigurable = $this->_getAttributeByCode('test_configurable');
        $this->assertEmpty($this->_product->getData('_cache_instance_configurable_attributes'));
        $this->_model->setUsedProductAttributeIds(array($testConfigurable->getId()), $this->_product);
        $attributes = $this->_product->getData('_cache_instance_configurable_attributes');
        $this->assertArrayHasKey(0, $attributes);
        $this->assertInstanceOf('Mage_Catalog_Model_Product_Type_Configurable_Attribute', $attributes[0]);
        $this->assertSame($testConfigurable, $attributes[0]->getProductAttribute());
    }

    public function testGetUsedProductAttributes()
    {
        $testConfigurable = $this->_getAttributeByCode('test_configurable');
        $attributeId = (int)$testConfigurable->getId();
        $attributes = $this->_model->getUsedProductAttributes($this->_product);
        $this->assertArrayHasKey($attributeId, $attributes);
        $this->assertSame($testConfigurable, $attributes[$attributeId]);
    }

    public function testGetConfigurableAttributes()
    {
        $collection = $this->_model->getConfigurableAttributes($this->_product);
        $this->assertInstanceOf('Mage_Catalog_Model_Resource_Product_Type_Configurable_Attribute_Collection',
            $collection
        );
        $testConfigurable = $this->_getAttributeByCode('test_configurable');
        foreach ($collection as $attribute) {
            $this->assertInstanceOf('Mage_Catalog_Model_Product_Type_Configurable_Attribute', $attribute);
            $this->assertEquals($testConfigurable->getId(), $attribute->getAttributeId());
            $prices = $attribute->getPrices();
            $this->assertCount(2, $prices); // fixture
            $this->assertArrayHasKey('pricing_value', $prices[0]);
            $this->assertEquals('Option 1', $prices[0]['label']);
            $this->assertEquals(5, $prices[0]['pricing_value']);
            $this->assertEquals('Option 2', $prices[1]['label']);
            $this->assertEquals(5, $prices[1]['pricing_value']);
            break;
        }
    }

    public function testGetConfigurableAttributesAsArray()
    {
        $attributes = $this->_model->getConfigurableAttributesAsArray($this->_product);
        $attribute = reset($attributes);
        $this->assertArrayHasKey('id', $attribute);
        $this->assertArrayHasKey('label', $attribute);
        $this->assertArrayHasKey('use_default', $attribute);
        $this->assertArrayHasKey('position', $attribute);
        $this->assertArrayHasKey('values', $attribute);
        $this->assertArrayHasKey(0, $attribute['values']);
        $this->assertArrayHasKey(1, $attribute['values']);
        foreach ($attribute['values'] as $attributeOption) {
            $this->assertArrayHasKey('product_super_attribute_id', $attributeOption);
            $this->assertArrayHasKey('value_index', $attributeOption);
            $this->assertArrayHasKey('label', $attributeOption);
            $this->assertArrayHasKey('default_label', $attributeOption);
            $this->assertArrayHasKey('store_label', $attributeOption);
            $this->assertArrayHasKey('is_percent', $attributeOption);
            $this->assertArrayHasKey('pricing_value', $attributeOption);
            $this->assertArrayHasKey('use_default_value', $attributeOption);
        }
        $this->assertArrayHasKey('attribute_id', $attribute);
        $this->assertArrayHasKey('attribute_code', $attribute);
        $this->assertArrayHasKey('frontend_label', $attribute);
        $this->assertArrayHasKey('store_label', $attribute);

        $testConfigurable = $this->_getAttributeByCode('test_configurable');
        $this->assertEquals($testConfigurable->getId(), $attribute['attribute_id']);
    }

    /**
     * @depends testGetConfigurableAttributesAsArray
     */
    public function testGetParentIdsByChild()
    {
        $attributes = $this->_model->getConfigurableAttributesAsArray($this->_product);
        $attribute = reset($attributes);
        $optionValueId = $attribute['values'][0]['value_index'];
        $result = $this->_model->getParentIdsByChild($optionValueId * 10); // fixture
        $this->assertEquals(array(1), $result);
    }

    public function testGetConfigurableAttributeCollection()
    {
        $collection = $this->_model->getConfigurableAttributeCollection($this->_product);
        $this->assertInstanceOf('Mage_Catalog_Model_Resource_Product_Type_Configurable_Attribute_Collection',
            $collection
        );
    }

    public function testGetUsedProductIds()
    {
        $ids = $this->_model->getUsedProductIds($this->_product);
        $this->assertInternalType('array', $ids);
        $this->assertTrue(2 === count($ids)); // impossible to check actual IDs, because they are dynamic in the fixture
    }

    public function testGetUsedProducts()
    {
        $products = $this->_model->getUsedProducts($this->_product);
        $this->assertInternalType('array', $products);
        $this->assertTrue(2 === count($products));
        foreach ($products as $product) {
            $this->assertInstanceOf('Mage_Catalog_Model_Product', $product);
        }
    }

    public function testGetUsedProductCollection()
    {
        $this->assertInstanceOf('Mage_Catalog_Model_Resource_Product_Type_Configurable_Product_Collection',
            $this->_model->getUsedProductCollection($this->_product)
        );
    }

    public function testBeforeSave()
    {
        $this->assertEmpty($this->_product->getTypeHasOptions());
        $this->assertEmpty($this->_product->getTypeHasRequiredOptions());

        $this->_product->setCanSaveConfigurableAttributes(true);
        $this->_product->setConfigurableAttributesData(array('values' => 'not empty'));
        $this->_model->beforeSave($this->_product);
        $this->assertTrue($this->_product->getTypeHasOptions());
        $this->assertTrue($this->_product->getTypeHasRequiredOptions());
    }

    public function testIsSalable()
    {
        $this->_product->unsetData('is_salable');
        $this->assertTrue($this->_model->isSalable($this->_product));
    }

    /**
     * @depends testGetConfigurableAttributesAsArray
     */
    public function testGetProductByAttributes()
    {
        $attributes = $this->_model->getConfigurableAttributesAsArray($this->_product);
        $attribute = reset($attributes);
        $optionValueId = $attribute['values'][0]['value_index'];

        $product = $this->_model->getProductByAttributes(
            array($attribute['attribute_id'] => $optionValueId),
            $this->_product
        );
        $this->assertInstanceOf('Mage_Catalog_Model_Product', $product);
        $this->assertEquals("simple_{$optionValueId}", $product->getSku());
    }

    /**
     * @depends testGetConfigurableAttributesAsArray
     */
    public function testGetSelectedAttributesInfo()
    {
        $attributes = $this->_model->getConfigurableAttributesAsArray($this->_product);
        $attribute = reset($attributes);
        $optionValueId = $attribute['values'][0]['value_index'];

        $this->_product->addCustomOption(
            'attributes', serialize(array($attribute['attribute_id'] => $optionValueId))
        );
        $info = $this->_model->getSelectedAttributesInfo($this->_product);
        $this->assertEquals(
            array(
                array(
                    'label' => 'Test Configurable',
                    'value' => 'Option 1'
                )
            ),
            $info
        );
    }

    /**
     * @depends testGetConfigurableAttributesAsArray
     */
    public function testPrepareForCart()
    {
        $attributes = $this->_model->getConfigurableAttributesAsArray($this->_product);
        $attribute = reset($attributes);
        $optionValueId = $attribute['values'][0]['value_index'];

        $buyRequest = new Varien_Object(array(
            'qty' => 5,
            'super_attribute' => array($attribute['attribute_id'] => $optionValueId)
        ));
        $result = $this->_model->prepareForCart($buyRequest, $this->_product);
        $this->assertInternalType('array', $result);
        $this->assertTrue(2 === count($result));
        foreach ($result as $product) {
            $this->assertInstanceOf('Mage_Catalog_Model_Product', $product);
        }
        $this->assertInstanceOf('Varien_Object', $result[1]->getCustomOption('parent_product_id'));
    }

    public function testGetSpecifyOptionMessage()
    {
        $this->assertEquals('Please specify the product\'s option(s).', $this->_model->getSpecifyOptionMessage());
    }

    /**
     * @depends testGetConfigurableAttributesAsArray
     * @depends testPrepareForCart
     */
    public function testGetOrderOptions()
    {
        $this->_prepareForCart();

        $result = $this->_model->getOrderOptions($this->_product);
        $this->assertArrayHasKey('info_buyRequest', $result);
        $this->assertArrayHasKey('attributes_info', $result);
        $this->assertEquals(
            array(array('label' => 'Test Configurable', 'value' => 'Option 1')), $result['attributes_info']
        );
        $this->assertArrayHasKey('product_calculations', $result);
        $this->assertArrayHasKey('shipment_type', $result);
        $this->assertEquals(
            Mage_Catalog_Model_Product_Type_Abstract::CALCULATE_PARENT, $result['product_calculations']
        );
        $this->assertEquals(Mage_Catalog_Model_Product_Type_Abstract::SHIPMENT_TOGETHER, $result['shipment_type']);
    }

    /**
     * @depends testGetConfigurableAttributesAsArray
     * @depends testPrepareForCart
     */
    public function testIsVirtual()
    {
        $this->_prepareForCart();
        $this->assertFalse($this->_model->isVirtual($this->_product));
    }

    public function testHasOptions()
    {
        $this->assertTrue($this->_model->hasOptions($this->_product));
    }

    public function testGetWeight()
    {
        $this->assertEmpty($this->_model->getWeight($this->_product));

        $this->_product->setCustomOptions(array(
            'simple_product' => new Varien_Object(array(
                'product' => new Varien_Object(array(
                    'weight' => 2
                ))
            ))
        ));
        $this->assertEquals(2, $this->_model->getWeight($this->_product));
    }

    public function testAssignProductToOption()
    {
        $option = new Varien_Object;
        $this->_model->assignProductToOption('test', $option, $this->_product);
        $this->assertEquals('test', $option->getProduct());

        // other branch of logic depends on Mage_Sales module
    }

    public function testGetProductsToPurchaseByReqGroups()
    {
        $result = $this->_model->getProductsToPurchaseByReqGroups($this->_product);
        $this->assertArrayHasKey(0, $result);
        $this->assertInternalType('array', $result[0]);
        $this->assertTrue(2 === count($result[0])); // fixture has 2 simple products
        foreach ($result[0] as $product) {
            $this->assertInstanceOf('Mage_Catalog_Model_Product', $product);
        }
    }

    public function testGetSku()
    {
        $this->assertEquals('configurable', $this->_model->getSku($this->_product));
        $this->_prepareForCart();
        $this->assertStringStartsWith('simple_', $this->_model->getSku($this->_product));
    }

    public function testProcessBuyRequest()
    {
        $buyRequest = new Varien_Object(array('super_attribute' => array('10', 'string')));
        $result = $this->_model->processBuyRequest($this->_product, $buyRequest);
        $this->assertEquals(array('super_attribute' => array(10)), $result);
    }

    public function testSaveProductRelationsOneChild()
    {
        $oldChildrenIds = $this->_product->getTypeInstance()->getChildrenIds(1);
        $oldChildrenIds = reset($oldChildrenIds);
        $oneChildId = reset($oldChildrenIds);
        $this->assertNotEmpty($oldChildrenIds);
        $this->assertNotEmpty($oneChildId);

        $this->_product->setAssociatedProductIds(array($oneChildId));
        $this->_model->save($this->_product);
        $this->_product->load(1);

        $this->assertEquals(
            array(array($oneChildId => $oneChildId)),
            $this->_product->getTypeInstance()->getChildrenIds(1)
        );
    }

    public function testSaveProductRelationsNoChildren()
    {
        $childrenIds = $this->_product->getTypeInstance()->getChildrenIds(1);
        $this->assertNotEmpty(reset($childrenIds));

        $this->_product->setAssociatedProductIds(array());
        $this->_model->save($this->_product);
        $this->_product->load(1);

        $this->assertEquals(
            array(array()),
            $this->_product->getTypeInstance()->getChildrenIds(1)
        );
    }

    /**
     * @param array $productsData
     * @dataProvider generateSimpleProductsDataProvider
     */
    public function testGenerateSimpleProducts($productsData)
    {
        $this->_product->setNewVariationsAttributeSetId(4); // Default attribute set id
        $generatedProducts = $this->_model->generateSimpleProducts($this->_product, $productsData);
        $this->assertEquals(3, count($generatedProducts));
        foreach ($generatedProducts as $productId) {
            /** @var $product Mage_Catalog_Model_Product */
            $product = Mage::getModel('Mage_Catalog_Model_Product');
            $product->load($productId);
            $this->assertNotNull($product->getName());
            $this->assertNotNull($product->getSku());
            $this->assertNotNull($product->getPrice());
            $this->assertNotNull($product->getWeight());
        }
    }

    /**
     * @return array
     */
    public static function generateSimpleProductsDataProvider()
    {
        return array(array(array(
            25 => array(
                'name' => '1-aaa',
                'configurable_attribute' => '{"configurable_attribute":"25"}',
                'price' => '3',
                'sku' => '1-aaa',
                'quantity_and_stock_status' => array('qty' => '5'),
                'weight' => '6'),
            24 => array(
                'name' => '1-bbb',
                'configurable_attribute' => '{"configurable_attribute":"24"}',
                'price' => '3',
                'sku' => '1-bbb',
                'quantity_and_stock_status' => array('qty' => '5'),
                'weight' => '6'),
            23 => array(
                'name' => '1-ccc',
                'configurable_attribute' => '{"configurable_attribute":"23"}',
                'price' => '3',
                'sku' => '1-ccc',
                'quantity_and_stock_status' => array('qty' => '5'),
                'weight' => '6'
            ),
        )));
    }

    /**
     * Find and instantiate a catalog attribute model by attribute code
     *
     * @param string $code
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected function _getAttributeByCode($code)
    {
        return Mage::getSingleton('Mage_Eav_Model_Config')->getAttribute('catalog_product', $code);
    }

    /**
     * Select one of the options and "prepare for cart" with a proper buy request
     */
    protected function _prepareForCart()
    {
        $attributes = $this->_model->getConfigurableAttributesAsArray($this->_product);
        $attribute = reset($attributes);
        $optionValueId = $attribute['values'][0]['value_index'];

        $buyRequest = new Varien_Object(array(
            'qty' => 5,
            'super_attribute' => array($attribute['attribute_id'] => $optionValueId)
        ));
        $this->_model->prepareForCart($buyRequest, $this->_product);
    }
}
