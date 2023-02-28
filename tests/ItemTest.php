<?php
/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 3/18/2015
 * Time: 6:17 PM
 */

use ScottLsi\Basket\Basket;
use Mockery as m;
use ScottLsi\Basket\BasketCondition;
use ScottLsi\Tests\helpers\MockProduct;

require_once __DIR__ . '/helpers/SessionMock.php';

class ItemTest extends PHPUnit\Framework\TestCase
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

    public function test_item_get_sum_price_using_property()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array());

        $item = $this->basket->get(455);

        $this->assertEquals(201.98, $item->getPriceSum(), 'Item summed price should be 201.98');
    }

    public function test_item_get_sum_price_using_array_style()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array());

        $item = $this->basket->get(455);

        $this->assertEquals(201.98, $item->getPriceSum(), 'Item summed price should be 201.98');
    }

    public function test_item_get_conditions_empty()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array());

        $item = $this->basket->get(455);

        $this->assertEmpty($item->getConditions(), 'Item should have no conditions');
    }

    public function test_item_get_conditions_with_conditions()
    {
        $itemCondition1 = new \ScottLsi\Basket\BasketCondition(array(
            'name' => 'SALE 5%',
            'type' => 'sale',
            'target' => 'item',
            'value' => '-5%',
        ));

        $itemCondition2 = new BasketCondition(array(
            'name' => 'Item Gift Pack 25.00',
            'type' => 'promo',
            'target' => 'item',
            'value' => '-25',
        ));

        $this->basket->add(455, 'Sample Item', 100.99, 2, array(), [$itemCondition1, $itemCondition2]);

        $item = $this->basket->get(455);

        $this->assertCount(2, $item->getConditions(), 'Item should have two conditions');
    }

    public function test_item_associate_model()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array())->associate(MockProduct::class);

        $item = $this->basket->get(455);

        $this->assertEquals(MockProduct::class, $item->associatedModel, 'Item assocaited model should be ' . MockProduct::class);
    }

    public function test_it_will_throw_an_exception_when_a_non_existing_model_is_being_associated()
    {
        $this->expectException(\ScottLsi\Basket\Exceptions\UnknownModelException::class);
        $this->expectExceptionMessage('The supplied model SomeModel does not exist.');

        $this->basket->add(1, 'Test item', 1, 10.00)->associate('SomeModel');
    }

    public function test_item_get_model()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array())->associate(MockProduct::class);

        $item = $this->basket->get(455);

        $this->assertInstanceOf(MockProduct::class, $item->model);
        $this->assertEquals('Sample Item', $item->model->name);
        $this->assertEquals(455, $item->model->id);
    }

    public function test_item_get_model_will_return_null_if_it_has_no_model()
    {
        $this->basket->add(455, 'Sample Item', 100.99, 2, array());

        $item = $this->basket->get(455);

        $this->assertEquals(null, $item->model);
    }
}
