<?php
/**
 * One Page Checkout Front Controller
 * Unified checkout page - PlusPower style
 */

/**
 * Simple checkout session object for payment modules
 * Provides the interface that payment modules expect
 */
class OpcCheckoutSession
{
    public $cart;
    public $customer;
    public $language;
    public $currency;

    public function __construct($cart, $customer, $language, $currency)
    {
        $this->cart = $cart;
        $this->customer = $customer;
        $this->language = $language;
        $this->currency = $currency;
    }

    public function getCart()
    {
        return $this->cart;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getCheckoutProcess()
    {
        return null;
    }
}

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

        $this->initCheckoutSession();
    }

    protected function initCheckoutSession()
    {
        if (!isset($this->context->cookie->opc_checkout_session)) {
            $this->checkout_session = [
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
        ]);
    }

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $customer = $this->context->customer;
        $is_logged = $customer->isLogged();

        // Get customer data
        $customer_data = $this->getCustomerData();

        // Get customer birthday parsed
        $customer_birthday = $this->parseCustomerBirthday($customer);

        // Get addresses
        $addresses = $this->getCustomerAddresses();

        // Get current address data (for pre-filling form)
        $address_data = $this->getCurrentAddressData($addresses);
        $selected_address_id = (int)$cart->id_address_delivery;

        // Get cart summary
        $cart_summary = $this->getCartSummary();

        // Get available carriers
        $carriers = $this->getAvailableCarriers();

        // Auto-select first carrier if none selected
        if (!$cart->id_carrier && !empty($carriers)) {
            $cart->id_carrier = (int)$carriers[0]['id_carrier'];
            $delivery_option = [];
            $delivery_option[(int)$cart->id_address_delivery] = $cart->id_carrier . ',';
            $cart->setDeliveryOption($delivery_option);
            $cart->save();
            // Refresh carriers to mark selected
            $carriers = $this->getAvailableCarriers();
        }

        // Get payment methods
        $payment_options = $this->getPaymentOptions();

        // Countries list
        $countries = Country::getCountries($this->context->language->id, true);

        // Customer types
        $customer_types = [
            ['value' => 'private', 'label' => $this->trans('Privato', [], 'Modules.Onepagecheckout.Shop')],
            ['value' => 'company', 'label' => $this->trans('Azienda', [], 'Modules.Onepagecheckout.Shop')],
        ];

        // Date dropdowns
        $days = range(1, 31);
        $months = $this->getMonthsList();
        $years = range(date('Y') - 100, date('Y') - 16);
        rsort($years);

        // Default country
        $default_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');

        // Terms configuration
        $show_terms = (bool)Configuration::get('PS_CONDITIONS');
        $terms_cms_id = (int)Configuration::get('PS_CONDITIONS_CMS_ID');
        $termsLink = $terms_cms_id ? $this->context->link->getCMSLink($terms_cms_id) : '';

        $this->context->smarty->assign([
            'is_logged' => $is_logged,
            'customer_data' => $customer_data,
            'customer_birthday' => $customer_birthday,
            'addresses' => $addresses,
            'address_data' => $address_data,
            'selected_address_id' => $selected_address_id,
            'cart_summary' => $cart_summary,
            'carriers' => $carriers,
            'payment_options' => $payment_options,
            'countries' => $countries,
            'customer_types' => $customer_types,
            'days' => $days,
            'months' => $months,
            'years' => $years,
            'default_country' => $default_country,
            'checkout_session' => $this->checkout_session,
            'show_terms' => $show_terms,
            'termsLink' => $termsLink,
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
            ];
        }

        return [
            'id' => (int)$customer->id,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'email' => $customer->email,
            'birthday' => $customer->birthday,
        ];
    }

    protected function parseCustomerBirthday($customer)
    {
        if (!$customer->isLogged() || empty($customer->birthday) || $customer->birthday == '0000-00-00') {
            return ['day' => '', 'month' => '', 'year' => ''];
        }

        $parts = explode('-', $customer->birthday);
        return [
            'year' => isset($parts[0]) ? $parts[0] : '',
            'month' => isset($parts[1]) ? $parts[1] : '',
            'day' => isset($parts[2]) ? $parts[2] : '',
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

    protected function getCurrentAddressData($addresses)
    {
        $cart = $this->context->cart;

        // If cart has delivery address, load it
        if ($cart->id_address_delivery) {
            $address = new Address((int)$cart->id_address_delivery);
            if (Validate::isLoadedObject($address)) {
                return [
                    'id_address' => (int)$address->id,
                    'firstname' => $address->firstname,
                    'lastname' => $address->lastname,
                    'company' => $address->company,
                    'vat_number' => $address->vat_number,
                    'address1' => $address->address1,
                    'address2' => $address->address2,
                    'postcode' => $address->postcode,
                    'city' => $address->city,
                    'id_country' => (int)$address->id_country,
                    'id_state' => (int)$address->id_state,
                    'phone' => $address->phone,
                    'phone_mobile' => $address->phone_mobile,
                ];
            }
        }

        // If customer has addresses, use the first one
        if (!empty($addresses)) {
            $addr = $addresses[0];
            return [
                'id_address' => (int)$addr['id_address'],
                'firstname' => $addr['firstname'],
                'lastname' => $addr['lastname'],
                'company' => isset($addr['company']) ? $addr['company'] : '',
                'vat_number' => isset($addr['vat_number']) ? $addr['vat_number'] : '',
                'address1' => $addr['address1'],
                'address2' => isset($addr['address2']) ? $addr['address2'] : '',
                'postcode' => $addr['postcode'],
                'city' => $addr['city'],
                'id_country' => (int)$addr['id_country'],
                'id_state' => isset($addr['id_state']) ? (int)$addr['id_state'] : 0,
                'phone' => isset($addr['phone']) ? $addr['phone'] : '',
                'phone_mobile' => isset($addr['phone_mobile']) ? $addr['phone_mobile'] : '',
            ];
        }

        // Return empty data
        return [
            'id_address' => 0,
            'firstname' => '',
            'lastname' => '',
            'company' => '',
            'vat_number' => '',
            'address1' => '',
            'address2' => '',
            'postcode' => '',
            'city' => '',
            'id_country' => (int)Configuration::get('PS_COUNTRY_DEFAULT'),
            'id_state' => 0,
            'phone' => '',
            'phone_mobile' => '',
        ];
    }

    protected function getCartSummary()
    {
        $cart = $this->context->cart;
        $products = $cart->getProducts(true);
        $cart_rules = $cart->getCartRules();

        $products_formatted = [];
        foreach ($products as $product) {
            // Calculate if there's a discount
            $has_discount = isset($product['reduction_applies']) && $product['reduction_applies'];
            $regular_price = $has_discount && isset($product['price_without_reduction'])
                ? $product['price_without_reduction']
                : $product['price_wt'];

            $products_formatted[] = [
                'id_product' => $product['id_product'],
                'id_product_attribute' => $product['id_product_attribute'],
                'name' => $product['name'],
                'reference' => $product['reference'],
                'quantity' => $product['quantity'],
                'price_wt' => $product['price_wt'],
                'total_wt' => $product['total_wt'],
                'regular_price_formatted' => Tools::displayPrice($regular_price),
                'reduction_percent' => isset($product['reduction_percent']) ? $product['reduction_percent'] : 0,
                'image' => $this->context->link->getImageLink(
                    $product['link_rewrite'],
                    $product['id_image'],
                    'cart_default'
                ),
                'attributes' => isset($product['attributes']) ? $product['attributes'] : '',
            ];
        }

        $subtotal = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $shipping = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $discounts = $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $total = $cart->getOrderTotal(true, Cart::BOTH);

        // Format cart rules
        $cart_rules_formatted = [];
        foreach ($cart_rules as $rule) {
            $cart_rules_formatted[] = [
                'id_cart_rule' => $rule['id_cart_rule'],
                'name' => $rule['name'],
                'value' => Tools::displayPrice($rule['value_real']),
            ];
        }

        return [
            'products' => $products_formatted,
            'products_count' => count($products),
            'cart_rules' => $cart_rules_formatted,
            'subtotal' => $subtotal,
            'subtotal_formatted' => Tools::displayPrice($subtotal),
            'shipping' => $shipping,
            'shipping_formatted' => $shipping > 0 ? Tools::displayPrice($shipping) : $this->trans('Gratis', [], 'Modules.Onepagecheckout.Shop'),
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

        if (!$id_address) {
            // Use default zone
            $id_zone = (int)Country::getIdZone((int)Configuration::get('PS_COUNTRY_DEFAULT'));
        } else {
            $id_zone = (int)Address::getZoneById($id_address);
        }

        $carriers = Carrier::getCarriersForOrder($id_zone, null, $cart);

        $carriers_formatted = [];
        foreach ($carriers as $carrier) {
            $shipping_cost = $cart->getPackageShippingCost((int)$carrier['id_carrier'], true, null, null, $id_zone);

            $carriers_formatted[] = [
                'id_carrier' => (int)$carrier['id_carrier'],
                'name' => $carrier['name'],
                'delay' => $carrier['delay'],
                'price' => $shipping_cost,
                'price_formatted' => $shipping_cost > 0
                    ? Tools::displayPrice($shipping_cost)
                    : $this->trans('Gratis', [], 'Modules.Onepagecheckout.Shop'),
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

            // Check if module has getPaymentOptions method (PS 1.7+)
            if (method_exists($module, 'getPaymentOptions')) {
                try {
                    $checkout_session = $this->buildCheckoutSession();
                    $options = $module->getPaymentOptions($checkout_session);
                    if (is_array($options)) {
                        foreach ($options as $option) {
                            $additional_info = '';
                            if (method_exists($option, 'getAdditionalInformation')) {
                                $additional_info = $option->getAdditionalInformation();
                            }

                            $payment_options[] = [
                                'module_name' => $module_info['name'],
                                'call_to_action_text' => $option->getCallToActionText(),
                                'logo' => $option->getLogo(),
                                'action' => $option->getAction(),
                                'form' => $option->getForm(),
                                'additional_information' => $additional_info,
                                'binary' => method_exists($option, 'isBinary') ? $option->isBinary() : false,
                            ];
                        }
                    }
                } catch (Exception $e) {
                    // Log error for debugging
                    PrestaShopLogger::addLog(
                        'OPC Payment Error: ' . $module_info['name'] . ' - ' . $e->getMessage(),
                        2,
                        null,
                        'Module',
                        null,
                        true
                    );
                    continue;
                }
            }
        }

        return $payment_options;
    }

    protected function buildCheckoutSession()
    {
        // Return a simple object with the required properties
        // Payment modules access these via magic methods or direct properties
        return new OpcCheckoutSession(
            $this->context->cart,
            $this->context->customer,
            $this->context->language,
            $this->context->currency
        );
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
