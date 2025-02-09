<?php

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/12/2015
 * Time: 9:59 PM
 */

use ScottLsi\Basket\Basket;
use Mockery as m;
use ScottLsi\Tests\helpers\MockProduct;

require_once __DIR__ . '/helpers/SessionMock.php';
require_once __DIR__ . '/helpers/MockProduct.php';

class BasketTest extends PHPUnit\Framework\TestCase
{

    /**
     * @var ScottLsi\Basket\Basket
     */
    protected $basket;

    public function setUp(): void
    {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch');

        $this->basket = new Basket(
            new SessionMock(),
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configMock.php')
        );
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function test_basket_can_add_item()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array());

        $this->assertFalse($this->basket->isEmpty(), 'Basket should not be empty');
        $this->assertEquals(1, $this->basket->getContent()->count(), 'Basket content should be 1');
        $this->assertEquals(455, $this->basket->getContent()->first()['id'], 'Item added has ID of 455 so first content ID should be 455');
        $this->assertEquals(100.99, $this->basket->getContent()->first()['price'], 'Item added has price of 100.99 so first content price should be 100.99');
    }

    public function test_basket_can_add_items_as_array()
    {
        $item = array(
            'id' => 456,
            'name' => 'Sample Item',
            'price' => 67.99,
            'quantity' => 4,
            'attributes' => array()
        );

        $this->basket->add($item);

        $this->assertFalse($this->basket->isEmpty(), 'Basket should not be empty');
        $this->assertEquals(1, $this->basket->getContent()->count(), 'Basket should have 1 item on it');
        $this->assertEquals(456, $this->basket->getContent()->first()['id'], 'The first content must have ID of 456');
        $this->assertEquals('Sample Item', $this->basket->getContent()->first()['name'], 'The first content must have name of "Sample Item"');
    }

    public function test_basket_can_add_items_with_multidimensional_array()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 4,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 4,
                'attributes' => array()
            ),
            array(
                'id' => 856,
                'name' => 'Sample Item 3',
                'price' => 50.25,
                'quantity' => 4,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $this->assertFalse($this->basket->isEmpty(), 'Basket should not be empty');
        $this->assertCount(3, $this->basket->getContent()->toArray(), 'Basket should have 3 items');
    }

    public function test_basket_can_add_item_without_attributes()
    {
        $item = array(
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 4
        );

        $this->basket->add($item);

        $this->assertFalse($this->basket->isEmpty(), 'Basket should not be empty');
    }

    public function test_basket_update_with_attribute_then_attributes_should_be_still_instance_of_ItemAttributeCollection()
    {
        $item = array(
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 4,
            'attributes' => array(
                'product_id' => '145',
                'color' => 'red'
            )
        );
        $this->basket->add($item);

        // lets get the attribute and prove first its an instance of
        // ItemAttributeCollection
        $item = $this->basket->get(456);

        $this->assertInstanceOf('ScottLsi\Basket\ItemAttributeCollection', $item->attributes);

        // now lets update the item with its new attributes
        // when we get that item from basket, it should still be an instance of ItemAttributeCollection
        $updatedItem = array(
            'attributes' => array(
                'product_id' => '145',
                'color' => 'red'
            )
        );
        $this->basket->update(456, $updatedItem);

        $this->assertInstanceOf('ScottLsi\Basket\ItemAttributeCollection', $item->attributes);
    }

    public function test_basket_items_attributes()
    {
        $item = array(
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 4,
            'attributes' => array(
                'size' => 'L',
                'color' => 'blue'
            )
        );

        $this->basket->add($item);

        $this->assertFalse($this->basket->isEmpty(), 'Basket should not be empty');
        $this->assertCount(2, $this->basket->getContent()->first()['attributes'], 'Item\'s attribute should have two');
        $this->assertEquals('L', $this->basket->getContent()->first()->attributes->size, 'Item should have attribute size of L');
        $this->assertEquals('blue', $this->basket->getContent()->first()->attributes->color, 'Item should have attribute color of blue');
        $this->assertTrue($this->basket->get(456)->has('attributes'), 'Item should have attributes');
        $this->assertEquals('L', $this->basket->get(456)->get('attributes')->size);
    }

    public function test_basket_update_existing_item()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 3,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 1,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $itemIdToEvaluate = 456;

        $item = $this->basket->get($itemIdToEvaluate);
        $this->assertEquals('Sample Item 1', $item['name'], 'Item name should be "Sample Item 1"');
        $this->assertEquals(67.99, $item['price'], 'Item price should be "67.99"');
        $this->assertEquals(3, $item['quantity'], 'Item quantity should be 3');

        // when basket's item quantity is updated, the subtotal should be updated as well
        $this->basket->update(456, array(
            'name' => 'Renamed',
            'quantity' => 2,
            'price' => 105,
        ));

        $item = $this->basket->get($itemIdToEvaluate);
        $this->assertEquals('Renamed', $item['name'], 'Item name should be "Renamed"');
        $this->assertEquals(105, $item['price'], 'Item price should be 105');
        $this->assertEquals(5, $item['quantity'], 'Item quantity should be 2');
    }

    public function test_basket_update_existing_item_with_quantity_as_array_and_not_relative()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 3,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $itemIdToEvaluate = 456;
        $item = $this->basket->get($itemIdToEvaluate);
        $this->assertEquals(3, $item['quantity'], 'Item quantity should be 3');

        // now by default when an update takes place and the quantity attribute
        // is present, it will evaluate for arithmetic operation if the quantity
        // should be incremented or decremented, we should also allow the quantity
        // value to be in array format and provide a field if the quantity should not be
        // treated as relative to Item quantity current value
        $this->basket->update($itemIdToEvaluate, array('quantity' => array('relative' => false, 'value' => 5)));

        $item = $this->basket->get($itemIdToEvaluate);
        $this->assertEquals(5, $item['quantity'], 'Item quantity should be 5');
    }

    public function test_item_price_should_be_normalized_when_added_to_basket()
    {
        // add a price in a string format should be converted to float
        $this->basket->add(455, 'Sample Item', '100.99', 2, array());

        $this->assertIsFloat($this->basket->getContent()->first()['price'], 'Basket price should be a float');
    }

    public function test_it_removes_an_item_on_basket_by_item_id()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 4,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 4,
                'attributes' => array()
            ),
            array(
                'id' => 856,
                'name' => 'Sample Item 3',
                'price' => 50.25,
                'quantity' => 4,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $removeItemId = 456;

        $this->basket->remove($removeItemId);

        $this->assertCount(2, $this->basket->getContent()->toArray(), 'Basket must have 2 items left');
        $this->assertFalse($this->basket->getContent()->has($removeItemId), 'Basket must have not contain the remove item anymore');
    }

    public function test_basket_sub_total()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 1,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 1,
                'attributes' => array()
            ),
            array(
                'id' => 856,
                'name' => 'Sample Item 3',
                'price' => 50.25,
                'quantity' => 1,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $this->assertEquals(187.49, $this->basket->getSubTotal(), 'Basket should have sub total of 187.49');

        // if we remove an item, the sub total should be updated as well
        $this->basket->remove(456);

        $this->assertEquals(119.5, $this->basket->getSubTotal(), 'Basket should have sub total of 119.5');
    }

    public function test_sub_total_when_item_quantity_is_updated()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 3,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 1,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $this->assertEquals(number_format(273.22, 2), number_format($this->basket->getSubTotal(), 2), 'Basket should have sub total of 273.22');

        // when basket's item quantity is updated, the subtotal should be updated as well
        $this->basket->update(456, array('quantity' => 2));

        $this->assertEquals(number_format('409.20', 2), number_format($this->basket->getSubTotal(), 2), 'Basket should have sub total of 409.2');
    }

    public function test_sub_total_when_item_quantity_is_updated_by_reduced()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 3,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 1,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $this->assertEquals(number_format('273.22', 2), number_format($this->basket->getSubTotal(), 2), 'Basket should have sub total of 273.22');

        // when basket's item quantity is updated, the subtotal should be updated as well
        $this->basket->update(456, array('quantity' => -1));

        // get the item to be evaluated
        $item = $this->basket->get(456);

        $this->assertEquals(2, $item['quantity'], 'Item quantity of with item ID of 456 should now be reduced to 2');
        $this->assertEquals(number_format('205.23', 2), number_format($this->basket->getSubTotal(), 2), 'Basket should have sub total of 205.23');
    }

    public function test_item_quantity_update_by_reduced_should_not_reduce_if_quantity_will_result_to_zero()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 3,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 1,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        // get the item to be evaluated
        $item = $this->basket->get(456);

        // prove first we have quantity of 3
        $this->assertEquals(3, $item['quantity'], 'Item quantity of with item ID of 456 should be reduced to 3');

        // when basket's item quantity is updated, and reduced to more than the current quantity
        // this should not work
        $this->basket->update(456, array('quantity' => -3));

        $this->assertEquals(3, $item['quantity'], 'Item quantity of with item ID of 456 should now be reduced to 2');
    }

    public function test_should_throw_exception_when_provided_invalid_values_scenario_one()
    {
        $this->expectException('ScottLsi\Basket\Exceptions\InvalidItemException');
        $this->basket->add(455, 'Sample Item', 100.99, 0, array());
    }

    public function test_should_throw_exception_when_provided_invalid_values_scenario_two()
    {
        $this->expectException('ScottLsi\Basket\Exceptions\InvalidItemException');
        $this->basket->add('', 'Sample Item', 100.99, 2, array());
    }

    public function test_should_throw_exception_when_provided_invalid_values_scenario_three()
    {
        $this->expectException('ScottLsi\Basket\Exceptions\InvalidItemException');
        $this->basket->add(523, '', 100.99, 2, array());
    }

    public function test_clearing_basket()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 3,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 1,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $this->assertFalse($this->basket->isEmpty(), 'prove first basket is not empty');

        // now let's clear basket
        $this->basket->clear();

        $this->assertTrue($this->basket->isEmpty(), 'basket should now be empty');
    }

    public function test_basket_get_total_quantity()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 3,
                'attributes' => array()
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 1,
                'attributes' => array()
            ),
        );

        $this->basket->add($items);

        $this->assertFalse($this->basket->isEmpty(), 'prove first basket is not empty');

        // now let's count the basket's quantity
        $this->assertIsInt($this->basket->getTotalQuantity(), 'Return type should be INT');
        $this->assertEquals(4, $this->basket->getTotalQuantity(), 'Basket\'s quantity should be 4.');
    }

    public function test_basket_can_add_items_as_array_with_associated_model()
    {
        $item = array(
            'id' => 456,
            'name' => 'Sample Item',
            'price' => 67.99,
            'quantity' => 4,
            'attributes' => array(),
            'associatedModel' => MockProduct::class
        );

        $this->basket->add($item);

        $addedItem = $this->basket->get($item['id']);

        $this->assertFalse($this->basket->isEmpty(), 'Basket should not be empty');
        $this->assertEquals(1, $this->basket->getContent()->count(), 'Basket should have 1 item on it');
        $this->assertEquals(456, $this->basket->getContent()->first()['id'], 'The first content must have ID of 456');
        $this->assertEquals('Sample Item', $this->basket->getContent()->first()['name'], 'The first content must have name of "Sample Item"');
        $this->assertInstanceOf('ScottLsi\Tests\helpers\MockProduct', $addedItem->model);
    }

    public function test_basket_can_add_items_with_multidimensional_array_with_associated_model()
    {
        $items = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 4,
                'attributes' => array(),
                'associatedModel' => MockProduct::class
            ),
            array(
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 4,
                'attributes' => array(),
                'associatedModel' => MockProduct::class
            ),
            array(
                'id' => 856,
                'name' => 'Sample Item 3',
                'price' => 50.25,
                'quantity' => 4,
                'attributes' => array(),
                'associatedModel' => MockProduct::class
            ),
        );

        $this->basket->add($items);

        $content = $this->basket->getContent();
        foreach ($content as $item) {
            $this->assertInstanceOf('ScottLsi\Tests\helpers\MockProduct', $item->model);
        }

        $this->assertFalse($this->basket->isEmpty(), 'Basket should not be empty');
        $this->assertCount(3, $this->basket->getContent()->toArray(), 'Basket should have 3 items');
        $this->assertIsInt($this->basket->getTotalQuantity(), 'Return type should be INT');
        $this->assertEquals(12, $this->basket->getTotalQuantity(),  'Basket\'s quantity should be 4.');
    }
}
