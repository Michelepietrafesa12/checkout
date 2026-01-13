<?php
/**
 * AJAX Controller for One Page Checkout
 * Handles all dynamic operations without page reload
 */

// Include checkout session class if not already defined
if (!class_exists('OpcCheckoutSession')) {
    /**
     * Complete checkout session object for payment modules
     * Provides the full interface that PrestaShop payment modules expect
     */
    class OpcCheckoutSession
    {
        protected $cart;
        protected $customer;
        protected $language;
        protected $currency;
        protected $context;

        public function __construct($cart, $customer, $language, $currency)
        {
            $this->cart = $cart;
            $this->customer = $customer;
            $this->language = $language;
            $this->currency = $currency;
            $this->context = Context::getContext();
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

        public function getDeliveryAddress()
        {
            if ($this->cart->id_address_delivery) {
                return new Address((int)$this->cart->id_address_delivery);
            }
            return null;
        }

        public function getInvoiceAddress()
        {
            $id_address = $this->cart->id_address_invoice ?: $this->cart->id_address_delivery;
            if ($id_address) {
                return new Address((int)$id_address);
            }
            return null;
        }

        public function isAddressComplete()
        {
            if (!$this->cart->id_address_delivery) {
                return false;
            }
            $address = new Address((int)$this->cart->id_address_delivery);
            return Validate::isLoadedObject($address);
        }

        public function __get($name)
        {
            if (property_exists($this, $name)) {
                return $this->$name;
            }
            return null;
        }
    }
}

class OnePageCheckoutAjaxModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    protected $json_response = [];

    public function init()
    {
        parent::init();
        header('Content-Type: application/json');
    }

    public function postProcess()
    {
        $action = Tools::getValue('action');

        // Validate token for security
        if (!$this->validateToken()) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Token di sicurezza non valido', [], 'Modules.Onepagecheckout.Shop'),
            ];
            $this->sendJsonResponse();
            return;
        }

        switch ($action) {
            case 'saveCustomerInfo':
                $this->saveCustomerInfo();
                break;
            case 'saveAddress':
                $this->saveAddress();
                break;
            case 'selectAddress':
                $this->selectAddress();
                break;
            case 'updateCarrier':
                $this->updateCarrier();
                break;
            case 'applyDiscount':
                $this->applyDiscount();
                break;
            case 'removeDiscount':
                $this->removeDiscount();
                break;
            case 'updateCartQuantity':
                $this->updateCartQuantity();
                break;
            case 'getCartSummary':
                $this->getCartSummary();
                break;
            case 'processCheckout':
                $this->processCheckout();
                break;
            case 'createOrder':
                $this->createOrder();
                break;
            case 'loginCustomer':
                $this->loginCustomer();
                break;
            case 'checkEmail':
                $this->checkEmail();
                break;
            case 'getCarriers':
                $this->getCarriers();
                break;
            default:
                $this->json_response = [
                    'success' => false,
                    'error' => $this->trans('Azione non valida', [], 'Modules.Onepagecheckout.Shop'),
                ];
        }

        $this->sendJsonResponse();
    }

    protected function validateToken()
    {
        $token = Tools::getValue('token');
        return !empty($token) && $token === Tools::getToken(false);
    }

    protected function saveCustomerInfo()
    {
        $firstname = Tools::getValue('firstname');
        $lastname = Tools::getValue('lastname');
        $email = Tools::getValue('email');
        $birthday_day = Tools::getValue('birthday_day');
        $birthday_month = Tools::getValue('birthday_month');
        $birthday_year = Tools::getValue('birthday_year');
        $phone = Tools::getValue('phone');
        $phone_mobile = Tools::getValue('phone_mobile');
        $password = Tools::getValue('password');
        $customer_type = Tools::getValue('customer_type', 'private');
        $wants_invoice = (bool)Tools::getValue('wants_invoice', false);
        $newsletter = (bool)Tools::getValue('newsletter', false);

        // Validation
        $errors = [];

        if (empty($firstname)) {
            $errors[] = $this->trans('Il nome è obbligatorio', [], 'Modules.Onepagecheckout.Shop');
        }
        if (empty($lastname)) {
            $errors[] = $this->trans('Il cognome è obbligatorio', [], 'Modules.Onepagecheckout.Shop');
        }
        if (empty($email) || !Validate::isEmail($email)) {
            $errors[] = $this->trans('Inserisci un indirizzo email valido', [], 'Modules.Onepagecheckout.Shop');
        }

        if (!empty($errors)) {
            $this->json_response = ['success' => false, 'errors' => $errors];
            return;
        }

        $customer = $this->context->customer;
        $is_new_customer = false;

        // If not logged in, create guest or new customer
        if (!$customer->isLogged()) {
            // Check if email already exists
            $existing_customer = Customer::customerExists($email, true);

            if ($existing_customer) {
                // Email exists, require login
                $this->json_response = [
                    'success' => false,
                    'email_exists' => true,
                    'error' => $this->trans(
                        'Questo indirizzo email è già registrato. Effettua il login.',
                        [],
                        'Modules.Onepagecheckout.Shop'
                    ),
                ];
                return;
            }

            // Create new customer (guest or registered)
            $customer = new Customer();
            $customer->firstname = $firstname;
            $customer->lastname = $lastname;
            $customer->email = $email;
            $customer->newsletter = $newsletter;
            $customer->optin = $newsletter;
            $customer->is_guest = empty($password) ? 1 : 0;
            $customer->active = 1;

            // Set birthday if provided
            if ($birthday_day && $birthday_month && $birthday_year) {
                $customer->birthday = $birthday_year . '-' . $birthday_month . '-' . $birthday_day;
            }

            // If password provided, hash it
            if (!empty($password)) {
                if (!Validate::isPasswd($password)) {
                    $this->json_response = [
                        'success' => false,
                        'errors' => [$this->trans('La password deve essere di almeno 8 caratteri', [], 'Modules.Onepagecheckout.Shop')],
                    ];
                    return;
                }
                $customer->passwd = Tools::hash($password);
            } else {
                // Generate random password for guest
                $customer->passwd = Tools::hash(Tools::passwdGen(16));
            }

            if (!$customer->add()) {
                $this->json_response = [
                    'success' => false,
                    'errors' => [$this->trans('Errore nella creazione del cliente', [], 'Modules.Onepagecheckout.Shop')],
                ];
                return;
            }

            $is_new_customer = true;

            // Log in the customer
            $this->context->customer = $customer;
            $this->context->cookie->id_customer = (int)$customer->id;
            $this->context->cookie->customer_lastname = $customer->lastname;
            $this->context->cookie->customer_firstname = $customer->firstname;
            $this->context->cookie->passwd = $customer->passwd;
            $this->context->cookie->logged = 1;
            $this->context->cookie->email = $customer->email;
            $this->context->cookie->is_guest = $customer->is_guest;

            // Update cart with customer
            $this->context->cart->id_customer = (int)$customer->id;
            $this->context->cart->secure_key = $customer->secure_key;
            $this->context->cart->save();
        } else {
            // Update existing customer
            $customer->firstname = $firstname;
            $customer->lastname = $lastname;
            if ($birthday_day && $birthday_month && $birthday_year) {
                $customer->birthday = $birthday_year . '-' . $birthday_month . '-' . $birthday_day;
            }
            if (!empty($password) && Validate::isPasswd($password)) {
                $customer->passwd = Tools::hash($password);
                $this->context->cookie->passwd = $customer->passwd;
            }
            $customer->update();
        }

        // Store checkout session data
        $checkout_session = [
            'step' => 'address',
            'customer_type' => $customer_type,
            'wants_invoice' => $wants_invoice,
            'phone' => $phone,
            'phone_mobile' => $phone_mobile,
        ];
        $this->context->cookie->opc_checkout_session = json_encode($checkout_session);

        $this->json_response = [
            'success' => true,
            'is_new_customer' => $is_new_customer,
            'customer_id' => (int)$customer->id,
            'message' => $this->trans('Dati salvati con successo', [], 'Modules.Onepagecheckout.Shop'),
        ];
    }

    protected function saveAddress()
    {
        $customer = $this->context->customer;

        if (!$customer->id) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Cliente non trovato', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        $id_address = (int)Tools::getValue('id_address', 0);
        $address_type = Tools::getValue('address_type', 'delivery'); // delivery or invoice

        // Get address data
        $address_data = [
            'alias' => Tools::getValue('alias', $this->trans('Il mio indirizzo', [], 'Modules.Onepagecheckout.Shop')),
            'firstname' => Tools::getValue('firstname', $customer->firstname),
            'lastname' => Tools::getValue('lastname', $customer->lastname),
            'company' => Tools::getValue('company', ''),
            'vat_number' => Tools::getValue('vat_number', ''),
            'address1' => Tools::getValue('address1'),
            'address2' => Tools::getValue('address2', ''),
            'postcode' => Tools::getValue('postcode'),
            'city' => Tools::getValue('city'),
            'id_country' => (int)Tools::getValue('id_country', Configuration::get('PS_COUNTRY_DEFAULT')),
            'id_state' => (int)Tools::getValue('id_state', 0),
            'phone' => Tools::getValue('phone', ''),
            'phone_mobile' => Tools::getValue('phone_mobile', ''),
        ];

        // Validation
        $errors = [];
        if (empty($address_data['address1'])) {
            $errors[] = $this->trans('L\'indirizzo è obbligatorio', [], 'Modules.Onepagecheckout.Shop');
        }
        if (empty($address_data['postcode'])) {
            $errors[] = $this->trans('Il CAP è obbligatorio', [], 'Modules.Onepagecheckout.Shop');
        }
        if (empty($address_data['city'])) {
            $errors[] = $this->trans('La città è obbligatoria', [], 'Modules.Onepagecheckout.Shop');
        }

        if (!empty($errors)) {
            $this->json_response = ['success' => false, 'errors' => $errors];
            return;
        }

        // Create or update address
        if ($id_address) {
            $address = new Address($id_address);
            if ($address->id_customer != $customer->id) {
                $this->json_response = [
                    'success' => false,
                    'error' => $this->trans('Indirizzo non autorizzato', [], 'Modules.Onepagecheckout.Shop'),
                ];
                return;
            }
        } else {
            $address = new Address();
            $address->id_customer = (int)$customer->id;
        }

        // Apply data
        foreach ($address_data as $key => $value) {
            $address->{$key} = $value;
        }

        if ($id_address) {
            $result = $address->update();
        } else {
            $result = $address->add();
        }

        if (!$result) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Errore nel salvataggio dell\'indirizzo', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Update cart with address
        if ($address_type === 'delivery' || $address_type === 'both') {
            $this->context->cart->id_address_delivery = (int)$address->id;
        }
        if ($address_type === 'invoice' || $address_type === 'both') {
            $this->context->cart->id_address_invoice = (int)$address->id;
        }
        $this->context->cart->save();

        // Update cart products with new address
        $this->context->cart->setNoMultishipping();

        $this->json_response = [
            'success' => true,
            'id_address' => (int)$address->id,
            'message' => $this->trans('Indirizzo salvato con successo', [], 'Modules.Onepagecheckout.Shop'),
            'carriers' => $this->getCarriersData(),
            'cart_summary' => $this->getCartSummaryData(),
        ];
    }

    protected function selectAddress()
    {
        $id_address = (int)Tools::getValue('id_address');
        $address_type = Tools::getValue('address_type', 'delivery');
        $customer = $this->context->customer;

        if (!$id_address || !$customer->id) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Indirizzo non valido', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        $address = new Address($id_address);
        if ($address->id_customer != $customer->id) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Indirizzo non autorizzato', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        if ($address_type === 'delivery' || $address_type === 'both') {
            $this->context->cart->id_address_delivery = $id_address;
        }
        if ($address_type === 'invoice' || $address_type === 'both') {
            $this->context->cart->id_address_invoice = $id_address;
        }
        $this->context->cart->save();
        $this->context->cart->setNoMultishipping();

        $this->json_response = [
            'success' => true,
            'message' => $this->trans('Indirizzo selezionato', [], 'Modules.Onepagecheckout.Shop'),
            'carriers' => $this->getCarriersData(),
            'cart_summary' => $this->getCartSummaryData(),
        ];
    }

    protected function updateCarrier()
    {
        $id_carrier = (int)Tools::getValue('id_carrier');

        if (!$id_carrier) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Corriere non valido', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        $cart = $this->context->cart;

        // Set delivery option format expected by PrestaShop
        $delivery_option = [];
        $delivery_option[(int)$cart->id_address_delivery] = $id_carrier . ',';
        $cart->setDeliveryOption($delivery_option);
        $cart->id_carrier = $id_carrier;
        $cart->save();

        $this->json_response = [
            'success' => true,
            'message' => $this->trans('Corriere aggiornato', [], 'Modules.Onepagecheckout.Shop'),
            'cart_summary' => $this->getCartSummaryData(),
        ];
    }

    protected function applyDiscount()
    {
        $discount_code = Tools::getValue('discount_code');

        if (empty($discount_code)) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Inserisci un codice sconto', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        $cart_rule = new CartRule(CartRule::getIdByCode($discount_code));

        if (!Validate::isLoadedObject($cart_rule)) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Codice sconto non valido', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Check if cart rule is valid
        $error = $cart_rule->checkValidity($this->context, false, true);
        if ($error) {
            $this->json_response = [
                'success' => false,
                'error' => $error,
            ];
            return;
        }

        // Add cart rule
        $this->context->cart->addCartRule((int)$cart_rule->id);

        $this->json_response = [
            'success' => true,
            'message' => $this->trans('Codice sconto applicato', [], 'Modules.Onepagecheckout.Shop'),
            'cart_summary' => $this->getCartSummaryData(),
        ];
    }

    protected function removeDiscount()
    {
        $id_cart_rule = (int)Tools::getValue('id_cart_rule');

        if (!$id_cart_rule) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Codice sconto non valido', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        $this->context->cart->removeCartRule($id_cart_rule);

        $this->json_response = [
            'success' => true,
            'message' => $this->trans('Codice sconto rimosso', [], 'Modules.Onepagecheckout.Shop'),
            'cart_summary' => $this->getCartSummaryData(),
        ];
    }

    protected function updateCartQuantity()
    {
        $id_product = (int)Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);
        $quantity = (int)Tools::getValue('quantity');
        $operation = Tools::getValue('operation', 'update'); // update, up, down, delete

        if (!$id_product) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Prodotto non valido', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        $cart = $this->context->cart;

        if ($operation === 'delete' || $quantity <= 0) {
            $result = $cart->deleteProduct($id_product, $id_product_attribute);
        } elseif ($operation === 'up') {
            $result = $cart->updateQty(1, $id_product, $id_product_attribute, false, 'up');
        } elseif ($operation === 'down') {
            $result = $cart->updateQty(1, $id_product, $id_product_attribute, false, 'down');
        } else {
            // Get current quantity
            $current_qty = $cart->getProductQuantity($id_product, $id_product_attribute);
            $current_qty = isset($current_qty['quantity']) ? (int)$current_qty['quantity'] : 0;
            $diff = $quantity - $current_qty;

            if ($diff > 0) {
                $result = $cart->updateQty($diff, $id_product, $id_product_attribute, false, 'up');
            } elseif ($diff < 0) {
                $result = $cart->updateQty(abs($diff), $id_product, $id_product_attribute, false, 'down');
            } else {
                $result = true;
            }
        }

        // Check if cart is now empty
        if (!$cart->nbProducts()) {
            $this->json_response = [
                'success' => true,
                'cart_empty' => true,
                'redirect' => $this->context->link->getPageLink('cart'),
            ];
            return;
        }

        $this->json_response = [
            'success' => (bool)$result,
            'message' => $result
                ? $this->trans('Carrello aggiornato', [], 'Modules.Onepagecheckout.Shop')
                : $this->trans('Errore nell\'aggiornamento del carrello', [], 'Modules.Onepagecheckout.Shop'),
            'cart_summary' => $this->getCartSummaryData(),
        ];
    }

    protected function getCartSummary()
    {
        $this->json_response = [
            'success' => true,
            'cart_summary' => $this->getCartSummaryData(),
        ];
    }

    protected function loginCustomer()
    {
        $email = Tools::getValue('email');
        $password = Tools::getValue('password');

        if (empty($email) || empty($password)) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Email e password sono obbligatorie', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        $customer = new Customer();
        $authentication = $customer->getByEmail($email, $password);

        if (!$authentication || !$customer->id) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Email o password non valide', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Log in the customer
        $this->context->cookie->id_customer = (int)$customer->id;
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->logged = 1;
        $this->context->cookie->email = $customer->email;
        $this->context->cookie->is_guest = $customer->is_guest;
        $this->context->customer = $customer;

        // Transfer cart to customer
        $this->context->cart->id_customer = (int)$customer->id;
        $this->context->cart->secure_key = $customer->secure_key;
        $this->context->cart->save();

        // Get customer addresses
        $addresses = $customer->getAddresses($this->context->language->id);

        // If customer has addresses, set the first one as delivery
        if (!empty($addresses)) {
            $this->context->cart->id_address_delivery = (int)$addresses[0]['id_address'];
            $this->context->cart->id_address_invoice = (int)$addresses[0]['id_address'];
            $this->context->cart->save();
        }

        $this->json_response = [
            'success' => true,
            'message' => $this->trans('Login effettuato con successo', [], 'Modules.Onepagecheckout.Shop'),
            'customer' => [
                'id' => (int)$customer->id,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email' => $customer->email,
            ],
            'addresses' => $addresses,
            'reload' => true,
        ];
    }

    protected function checkEmail()
    {
        $email = Tools::getValue('email');

        if (!Validate::isEmail($email)) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Email non valida', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        $exists = Customer::customerExists($email);

        $this->json_response = [
            'success' => true,
            'exists' => $exists,
        ];
    }

    protected function getCarriers()
    {
        $this->json_response = [
            'success' => true,
            'carriers' => $this->getCarriersData(),
        ];
    }

    protected function processCheckout()
    {
        $cart = $this->context->cart;
        $customer = $this->context->customer;

        // Validate cart
        if (!$cart->id || !$cart->nbProducts()) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Il carrello è vuoto', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Validate customer
        if (!$customer->id) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Devi inserire i tuoi dati', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Validate addresses
        if (!$cart->id_address_delivery || !Address::isCountryActiveById((int)$cart->id_address_delivery)) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Indirizzo di spedizione non valido', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Validate carrier
        if (!$cart->id_carrier) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Seleziona un metodo di spedizione', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Validate terms
        $terms_accepted = (bool)Tools::getValue('terms_accepted');
        if (Configuration::get('PS_CONDITIONS') && !$terms_accepted) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Devi accettare i termini e le condizioni', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Get payment module
        $payment_module = Tools::getValue('payment_module');

        if (empty($payment_module)) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Seleziona un metodo di pagamento', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Store order notes
        $order_message = Tools::getValue('order_message', '');
        if (!empty($order_message)) {
            $message = new Message();
            $message->id_cart = (int)$cart->id;
            $message->id_customer = (int)$customer->id;
            $message->message = pSQL($order_message);
            $message->private = false;
            $message->add();
        }

        // Get payment module instance
        $payment_module_instance = Module::getInstanceByName($payment_module);

        if (!$payment_module_instance || !$payment_module_instance->active) {
            $this->json_response = [
                'success' => false,
                'error' => $this->trans('Metodo di pagamento non disponibile', [], 'Modules.Onepagecheckout.Shop'),
            ];
            return;
        }

        // Store validation data in session for payment module
        $this->context->cookie->opc_checkout_validated = 1;
        $this->context->cookie->opc_payment_module = $payment_module;

        // Get payment options to determine if it's inline or redirect
        $payment_type = 'redirect'; // Default
        $payment_form_action = '';

        if (method_exists($payment_module_instance, 'getPaymentOptions')) {
            try {
                $options = $payment_module_instance->getPaymentOptions($this->buildCheckoutSession());
                if (is_array($options) && !empty($options)) {
                    $option = $options[0];
                    $payment_form_action = $option->getAction();
                    // If has form and action, it might be inline submittable
                    if ($option->getForm() && $payment_form_action) {
                        $payment_type = 'inline_form';
                    }
                }
            } catch (Exception $e) {
                // Fallback to redirect
            }
        }

        // Build payment URL
        $payment_url = $this->context->link->getModuleLink(
            $payment_module,
            'payment',
            [],
            true
        );

        // For some payment modules, try validation controller
        $validation_url = $this->context->link->getModuleLink(
            $payment_module,
            'validation',
            [],
            true
        );

        $this->json_response = [
            'success' => true,
            'payment_type' => $payment_type,
            'payment_url' => $payment_url,
            'payment_form_action' => $payment_form_action ?: $validation_url,
            'payment_module' => $payment_module,
        ];
    }

    /**
     * Create order for inline payment processing
     * This creates the order first, then allows payment to complete
     */
    protected function createOrder()
    {
        $cart = $this->context->cart;
        $customer = $this->context->customer;
        $payment_module = Tools::getValue('payment_module');

        // Validate everything first
        if (!$cart->id || !$cart->nbProducts()) {
            $this->json_response = ['success' => false, 'error' => $this->trans('Il carrello è vuoto', [], 'Modules.Onepagecheckout.Shop')];
            return;
        }

        if (!$customer->id) {
            $this->json_response = ['success' => false, 'error' => $this->trans('Devi inserire i tuoi dati', [], 'Modules.Onepagecheckout.Shop')];
            return;
        }

        if (!$cart->id_address_delivery) {
            $this->json_response = ['success' => false, 'error' => $this->trans('Indirizzo non valido', [], 'Modules.Onepagecheckout.Shop')];
            return;
        }

        if (!$cart->id_carrier) {
            $this->json_response = ['success' => false, 'error' => $this->trans('Seleziona la spedizione', [], 'Modules.Onepagecheckout.Shop')];
            return;
        }

        if (empty($payment_module)) {
            $this->json_response = ['success' => false, 'error' => $this->trans('Seleziona il pagamento', [], 'Modules.Onepagecheckout.Shop')];
            return;
        }

        // Get payment module
        $module = Module::getInstanceByName($payment_module);
        if (!$module || !$module->active) {
            $this->json_response = ['success' => false, 'error' => $this->trans('Metodo di pagamento non disponibile', [], 'Modules.Onepagecheckout.Shop')];
            return;
        }

        // Store order message
        $order_message = Tools::getValue('order_message', '');
        if (!empty($order_message)) {
            $message = new Message();
            $message->id_cart = (int)$cart->id;
            $message->id_customer = (int)$customer->id;
            $message->message = pSQL($order_message);
            $message->private = false;
            $message->add();
        }

        // For offline payment methods (wire transfer, check, COD), we can create the order directly
        $offline_modules = ['ps_wirepayment', 'ps_checkpayment', 'ps_cashondelivery'];

        if (in_array($payment_module, $offline_modules)) {
            // Create order with awaiting payment status
            try {
                $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
                $currency = $this->context->currency;

                // Get appropriate order status
                $order_status = Configuration::get('PS_OS_BANKWIRE'); // Awaiting bank wire
                if ($payment_module === 'ps_checkpayment') {
                    $order_status = Configuration::get('PS_OS_CHEQUE');
                } elseif ($payment_module === 'ps_cashondelivery') {
                    $order_status = Configuration::get('PS_OS_COD_VALIDATION') ?: Configuration::get('PS_OS_PREPARATION');
                }

                $module->validateOrder(
                    (int)$cart->id,
                    (int)$order_status,
                    $total,
                    $module->displayName,
                    null,
                    [],
                    (int)$currency->id,
                    false,
                    $customer->secure_key
                );

                $order = new Order((int)$module->currentOrder);

                $this->json_response = [
                    'success' => true,
                    'order_created' => true,
                    'order_id' => (int)$order->id,
                    'order_reference' => $order->reference,
                    'confirmation_url' => $this->context->link->getPageLink(
                        'order-confirmation',
                        true,
                        null,
                        [
                            'id_cart' => (int)$cart->id,
                            'id_module' => (int)$module->id,
                            'id_order' => (int)$order->id,
                            'key' => $customer->secure_key,
                        ]
                    ),
                ];
                return;
            } catch (Exception $e) {
                $this->json_response = [
                    'success' => false,
                    'error' => $this->trans('Errore nella creazione dell\'ordine', [], 'Modules.Onepagecheckout.Shop') . ': ' . $e->getMessage(),
                ];
                return;
            }
        }

        // For online payment methods, return payment URL for iframe/redirect
        $payment_url = $this->context->link->getModuleLink($payment_module, 'payment', [], true);

        $this->json_response = [
            'success' => true,
            'order_created' => false,
            'payment_url' => $payment_url,
            'payment_module' => $payment_module,
            'use_iframe' => true, // Suggest using iframe for online payments
        ];
    }

    protected function buildCheckoutSession()
    {
        return new OpcCheckoutSession(
            $this->context->cart,
            $this->context->customer,
            $this->context->language,
            $this->context->currency
        );
    }

    protected function getCartSummaryData()
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
                'price_formatted' => Tools::displayPrice($product['price_wt']),
                'total_formatted' => Tools::displayPrice($product['total_wt']),
                'image' => $this->context->link->getImageLink(
                    $product['link_rewrite'],
                    $product['id_image'],
                    'cart_default'
                ),
            ];
        }

        $subtotal = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $shipping = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $discounts = $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $total = $cart->getOrderTotal(true, Cart::BOTH);

        return [
            'products' => $products_formatted,
            'products_count' => count($products),
            'cart_rules' => array_map(function ($rule) {
                return [
                    'id_cart_rule' => $rule['id_cart_rule'],
                    'name' => $rule['name'],
                    'value' => Tools::displayPrice($rule['value_real']),
                ];
            }, $cart_rules),
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

    protected function getCarriersData()
    {
        $cart = $this->context->cart;
        $id_address = (int)$cart->id_address_delivery;

        if (!$id_address) {
            return [];
        }

        $id_zone = Address::getZoneById($id_address);
        $carriers = Carrier::getCarriersForOrder($id_zone, null, $cart);

        $carriers_formatted = [];
        foreach ($carriers as $carrier) {
            $shipping_cost = $cart->getPackageShippingCost((int)$carrier['id_carrier'], true, null, null, $id_zone);

            $carriers_formatted[] = [
                'id_carrier' => (int)$carrier['id_carrier'],
                'name' => $carrier['name'],
                'delay' => $carrier['delay'],
                'price' => $shipping_cost,
                'price_formatted' => $shipping_cost > 0 ? Tools::displayPrice($shipping_cost) : $this->trans('Gratis', [], 'Modules.Onepagecheckout.Shop'),
                'selected' => ((int)$cart->id_carrier === (int)$carrier['id_carrier']),
            ];
        }

        return $carriers_formatted;
    }

    protected function sendJsonResponse()
    {
        die(json_encode($this->json_response));
    }
}
