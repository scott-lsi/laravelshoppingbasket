# Laravel 10 Shopping Basket

[![Latest Stable Version](http://poser.pugx.org/scott-lsi/laravelshoppingbasket/v)](https://packagist.org/packages/scott-lsi/laravelshoppingbasket)
[![Total Downloads](http://poser.pugx.org/scott-lsi/laravelshoppingbasket/downloads)](https://packagist.org/packages/scott-lsi/laravelshoppingbasket)
[![License](http://poser.pugx.org/scott-lsi/laravelshoppingbasket/license)](https://packagist.org/packages/scott-lsi/laravelshoppingbasket)
[![PHP Version Require](http://poser.pugx.org/scott-lsi/laravelshoppingbasket/require/php)](https://packagist.org/packages/scott-lsi/laravelshoppingbasket)

## Credits

This is a fork of [ultrono/laravelshoppingcart-1](https://github.com/ultrono/laravelshoppingcart-1) which is a fork of [darryldecode/laravelshoppingbasket](https://github.com/darryldecode/laravelshoppingcart)

## Installation

`composer require scott-lsi/laravelshoppingbasket`

## Configuration

The service provider and alias are autodiscovered.

You may publish the configuration file using:

`php artisan vendor:publish --provider="ScottLsi\Basket\BasketServiceProvider" --tag="config"`

## How To Use

-   [Quick Usage](#usage-usage-example)
-   [Usage](#usage)
-   [Conditions](#conditions)
-   [Items](#items)
-   [Associating Models](#associating-models)
-   [Instances](#instances)
-   [Exceptions](#exceptions)
-   [Events](#events)
-   [Format Response](#format)
-   [Examples](#examples)
-   [Using Different Storage](#storage)
-   [License](#license)

## Quick Usage Example

```php
// Quick Usage with the Product Model Association & User session binding

$Product = Product::find($productId); // assuming you have a Product model with id, name, description & price
$rowId = 456; // generate a unique() row ID
$userID = 2; // the user ID to bind the basket contents

// add the product to basket
\Basket::session($userID)->add(array(
    'id' => $rowId,
    'name' => $Product->name,
    'price' => $Product->price,
    'quantity' => 4,
    'attributes' => array(),
    'associatedModel' => $Product
));

// update the item on basket
\Basket::session($userID)->update($rowId,[
	'quantity' => 2,
	'price' => 98.67
]);

// delete an item on basket
\Basket::session($userID)->remove($rowId);

// view the basket items
$items = \Basket::getContent();
foreach($items as $row) {

	echo $row->id; // row ID
	echo $row->name;
	echo $row->qty;
	echo $row->price;
	
	echo $item->associatedModel->id; // whatever properties your model have
        echo $item->associatedModel->name; // whatever properties your model have
        echo $item->associatedModel->description; // whatever properties your model have
}

// FOR FULL USAGE, SEE BELOW..
```

## Usage

### IMPORTANT NOTE!

By default, the basket has a default sessionKey that holds the basket data. This
also serves as a basket unique identifier which you can use to bind a basket to a specific user.
To override this default session Key, you will just simply call the `\Basket::session($sessionKey)` method
BEFORE ANY OTHER METHODS!!.

Example:

```php
$userId // the current login user id

// This tells the basket that we only need or manipulate
// the basket data of a specific user. It doesn't need to be $userId,
// you can use any unique key that represents a unique to a user or customer.
// basically this binds the basket to a specific user.
\Basket::session($userId);

// then followed by the normal basket usage
\Basket::add();
\Basket::update();
\Basket::remove();
\Basket::condition($condition1);
\Basket::getTotal();
\Basket::getSubTotal();
\Basket::addItemCondition($productID, $coupon101);
// and so on..
```

See More Examples below:

Adding Item on Basket: **Basket::add()**

There are several ways you can add items on your basket, see below:

```php
/**
 * add item to the basket, it can be an array or multi dimensional array
 *
 * @param string|array $id
 * @param string $name
 * @param float $price
 * @param int $quantity
 * @param array $attributes
 * @param BasketCondition|array $conditions
 * @return $this
 * @throws InvalidItemException
 */

 # ALWAYS REMEMBER TO BIND THE CART TO A USER BEFORE CALLING ANY CART FUNCTION
 # SO CART WILL KNOW WHO'S CART DATA YOU WANT TO MANIPULATE. SEE IMPORTANT NOTICE ABOVE.
 # EXAMPLE: \Basket::session($userId); then followed by basket normal usage.
 
 # NOTE:
 # the 'id' field in adding a new item on basket is not intended for the Model ID (example Product ID)
 # instead make sure to put a unique ID for every unique product or product that has it's own unique prirce, 
 # because it is used for updating basket and how each item on basket are segregated during calculation and quantities. 
 # You can put the model_id instead as an attribute for full flexibility.
 # Example is that if you want to add same products on the basket but with totally different attribute and price.
 # If you use the Product's ID as the 'id' field in basket, it will result to increase in quanity instead
 # of adding it as a unique product with unique attribute and price.

// Simplest form to add item on your basket
Basket::add(455, 'Sample Item', 100.99, 2, array());

// array format
Basket::add(array(
    'id' => 456, // inique row ID
    'name' => 'Sample Item',
    'price' => 67.99,
    'quantity' => 4,
    'attributes' => array()
));

// add multiple items at one time
Basket::add(array(
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
      'attributes' => array(
        'size' => 'L',
        'color' => 'blue'
      )
  ),
));

// add basket items to a specific user
$userId = auth()->user()->id; // or any string represents user identifier
Basket::session($userId)->add(array(
    'id' => 456, // inique row ID
    'name' => 'Sample Item',
    'price' => 67.99,
    'quantity' => 4,
    'attributes' => array(),
    'associatedModel' => $Product
));

// NOTE:
// Please keep in mind that when adding an item on basket, the "id" should be unique as it serves as
// row identifier as well. If you provide same ID, it will assume the operation will be an update to its quantity
// to avoid basket item duplicates
```

Updating an item on a basket: **Basket::update()**

Updating an item on a basket is very simple:

```php
/**
 * update a basket
 *
 * @param $id (the item ID)
 * @param array $data
 *
 * the $data will be an associative array, you don't need to pass all the data, only the key value
 * of the item you want to update on it
 */

Basket::update(456, array(
  'name' => 'New Item Name', // new item name
  'price' => 98.67, // new item price, price can also be a string format like so: '98.67'
));

// you may also want to update a product's quantity
Basket::update(456, array(
  'quantity' => 2, // so if the current product has a quantity of 4, another 2 will be added so this will result to 6
));

// you may also want to update a product by reducing its quantity, you do this like so:
Basket::update(456, array(
  'quantity' => -1, // so if the current product has a quantity of 4, it will subtract 1 and will result to 3
));

// NOTE: as you can see by default, the quantity update is relative to its current value
// if you want to just totally replace the quantity instead of incrementing or decrementing its current quantity value
// you can pass an array in quantity value like so:
Basket::update(456, array(
  'quantity' => array(
      'relative' => false,
      'value' => 5
  ),
));
// so with that code above as relative is flagged as false, if the item's quantity before is 2 it will now be 5 instead of
// 5 + 2 which results to 7 if updated relatively..

// updating a basket for a specific user
$userId = auth()->user()->id; // or any string represents user identifier
Basket::session($userId)->update(456, array(
  'name' => 'New Item Name', // new item name
  'price' => 98.67, // new item price, price can also be a string format like so: '98.67'
));
```

Removing an item on a basket: **Basket::remove()**

Removing an item on a basket is very easy:

```php
/**
 * removes an item on basket by item ID
 *
 * @param $id
 */

Basket::remove(456);

// removing basket item for a specific user's basket
$userId = auth()->user()->id; // or any string represents user identifier
Basket::session($userId)->remove(456);
```

Getting an item on a basket: **Basket::get()**

```php

/**
 * get an item on a basket by item ID
 * if item ID is not found, this will return null
 *
 * @param $itemId
 * @return null|array
 */

$itemId = 456;

Basket::get($itemId);

// You can also get the sum of the Item multiplied by its quantity, see below:
$summedPrice = Basket::get($itemId)->getPriceSum();

// get an item on a basket by item ID for a specific user's basket
$userId = auth()->user()->id; // or any string represents user identifier
Basket::session($userId)->get($itemId);
```

Getting basket's contents and count: **Basket::getContent()**

```php

/**
 * get the basket
 *
 * @return BasketCollection
 */

$basketCollection = Basket::getContent();

// NOTE: Because basket collection extends Laravel's Collection
// You can use methods you already know about Laravel's Collection
// See some of its method below:

// count baskets contents
$basketCollection->count();

// transformations
$basketCollection->toArray();
$basketCollection->toJson();

// Getting basket's contents for a specific user
$userId = auth()->user()->id; // or any string represents user identifier
Basket::session($userId)->getContent($itemId);
```

Check if basket is empty: **Basket::isEmpty()**

```php
/**
* check if basket is empty
*
* @return bool
*/
Basket::isEmpty();

// Check if basket's contents is empty for a specific user
$userId = auth()->user()->id; // or any string represents user identifier
Basket::session($userId)->isEmpty();
```

Get basket total quantity: **Basket::getTotalQuantity()**

```php
/**
* get total quantity of items in the basket
*
* @return int
*/
$basketTotalQuantity = Basket::getTotalQuantity();

// for a specific user
$basketTotalQuantity = Basket::session($userId)->getTotalQuantity();
```

Get basket subtotal: **Basket::getSubTotal()**

```php
/**
* get basket sub total
*
* @return float
*/
$subTotal = Basket::getSubTotal();

// for a specific user
$subTotal = Basket::session($userId)->getSubTotal();
```

Get basket total: **Basket::getTotal()**

```php
/**
 * the new total in which conditions are already applied
 *
 * @return float
 */
$total = Basket::getTotal();

// for a specific user
$total = Basket::session($userId)->getTotal();
```

Clearing the Basket: **Basket::clear()**

```php
/**
* clear basket
*
* @return void
*/
Basket::clear();
Basket::session($userId)->clear();
```

## Conditions

Laravel Shopping Basket supports basket conditions.
Conditions are very useful in terms of (coupons,discounts,sale,per-item sale and discounts etc.)
See below carefully on how to use conditions.

Conditions can be added on:

1.) Whole Basket Value bases

2.) Per-Item Bases

First let's add a condition on a Basket Bases:

There are also several ways of adding a condition on a basket:
NOTE:

When adding a condition on a basket bases, the 'target' should have value of 'subtotal' or 'total'.
If the target is "subtotal" then this condition will be applied to subtotal.
If the target is "total" then this condition will be applied to total.
The order of operation also during calculation will vary on the order you have added the conditions.

Also, when adding conditions, the 'value' field will be the bases of calculation. You can change this order
by adding 'order' parameter in BasketCondition.

```php

// add single condition on a basket bases
$condition = new \ScottLsi\Basket\BasketCondition(array(
    'name' => 'VAT 12.5%',
    'type' => 'tax',
    'target' => 'subtotal', // this condition will be applied to basket's subtotal when getSubTotal() is called.
    'value' => '12.5%',
    'attributes' => array( // attributes field is optional
    	'description' => 'Value added tax',
    	'more_data' => 'more data here'
    )
));

Basket::condition($condition);
Basket::session($userId)->condition($condition); // for a speicifc user's basket

// or add multiple conditions from different condition instances
$condition1 = new \ScottLsi\Basket\BasketCondition(array(
    'name' => 'VAT 12.5%',
    'type' => 'tax',
    'target' => 'subtotal', // this condition will be applied to basket's subtotal when getSubTotal() is called.
    'value' => '12.5%',
    'order' => 2
));
$condition2 = new \ScottLsi\Basket\BasketCondition(array(
    'name' => 'Express Shipping $15',
    'type' => 'shipping',
    'target' => 'subtotal', // this condition will be applied to basket's subtotal when getSubTotal() is called.
    'value' => '+15',
    'order' => 1
));
Basket::condition($condition1);
Basket::condition($condition2);

// Note that after adding conditions that are targeted to be applied on subtotal, the result on getTotal()
// will also be affected as getTotal() depends in getSubTotal() which is the subtotal.

// add condition to only apply on totals, not in subtotal
$condition = new \ScottLsi\Basket\BasketCondition(array(
    'name' => 'Express Shipping $15',
    'type' => 'shipping',
    'target' => 'total', // this condition will be applied to basket's total when getTotal() is called.
    'value' => '+15',
    'order' => 1 // the order of calculation of basket base conditions. The bigger the later to be applied.
));
Basket::condition($condition);

// The property 'order' lets you control the sequence of conditions when calculated. Also it lets you add different conditions through for example a shopping process with multiple
// pages and still be able to set an order to apply the conditions. If no order is defined defaults to 0

// NOTE!! On current version, 'order' parameter is only applicable for conditions for basket bases. It does not support on per item conditions.

// or add multiple conditions as array
Basket::condition([$condition1, $condition2]);

// To get all applied conditions on a basket, use below:
$basketConditions = Basket::getConditions();
foreach($basketConditions as $condition)
{
    $condition->getTarget(); // the target of which the condition was applied
    $condition->getName(); // the name of the condition
    $condition->getType(); // the type
    $condition->getValue(); // the value of the condition
    $condition->getOrder(); // the order of the condition
    $condition->getAttributes(); // the attributes of the condition, returns an empty [] if no attributes added
}

// You can also get a condition that has been applied on the basket by using its name, use below:
$condition = Basket::getCondition('VAT 12.5%');
$condition->getTarget(); // the target of which the condition was applied
$condition->getName(); // the name of the condition
$condition->getType(); // the type
$condition->getValue(); // the value of the condition
$condition->getAttributes(); // the attributes of the condition, returns an empty [] if no attributes added

// You can get the conditions calculated value by providing the subtotal, see below:
$subTotal = Basket::getSubTotal();
$condition = Basket::getCondition('VAT 12.5%');
$conditionCalculatedValue = $condition->getCalculatedValue($subTotal);
```

> NOTE: All basket based conditions should be added to basket's conditions before calling **Basket::getTotal()**
> and if there are also conditions that are targeted to be applied to subtotal, it should be added to basket's conditions
> before calling **Basket::getSubTotal()**

```php
$basketTotal = Basket::getSubTotal(); // the subtotal with the conditions targeted to "subtotal" applied
$basketTotal = Basket::getTotal(); // the total with the conditions targeted to "total" applied
$basketTotal = Basket::session($userId)->getSubTotal(); // for a specific user's basket
$basketTotal = Basket::session($userId)->getTotal(); // for a specific user's basket
```

Next is the Condition on Per-Item Bases.

This is very useful if you have coupons to be applied specifically on an item and not on the whole basket value.

> NOTE: When adding a condition on a per-item bases, the 'target' parameter is not needed or can be omitted.
> unlike when adding conditions or per basket bases.

Now let's add condition on an item.

```php

// lets create first our condition instance
$saleCondition = new \ScottLsi\Basket\BasketCondition(array(
            'name' => 'SALE 5%',
            'type' => 'tax',
            'value' => '-5%',
        ));

// now the product to be added on basket
$product = array(
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 100,
            'quantity' => 1,
            'attributes' => array(),
            'conditions' => $saleCondition
        );

// finally add the product on the basket
Basket::add($product);

// you may also add multiple condition on an item
$itemCondition1 = new \ScottLsi\Basket\BasketCondition(array(
    'name' => 'SALE 5%',
    'type' => 'sale',
    'value' => '-5%',
));
$itemCondition2 = new BasketCondition(array(
    'name' => 'Item Gift Pack 25.00',
    'type' => 'promo',
    'value' => '-25',
));
$itemCondition3 = new \ScottLsi\Basket\BasketCondition(array(
    'name' => 'MISC',
    'type' => 'misc',
    'value' => '+10',
));

$item = array(
          'id' => 456,
          'name' => 'Sample Item 1',
          'price' => 100,
          'quantity' => 1,
          'attributes' => array(),
          'conditions' => [$itemCondition1, $itemCondition2, $itemCondition3]
      );

Basket::add($item);
```

> NOTE: All basket per-item conditions should be added before calling **Basket::getSubTotal()**

Then Finally you can call **Basket::getSubTotal()** to get the Basket sub total with the applied conditions on each of the items.

```php
// the subtotal will be calculated based on the conditions added that has target => "subtotal"
// and also conditions that are added on per item
$basketSubTotal = Basket::getSubTotal();
```

Add condition to existing Item on the basket: **Basket::addItemCondition($productId, $itemCondition)**

Adding Condition to an existing Item on the basket is simple as well.

This is very useful when adding new conditions on an item during checkout process like coupons and promo codes.
Let's see the example how to do it:

```php
$productID = 456;
$coupon101 = new BasketCondition(array(
            'name' => 'COUPON 101',
            'type' => 'coupon',
            'value' => '-5%',
        ));

Basket::addItemCondition($productID, $coupon101);
```

Clearing Basket Conditions: **Basket::clearBasketConditions()**

```php
/**
* clears all conditions on a basket,
* this does not remove conditions that has been added specifically to an item/product.
* If you wish to remove a specific condition to a product, you may use the method: removeItemCondition($itemId,$conditionName)
*
* @return void
*/
Basket::clearBasketConditions()
```

Remove Specific Basket Condition: **Basket::removeBasketCondition(\$conditionName)**

```php
/**
* removes a condition on a basket by condition name,
* this can only remove conditions that are added on basket bases not conditions that are added on an item/product.
* If you wish to remove a condition that has been added for a specific item/product, you may
* use the removeItemCondition(itemId, conditionName) method instead.
*
* @param $conditionName
* @return void
*/
$conditionName = 'Summer Sale 5%';

Basket::removeBasketCondition($conditionName)
```

Remove Specific Item Condition: **Basket::removeItemCondition($itemId, $conditionName)**

```php
/**
* remove a condition that has been applied on an item that is already on the basket
*
* @param $itemId
* @param $conditionName
* @return bool
*/
Basket::removeItemCondition($itemId, $conditionName)
```

Clear all Item Conditions: **Basket::clearItemConditions(\$itemId)**

```php
/**
* remove all conditions that has been applied on an item that is already on the basket
*
* @param $itemId
* @return bool
*/
Basket::clearItemConditions($itemId)
```

Get conditions by type: **Basket::getConditionsByType(\$type)**

```php
/**
* Get all the condition filtered by Type
* Please Note that this will only return condition added on basket bases, not those conditions added
* specifically on an per item bases
*
* @param $type
* @return BasketConditionCollection
*/
public function getConditionsByType($type)
```

Remove conditions by type: **Basket::removeConditionsByType(\$type)**

```php
/**
* Remove all the condition with the $type specified
* Please Note that this will only remove condition added on basket bases, not those conditions added
* specifically on an per item bases
*
* @param $type
* @return $this
*/
public function removeConditionsByType($type)
```

## Items

The method **Basket::getContent()** returns a collection of items.

To get the id of an item, use the property **\$item->id**.

To get the name of an item, use the property **\$item->name**.

To get the quantity of an item, use the property **\$item->quantity**.

To get the attributes of an item, use the property **\$item->attributes**.

To get the price of a single item without the conditions applied, use the property **\$item->price**.

To get the subtotal of an item without the conditions applied, use the method **\$item->getPriceSum()**.

```php
/**
* get the sum of price
*
* @return mixed|null
*/
public function getPriceSum()

```

To get the price of a single item without the conditions applied, use the method

**\$item->getPriceWithConditions()**.

```php
/**
* get the single price in which conditions are already applied
*
* @return mixed|null
*/
public function getPriceWithConditions()

```

To get the subtotal of an item with the conditions applied, use the method

**\$item->getPriceSumWithConditions()**

```php
/**
* get the sum of price in which conditions are already applied
*
* @return mixed|null
*/
public function getPriceSumWithConditions()

```

**NOTE**: When you get price with conditions applied, only the conditions assigned to the current item will be calculated.
Basket conditions won't be applied to price.

## Associating Models

One can associate a basket item to a model. Let's say you have a `Product` model in your application. With the `associate()` method, you can tell the basket that an item in the basket, is associated to the `Product` model.

That way you can access your model using the property **\$item->model**.

Here is an example:

```php

// add the item to the basket.
$basketItem = Basket::add(455, 'Sample Item', 100.99, 2, array())->associate('Product');

// array format
Basket::add(array(
    'id' => 456,
    'name' => 'Sample Item',
    'price' => 67.99,
    'quantity' => 4,
    'attributes' => array(),
    'associatedModel' => 'Product'
));

// add multiple items at one time
Basket::add(array(
  array(
      'id' => 456,
      'name' => 'Sample Item 1',
      'price' => 67.99,
      'quantity' => 4,
      'attributes' => array(),
      'associatedModel' => 'Product'
  ),
  array(
      'id' => 568,
      'name' => 'Sample Item 2',
      'price' => 69.25,
      'quantity' => 4,
      'attributes' => array(
        'size' => 'L',
        'color' => 'blue'
      ),
      'associatedModel' => 'Product'
  ),
));

// Now, when iterating over the content of the basket, you can access the model.
foreach(Basket::getContent() as $row) {
	echo 'You have ' . $row->qty . ' items of ' . $row->model->name . ' with description: "' . $row->model->description . '" in your basket.';
}
```

**NOTE**: This only works when adding an item to basket.

## Instances

You may also want multiple basket instances on the same page without conflicts.
To do that,

Create a new Service Provider and then on register() method, you can put this like so:

```php
$this->app['wishlist'] = $this->app->share(function($app)
		{
			$storage = $app['session']; // laravel session storage
			$events = $app['events']; // laravel event handler
			$instanceName = 'wishlist'; // your basket instance name
			$session_key = 'AsASDMCks0ks1'; // your unique session key to hold basket items

			return new Basket(
				$storage,
				$events,
				$instanceName,
				$session_key
			);
		});

// for 5.4 or newer
use ScottLsi\Basket\Basket;
use Illuminate\Support\ServiceProvider;

class WishListProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('wishlist', function($app)
        {
            $storage = $app['session'];
            $events = $app['events'];
            $instanceName = 'basket_2';
            $session_key = '88uuiioo99888';
            return new Basket(
                $storage,
                $events,
                $instanceName,
                $session_key,
                config('shopping_basket')
            );
        });
    }
}
```

IF you are having problem with multiple basket instance, please see the codes on
this demo repo here: [DEMO](https://github.com/darryldecode/laravelshoppingbasket-demo)

## Exceptions

There are currently only two exceptions.

| Exception                   | Description                                                               |
| --------------------------- | ------------------------------------------------------------------------- |
| _InvalidConditionException_ | When there is an invalid field value during instantiating a new Condition |
| _InvalidItemException_      | When a new product has invalid field values (id,name,price,quantity)      |
| _UnknownModelException_     | When you try to associate a none existing model to a basket item.           |

## Events

The basket has currently 9 events you can listen and hook some actons.

| Event                        | Fired                                  |
| ---------------------------- | -------------------------------------- |
| basket.created(\$basket)         | When a basket is instantiated            |
| basket.adding($items, $basket)   | When an item is attempted to be added  |
| basket.added($items, $basket)    | When an item is added on basket          |
| basket.updating($items, $basket) | When an item is being updated          |
| basket.updated($items, $basket)  | When an item is updated                |
| basket.removing($id, $basket)    | When an item is being remove           |
| basket.removed($id, $basket)     | When an item is removed                |
| basket.clearing(\$basket)        | When a basket is attempted to be cleared |
| basket.cleared(\$basket)         | When a basket is cleared                 |

**NOTE**: For different basket instance, dealing events is simple. For example you have created another basket instance which
you have given an instance name of "wishlist". The Events will be something like: {$instanceName}.created($basket)

So for you wishlist basket instance, events will look like this:

-   wishlist.created(\$basket)
-   wishlist.adding($items, $basket)
-   wishlist.added($items, $basket) and so on..

## Format Response

Now you can format all the responses. You can publish the config file from the package or use env vars to set the configuration.
The options you have are:

-   format_numbers or env('SHOPPING_FORMAT_VALUES', false) => Activate or deactivate this feature. Default to false,
-   decimals or env('SHOPPING_DECIMALS', 0) => Number of decimals you want to show. Defaults to 0.
-   dec_point or env('SHOPPING_DEC_POINT', '.') => Decimal point type. Defaults to a '.'.
-   thousands_sep or env('SHOPPING_THOUSANDS_SEP', ',') => Thousands separator for value. Defaults to ','.

## Examples

```php

// add items to basket
Basket::add(array(
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
      'attributes' => array(
        'size' => 'L',
        'color' => 'blue'
      )
  ),
));

// then you can:
$items = Basket::getContent();

foreach($items as $item)
{
    $item->id; // the Id of the item
    $item->name; // the name
    $item->price; // the single price without conditions applied
    $item->getPriceSum(); // the subtotal without conditions applied
    $item->getPriceWithConditions(); // the single price with conditions applied
    $item->getPriceSumWithConditions(); // the subtotal with conditions applied
    $item->quantity; // the quantity
    $item->attributes; // the attributes

    // Note that attribute returns ItemAttributeCollection object that extends the native laravel collection
    // so you can do things like below:

    if( $item->attributes->has('size') )
    {
        // item has attribute size
    }
    else
    {
        // item has no attribute size
    }
}

// or
$items->each(function($item)
{
    $item->id; // the Id of the item
    $item->name; // the name
    $item->price; // the single price without conditions applied
    $item->getPriceSum(); // the subtotal without conditions applied
    $item->getPriceWithConditions(); // the single price with conditions applied
    $item->getPriceSumWithConditions(); // the subtotal with conditions applied
    $item->quantity; // the quantity
    $item->attributes; // the attributes

    if( $item->attributes->has('size') )
    {
        // item has attribute size
    }
    else
    {
        // item has no attribute size
    }
});

```

## Storage

Using different storage for the baskets items is pretty straight forward. The storage
class that is injected to the Basket's instance will only need methods.

Example we will need a wishlist, and we want to store its key value pair in database instead
of the default session.

To do this, we will need first a database table that will hold our basket data.
Let's create it by issuing `php artisan make:migration create_basket_storage_table`

Example Code:

```php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBasketStorageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('basket_storage', function (Blueprint $table) {
            $table->string('id')->index();
            $table->longText('basket_data');
            $table->timestamps();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('basket_storage');
    }
}
```

Next, lets create an eloquent Model on this table so we can easily deal with the data. It is up to you where you want
to store this model. For this example, lets just assume to store it in our App namespace.

Code:

```php
namespace App;

use Illuminate\Database\Eloquent\Model;


class DatabaseStorageModel extends Model
{
    protected $table = 'basket_storage';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'basket_data',
    ];

    public function setBasketDataAttribute($value)
    {
        $this->attributes['basket_data'] = serialize($value);
    }

    public function getBasketDataAttribute($value)
    {
        return unserialize($value);
    }
}
```

Next, Create a new class for your storage to be injected to our basket instance:

Eg.

```php
class DBStorage {

    public function has($key)
    {
        return DatabaseStorageModel::find($key);
    }

    public function get($key)
    {
        if($this->has($key))
        {
            return new BasketCollection(DatabaseStorageModel::find($key)->basket_data);
        }
        else
        {
            return [];
        }
    }

    public function put($key, $value)
    {
        if($row = DatabaseStorageModel::find($key))
        {
            // update
            $row->basket_data = $value;
            $row->save();
        }
        else
        {
            DatabaseStorageModel::create([
                'id' => $key,
                'basket_data' => $value
            ]);
        }
    }
}
```

For example you can also leverage Laravel's Caching (redis, memcached, file, dynamo, etc) using the example below. Example also includes cookie persistance, so that basket would be still available for 30 days. Sessions by default persists only 20 minutes. 

```php
namespace App\Basket;

use Carbon\Carbon;
use Cookie;
use ScottLsi\Basket\BasketCollection;

class CacheStorage
{
    private $data = [];
    private $basket_id;

    public function __construct()
    {
        $this->basket_id = \Cookie::get('basket');
        if ($this->basket_id) {
            $this->data = \Cache::get('basket_' . $this->basket_id, []);
        } else {
            $this->basket_id = uniqid();
        }
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function get($key)
    {
        return new BasketCollection($this->data[$key] ?? []);
    }

    public function put($key, $value)
    {
        $this->data[$key] = $value;
        \Cache::put('basket_' . $this->basket_id, $this->data, Carbon::now()->addDays(30));

        if (!Cookie::hasQueued('basket')) {
            Cookie::queue(
                Cookie::make('basket', $this->basket_id, 60 * 24 * 30)
            );
        }
    }
}
```

To make this the basket's default storage, let's update the basket's configuration file.
First, let us publish first the basket config file for us to enable to override it.
`php artisan vendor:publish --provider="ScottLsi\Basket\BasketServiceProvider" --tag="config"`

after running that command, there should be a new file on your config folder name `shopping_basket.php`

Open this file and let's update the storage use. Find the key which says `'storage' => null,`
And update it to your newly created DBStorage Class, which on our example,
`'storage' => \App\DBStorage::class,`

OR If you have multiple basket instance (example WishList), you can inject the custom database storage
to your basket instance by injecting it to the service provider of your wishlist basket, you replace the storage
to use your custom storage. See below:

```php
use ScottLsi\Basket\Basket;
use Illuminate\Support\ServiceProvider;

class WishListProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('wishlist', function($app)
        {
            $storage = new DBStorage(); <-- Your new custom storage
            $events = $app['events'];
            $instanceName = 'basket_2';
            $session_key = '88uuiioo99888';
            return new Basket(
                $storage,
                $events,
                $instanceName,
                $session_key,
                config('shopping_basket')
            );
        });
    }
}
```

## License

The Laravel Shopping Basket is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Disclaimer

THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR, OR ANY OF THE CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.