<?php
/**
 * One Page Checkout Front Controller
 * Main controller for the checkout page
 */

class OnePageCheckoutCheckoutModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    protected $checkout_session;

    public function init()
    {
        parent::init();

        // Redirect if cart is empty
        if (!$this->context->cart->nbProducts()) {
            Tools::redirect('index.php?controller=cart');
        }

        // Initialize checkout session data
        $this->initCheckoutSession();
    }

    protected function initCheckoutSession()
    {
        if (!isset($this->context->cookie->opc_checkout_session)) {
            $this->checkout_session = [
                'step' => 'personal_info',
                'guest_email' => '',
                'customer_type' => 'private',
                'wants_invoice' => false,
            ];
            $this->context->cookie->opc_checkout_session = json_encode($this->checkout_session);
        } else {
            $this->checkout_session = json_decode($this->context->cookie->opc_checkout_session, true);
        }
    }

    public function setMedia()
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-opc-styles',
            'modules/' . $this->module->name . '/views/css/onepagecheckout.css',
            ['media' => 'all', 'priority' => 200]
        );

        $this->registerJavascript(
            'module-opc-scripts',
            'modules/' . $this->module->name . '/views/js/onepagecheckout.js',
            ['position' => 'bottom', 'priority' => 200]
        );

        Media::addJsDef([
            'opc_ajax_url' => $this->context->link->getModuleLink($this->module->name, 'ajax'),
            'opc_token' => Tools::getToken(false),
            'opc_texts' => [
                'error_required' => $this->trans('Questo campo è obbligatorio', [], 'Modules.Onepagecheckout.Shop'),
                'error_email' => $this->trans('Inserisci un indirizzo email valido', [], 'Modules.Onepagecheckout.Shop'),
                'error_phone' => $this->trans('Inserisci un numero di telefono valido', [], 'Modules.Onepagecheckout.Shop'),
                'processing' => $this->trans('Elaborazione in corso...', [], 'Modules.Onepagecheckout.Shop'),
            ],
        ]);
    }

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $customer = $this->context->customer;

        // Check if customer is logged in
        $is_logged = $this->context->customer->isLogged();

        // Get customer data if logged in
        $customer_data = $this->getCustomerData();

        // Get address data
        $addresses = $this->getCustomerAddresses();

        // Get cart summary
        $cart_summary = $this->getCartSummary();

        // Get available carriers
        $carriers = $this->getAvailableCarriers();

        // Get available payment methods
        $payment_options = $this->getPaymentOptions();

        // Get countries list
        $countries = Country::getCountries($this->context->language->id, true);

        // Get customer groups for B2B/B2C
        $customer_types = [
            ['value' => 'private', 'label' => $this->trans('Privato', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => 'company', 'label' => $this->trans('Azienda', [], 'Modules.Onepagecheckout.Shop')],
        ];

        // Build date dropdowns
        $days = range(1, 31);
        $months = $this->getMonthsList();
        $years = range(date('Y') - 100, date('Y') - 16);
        rsort($years);

        $this->context->smarty->assign([
            'is_logged' => $is_logged,
            'customer_data' => $customer_data,
            'addresses' => $addresses,
            'cart_summary' => $cart_summary,
            'carriers' => $carriers,
            'payment_options' => $payment_options,
            'countries' => $countries,
            'customer_types' => $customer_types,
            'days' => $days,
            'months' => $months,
            'years' => $years,
            'guest_checkout_enabled' => (bool)Configuration::get('OPC_GUEST_CHECKOUT'),
            'show_newsletter' => (bool)Configuration::get('OPC_SHOW_NEWSLETTER'),
            'require_phone' => (bool)Configuration::get('OPC_REQUIRE_PHONE'),
            'checkout_session' => $this->checkout_session,
            'id_cart' => (int)$cart->id,
            'conditions_to_approve' => $this->getConditionsToApprove(),
            'termsLink' => $this->context->link->getCMSLink(
                Configuration::get('PS_CONDITIONS_CMS_ID'),
                null,
                null,
                $this->context->language->id
            ),
        ]);

        $this->setTemplate('module:onepagecheckout/views/templates/front/checkout.tpl');
    }

    protected function getCustomerData()
    {
        $customer = $this->context->customer;

        if (!$customer->isLogged()) {
            return [
                'id' => 0,
                'firstname' => '',
                'lastname' => '',
                'email' => '',
                'birthday' => '',
                'phone' => '',
                'phone_mobile' => '',
            ];
        }

        // Get primary address phone if available
        $phone = '';
        $phone_mobile = '';
        $addresses = $customer->getAddresses($this->context->language->id);
        if (!empty($addresses)) {
            $phone = $addresses[0]['phone'];
            $phone_mobile = $addresses[0]['phone_mobile'];
        }

        return [
            'id' => (int)$customer->id,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'email' => $customer->email,
            'birthday' => $customer->birthday,
            'phone' => $phone,
            'phone_mobile' => $phone_mobile,
        ];
    }

    protected function getCustomerAddresses()
    {
        $customer = $this->context->customer;

        if (!$customer->isLogged()) {
            return [];
        }

        return $customer->getAddresses($this->context->language->id);
    }

    protected function getCartSummary()
    {
        $cart = $this->context->cart;
        $products = $cart->getProducts(true);
        $cart_rules = $cart->getCartRules();

        $products_formatted = [];
        foreach ($products as $product) {
            $products_formatted[] = [
                'id_product' => $product['id_product'],
                'id_product_attribute' => $product['id_product_attribute'],
                'name' => $product['name'],
                'reference' => $product['reference'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'price_wt' => $product['price_wt'],
                'total' => $product['total'],
                'total_wt' => $product['total_wt'],
                'image' => $this->context->link->getImageLink(
                    $product['link_rewrite'],
                    $product['id_image'],
                    'cart_default'
                ),
                'attributes' => isset($product['attributes']) ? $product['attributes'] : '',
                'availability_date' => isset($product['available_date']) ? $product['available_date'] : '',
                'stock_quantity' => $product['stock_quantity'],
            ];
        }

        $subtotal = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $shipping = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $discounts = $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $total = $cart->getOrderTotal(true, Cart::BOTH);

        return [
            'products' => $products_formatted,
            'products_count' => count($products),
            'cart_rules' => $cart_rules,
            'subtotal' => $subtotal,
            'subtotal_formatted' => Tools::displayPrice($subtotal),
            'shipping' => $shipping,
            'shipping_formatted' => Tools::displayPrice($shipping),
            'discounts' => $discounts,
            'discounts_formatted' => Tools::displayPrice($discounts),
            'total' => $total,
            'total_formatted' => Tools::displayPrice($total),
        ];
    }

    protected function getAvailableCarriers()
    {
        $cart = $this->context->cart;
        $id_address = (int)$cart->id_address_delivery;

        if (!$id_address && $this->context->customer->isLogged()) {
            $addresses = $this->context->customer->getAddresses($this->context->language->id);
            if (!empty($addresses)) {
                $id_address = (int)$addresses[0]['id_address'];
                $cart->id_address_delivery = $id_address;
                $cart->save();
            }
        }

        if ($id_address) {
            $id_zone = Address::getZoneById($id_address);
        } else {
            $id_zone = (int)Country::getIdZone((int)Configuration::get('PS_COUNTRY_DEFAULT'));
        }

        $carriers = Carrier::getCarriersForOrder($id_zone, null, $cart);

        $carriers_formatted = [];
        foreach ($carriers as $carrier) {
            $carrier_obj = new Carrier((int)$carrier['id_carrier']);
            $shipping_cost = $cart->getPackageShippingCost((int)$carrier['id_carrier'], true, null, null, $id_zone);

            $carriers_formatted[] = [
                'id_carrier' => (int)$carrier['id_carrier'],
                'name' => $carrier['name'],
                'delay' => $carrier['delay'],
                'price' => $shipping_cost,
                'price_formatted' => Tools::displayPrice($shipping_cost),
                'logo' => $carrier_obj->logo ? _THEME_SHIP_DIR_ . $carrier_obj->logo : '',
                'is_free' => $carrier['is_free'],
                'selected' => ((int)$cart->id_carrier === (int)$carrier['id_carrier']),
            ];
        }

        return $carriers_formatted;
    }

    protected function getPaymentOptions()
    {
        $payment_options = [];
        $payment_modules = PaymentModule::getInstalledPaymentModules();

        foreach ($payment_modules as $module_info) {
            $module = Module::getInstanceByName($module_info['name']);
            if (!$module || !$module->active) {
                continue;
            }

            // Check if module has payment options method (PS 1.7+)
            if (method_exists($module, 'getPaymentOptions')) {
                try {
                    $options = $module->getPaymentOptions($this->getCheckoutSession());
                    if (is_array($options)) {
                        foreach ($options as $option) {
                            $payment_options[] = [
                                'module_name' => $module_info['name'],
                                'call_to_action_text' => $option->getCallToActionText(),
                                'logo' => $option->getLogo(),
                                'action' => $option->getAction(),
                                'inputs' => $option->getInputs(),
                                'form' => $option->getForm(),
                                'binary' => $option->isBinary(),
                                'additional_information' => $option->getAdditionalInformation(),
                            ];
                        }
                    }
                } catch (Exception $e) {
                    // Module doesn't support this checkout, skip
                    continue;
                }
            }
        }

        return $payment_options;
    }

    protected function getCheckoutSession()
    {
        $delivery_option = $this->context->cart->getDeliveryOption(null, false, false);
        $delivery_address = new Address((int)$this->context->cart->id_address_delivery);

        return new PrestaShop\PrestaShop\Adapter\Cart\CartCheckoutContext(
            $this->context->cart,
            $this->context->language,
            $this->context->currency,
            $this->context->customer
        );
    }

    protected function getConditionsToApprove()
    {
        $cms = new CMS((int)Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);

        $conditions = [];

        if (Configuration::get('PS_CONDITIONS')) {
            $conditions['terms-and-conditions'] = [
                'label' => $this->trans(
                    'Accetto i [1]termini e condizioni[/1] generali di vendita',
                    ['[1]' => '<a href="' . $this->context->link->getCMSLink($cms) . '" target="_blank">', '[/1]' => '</a>'],
                    'Modules.Onepagecheckout.Shop'
                ),
                'required' => true,
            ];
        }

        return $conditions;
    }

    protected function getMonthsList()
    {
        return [
            ['value' => '01', 'label' => $this->trans('Gennaio', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '02', 'label' => $this->trans('Febbraio', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '03', 'label' => $this->trans('Marzo', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '04', 'label' => $this->trans('Aprile', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '05', 'label' => $this->trans('Maggio', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '06', 'label' => $this->trans('Giugno', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '07', 'label' => $this->trans('Luglio', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '08', 'label' => $this->trans('Agosto', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '09', 'label' => $this->trans('Settembre', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '10', 'label' => $this->trans('Ottobre', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '11', 'label' => $this->trans('Novembre', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => '12', 'label' => $this->trans('Dicembre', [], 'Modules.Onepagecheckout.Shop')],
        ];
    }
}
