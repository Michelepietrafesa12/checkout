/**
 * One Page Checkout - PlusPower Style
 * Unified checkout JavaScript
 */

(function() {
    'use strict';

    const OPC = {
        ajaxUrl: typeof opc_ajax_url !== 'undefined' ? opc_ajax_url : '',
        token: typeof opc_token !== 'undefined' ? opc_token : '',
        saveTimer: null,
        isProcessing: false
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        bindEvents();
        initCompanyFields();
        initAddressSelector();
    }

    function bindEvents() {
        // Customer type change
        on('#customer_type', 'change', handleCustomerType);

        // Invoice checkbox
        on('#wants_invoice', 'change', handleInvoiceToggle);

        // Email blur - check if exists
        on('#email', 'blur', handleEmailCheck);

        // Show login form
        on('#opc-show-login', 'click', function(e) {
            e.preventDefault();
            show('#opc-login-inline');
        });

        // Login button
        on('#opc-login-btn', 'click', handleLogin);

        // Address selector
        on('#select_address', 'change', handleAddressSelect);

        // Form fields auto-save
        document.querySelectorAll('#opc-checkout-form input, #opc-checkout-form select').forEach(function(el) {
            el.addEventListener('change', debouncedSave);
            el.addEventListener('blur', debouncedSave);
        });

        // Country change - update carriers
        on('#id_country', 'change', function() {
            saveAllData(function() {
                refreshCarriers();
            });
        });

        // Carrier selection
        document.querySelectorAll('input[name="id_carrier"]').forEach(function(el) {
            el.addEventListener('change', handleCarrierSelect);
        });

        // Payment selection
        document.querySelectorAll('input[name="payment_module"]').forEach(function(el) {
            el.addEventListener('change', handlePaymentSelect);
        });

        // Apply discount
        on('#opc-apply-discount', 'click', handleApplyDiscount);

        // Remove discount
        document.querySelectorAll('.opc-remove-discount').forEach(function(el) {
            el.addEventListener('click', handleRemoveDiscount);
        });

        // Submit order
        on('#opc-submit-order', 'click', handleSubmit);

        // Toast close
        on('.opc-toast-close', 'click', hideToast);
    }

    // Company fields visibility
    function initCompanyFields() {
        handleCustomerType();
        handleInvoiceToggle();
    }

    function handleCustomerType() {
        var type = getValue('#customer_type');
        var companyFields = document.getElementById('opc-company-fields');
        if (companyFields) {
            if (type === 'company') {
                companyFields.classList.add('visible');
            } else if (!isChecked('#wants_invoice')) {
                companyFields.classList.remove('visible');
            }
        }
    }

    function handleInvoiceToggle() {
        var companyFields = document.getElementById('opc-company-fields');
        if (companyFields) {
            if (isChecked('#wants_invoice') || getValue('#customer_type') === 'company') {
                companyFields.classList.add('visible');
            } else {
                companyFields.classList.remove('visible');
            }
        }
    }

    // Email check for existing account
    function handleEmailCheck() {
        var email = getValue('#email');
        if (!email || !isValidEmail(email)) return;

        ajax('checkEmail', { email: email }, function(response) {
            if (response.success && response.exists) {
                show('#opc-login-prompt');
            } else {
                hide('#opc-login-prompt');
                hide('#opc-login-inline');
            }
        });
    }

    // Login
    function handleLogin() {
        var email = getValue('#email');
        var password = getValue('#login_password');

        if (!email || !password) {
            showToast('Inserisci email e password', 'error');
            return;
        }

        showLoading();
        ajax('loginCustomer', { email: email, password: password }, function(response) {
            hideLoading();
            if (response.success) {
                window.location.reload();
            } else {
                showToast(response.error || 'Login fallito', 'error');
            }
        });
    }

    // Address selector for logged users
    function initAddressSelector() {
        var selector = document.getElementById('select_address');
        if (selector && selector.value) {
            fillAddressFromSelector(selector.value);
        }
    }

    function handleAddressSelect() {
        var selector = document.getElementById('select_address');
        if (!selector) return;

        var addressId = selector.value;
        if (addressId) {
            fillAddressFromSelector(addressId);
            showLoading();
            ajax('selectAddress', { id_address: addressId, address_type: 'both' }, function(response) {
                hideLoading();
                if (response.success) {
                    updateCarriers(response.carriers);
                    updateTotals(response.cart_summary);
                }
            });
        }
    }

    function fillAddressFromSelector(addressId) {
        var selector = document.getElementById('select_address');
        if (!selector) return;

        var option = selector.querySelector('option[value="' + addressId + '"]');
        if (!option) return;

        try {
            var data = JSON.parse(option.getAttribute('data-address'));
            if (data) {
                setValueIfExists('#address1', data.address1);
                setValueIfExists('#address2', data.address2);
                setValueIfExists('#postcode', data.postcode);
                setValueIfExists('#city', data.city);
                setValueIfExists('#id_country', data.id_country);
                setValueIfExists('#phone', data.phone);
                setValueIfExists('#phone_mobile', data.phone_mobile);
                setValueIfExists('#company', data.company);
                setValueIfExists('#vat_number', data.vat_number);
            }
        } catch (e) {
            // Ignore JSON parse errors
        }
    }

    // Auto-save with debounce
    function debouncedSave() {
        if (OPC.saveTimer) clearTimeout(OPC.saveTimer);
        OPC.saveTimer = setTimeout(function() {
            saveAllData();
        }, 800);
    }

    function saveAllData(callback) {
        var customerData = {
            firstname: getValue('#firstname'),
            lastname: getValue('#lastname'),
            email: getValue('#email'),
            birthday_day: getValue('#birthday_day'),
            birthday_month: getValue('#birthday_month'),
            birthday_year: getValue('#birthday_year'),
            phone: getValue('#phone'),
            phone_mobile: getValue('#phone_mobile'),
            customer_type: getValue('#customer_type'),
            wants_invoice: isChecked('#wants_invoice') ? 1 : 0,
            company: getValue('#company'),
            vat_number: getValue('#vat_number')
        };

        // Only save if we have minimum data
        if (!customerData.firstname || !customerData.lastname) {
            if (callback) callback();
            return;
        }

        ajax('saveCustomerInfo', customerData, function(response) {
            if (response.success) {
                saveAddress(callback);
            } else if (callback) {
                callback();
            }
        });
    }

    function saveAddress(callback) {
        var addressData = {
            address1: getValue('#address1'),
            address2: getValue('#address2'),
            postcode: getValue('#postcode'),
            city: getValue('#city'),
            id_country: getValue('#id_country'),
            phone: getValue('#phone'),
            phone_mobile: getValue('#phone_mobile'),
            company: getValue('#company'),
            vat_number: getValue('#vat_number'),
            address_type: 'both'
        };

        // Only save if we have minimum address data
        if (!addressData.address1 || !addressData.postcode || !addressData.city) {
            if (callback) callback();
            return;
        }

        ajax('saveAddress', addressData, function(response) {
            if (response.success) {
                updateCarriers(response.carriers);
                updateTotals(response.cart_summary);
            }
            if (callback) callback();
        });
    }

    // Carrier selection
    function handleCarrierSelect(e) {
        var carrierId = e.target.value;

        // Update visual state
        document.querySelectorAll('.opc-carrier-option').forEach(function(el) {
            el.classList.remove('selected');
        });
        e.target.closest('.opc-carrier-option').classList.add('selected');

        showLoading();
        ajax('updateCarrier', { id_carrier: carrierId }, function(response) {
            hideLoading();
            if (response.success) {
                updateTotals(response.cart_summary);
            }
        });
    }

    function refreshCarriers() {
        ajax('getCarriers', {}, function(response) {
            if (response.success) {
                updateCarriers(response.carriers);
            }
        });
    }

    function updateCarriers(carriers) {
        var container = document.getElementById('opc-carriers-list');
        if (!container || !carriers) return;

        if (carriers.length === 0) {
            container.innerHTML = '<p class="opc-message">Completa l\'indirizzo per vedere le opzioni di spedizione</p>';
            return;
        }

        var html = '';
        carriers.forEach(function(carrier) {
            html += '<label class="opc-carrier-option ' + (carrier.selected ? 'selected' : '') + '">';
            html += '<input type="radio" name="id_carrier" value="' + carrier.id_carrier + '"' + (carrier.selected ? ' checked' : '') + ' />';
            html += '<span class="opc-carrier-name">' + carrier.name + '</span>';
            html += '<span class="opc-carrier-price">' + carrier.price_formatted + '</span>';
            html += '</label>';
        });

        container.innerHTML = html;

        // Rebind events
        document.querySelectorAll('input[name="id_carrier"]').forEach(function(el) {
            el.addEventListener('change', handleCarrierSelect);
        });
    }

    // Payment selection
    function handlePaymentSelect(e) {
        // Show/hide payment forms
        document.querySelectorAll('.opc-payment-form').forEach(function(el) {
            el.style.display = 'none';
        });

        var option = e.target.closest('.opc-payment-option');
        if (option) {
            var form = option.querySelector('.opc-payment-form');
            if (form) form.style.display = 'block';
        }
    }

    // Discount codes
    function handleApplyDiscount() {
        var code = getValue('#discount_code');
        if (!code) {
            showToast('Inserisci un codice sconto', 'error');
            return;
        }

        showLoading();
        ajax('applyDiscount', { discount_code: code }, function(response) {
            hideLoading();
            if (response.success) {
                updateTotals(response.cart_summary);
                setValue('#discount_code', '');
                showToast('Codice sconto applicato', 'success');
                // Reload to show applied discount
                window.location.reload();
            } else {
                showToast(response.error || 'Codice non valido', 'error');
            }
        });
    }

    function handleRemoveDiscount(e) {
        var id = e.target.getAttribute('data-id');
        if (!id) return;

        showLoading();
        ajax('removeDiscount', { id_cart_rule: id }, function(response) {
            hideLoading();
            if (response.success) {
                updateTotals(response.cart_summary);
                // Remove from DOM
                var el = e.target.closest('.opc-applied-discount');
                if (el) el.remove();
            }
        });
    }

    // Update totals
    function updateTotals(summary) {
        if (!summary) return;

        setTextIfExists('#opc-subtotal', summary.subtotal_formatted);
        setTextIfExists('#opc-shipping-cost', summary.shipping_formatted);
        setTextIfExists('#opc-discounts', '-' + summary.discounts_formatted);
        setTextIfExists('#opc-total', summary.total_formatted);
    }

    // Form submission
    function handleSubmit() {
        if (OPC.isProcessing) return;

        // Validate form
        var errors = validateForm();
        if (errors.length > 0) {
            showToast(errors[0], 'error');
            return;
        }

        OPC.isProcessing = true;
        showLoading();

        // Save all data first
        saveAllData(function() {
            processCheckout();
        });
    }

    function validateForm() {
        var errors = [];

        if (!getValue('#firstname')) errors.push('Il nome è obbligatorio');
        if (!getValue('#lastname')) errors.push('Il cognome è obbligatorio');

        var emailField = document.getElementById('email');
        if (emailField && !getValue('#email')) errors.push('L\'email è obbligatoria');

        if (!getValue('#address1')) errors.push('L\'indirizzo è obbligatorio');
        if (!getValue('#postcode')) errors.push('Il CAP è obbligatorio');
        if (!getValue('#city')) errors.push('La città è obbligatoria');

        if (!document.querySelector('input[name="id_carrier"]:checked')) {
            errors.push('Seleziona un metodo di spedizione');
        }

        if (!document.querySelector('input[name="payment_module"]:checked')) {
            errors.push('Seleziona un metodo di pagamento');
        }

        var terms = document.getElementById('terms-and-conditions');
        if (terms && !terms.checked) {
            errors.push('Devi accettare i termini e condizioni');
        }

        return errors;
    }

    function processCheckout() {
        var paymentRadio = document.querySelector('input[name="payment_module"]:checked');
        var termsCheckbox = document.getElementById('terms-and-conditions');

        var data = {
            payment_module: paymentRadio ? paymentRadio.value : '',
            terms_accepted: termsCheckbox ? termsCheckbox.checked : true,
            order_message: getValue('#order_message')
        };

        ajax('processCheckout', data, function(response) {
            hideLoading();
            OPC.isProcessing = false;

            if (response.success && response.payment_url) {
                window.location.href = response.payment_url;
            } else {
                showToast(response.error || 'Errore durante il checkout', 'error');
            }
        });
    }

    // AJAX helper
    function ajax(action, data, callback) {
        data.action = action;
        data.token = OPC.token;

        var formData = new FormData();
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                formData.append(key, data[key]);
            }
        }

        fetch(OPC.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(json) { if (callback) callback(json); })
        .catch(function(error) {
            console.error('OPC Error:', error);
            if (callback) callback({ success: false, error: 'Errore di connessione' });
        });
    }

    // DOM helpers
    function on(selector, event, handler) {
        var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (el) el.addEventListener(event, handler);
    }

    function getValue(selector) {
        var el = document.querySelector(selector);
        return el ? el.value.trim() : '';
    }

    function setValue(selector, value) {
        var el = document.querySelector(selector);
        if (el) el.value = value;
    }

    function setValueIfExists(selector, value) {
        if (value !== undefined && value !== null) {
            setValue(selector, value);
        }
    }

    function setTextIfExists(selector, text) {
        var el = document.querySelector(selector);
        if (el && text !== undefined) el.textContent = text;
    }

    function isChecked(selector) {
        var el = document.querySelector(selector);
        return el ? el.checked : false;
    }

    function show(selector) {
        var el = document.querySelector(selector);
        if (el) el.style.display = 'block';
    }

    function hide(selector) {
        var el = document.querySelector(selector);
        if (el) el.style.display = 'none';
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Loading
    function showLoading() {
        show('#opc-loading');
    }

    function hideLoading() {
        hide('#opc-loading');
    }

    // Toast notifications
    function showToast(message, type) {
        var toast = document.getElementById('opc-toast');
        var msgEl = document.getElementById('opc-toast-message');

        if (toast && msgEl) {
            msgEl.textContent = message;
            toast.className = 'opc-toast ' + (type || '');
            toast.style.display = 'flex';

            setTimeout(hideToast, 5000);
        }
    }

    function hideToast() {
        var toast = document.getElementById('opc-toast');
        if (toast) toast.style.display = 'none';
    }

})();
