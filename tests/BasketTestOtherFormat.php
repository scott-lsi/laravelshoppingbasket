<?php
/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/12/2015
 * Time: 9:59 PM
 */

use ScottLsi\Basket\Basket;
use Mockery as m;

require_once __DIR__.'/helpers/SessionMock.php';

class BasketTestOtherFormat extends PHPUnit\Framework\TestCase  {

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
            require(__DIR__.'/helpers/configMockOtherFormat.php')
    );
    }

    public function tearDown(): void
    {
        m::close();
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

        $this->assertEquals('187,490', $this->basket->getSubTotal(), 'Basket should have sub total of 187,490');

        // if we remove an item, the sub total should be updated as well
        $this->basket->remove(456);

        $this->assertEquals('119,500', $this->basket->getSubTotal(), 'Basket should have sub total of 119,500');
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

        $this->assertEquals('273,220', $this->basket->getSubTotal(), 'Basket should have sub total of 273.22');

        // when basket's item quantity is updated, the subtotal should be updated as well
        $this->basket->update(456, array('quantity' => 2));

        $this->assertEquals('409,200', $this->basket->getSubTotal(), 'Basket should have sub total of 409.2');
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

        $this->assertEquals('273,220', $this->basket->getSubTotal(), 'Basket should have sub total of 273.22');

        // when basket's item quantity is updated, the subtotal should be updated as well
        $this->basket->update(456, array('quantity' => -1));

        // get the item to be evaluated
        $item = $this->basket->get(456);

        $this->assertEquals(2, $item['quantity'], 'Item quantity of with item ID of 456 should now be reduced to 2');
        $this->assertEquals('205,230', $this->basket->getSubTotal(), 'Basket should have sub total of 205.23');
    }
}