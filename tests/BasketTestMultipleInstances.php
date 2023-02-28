<?php
/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/16/2015
 * Time: 1:45 PM
 */

use ScottLsi\Basket\Basket;
use Mockery as m;

require_once __DIR__.'/helpers/SessionMock.php';

class BasketTestMultipleInstances extends PHPUnit\Framework\TestCase {

    /**
     * @var ScottLsi\Basket\Basket
     */
    protected $basket1;

    /**
     * @var ScottLsi\Basket\Basket
     */
    protected $basket2;

    public function setUp(): void
    {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch');

        $this->basket1 = new Basket(
            new SessionMock(),
            $events,
            'shopping',
            'uniquesessionkey123',
            require(__DIR__.'/helpers/configMock.php')
        );

        $this->basket2 = new Basket(
            new SessionMock(),
            $events,
            'wishlist',
            'uniquesessionkey456',
            require(__DIR__.'/helpers/configMock.php')
        );
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function test_basket_multiple_instances()
    {
        // add 3 items on basket 1
        $itemsForBasket1 = array(
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

        $this->basket1->add($itemsForBasket1);

        $this->assertFalse($this->basket1->isEmpty(), 'Basket should not be empty');
        $this->assertCount(3, $this->basket1->getContent()->toArray(), 'Basket should have 3 items');
        $this->assertEquals('shopping', $this->basket1->getInstanceName(), 'Basket 1 should have instance name of "shopping"');

        // add 1 item on basket 2
        $itemsForBasket2 = array(
            array(
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 4,
                'attributes' => array()
            ),
        );

        $this->basket2->add($itemsForBasket2);

        $this->assertFalse($this->basket2->isEmpty(), 'Basket should not be empty');
        $this->assertCount(1, $this->basket2->getContent()->toArray(), 'Basket should have 3 items');
        $this->assertEquals('wishlist', $this->basket2->getInstanceName(), 'Basket 2 should have instance name of "wishlist"');
    }
}