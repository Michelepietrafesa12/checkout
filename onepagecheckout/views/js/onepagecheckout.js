/**
 * One Page Checkout JavaScript
 * Handles all checkout interactions without page reload
 */

(function() {
    'use strict';

    // Configuration
    const OPC = {
        ajaxUrl: typeof opc_ajax_url !== 'undefined' ? opc_ajax_url : '',
        token: typeof opc_token !== 'undefined' ? opc_token : '',
        texts: typeof opc_texts !== 'undefined' ? opc_texts : {},
        debounceTimer: null,
        isProcessing: false
    };

    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        initEventListeners();
        initFormValidation();
        checkInitialState();
    });

    /**
     * Initialize all event listeners
     */
    function initEventListeners() {
        // Login toggle
        const showLoginBtn = document.getElementById('opc-show-login');
        if (showLoginBtn) {
            showLoginBtn.addEventListener('click', toggleLoginForm);
        }

        // Login submit
        const loginBtn = document.getElementById('opc-login-btn');
        if (loginBtn) {
            loginBtn.addEventListener('click', handleLogin);
        }

        // Customer type change
        const customerType = document.getElementById('customer_type');
        if (customerType) {
            customerType.addEventListener('change', handleCustomerTypeChange);
        }

        // Invoice checkbox
        const wantsInvoice = document.getElementById('wants_invoice');
        if (wantsInvoice) {
            wantsInvoice.addEventListener('change', handleInvoiceChange);
        }

        // Address selection
        const selectAddress = document.getElementById('select_address');
        if (selectAddress) {
            selectAddress.addEventListener('change', handleAddressSelection);
        }

        // Different billing address
        const differentBilling = document.getElementById('different_billing');
        if (differentBilling) {
            differentBilling.addEventListener('change', toggleBillingAddress);
        }

        // Carrier selection
        document.querySelectorAll('input[name="id_carrier"]').forEach(function(radio) {
            radio.addEventListener('change', handleCarrierChange);
        });

        // Payment selection
        document.querySelectorAll('input[name="payment_module"]').forEach(function(radio) {
            radio.addEventListener('change', handlePaymentChange);
        });

        // Discount code
        const applyDiscountBtn = document.getElementById('opc-apply-discount');
        if (applyDiscountBtn) {
            applyDiscountBtn.addEventListener('click', handleApplyDiscount);
        }

        // Submit order
        const submitBtn = document.getElementById('opc-submit-order');
        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmitOrder);
        }

        // Form field blur events for auto-save
        document.querySelectorAll('#opc-personal-form input, #opc-address-form input').forEach(function(input) {
            input.addEventListener('blur', debounceAutoSave);
        });

        // Country change to update carriers
        const countrySelect = document.getElementById('id_country');
        if (countrySelect) {
            countrySelect.addEventListener('change', handleCountryChange);
        }

        // Email check for existing account
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('blur', checkEmailExists);
        }

        // Modal close
        const modalClose = document.querySelector('.opc-modal-close');
        if (modalClose) {
            modalClose.addEventListener('click', closeErrorModal);
        }

        // Close modal on outside click
        const modal = document.getElementById('opc-error-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeErrorModal();
                }
            });
        }
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Real-time validation on required fields
        document.querySelectorAll('.opc-input[required], .opc-select[required]').forEach(function(field) {
            field.addEventListener('invalid', function(e) {
                e.preventDefault();
                showFieldError(field, OPC.texts.error_required || 'Questo campo è obbligatorio');
            });

            field.addEventListener('input', function() {
                clearFieldError(field);
            });
        });
    }

    /**
     * Check initial state and show/hide sections
     */
    function checkInitialState() {
        handleCustomerTypeChange();
        handleInvoiceChange();
    }

    /**
     * Toggle login form visibility
     */
    function toggleLoginForm() {
        const loginForm = document.getElementById('opc-login-form');
        if (loginForm) {
            loginForm.style.display = loginForm.style.display === 'none' ? 'block' : 'none';
        }
    }

    /**
     * Handle login submission
     */
    function handleLogin() {
        const email = document.getElementById('login_email').value;
        const password = document.getElementById('login_password').value;

        if (!email || !password) {
            showError('Inserisci email e password');
            return;
        }

        showLoading();

        ajaxRequest('loginCustomer', {
            email: email,
            password: password
        }, function(response) {
            hideLoading();
            if (response.success) {
                if (response.reload) {
                    window.location.reload();
                }
            } else {
                showError(response.error);
            }
        });
    }

    /**
     * Handle customer type change (private/company)
     */
    function handleCustomerTypeChange() {
        const customerType = document.getElementById('customer_type');
        const companyFields = document.getElementById('opc-company-fields');

        if (customerType && companyFields) {
            companyFields.style.display = customerType.value === 'company' ? 'block' : 'none';
        }
    }

    /**
     * Handle invoice checkbox change
     */
    function handleInvoiceChange() {
        const wantsInvoice = document.getElementById('wants_invoice');
        const companyFields = document.getElementById('opc-company-fields');
        const customerType = document.getElementById('customer_type');

        if (wantsInvoice && companyFields) {
            if (wantsInvoice.checked || (customerType && customerType.value === 'company')) {
                companyFields.style.display = 'block';
            } else if (customerType && customerType.value !== 'company') {
                companyFields.style.display = 'none';
            }
        }
    }

    /**
     * Handle existing address selection
     */
    function handleAddressSelection() {
        const selectAddress = document.getElementById('select_address');
        const addressForm = document.getElementById('opc-address-form');

        if (!selectAddress) return;

        const idAddress = selectAddress.value;

        if (idAddress) {
            // Hide form and select existing address
            if (addressForm) {
                addressForm.style.display = 'none';
            }

            showLoading();

            ajaxRequest('selectAddress', {
                id_address: idAddress,
                address_type: 'delivery'
            }, function(response) {
                hideLoading();
                if (response.success) {
                    updateCarriers(response.carriers);
                    updateCartSummary(response.cart_summary);
                } else {
                    showError(response.error);
                }
            });
        } else {
            // Show form for new address
            if (addressForm) {
                addressForm.style.display = 'block';
            }
        }
    }

    /**
     * Toggle billing address section
     */
    function toggleBillingAddress() {
        const differentBilling = document.getElementById('different_billing');
        const billingSection = document.getElementById('opc-billing-address');

        if (differentBilling && billingSection) {
            billingSection.style.display = differentBilling.checked ? 'block' : 'none';
        }
    }

    /**
     * Handle carrier selection change
     */
    function handleCarrierChange(e) {
        const idCarrier = e.target.value;

        showLoading();

        ajaxRequest('updateCarrier', {
            id_carrier: idCarrier
        }, function(response) {
            hideLoading();
            if (response.success) {
                updateCartSummary(response.cart_summary);
            } else {
                showError(response.error);
            }
        });
    }

    /**
     * Handle payment method selection
     */
    function handlePaymentChange(e) {
        // Show/hide additional payment forms
        document.querySelectorAll('.opc-payment-option').forEach(function(option) {
            const form = option.querySelector('.opc-payment-form');
            const info = option.querySelector('.opc-payment-info-extra');
            const radio = option.querySelector('input[type="radio"]');

            if (form) {
                form.style.display = radio && radio.checked ? 'block' : 'none';
            }
            if (info) {
                info.style.display = radio && radio.checked ? 'block' : 'none';
            }
        });
    }

    /**
     * Handle country change
     */
    function handleCountryChange() {
        // Save address and update carriers
        saveAddress(function(response) {
            if (response.success) {
                updateCarriers(response.carriers);
                updateCartSummary(response.cart_summary);
            }
        });
    }

    /**
     * Check if email exists
     */
    function checkEmailExists() {
        const emailInput = document.getElementById('email');
        if (!emailInput || !emailInput.value) return;

        ajaxRequest('checkEmail', {
            email: emailInput.value
        }, function(response) {
            if (response.success && response.exists) {
                showFieldError(emailInput, 'Questa email è già registrata. Effettua il login.');
                toggleLoginForm();
                document.getElementById('login_email').value = emailInput.value;
            }
        });
    }

    /**
     * Handle apply discount code
     */
    function handleApplyDiscount() {
        const discountInput = document.getElementById('discount_code');
        const code = discountInput ? discountInput.value.trim() : '';

        if (!code) {
            showError('Inserisci un codice sconto');
            return;
        }

        showLoading();

        ajaxRequest('applyDiscount', {
            discount_code: code
        }, function(response) {
            hideLoading();
            if (response.success) {
                updateCartSummary(response.cart_summary);
                if (discountInput) {
                    discountInput.value = '';
                }
            } else {
                showError(response.error);
            }
        });
    }

    /**
     * Debounced auto-save for form fields
     */
    function debounceAutoSave() {
        if (OPC.debounceTimer) {
            clearTimeout(OPC.debounceTimer);
        }

        OPC.debounceTimer = setTimeout(function() {
            autoSaveData();
        }, 1000);
    }

    /**
     * Auto-save customer and address data
     */
    function autoSaveData() {
        // First save customer info
        saveCustomerInfo(function(customerResponse) {
            if (customerResponse.success) {
                // Then save address
                saveAddress(function(addressResponse) {
                    if (addressResponse.success && addressResponse.carriers) {
                        updateCarriers(addressResponse.carriers);
                        updateCartSummary(addressResponse.cart_summary);
                    }
                });
            }
        });
    }

    /**
     * Save customer information
     */
    function saveCustomerInfo(callback) {
        const formData = {
            firstname: getValue('firstname'),
            lastname: getValue('lastname'),
            email: getValue('email'),
            birthday_day: getValue('birthday_day'),
            birthday_month: getValue('birthday_month'),
            birthday_year: getValue('birthday_year'),
            phone: getValue('phone'),
            phone_mobile: getValue('phone_mobile'),
            customer_type: getValue('customer_type'),
            wants_invoice: getCheckboxValue('wants_invoice'),
            company: getValue('company'),
            vat_number: getValue('vat_number')
        };

        // Validate required fields
        if (!formData.firstname || !formData.lastname) {
            if (callback) callback({ success: false, error: 'Nome e cognome sono obbligatori' });
            return;
        }

        if (!document.querySelector('.opc-login-section') && !formData.email) {
            // Email required only for guests
        }

        ajaxRequest('saveCustomerInfo', formData, callback);
    }

    /**
     * Save address information
     */
    function saveAddress(callback) {
        const formData = {
            address1: getValue('address1'),
            address2: getValue('address2'),
            postcode: getValue('postcode'),
            city: getValue('city'),
            id_country: getValue('id_country'),
            phone: getValue('phone'),
            phone_mobile: getValue('phone_mobile'),
            company: getValue('company'),
            vat_number: getValue('vat_number'),
            address_type: 'both'
        };

        // Check if we have minimum address data
        if (!formData.address1 || !formData.postcode || !formData.city) {
            if (callback) callback({ success: false, error: 'Completa l\'indirizzo' });
            return;
        }

        ajaxRequest('saveAddress', formData, callback);
    }

    /**
     * Handle order submission
     */
    function handleSubmitOrder() {
        if (OPC.isProcessing) return;

        // Validate all required fields
        const errors = validateCheckout();
        if (errors.length > 0) {
            showError(errors.join('<br>'));
            return;
        }

        OPC.isProcessing = true;
        showLoading();

        // First save all data
        saveCustomerInfo(function(customerResponse) {
            if (!customerResponse.success) {
                hideLoading();
                OPC.isProcessing = false;
                showError(customerResponse.error || customerResponse.errors.join('<br>'));
                return;
            }

            saveAddress(function(addressResponse) {
                if (!addressResponse.success) {
                    hideLoading();
                    OPC.isProcessing = false;
                    showError(addressResponse.error || addressResponse.errors.join('<br>'));
                    return;
                }

                // Process checkout
                processCheckout();
            });
        });
    }

    /**
     * Process final checkout
     */
    function processCheckout() {
        const selectedPayment = document.querySelector('input[name="payment_module"]:checked');
        const termsCheckbox = document.getElementById('terms-and-conditions');

        const formData = {
            payment_module: selectedPayment ? selectedPayment.value : '',
            terms_accepted: termsCheckbox ? termsCheckbox.checked : false,
            order_message: getValue('order_message')
        };

        ajaxRequest('processCheckout', formData, function(response) {
            hideLoading();
            OPC.isProcessing = false;

            if (response.success) {
                // Redirect to payment
                if (response.payment_url) {
                    window.location.href = response.payment_url;
                }
            } else {
                showError(response.error);
            }
        });
    }

    /**
     * Validate checkout form
     */
    function validateCheckout() {
        const errors = [];

        // Check required personal fields
        if (!getValue('firstname')) errors.push('Il nome è obbligatorio');
        if (!getValue('lastname')) errors.push('Il cognome è obbligatorio');

        // Check email for guests
        const emailField = document.getElementById('email');
        if (emailField && !emailField.value) {
            errors.push('L\'email è obbligatoria');
        }

        // Check address
        if (!getValue('address1')) errors.push('L\'indirizzo è obbligatorio');
        if (!getValue('postcode')) errors.push('Il CAP è obbligatorio');
        if (!getValue('city')) errors.push('La città è obbligatoria');

        // Check carrier
        const selectedCarrier = document.querySelector('input[name="id_carrier"]:checked');
        if (!selectedCarrier) {
            errors.push('Seleziona un metodo di spedizione');
        }

        // Check payment
        const selectedPayment = document.querySelector('input[name="payment_module"]:checked');
        if (!selectedPayment) {
            errors.push('Seleziona un metodo di pagamento');
        }

        // Check terms
        const termsCheckbox = document.getElementById('terms-and-conditions');
        if (termsCheckbox && !termsCheckbox.checked) {
            errors.push('Devi accettare i termini e le condizioni');
        }

        return errors;
    }

    /**
     * Update carriers list
     */
    function updateCarriers(carriers) {
        const carriersList = document.getElementById('opc-carriers-list');
        if (!carriersList || !carriers) return;

        if (carriers.length === 0) {
            carriersList.innerHTML = '<p class="opc-no-carriers">Nessun corriere disponibile per questo indirizzo</p>';
            return;
        }

        let html = '';
        carriers.forEach(function(carrier) {
            html += `
                <div class="opc-carrier-option">
                    <label class="opc-radio-label">
                        <input type="radio" name="id_carrier" value="${carrier.id_carrier}"
                               ${carrier.selected ? 'checked' : ''} />
                        <span class="opc-carrier-info">
                            <span class="opc-carrier-name">${carrier.name}</span>
                            <span class="opc-carrier-delay">${carrier.delay || ''}</span>
                        </span>
                        <span class="opc-carrier-price">${carrier.price_formatted}</span>
                    </label>
                </div>
            `;
        });

        carriersList.innerHTML = html;

        // Re-attach event listeners
        document.querySelectorAll('input[name="id_carrier"]').forEach(function(radio) {
            radio.addEventListener('change', handleCarrierChange);
        });
    }

    /**
     * Update cart summary
     */
    function updateCartSummary(summary) {
        if (!summary) return;

        // Update subtotal
        const subtotalEl = document.getElementById('opc-subtotal');
        if (subtotalEl) {
            subtotalEl.textContent = summary.subtotal_formatted;
        }

        // Update shipping
        const shippingEl = document.getElementById('opc-shipping');
        if (shippingEl) {
            shippingEl.textContent = summary.shipping_formatted;
        }

        // Update discounts
        const discountsEl = document.getElementById('opc-discounts');
        if (discountsEl) {
            discountsEl.textContent = '-' + summary.discounts_formatted;
        }

        // Update total
        const totalEl = document.getElementById('opc-total');
        if (totalEl) {
            totalEl.textContent = summary.total_formatted;
        }
    }

    /**
     * AJAX request helper
     */
    function ajaxRequest(action, data, callback) {
        data.action = action;
        data.token = OPC.token;

        const formData = new FormData();
        for (const key in data) {
            if (data.hasOwnProperty(key)) {
                formData.append(key, data[key]);
            }
        }

        fetch(OPC.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (callback) callback(data);
        })
        .catch(function(error) {
            console.error('OPC Ajax Error:', error);
            if (callback) callback({ success: false, error: 'Errore di connessione' });
        });
    }

    /**
     * Helper functions
     */
    function getValue(id) {
        const el = document.getElementById(id);
        return el ? el.value : '';
    }

    function getCheckboxValue(id) {
        const el = document.getElementById(id);
        return el ? (el.checked ? 1 : 0) : 0;
    }

    function showLoading() {
        const loading = document.getElementById('opc-loading');
        if (loading) {
            loading.style.display = 'flex';
        }
    }

    function hideLoading() {
        const loading = document.getElementById('opc-loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    function showError(message) {
        const modal = document.getElementById('opc-error-modal');
        const content = document.getElementById('opc-error-content');

        if (modal && content) {
            content.innerHTML = message;
            modal.style.display = 'flex';
        } else {
            alert(message);
        }
    }

    function closeErrorModal() {
        const modal = document.getElementById('opc-error-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function showFieldError(field, message) {
        clearFieldError(field);

        field.classList.add('error');

        const errorEl = document.createElement('span');
        errorEl.className = 'opc-error-message';
        errorEl.textContent = message;

        field.parentNode.appendChild(errorEl);
    }

    function clearFieldError(field) {
        field.classList.remove('error');

        const existingError = field.parentNode.querySelector('.opc-error-message');
        if (existingError) {
            existingError.remove();
        }
    }

})();
