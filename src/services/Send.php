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
use craft\mail\Message;

/**
 * Send Service
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
class Send extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     CommerceEmailScheduler::$plugin->send->exampleService()
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
     *     CommerceEmailScheduler::$plugin->send->exampleService()
     *
     * @return mixed
     */
    public function sendScheduledEmails($orders = [], $templateId = 0, $productIds = [])
    {

        // prep the return object
        $return = new \stdClass();

        $emailTemplate = Commerce::getInstance()->emails->getEmailById($templateId);
        if (!$emailTemplate) {
            $return->error = "Email template does not exist within Craft Commerce";
            return $return;
        }        
        if (!$emailTemplate->enabled) {
            $return->error = "Email template is not enabled.";
            return $return;
        }

        foreach ($orders as $order) {
            $return->attempts[] = CommerceEmailScheduler::$plugin->send->sendEmail($order, $emailTemplate, $productIds);
        }

        return $return;

    }

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     CommerceEmailScheduler::$plugin->send->sendEmail()
     *
     * @return mixed
     */
    public function sendEmail($order, $emailTemplate, $productIds)
    {

        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

        $renderVariables = compact('order', 'productIds');
        $settings = Craft::$app->systemSettings->getSettings('email');

        $newEmail = new Message();

        // TO ADDRESS
        $newEmail->setTo($order->email);

        // FROM ADDRESS
        $newEmail->setFrom([$settings['fromEmail'] => $settings['fromName']]);
        $emailOverride = Commerce::getInstance()->getSettings()->emailSenderAddress;
        $nameOverride  = Commerce::getInstance()->getSettings()->emailSenderName;
        if ($emailOverride) {
            $newEmail->setFrom($emailOverride);
        }   

        // BCC ADDRESS
        if ($emailTemplate->bcc) {
            $bcc = $view->renderString($emailTemplate->bcc, $renderVariables);
            $bcc = str_replace(';', ',', $bcc);
            $bcc = preg_split('/[\s,]+/', $bcc);
            if (array_filter($bcc)) {
                $newEmail->setBcc($bcc);
            }
        }

        // SUBJECT
        $newEmail->setSubject($view->renderString($emailTemplate->subject, $renderVariables));

        // TEMPLATE PATH
        $templatePath = $view->renderString($emailTemplate->templatePath, $renderVariables);

        $body = $view->renderTemplate($templatePath, $renderVariables); 
        $newEmail->setHtmlBody($body);

        // SEND IT
        if (!Craft::$app->mailer->send($newEmail)) {
            return "Problem sending email for for Order " . $order->id . " to " . $order->email . ".";
        }

        return "Email sent for Order " . $order->id . " to " . $order->email . ".";
    }
}
