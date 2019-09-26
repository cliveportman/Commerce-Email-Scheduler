<?php
/**
 * Commerce email scheduler plugin for Craft CMS 3.x
 *
 * Email scheduler for Craft Commerce
 *
 * @link      https://clive.theportman.co
 * @copyright Copyright (c) 2019 Clive Portman
 */

namespace cliveportman\commerceemailscheduler\variables;

use cliveportman\commerceemailscheduler\CommerceEmailScheduler;

use Craft;

/**
 * Commerce email scheduler Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.commerceEmailScheduler }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Clive Portman
 * @package   CommerceEmailScheduler
 * @since     1.0.0
 */
class CommerceEmailSchedulerVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.commerceEmailScheduler.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.commerceEmailScheduler.exampleVariable(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function exampleVariable($optional = null)
    {
        $result = "And away we go to the Twig template...";
        if ($optional) {
            $result = "I'm feeling optional today...";
        }
        return $result;
    }

    /**
     * Call this with a daily cron job, to check for orders with a scheduled email.
     * An email will be sent to each order customer where the order contains the product type with a date matching the date being targeted.
     * 
     * The template called by the cron job
     * should trigger like so, passing a hash variable from the URL
     * to help prevent accidental triggers
     * 
     *     {{ craft.commerceEmailScheduler.checkOrders(hash, days, productType, dateField) }}
     *      hash = string to match for security
     *      days = int, number of days from the product date field that we're matching
     *      productTypeId = int, id of product type to search orders for
     *      dateFieldHandle = string, slug for the product field containing the date
     *
     * @param null $hash
     * @return string
     */   
    public function checkOrders(
        $hash = null,
        $productTypeId = 0, 
        $dateFieldHandle = null, 
        $days = 0,
        $commerceEmailTemplateId = 0
    )
    {
        if ($hash != 'abcdef') {
            return false;
        }

        $fetch = CommerceEmailScheduler::$plugin->check->fetchOrders($productTypeId, $dateFieldHandle, $days);
        $result = CommerceEmailScheduler::$plugin->send->sendScheduledEmails($fetch->orders, $commerceEmailTemplateId, $fetch->productIds);

        return $result;

    }
}
