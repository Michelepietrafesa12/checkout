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
        // Install override manually for reliability
        if (!$this->installOverrideFiles()) {
            $this->_errors[] = $this->l('Impossibile installare gli override');
            return false;
        }

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionDispatcher')
            && $this->registerHook('moduleRoutes')
            && Configuration::updateValue('OPC_GUEST_CHECKOUT', 1)
            && Configuration::updateValue('OPC_SHOW_NEWSLETTER', 1)
            && Configuration::updateValue('OPC_REQUIRE_PHONE', 0)
            && Configuration::updateValue('OPC_ENABLED', 1)
            // Color settings - Shopify-like defaults
            && Configuration::updateValue('OPC_COLOR_PRIMARY', '#008060')
            && Configuration::updateValue('OPC_COLOR_PRIMARY_HOVER', '#006e52')
            && Configuration::updateValue('OPC_COLOR_TEXT', '#333333')
            && Configuration::updateValue('OPC_COLOR_TEXT_SECONDARY', '#6b7177')
            && Configuration::updateValue('OPC_COLOR_BORDER', '#d9d9d9')
            && Configuration::updateValue('OPC_COLOR_BACKGROUND', '#fafafa')
            && Configuration::updateValue('OPC_COLOR_ERROR', '#d72c0d')
            && Configuration::updateValue('OPC_COLOR_SUCCESS', '#008060')
            && Configuration::updateValue('OPC_BUTTON_RADIUS', '5');
    }

    public function uninstall()
    {
        // Remove override
        $this->uninstallOverrideFiles();

        return parent::uninstall()
            && Configuration::deleteByName('OPC_GUEST_CHECKOUT')
            && Configuration::deleteByName('OPC_SHOW_NEWSLETTER')
            && Configuration::deleteByName('OPC_REQUIRE_PHONE')
            && Configuration::deleteByName('OPC_ENABLED')
            && Configuration::deleteByName('OPC_COLOR_PRIMARY')
            && Configuration::deleteByName('OPC_COLOR_PRIMARY_HOVER')
            && Configuration::deleteByName('OPC_COLOR_TEXT')
            && Configuration::deleteByName('OPC_COLOR_TEXT_SECONDARY')
            && Configuration::deleteByName('OPC_COLOR_BORDER')
            && Configuration::deleteByName('OPC_COLOR_BACKGROUND')
            && Configuration::deleteByName('OPC_COLOR_ERROR')
            && Configuration::deleteByName('OPC_COLOR_SUCCESS')
            && Configuration::deleteByName('OPC_BUTTON_RADIUS');
    }

    /**
     * Install override files manually
     */
    protected function installOverrideFiles()
    {
        $override_src = $this->getLocalPath() . 'override/controllers/front/OrderController.php';
        $override_dst = _PS_OVERRIDE_DIR_ . 'controllers/front/OrderController.php';

        // Create directory if not exists
        if (!is_dir(_PS_OVERRIDE_DIR_ . 'controllers/front/')) {
            @mkdir(_PS_OVERRIDE_DIR_ . 'controllers/front/', 0755, true);
        }

        // Check if override already exists from another module
        if (file_exists($override_dst)) {
            // Read existing override to check if it's ours or another module's
            $existing_content = file_get_contents($override_dst);
            if (strpos($existing_content, 'onepagecheckout') === false) {
                // Another module's override exists, we'll use hook method instead
                Configuration::updateValue('OPC_USE_HOOK_REDIRECT', 1);
                return true;
            }
        }

        // Copy our override file
        if (file_exists($override_src)) {
            if (@copy($override_src, $override_dst)) {
                // Clear class index cache
                if (function_exists('opcache_reset')) {
                    @opcache_reset();
                }
                // Try to clear PrestaShop class index
                $class_index = _PS_ROOT_DIR_ . '/app/cache/dev/class_index.php';
                $class_index_prod = _PS_ROOT_DIR_ . '/app/cache/prod/class_index.php';
                @unlink($class_index);
                @unlink($class_index_prod);
                // Also try var/cache for newer PS versions
                @unlink(_PS_ROOT_DIR_ . '/var/cache/dev/class_index.php');
                @unlink(_PS_ROOT_DIR_ . '/var/cache/prod/class_index.php');

                Configuration::updateValue('OPC_USE_HOOK_REDIRECT', 0);
                return true;
            }
        }

        // Fallback to hook-based redirect
        Configuration::updateValue('OPC_USE_HOOK_REDIRECT', 1);
        return true;
    }

    /**
     * Uninstall override files
     */
    protected function uninstallOverrideFiles()
    {
        $override_file = _PS_OVERRIDE_DIR_ . 'controllers/front/OrderController.php';

        if (file_exists($override_file)) {
            $content = file_get_contents($override_file);
            // Only remove if it's our override
            if (strpos($content, 'onepagecheckout') !== false) {
                @unlink($override_file);
                // Clear cache
                @unlink(_PS_ROOT_DIR_ . '/app/cache/dev/class_index.php');
                @unlink(_PS_ROOT_DIR_ . '/app/cache/prod/class_index.php');
                @unlink(_PS_ROOT_DIR_ . '/var/cache/dev/class_index.php');
                @unlink(_PS_ROOT_DIR_ . '/var/cache/prod/class_index.php');
            }
        }

        Configuration::deleteByName('OPC_USE_HOOK_REDIRECT');
        return true;
    }

    /**
     * Hook to redirect checkout via dispatcher (fallback method)
     */
    public function hookActionDispatcher($params)
    {
        // Check if OPC is enabled
        if (!Configuration::get('OPC_ENABLED')) {
            return;
        }

        // Only use this method if override didn't work
        if (!Configuration::get('OPC_USE_HOOK_REDIRECT')) {
            return;
        }

        // Check if we're on the order controller (checkout)
        $controller = Tools::getValue('controller');
        if ($controller === 'order' || $controller === 'orderopc') {
            // Prevent redirect loop
            if (Tools::getValue('module') !== 'onepagecheckout') {
                Tools::redirect(
                    $this->context->link->getModuleLink('onepagecheckout', 'checkout')
                );
            }
        }
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitOpcSettings')) {
            Configuration::updateValue('OPC_ENABLED', (int)Tools::getValue('OPC_ENABLED'));
            Configuration::updateValue('OPC_GUEST_CHECKOUT', (int)Tools::getValue('OPC_GUEST_CHECKOUT'));
            Configuration::updateValue('OPC_SHOW_NEWSLETTER', (int)Tools::getValue('OPC_SHOW_NEWSLETTER'));
            Configuration::updateValue('OPC_REQUIRE_PHONE', (int)Tools::getValue('OPC_REQUIRE_PHONE'));
            $output .= $this->displayConfirmation($this->l('Impostazioni salvate'));
        }

        if (Tools::isSubmit('submitOpcColors')) {
            Configuration::updateValue('OPC_COLOR_PRIMARY', pSQL(Tools::getValue('OPC_COLOR_PRIMARY')));
            Configuration::updateValue('OPC_COLOR_PRIMARY_HOVER', pSQL(Tools::getValue('OPC_COLOR_PRIMARY_HOVER')));
            Configuration::updateValue('OPC_COLOR_TEXT', pSQL(Tools::getValue('OPC_COLOR_TEXT')));
            Configuration::updateValue('OPC_COLOR_TEXT_SECONDARY', pSQL(Tools::getValue('OPC_COLOR_TEXT_SECONDARY')));
            Configuration::updateValue('OPC_COLOR_BORDER', pSQL(Tools::getValue('OPC_COLOR_BORDER')));
            Configuration::updateValue('OPC_COLOR_BACKGROUND', pSQL(Tools::getValue('OPC_COLOR_BACKGROUND')));
            Configuration::updateValue('OPC_COLOR_ERROR', pSQL(Tools::getValue('OPC_COLOR_ERROR')));
            Configuration::updateValue('OPC_COLOR_SUCCESS', pSQL(Tools::getValue('OPC_COLOR_SUCCESS')));
            Configuration::updateValue('OPC_BUTTON_RADIUS', (int)Tools::getValue('OPC_BUTTON_RADIUS'));
            $output .= $this->displayConfirmation($this->l('Colori salvati'));
        }

        if (Tools::isSubmit('resetOpcColors')) {
            // Reset to Shopify-like defaults
            Configuration::updateValue('OPC_COLOR_PRIMARY', '#008060');
            Configuration::updateValue('OPC_COLOR_PRIMARY_HOVER', '#006e52');
            Configuration::updateValue('OPC_COLOR_TEXT', '#333333');
            Configuration::updateValue('OPC_COLOR_TEXT_SECONDARY', '#6b7177');
            Configuration::updateValue('OPC_COLOR_BORDER', '#d9d9d9');
            Configuration::updateValue('OPC_COLOR_BACKGROUND', '#fafafa');
            Configuration::updateValue('OPC_COLOR_ERROR', '#d72c0d');
            Configuration::updateValue('OPC_COLOR_SUCCESS', '#008060');
            Configuration::updateValue('OPC_BUTTON_RADIUS', '5');
            $output .= $this->displayConfirmation($this->l('Colori ripristinati ai valori predefiniti (stile Shopify)'));
        }

        return $output . $this->renderConfigForm() . $this->renderColorForm();
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
                        'label' => $this->l('Abilita One Page Checkout'),
                        'desc' => $this->l('Sostituisce il checkout standard di PrestaShop'),
                        'name' => 'OPC_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'enabled_on', 'value' => 1, 'label' => $this->l('Sì')],
                            ['id' => 'enabled_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
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
            'OPC_ENABLED' => Configuration::get('OPC_ENABLED'),
            'OPC_GUEST_CHECKOUT' => Configuration::get('OPC_GUEST_CHECKOUT'),
            'OPC_SHOW_NEWSLETTER' => Configuration::get('OPC_SHOW_NEWSLETTER'),
            'OPC_REQUIRE_PHONE' => Configuration::get('OPC_REQUIRE_PHONE'),
        ];

        return $helper->generateForm([$fields_form]);
    }

    protected function renderColorForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Personalizzazione Colori e Stile'),
                    'icon' => 'icon-paint-brush',
                ],
                'description' => $this->l('Personalizza i colori del checkout. Lo stile predefinito è simile a Shopify per un\'esperienza utente moderna e pulita.'),
                'input' => [
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore Primario'),
                        'name' => 'OPC_COLOR_PRIMARY',
                        'desc' => $this->l('Colore principale per pulsanti e accenti (default Shopify: #008060)'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore Primario Hover'),
                        'name' => 'OPC_COLOR_PRIMARY_HOVER',
                        'desc' => $this->l('Colore quando il mouse passa sopra elementi primari'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore Testo Principale'),
                        'name' => 'OPC_COLOR_TEXT',
                        'desc' => $this->l('Colore del testo principale'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore Testo Secondario'),
                        'name' => 'OPC_COLOR_TEXT_SECONDARY',
                        'desc' => $this->l('Colore per testi secondari e descrizioni'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore Bordi'),
                        'name' => 'OPC_COLOR_BORDER',
                        'desc' => $this->l('Colore dei bordi e separatori'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore Sfondo'),
                        'name' => 'OPC_COLOR_BACKGROUND',
                        'desc' => $this->l('Colore di sfondo della pagina'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore Errore'),
                        'name' => 'OPC_COLOR_ERROR',
                        'desc' => $this->l('Colore per messaggi di errore'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->l('Colore Successo'),
                        'name' => 'OPC_COLOR_SUCCESS',
                        'desc' => $this->l('Colore per messaggi di successo'),
                        'class' => 'mColorPicker',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Raggio Bordi Pulsanti (px)'),
                        'name' => 'OPC_BUTTON_RADIUS',
                        'desc' => $this->l('Arrotondamento angoli dei pulsanti in pixel (Shopify usa 5px)'),
                        'class' => 'fixed-width-xs',
                        'suffix' => 'px',
                    ],
                ],
                'buttons' => [
                    [
                        'title' => $this->l('Ripristina Valori Shopify'),
                        'icon' => 'process-icon-refresh',
                        'name' => 'resetOpcColors',
                        'type' => 'submit',
                        'class' => 'btn btn-default pull-left',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Salva Colori'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitOpcColors';
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->fields_value = [
            'OPC_COLOR_PRIMARY' => Configuration::get('OPC_COLOR_PRIMARY') ?: '#008060',
            'OPC_COLOR_PRIMARY_HOVER' => Configuration::get('OPC_COLOR_PRIMARY_HOVER') ?: '#006e52',
            'OPC_COLOR_TEXT' => Configuration::get('OPC_COLOR_TEXT') ?: '#333333',
            'OPC_COLOR_TEXT_SECONDARY' => Configuration::get('OPC_COLOR_TEXT_SECONDARY') ?: '#6b7177',
            'OPC_COLOR_BORDER' => Configuration::get('OPC_COLOR_BORDER') ?: '#d9d9d9',
            'OPC_COLOR_BACKGROUND' => Configuration::get('OPC_COLOR_BACKGROUND') ?: '#fafafa',
            'OPC_COLOR_ERROR' => Configuration::get('OPC_COLOR_ERROR') ?: '#d72c0d',
            'OPC_COLOR_SUCCESS' => Configuration::get('OPC_COLOR_SUCCESS') ?: '#008060',
            'OPC_BUTTON_RADIUS' => Configuration::get('OPC_BUTTON_RADIUS') ?: '5',
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Get color configuration as CSS variables
     */
    public function getColorConfig()
    {
        return [
            'primary' => Configuration::get('OPC_COLOR_PRIMARY') ?: '#008060',
            'primary_hover' => Configuration::get('OPC_COLOR_PRIMARY_HOVER') ?: '#006e52',
            'text' => Configuration::get('OPC_COLOR_TEXT') ?: '#333333',
            'text_secondary' => Configuration::get('OPC_COLOR_TEXT_SECONDARY') ?: '#6b7177',
            'border' => Configuration::get('OPC_COLOR_BORDER') ?: '#d9d9d9',
            'background' => Configuration::get('OPC_COLOR_BACKGROUND') ?: '#fafafa',
            'error' => Configuration::get('OPC_COLOR_ERROR') ?: '#d72c0d',
            'success' => Configuration::get('OPC_COLOR_SUCCESS') ?: '#008060',
            'button_radius' => (Configuration::get('OPC_BUTTON_RADIUS') ?: '5') . 'px',
        ];
    }

    /**
     * Register module routes for friendly URL /checkout
     */
    public function hookModuleRoutes()
    {
        return [
            'module-onepagecheckout-checkout' => [
                'rule' => 'checkout',
                'keywords' => [],
                'controller' => 'checkout',
                'params' => [
                    'fc' => 'module',
                    'module' => 'onepagecheckout',
                ],
            ],
        ];
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
