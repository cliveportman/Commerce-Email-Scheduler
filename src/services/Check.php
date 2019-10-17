<?php
/**
 * Commerce email scheduler plugin for Craft CMS 3.x
 *
 * Email scheduler for Craft Commerce
 *
 * @link      https://clive.theportman.co
 * @copyright Copyright (c) 2019 Clive Portman
 */

namespace cliveportman\commerceemailscheduler\services;

use cliveportman\commerceemailscheduler\CommerceEmailScheduler;

use Craft;
use craft\base\Component;

use craft\commerce\Plugin as Commerce;

/**
 * Check Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Clive Portman
 * @package   CommerceEmailScheduler
 * @since     1.0.0
 */
class Check extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     CommerceEmailScheduler::$plugin->check->exampleService()
     *
     * @return mixed
     */
    public function exampleService()
    {
        $result = 'something';
        // Check our Plugin's settings for `someAttribute`
        if (CommerceEmailScheduler::$plugin->getSettings()->someAttribute) {
        }

        return $result;
    }

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     CommerceEmailScheduler::$plugin->check->fetchOrders()
     *
     * @return mixed
     */
    public function fetchOrders($productTypeId = null, $dateFieldHandle = null, $days = 0)
    {

        // prep the return object
        $return = new \stdClass();
        $return->error = '';
        $return->result = '';
        $return->productIds = [];
        $return->orders = [];

        // check the essential vars exist
        if (!$productTypeId || ! $dateFieldHandle) {
            $return->error = "You must provide both a product type ID and a handle for the date field.";
            return $return;
        }

        // check the date field exists
        $field = Craft::$app->fields->getFieldByHandle($dateFieldHandle);
        if (!$field) {
            $return->error = "No field exists with the handle '$dateFieldHandle'";
            return $return;
        }

        // check the product type exists
        $productType = Commerce::getInstance()->productTypes->getProductTypeById($productTypeId);
        if (!$productType) {
            $return->error = "No product type exists with the ID $productTypeId";
            return $return;
        }        
        
        // prep the dates (beginning and end of a single day) to compare
        $start = new \DateTime(('+' . ($days - 1) . ' days'));
        $start = $start->format(\DateTime::ATOM);
        
        $end = new \DateTime('+' . $days . ' days');
        $end = $end->format(\DateTime::ATOM);

        // get any products on the date
        $productIds = \craft\commerce\elements\Product::find()
            ->date(['and', ">= {$start}", "< {$end}"])
            ->ids();

        // inform if there aren't any matches
        if (!$productIds) {
            $return->result = "No products with product type ID $productTypeId match this date.";
            return $return;
        }

        $return->productIds = $productIds;

        // create an array of purchasables based on the protuct IDs
        $purchasables = \craft\commerce\elements\Variant::find()
            ->productId($productIds)
            ->all();

        $orders = \craft\commerce\elements\Order::find()
            ->hasPurchasables($purchasables)
            ->isCompleted()
            ->all();

        if (!$orders) {
            $return->success = "No orders contain purchases of this product.";
        } else {
            $return->success = "There are " . count($orders) . " orders containing purchases of this product.";
            $return->orders = $orders;
        }

        return $return;
    }



}
