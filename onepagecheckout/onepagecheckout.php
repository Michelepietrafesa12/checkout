<?php
/**
 * One Page Checkout Module for PrestaShop 1.7
 *
 * @author Developer
 * @version 1.0.0
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OnePageCheckout extends Module
{
    public function __construct()
    {
        $this->name = 'onepagecheckout';
        $this->tab = 'checkout';
        $this->version = '1.0.0';
        $this->author = 'Developer';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('One Page Checkout');
        $this->description = $this->l('Checkout completo in una sola pagina per PrestaShop 1.7');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionFrontControllerSetMedia')
            && Configuration::updateValue('OPC_GUEST_CHECKOUT', 1)
            && Configuration::updateValue('OPC_SHOW_NEWSLETTER', 1)
            && Configuration::updateValue('OPC_REQUIRE_PHONE', 0);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('OPC_GUEST_CHECKOUT')
            && Configuration::deleteByName('OPC_SHOW_NEWSLETTER')
            && Configuration::deleteByName('OPC_REQUIRE_PHONE');
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitOpcSettings')) {
            Configuration::updateValue('OPC_GUEST_CHECKOUT', (int)Tools::getValue('OPC_GUEST_CHECKOUT'));
            Configuration::updateValue('OPC_SHOW_NEWSLETTER', (int)Tools::getValue('OPC_SHOW_NEWSLETTER'));
            Configuration::updateValue('OPC_REQUIRE_PHONE', (int)Tools::getValue('OPC_REQUIRE_PHONE'));
            $output .= $this->displayConfirmation($this->l('Impostazioni salvate'));
        }

        return $output . $this->renderConfigForm();
    }

    protected function renderConfigForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Impostazioni One Page Checkout'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Abilita checkout ospite'),
                        'name' => 'OPC_GUEST_CHECKOUT',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Mostra checkbox newsletter'),
                        'name' => 'OPC_SHOW_NEWSLETTER',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Telefono obbligatorio'),
                        'name' => 'OPC_REQUIRE_PHONE',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Sì')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Salva'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitOpcSettings';
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->fields_value = [
            'OPC_GUEST_CHECKOUT' => Configuration::get('OPC_GUEST_CHECKOUT'),
            'OPC_SHOW_NEWSLETTER' => Configuration::get('OPC_SHOW_NEWSLETTER'),
            'OPC_REQUIRE_PHONE' => Configuration::get('OPC_REQUIRE_PHONE'),
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function hookDisplayHeader()
    {
        // Only on our checkout page
        if ($this->context->controller instanceof OnePageCheckoutModuleFrontController) {
            $this->context->controller->addCSS($this->_path . 'views/css/onepagecheckout.css');
            $this->context->controller->addJS($this->_path . 'views/js/onepagecheckout.js');
        }
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if ($this->context->controller instanceof OnePageCheckoutModuleFrontController) {
            Media::addJsDef([
                'opc_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax'),
                'opc_token' => Tools::getToken(false),
            ]);
        }
    }

    /**
     * Get available payment modules for checkout
     * Note: Method renamed to avoid conflict with ModuleCore::getPaymentModules()
     */
    public function getActivePaymentModulesForCheckout()
    {
        return PaymentModule::getInstalledPaymentModules();
    }

    /**
     * Get available carriers for the cart
     */
    public function getAvailableCarriers($id_address = null)
    {
        $cart = $this->context->cart;

        if (!$id_address) {
            $id_address = (int)$cart->id_address_delivery;
        }

        if (!$id_address) {
            // Use default country if no address
            $id_zone = (int)Country::getIdZone((int)Configuration::get('PS_COUNTRY_DEFAULT'));
        } else {
            $address = new Address($id_address);
            $id_zone = (int)Address::getZoneById($id_address);
        }

        $carriers = Carrier::getCarriersForOrder($id_zone, null, $cart);

        return $carriers;
    }
}
