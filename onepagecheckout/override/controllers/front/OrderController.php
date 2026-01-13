<?php
/**
 * Override OrderController to redirect to One Page Checkout
 * This file is automatically installed by the onepagecheckout module
 */

class OrderController extends OrderControllerCore
{
    public function init()
    {
        // Check if One Page Checkout module is active and enabled
        if (Module::isEnabled('onepagecheckout') && Configuration::get('OPC_ENABLED')) {
            // Redirect to our one page checkout
            Tools::redirect(
                $this->context->link->getModuleLink('onepagecheckout', 'checkout')
            );
        }

        // Fallback to default behavior if module is disabled
        parent::init();
    }
}
