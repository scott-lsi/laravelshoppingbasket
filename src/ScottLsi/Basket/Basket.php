<?php namespace ScottLsi\Basket;

use ScottLsi\Basket\Exceptions\InvalidConditionException;
use ScottLsi\Basket\Exceptions\InvalidItemException;
use ScottLsi\Basket\Helpers\Helpers;
use ScottLsi\Basket\Validators\BasketItemValidator;
use ScottLsi\Basket\Exceptions\UnknownModelException;

/**
 * Class Basket
 * @package ScottLsi\Basket
 */
class Basket
{

    /**
     * the item storage
     *
     * @var
     */
    protected $session;

    /**
     * the event dispatcher
     *
     * @var
     */
    protected $events;

    /**
     * the basket session key
     *
     * @var
     */
    protected $instanceName;

    /**
     * the session key use for the basket
     *
     * @var
     */
    protected $sessionKey;

    /**
     * the session key use to persist basket items
     *
     * @var
     */
    protected $sessionKeyBasketItems;

    /**
     * the session key use to persist basket conditions
     *
     * @var
     */
    protected $sessionKeyBasketConditions;

    /**
     * Configuration to pass to ItemCollection
     *
     * @var
     */
    protected $config;

    /**
     * This holds the currently added item id in basket for association
     * 
     * @var
     */
    protected $currentItemId;

    /**
     * our object constructor
     *
     * @param $session
     * @param $events
     * @param $instanceName
     * @param $session_key
     * @param $config
     */
    public function __construct($session, $events, $instanceName, $session_key, $config)
    {
        $this->events = $events;
        $this->session = $session;
        $this->instanceName = $instanceName;
        $this->sessionKey = $session_key;
        $this->sessionKeyBasketItems = $this->sessionKey . '_basket_items';
        $this->sessionKeyBasketConditions = $this->sessionKey . '_basket_conditions';
        $this->config = $config;
        $this->currentItem = null;
        $this->fireEvent('created');
    }

    /**
     * sets the session key
     *
     * @param string $sessionKey the session key or identifier
     * @return $this|bool
     * @throws \Exception
     */
    public function session($sessionKey)
    {
        if (!$sessionKey) throw new \Exception("Session key is required.");

        $this->sessionKey = $sessionKey;
        $this->sessionKeyBasketItems = $this->sessionKey . '_basket_items';
        $this->sessionKeyBasketConditions = $this->sessionKey . '_basket_conditions';

        return $this;
    }

    /**
     * get instance name of the basket
     *
     * @return string
     */
    public function getInstanceName()
    {
        return $this->instanceName;
    }

    /**
     * get an item on a basket by item ID
     *
     * @param $itemId
     * @return mixed
     */
    public function get($itemId)
    {
        return $this->getContent()->get($itemId);
    }

    /**
     * check if an item exists by item ID
     *
     * @param $itemId
     * @return bool
     */
    public function has($itemId)
    {
        return $this->getContent()->has($itemId);
    }

    /**
     * add item to the basket, it can be an array or multi dimensional array
     *
     * @param string|array $id
     * @param string $name
     * @param float $price
     * @param int $quantity
     * @param array $attributes
     * @param BasketCondition|array $conditions
     * @param string $associatedModel
     * @return $this
     * @throws InvalidItemException
     */
    public function add($id, $name = null, $price = null, $quantity = null, $attributes = array(), $conditions = array(), $associatedModel = null)
    {
        // if the first argument is an array,
        // we will need to call add again
        if (is_array($id)) {
            // the first argument is an array, now we will need to check if it is a multi dimensional
            // array, if so, we will iterate through each item and call add again
            if (Helpers::isMultiArray($id)) {
                foreach ($id as $item) {
                    $this->add(
                        $item['id'],
                        $item['name'],
                        $item['price'],
                        $item['quantity'],
                        Helpers::issetAndHasValueOrAssignDefault($item['attributes'], array()),
                        Helpers::issetAndHasValueOrAssignDefault($item['conditions'], array()),
                        Helpers::issetAndHasValueOrAssignDefault($item['associatedModel'], null)
                    );
                }
            } else {
                $this->add(
                    $id['id'],
                    $id['name'],
                    $id['price'],
                    $id['quantity'],
                    Helpers::issetAndHasValueOrAssignDefault($id['attributes'], array()),
                    Helpers::issetAndHasValueOrAssignDefault($id['conditions'], array()),
                    Helpers::issetAndHasValueOrAssignDefault($id['associatedModel'], null)
                );
            }

            return $this;
        }

        $data = array(
            'id' => $id,
            'name' => $name,
            'price' => Helpers::normalizePrice($price),
            'quantity' => $quantity,
            'attributes' => new ItemAttributeCollection($attributes),
            'conditions' => $conditions
        );

        if (isset($associatedModel) && $associatedModel != '') {
            $data['associatedModel'] = $associatedModel;
        }

        // validate data
        $item = $this->validate($data);

        // get the basket
        $basket = $this->getContent();

        // if the item is already in the basket we will just update it
        if ($basket->has($id)) {

            $this->update($id, $item);
        } else {

            $this->addRow($id, $item);
        }

        $this->currentItemId = $id;

        return $this;
    }

    /**
     * update a basket
     *
     * @param $id
     * @param array $data
     *
     * the $data will be an associative array, you don't need to pass all the data, only the key value
     * of the item you want to update on it
     * @return bool
     */
    public function update($id, $data)
    {
        if ($this->fireEvent('updating', $data) === false) {
            return false;
        }

        $basket = $this->getContent();

        $item = $basket->pull($id);

        foreach ($data as $key => $value) {
            // if the key is currently "quantity" we will need to check if an arithmetic
            // symbol is present so we can decide if the update of quantity is being added
            // or being reduced.
            if ($key == 'quantity') {
                // we will check if quantity value provided is array,
                // if it is, we will need to check if a key "relative" is set
                // and we will evaluate its value if true or false,
                // this tells us how to treat the quantity value if it should be updated
                // relatively to its current quantity value or just totally replace the value
                if (is_array($value)) {
                    if (isset($value['relative'])) {
                        if ((bool)$value['relative']) {
                            $item = $this->updateQuantityRelative($item, $key, $value['value']);
                        } else {
                            $item = $this->updateQuantityNotRelative($item, $key, $value['value']);
                        }
                    }
                } else {
                    $item = $this->updateQuantityRelative($item, $key, $value);
                }
            } elseif ($key == 'attributes') {
                $item[$key] = new ItemAttributeCollection($value);
            } else {
                $item[$key] = $value;
            }
        }

        $basket->put($id, $item);

        $this->save($basket);

        $this->fireEvent('updated', $item);
        return true;
    }

    /**
     * add condition on an existing item on the basket
     *
     * @param int|string $productId
     * @param BasketCondition $itemCondition
     * @return $this
     */
    public function addItemCondition($productId, $itemCondition)
    {
        if ($product = $this->get($productId)) {
            $conditionInstance = "\\ScottLsi\\Basket\\BasketCondition";

            if ($itemCondition instanceof $conditionInstance) {
                // we need to copy first to a temporary variable to hold the conditions
                // to avoid hitting this error "Indirect modification of overloaded element of ScottLsi\Basket\ItemCollection has no effect"
                // this is due to laravel Collection instance that implements Array Access
                // // see link for more info: http://stackoverflow.com/questions/20053269/indirect-modification-of-overloaded-element-of-splfixedarray-has-no-effect
                $itemConditionTempHolder = $product['conditions'];

                if (is_array($itemConditionTempHolder)) {
                    array_push($itemConditionTempHolder, $itemCondition);
                } else {
                    $itemConditionTempHolder = $itemCondition;
                }

                $this->update($productId, array(
                    'conditions' => $itemConditionTempHolder // the newly updated conditions
                ));
            }
        }

        return $this;
    }

    /**
     * removes an item on basket by item ID
     *
     * @param $id
     * @return bool
     */
    public function remove($id)
    {
        $basket = $this->getContent();

        if ($this->fireEvent('removing', $id) === false) {
            return false;
        }

        $basket->forget($id);

        $this->save($basket);

        $this->fireEvent('removed', $id);
        return true;
    }

    /**
     * clear basket
     * @return bool
     */
    public function clear()
    {
        if ($this->fireEvent('clearing') === false) {
            return false;
        }

        $this->session->put(
            $this->sessionKeyBasketItems,
            array()
        );

        $this->fireEvent('cleared');
        return true;
    }

    /**
     * add a condition on the basket
     *
     * @param BasketCondition|array $condition
     * @return $this
     * @throws InvalidConditionException
     */
    public function condition($condition)
    {
        if (is_array($condition)) {
            foreach ($condition as $c) {
                $this->condition($c);
            }

            return $this;
        }

        if (!$condition instanceof BasketCondition) throw new InvalidConditionException('Argument 1 must be an instance of \'ScottLsi\Basket\BasketCondition\'');

        $conditions = $this->getConditions();

        // Check if order has been applied
        if ($condition->getOrder() == 0) {
            $last = $conditions->last();
            $condition->setOrder(!is_null($last) ? $last->getOrder() + 1 : 1);
        }

        $conditions->put($condition->getName(), $condition);

        $conditions = $conditions->sortBy(function ($condition, $key) {
            return $condition->getOrder();
        });

        $this->saveConditions($conditions);

        return $this;
    }

    /**
     * get conditions applied on the basket
     *
     * @return BasketConditionCollection
     */
    public function getConditions()
    {
        return new BasketConditionCollection($this->session->get($this->sessionKeyBasketConditions));
    }

    /**
     * get condition applied on the basket by its name
     *
     * @param $conditionName
     * @return BasketCondition
     */
    public function getCondition($conditionName)
    {
        return $this->getConditions()->get($conditionName);
    }

    /**
     * Get all the condition filtered by Type
     * Please Note that this will only return condition added on basket bases, not those conditions added
     * specifically on an per item bases
     *
     * @param $type
     * @return BasketConditionCollection
     */
    public function getConditionsByType($type)
    {
        return $this->getConditions()->filter(function (BasketCondition $condition) use ($type) {
            return $condition->getType() == $type;
        });
    }


    /**
     * Remove all the condition with the $type specified
     * Please Note that this will only remove condition added on basket bases, not those conditions added
     * specifically on an per item bases
     *
     * @param $type
     * @return $this
     */
    public function removeConditionsByType($type)
    {
        $this->getConditionsByType($type)->each(function ($condition) {
            $this->removeBasketCondition($condition->getName());
        });
    }


    /**
     * removes a condition on a basket by condition name,
     * this can only remove conditions that are added on basket bases not conditions that are added on an item/product.
     * If you wish to remove a condition that has been added for a specific item/product, you may
     * use the removeItemCondition(itemId, conditionName) method instead.
     *
     * @param $conditionName
     * @return void
     */
    public function removeBasketCondition($conditionName)
    {
        $conditions = $this->getConditions();

        $conditions->pull($conditionName);

        $this->saveConditions($conditions);
    }

    /**
     * remove a condition that has been applied on an item that is already on the basket
     *
     * @param $itemId
     * @param $conditionName
     * @return bool
     */
    public function removeItemCondition($itemId, $conditionName)
    {
        if (!$item = $this->getContent()->get($itemId)) {
            return false;
        }

        if ($this->itemHasConditions($item)) {
            // NOTE:
            // we do it this way, we get first conditions and store
            // it in a temp variable $originalConditions, then we will modify the array there
            // and after modification we will store it again on $item['conditions']
            // This is because of ArrayAccess implementation
            // see link for more info: http://stackoverflow.com/questions/20053269/indirect-modification-of-overloaded-element-of-splfixedarray-has-no-effect

            $tempConditionsHolder = $item['conditions'];

            // if the item's conditions is in array format
            // we will iterate through all of it and check if the name matches
            // to the given name the user wants to remove, if so, remove it
            if (is_array($tempConditionsHolder)) {
                foreach ($tempConditionsHolder as $k => $condition) {
                    if ($condition->getName() == $conditionName) {
                        unset($tempConditionsHolder[$k]);
                    }
                }

                $item['conditions'] = $tempConditionsHolder;
            }

            // if the item condition is not an array, we will check if it is
            // an instance of a Condition, if so, we will check if the name matches
            // on the given condition name the user wants to remove, if so,
            // lets just make $item['conditions'] an empty array as there's just 1 condition on it anyway
            else {
                $conditionInstance = "ScottLsi\\Basket\\BasketCondition";

                if ($item['conditions'] instanceof $conditionInstance) {
                    if ($tempConditionsHolder->getName() == $conditionName) {
                        $item['conditions'] = array();
                    }
                }
            }
        }

        $this->update($itemId, array(
            'conditions' => $item['conditions']
        ));

        return true;
    }

    /**
     * remove all conditions that has been applied on an item that is already on the basket
     *
     * @param $itemId
     * @return bool
     */
    public function clearItemConditions($itemId)
    {
        if (!$item = $this->getContent()->get($itemId)) {
            return false;
        }

        $this->update($itemId, array(
            'conditions' => array()
        ));

        return true;
    }

    /**
     * clears all conditions on a basket,
     * this does not remove conditions that has been added specifically to an item/product.
     * If you wish to remove a specific condition to a product, you may use the method: removeItemCondition($itemId, $conditionName)
     *
     * @return void
     */
    public function clearBasketConditions()
    {
        $this->session->put(
            $this->sessionKeyBasketConditions,
            array()
        );
    }

    /**
     * get basket sub total without conditions
     * @param bool $formatted
     * @return float
     */
    public function getSubTotalWithoutConditions($formatted = true)
    {
        $basket = $this->getContent();

        $sum = $basket->sum(function ($item) {
            return $item->getPriceSum();
        });

        return Helpers::formatValue(floatval($sum), $formatted, $this->config);
    }

    /**
     * get basket sub total
     * @param bool $formatted
     * @return float
     */
    public function getSubTotal($formatted = true)
    {
        $basket = $this->getContent();

        $sum = $basket->sum(function (ItemCollection $item) {
            return $item->getPriceSumWithConditions(false);
        });

        // get the conditions that are meant to be applied
        // on the subtotal and apply it here before returning the subtotal
        $conditions = $this
            ->getConditions()
            ->filter(function (BasketCondition $cond) {
                return $cond->getTarget() === 'subtotal';
            });

        // if there is no conditions, lets just return the sum
        if (!$conditions->count()) return Helpers::formatValue(floatval($sum), $formatted, $this->config);

        // there are conditions, lets apply it
        $newTotal = 0.00;
        $process = 0;

        $conditions->each(function (BasketCondition $cond) use ($sum, &$newTotal, &$process) {

            // if this is the first iteration, the toBeCalculated
            // should be the sum as initial point of value.
            $toBeCalculated = ($process > 0) ? $newTotal : $sum;

            $newTotal = $cond->applyCondition($toBeCalculated);

            $process++;
        });

        return Helpers::formatValue(floatval($newTotal), $formatted, $this->config);
    }

    /**
     * the new total in which conditions are already applied
     *
     * @return float
     */
    public function getTotal()
    {
        $subTotal = $this->getSubTotal(false);

        $newTotal = 0.00;

        $process = 0;

        $conditions = $this
            ->getConditions()
            ->filter(function (BasketCondition $cond) {
                return $cond->getTarget() === 'total';
            });

        // if no conditions were added, just return the sub total
        if (!$conditions->count()) {
            return Helpers::formatValue($subTotal, $this->config['format_numbers'], $this->config);
        }

        $conditions
            ->each(function (BasketCondition $cond) use ($subTotal, &$newTotal, &$process) {
                $toBeCalculated = ($process > 0) ? $newTotal : $subTotal;

                $newTotal = $cond->applyCondition($toBeCalculated);

                $process++;
            });

        return Helpers::formatValue($newTotal, $this->config['format_numbers'], $this->config);
    }

    /**
     * get total quantity of items in the basket
     *
     * @return int
     */
    public function getTotalQuantity()
    {
        $items = $this->getContent();

        if ($items->isEmpty()) return 0;

        $count = $items->sum(function ($item) {
            return $item['quantity'];
        });

        return $count;
    }

    /**
     * get the basket
     *
     * @return BasketCollection
     */
    public function getContent()
    {
        return (new BasketCollection($this->session->get($this->sessionKeyBasketItems)))->reject(function($item) {
            return ! ($item instanceof ItemCollection);
        });
    }

    /**
     * check if basket is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getContent()->isEmpty();
    }

    /**
     * validate Item data
     *
     * @param $item
     * @return array $item;
     * @throws InvalidItemException
     */
    protected function validate($item)
    {
        $rules = array(
            'id' => 'required',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric|min:0.1',
            'name' => 'required',
        );

        $validator = BasketItemValidator::make($item, $rules);

        if ($validator->fails()) {
            throw new InvalidItemException($validator->messages()->first());
        }

        return $item;
    }

    /**
     * add row to basket collection
     *
     * @param $id
     * @param $item
     * @return bool
     */
    protected function addRow($id, $item)
    {
        if ($this->fireEvent('adding', $item) === false) {
            return false;
        }

        $basket = $this->getContent();

        $basket->put($id, new ItemCollection($item, $this->config));

        $this->save($basket);

        $this->fireEvent('added', $item);

        return true;
    }

    /**
     * save the basket
     *
     * @param $basket BasketCollection
     */
    protected function save($basket)
    {
        $this->session->put($this->sessionKeyBasketItems, $basket);
    }

    /**
     * save the basket conditions
     *
     * @param $conditions
     */
    protected function saveConditions($conditions)
    {
        $this->session->put($this->sessionKeyBasketConditions, $conditions);
    }

    /**
     * check if an item has condition
     *
     * @param $item
     * @return bool
     */
    protected function itemHasConditions($item)
    {
        if (!isset($item['conditions'])) return false;

        if (is_array($item['conditions'])) {
            return count($item['conditions']) > 0;
        }

        $conditionInstance = "ScottLsi\\Basket\\BasketCondition";

        if ($item['conditions'] instanceof $conditionInstance) return true;

        return false;
    }

    /**
     * update a basket item quantity relative to its current quantity
     *
     * @param $item
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function updateQuantityRelative($item, $key, $value)
    {
        if (preg_match('/\-/', $value) == 1) {
            $value = (int)str_replace('-', '', $value);

            // we will not allowed to reduced quantity to 0, so if the given value
            // would result to item quantity of 0, we will not do it.
            if (($item[$key] - $value) > 0) {
                $item[$key] -= $value;
            }
        } elseif (preg_match('/\+/', $value) == 1) {
            $item[$key] += (int)str_replace('+', '', $value);
        } else {
            $item[$key] += (int)$value;
        }

        return $item;
    }

    /**
     * update basket item quantity not relative to its current quantity value
     *
     * @param $item
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function updateQuantityNotRelative($item, $key, $value)
    {
        $item[$key] = (int)$value;

        return $item;
    }

    /**
     * Setter for decimals. Change value on demand.
     * @param $decimals
     */
    public function setDecimals($decimals)
    {
        $this->decimals = $decimals;
    }

    /**
     * Setter for decimals point. Change value on demand.
     * @param $dec_point
     */
    public function setDecPoint($dec_point)
    {
        $this->dec_point = $dec_point;
    }

    public function setThousandsSep($thousands_sep)
    {
        $this->thousands_sep = $thousands_sep;
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    protected function fireEvent($name, $value = [])
    {
        return $this->events->dispatch($this->getInstanceName() . '.' . $name, array_values([$value, $this]), true);
    }

    /**
     * Associate the basket item with the given id with the given model.
     *
     * @param string $id
     * @param mixed  $model
     *
     * @return void
     */
    public function associate($model)
    {
        if (is_string($model) && !class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $basket = $this->getContent();

        $item = $basket->pull($this->currentItemId);

        $item['associatedModel'] = $model;

        $basket->put($this->currentItemId, new ItemCollection($item, $this->config));

        $this->save($basket);

        return $this;
    }
}
