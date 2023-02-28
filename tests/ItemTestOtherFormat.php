<?php
/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 3/18/2015
 * Time: 6:17 PM
 */

use ScottLsi\Basket\Basket;
use Mockery as m;

require_once __DIR__.'/helpers/SessionMock.php';

class ItemTestOtherFormat extends PHPUnit\Framework\TestCase
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
            require(__DIR__.'/helpers/configMockOtherFormat.php')
        );
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function test_item_get_sum_price_using_property()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array());

        $item = $this->basket->get(455);

        $this->assertEquals('201,980', $item->getPriceSum(), 'Item summed price should be 201.98');
    }

    public function test_item_get_sum_price_using_array_style()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array());

        $item = $this->basket->get(455);

        $this->assertEquals('201,980', $item->getPriceSum(), 'Item summed price should be 201.98');
    }
}