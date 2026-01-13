{**
 * One Page Checkout Template
 * Main checkout page with all steps in one view
 *}

{extends file='page.tpl'}

{block name='page_header_container'}{/block}

{block name='page_content'}
<div class="opc-checkout-container">
    <h1 class="opc-title">{l s='Conferma d\'ordine' mod='onepagecheckout'}</h1>

    <div class="opc-row">
        {* Left Column - Customer Data *}
        <div class="opc-col-left">
            {* Login Section (for guests) *}
            {if !$is_logged}
            <div class="opc-section opc-login-section" id="opc-login-section">
                <div class="opc-section-header">
                    <span class="opc-section-title">{l s='Hai già un account?' mod='onepagecheckout'}</span>
                    <button type="button" class="opc-btn-link" id="opc-show-login">
                        {l s='Accedi' mod='onepagecheckout'}
                    </button>
                </div>
                <div class="opc-login-form" id="opc-login-form" style="display: none;">
                    <div class="opc-form-group">
                        <label for="login_email">{l s='Email' mod='onepagecheckout'}</label>
                        <input type="email" id="login_email" name="login_email" class="opc-input" />
                    </div>
                    <div class="opc-form-group">
                        <label for="login_password">{l s='Password' mod='onepagecheckout'}</label>
                        <input type="password" id="login_password" name="login_password" class="opc-input" />
                    </div>
                    <button type="button" class="opc-btn opc-btn-primary" id="opc-login-btn">
                        {l s='Accedi' mod='onepagecheckout'}
                    </button>
                </div>
            </div>
            {/if}

            {* Personal Information Section *}
            <div class="opc-section" id="opc-personal-section">
                <h2 class="opc-section-title">{l s='I miei dati' mod='onepagecheckout'}</h2>

                <form id="opc-personal-form" class="opc-form">
                    {* Customer Type *}
                    <div class="opc-form-group">
                        <label for="customer_type">{l s='Sono un/una' mod='onepagecheckout'}</label>
                        <select id="customer_type" name="customer_type" class="opc-select">
                            {foreach $customer_types as $type}
                                <option value="{$type.value}" {if $checkout_session.customer_type == $type.value}selected{/if}>
                                    {$type.label}
                                </option>
                            {/foreach}
                        </select>
                    </div>

                    {* Invoice Request *}
                    <div class="opc-form-group opc-checkbox-group">
                        <label class="opc-checkbox-label">
                            <input type="checkbox" id="wants_invoice" name="wants_invoice" value="1"
                                   {if $checkout_session.wants_invoice}checked{/if} />
                            <span>{l s='Richiedo l\'emissione della fattura' mod='onepagecheckout'}</span>
                        </label>
                    </div>

                    {* Company fields (shown when company type or invoice requested) *}
                    <div id="opc-company-fields" style="display: none;">
                        <div class="opc-form-group">
                            <label for="company">{l s='Ragione Sociale' mod='onepagecheckout'}</label>
                            <input type="text" id="company" name="company" class="opc-input" />
                        </div>
                        <div class="opc-form-group">
                            <label for="vat_number">{l s='Partita IVA' mod='onepagecheckout'}</label>
                            <input type="text" id="vat_number" name="vat_number" class="opc-input" />
                        </div>
                    </div>

                    {* Name Fields *}
                    <div class="opc-form-group">
                        <label for="firstname">{l s='Nome' mod='onepagecheckout'} <span class="required">*</span></label>
                        <input type="text" id="firstname" name="firstname" class="opc-input"
                               value="{$customer_data.firstname|escape:'html':'UTF-8'}" required />
                    </div>

                    <div class="opc-form-group">
                        <label for="lastname">{l s='Cognome' mod='onepagecheckout'} <span class="required">*</span></label>
                        <input type="text" id="lastname" name="lastname" class="opc-input"
                               value="{$customer_data.lastname|escape:'html':'UTF-8'}" required />
                    </div>

                    {* Birthday *}
                    <div class="opc-form-group opc-birthday-group">
                        <label>{l s='Data di nascita:' mod='onepagecheckout'}</label>
                        <div class="opc-birthday-selects">
                            <select id="birthday_day" name="birthday_day" class="opc-select opc-select-day">
                                <option value="">{l s='Giorno' mod='onepagecheckout'}</option>
                                {foreach $days as $day}
                                    <option value="{$day|string_format:'%02d'}">{$day}</option>
                                {/foreach}
                            </select>
                            <select id="birthday_month" name="birthday_month" class="opc-select opc-select-month">
                                <option value="">{l s='Mese' mod='onepagecheckout'}</option>
                                {foreach $months as $month}
                                    <option value="{$month.value}">{$month.label}</option>
                                {/foreach}
                            </select>
                            <select id="birthday_year" name="birthday_year" class="opc-select opc-select-year">
                                <option value="">{l s='Anno' mod='onepagecheckout'}</option>
                                {foreach $years as $year}
                                    <option value="{$year}">{$year}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    {* Email (for guests) *}
                    {if !$is_logged}
                    <div class="opc-form-group">
                        <label for="email">{l s='Email' mod='onepagecheckout'} <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="opc-input" required />
                    </div>
                    {/if}

                    {* Phone *}
                    <div class="opc-form-group">
                        <label for="phone">
                            {l s='Telefono' mod='onepagecheckout'}
                            {if !$require_phone}<span class="opc-optional">{l s='Campo non obbligatorio' mod='onepagecheckout'}</span>{/if}
                        </label>
                        <input type="tel" id="phone" name="phone" class="opc-input"
                               value="{$customer_data.phone|escape:'html':'UTF-8'}"
                               {if $require_phone}required{/if} />
                    </div>

                    <div class="opc-form-group">
                        <label for="phone_mobile">{l s='Cellulare' mod='onepagecheckout'}</label>
                        <input type="tel" id="phone_mobile" name="phone_mobile" class="opc-input"
                               value="{$customer_data.phone_mobile|escape:'html':'UTF-8'}" />
                    </div>
                </form>
            </div>

            {* Address Section *}
            <div class="opc-section" id="opc-address-section">
                <h2 class="opc-section-title">{l s='Indirizzo di spedizione' mod='onepagecheckout'}</h2>

                {* Existing Addresses (for logged in customers) *}
                {if $is_logged && count($addresses) > 0}
                <div class="opc-existing-addresses" id="opc-existing-addresses">
                    <div class="opc-form-group">
                        <label for="select_address">{l s='Seleziona un indirizzo esistente' mod='onepagecheckout'}</label>
                        <select id="select_address" name="select_address" class="opc-select">
                            <option value="">{l s='-- Nuovo indirizzo --' mod='onepagecheckout'}</option>
                            {foreach $addresses as $address}
                                <option value="{$address.id_address}">
                                    {$address.alias} - {$address.address1}, {$address.postcode} {$address.city}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                {/if}

                <form id="opc-address-form" class="opc-form">
                    <div class="opc-form-group">
                        <label for="address1">{l s='Indirizzo e numero civico' mod='onepagecheckout'} <span class="required">*</span></label>
                        <input type="text" id="address1" name="address1" class="opc-input" required />
                    </div>

                    <div class="opc-form-group">
                        <label for="address2">{l s='Interno/Scala/Altro' mod='onepagecheckout'}</label>
                        <input type="text" id="address2" name="address2" class="opc-input" />
                    </div>

                    <div class="opc-form-row">
                        <div class="opc-form-group opc-form-group-half">
                            <label for="postcode">{l s='CAP' mod='onepagecheckout'} <span class="required">*</span></label>
                            <input type="text" id="postcode" name="postcode" class="opc-input" required />
                        </div>
                        <div class="opc-form-group opc-form-group-half">
                            <label for="city">{l s='Città' mod='onepagecheckout'} <span class="required">*</span></label>
                            <input type="text" id="city" name="city" class="opc-input" required />
                        </div>
                    </div>

                    <div class="opc-form-group">
                        <label for="id_country">{l s='Nazione' mod='onepagecheckout'} <span class="required">*</span></label>
                        <select id="id_country" name="id_country" class="opc-select" required>
                            {foreach $countries as $country}
                                <option value="{$country.id_country}"
                                        {if $country.id_country == Configuration::get('PS_COUNTRY_DEFAULT')}selected{/if}>
                                    {$country.name}
                                </option>
                            {/foreach}
                        </select>
                    </div>

                    {* Different billing address checkbox *}
                    <div class="opc-form-group opc-checkbox-group">
                        <label class="opc-checkbox-label">
                            <input type="checkbox" id="different_billing" name="different_billing" value="1" />
                            <span>{l s='Usa un indirizzo di fatturazione diverso' mod='onepagecheckout'}</span>
                        </label>
                    </div>
                </form>

                {* Billing Address (hidden by default) *}
                <div id="opc-billing-address" style="display: none;">
                    <h3 class="opc-subsection-title">{l s='Indirizzo di fatturazione' mod='onepagecheckout'}</h3>
                    <form id="opc-billing-form" class="opc-form">
                        <div class="opc-form-group">
                            <label for="billing_address1">{l s='Indirizzo e numero civico' mod='onepagecheckout'} <span class="required">*</span></label>
                            <input type="text" id="billing_address1" name="billing_address1" class="opc-input" />
                        </div>
                        <div class="opc-form-row">
                            <div class="opc-form-group opc-form-group-half">
                                <label for="billing_postcode">{l s='CAP' mod='onepagecheckout'}</label>
                                <input type="text" id="billing_postcode" name="billing_postcode" class="opc-input" />
                            </div>
                            <div class="opc-form-group opc-form-group-half">
                                <label for="billing_city">{l s='Città' mod='onepagecheckout'}</label>
                                <input type="text" id="billing_city" name="billing_city" class="opc-input" />
                            </div>
                        </div>
                        <div class="opc-form-group">
                            <label for="billing_country">{l s='Nazione' mod='onepagecheckout'}</label>
                            <select id="billing_country" name="billing_country" class="opc-select">
                                {foreach $countries as $country}
                                    <option value="{$country.id_country}">{$country.name}</option>
                                {/foreach}
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            {* Shipping Method Section *}
            <div class="opc-section" id="opc-shipping-section">
                <h2 class="opc-section-title">{l s='Metodo di spedizione' mod='onepagecheckout'}</h2>

                <div class="opc-carriers-list" id="opc-carriers-list">
                    {if count($carriers) > 0}
                        {foreach $carriers as $carrier}
                        <div class="opc-carrier-option">
                            <label class="opc-radio-label">
                                <input type="radio" name="id_carrier" value="{$carrier.id_carrier}"
                                       {if $carrier.selected}checked{/if} />
                                <span class="opc-carrier-info">
                                    {if $carrier.logo}
                                        <img src="{$carrier.logo}" alt="{$carrier.name}" class="opc-carrier-logo" />
                                    {/if}
                                    <span class="opc-carrier-name">{$carrier.name}</span>
                                    <span class="opc-carrier-delay">{$carrier.delay}</span>
                                </span>
                                <span class="opc-carrier-price">{$carrier.price_formatted}</span>
                            </label>
                        </div>
                        {/foreach}
                    {else}
                        <p class="opc-no-carriers">{l s='Inserisci l\'indirizzo per vedere i metodi di spedizione disponibili' mod='onepagecheckout'}</p>
                    {/if}
                </div>
            </div>

            {* Payment Method Section *}
            <div class="opc-section" id="opc-payment-section">
                <h2 class="opc-section-title">{l s='Metodo di pagamento' mod='onepagecheckout'}</h2>

                <div class="opc-payment-list" id="opc-payment-list">
                    {if count($payment_options) > 0}
                        {foreach $payment_options as $option}
                        <div class="opc-payment-option">
                            <label class="opc-radio-label">
                                <input type="radio" name="payment_module" value="{$option.module_name}" />
                                <span class="opc-payment-info">
                                    {if $option.logo}
                                        <img src="{$option.logo}" alt="{$option.call_to_action_text}" class="opc-payment-logo" />
                                    {/if}
                                    <span class="opc-payment-name">{$option.call_to_action_text}</span>
                                </span>
                            </label>
                            {if $option.additional_information}
                                <div class="opc-payment-info-extra">{$option.additional_information nofilter}</div>
                            {/if}
                            {if $option.form}
                                <div class="opc-payment-form">{$option.form nofilter}</div>
                            {/if}
                        </div>
                        {/foreach}
                    {else}
                        <p class="opc-no-payments">{l s='Nessun metodo di pagamento disponibile' mod='onepagecheckout'}</p>
                    {/if}
                </div>
            </div>

            {* Terms and Conditions *}
            <div class="opc-section" id="opc-terms-section">
                {foreach $conditions_to_approve as $condition_key => $condition}
                <div class="opc-form-group opc-checkbox-group">
                    <label class="opc-checkbox-label">
                        <input type="checkbox" id="{$condition_key}" name="conditions_to_approve[{$condition_key}]"
                               value="1" {if $condition.required}required{/if} />
                        <span>{$condition.label nofilter}</span>
                    </label>
                </div>
                {/foreach}
            </div>
        </div>

        {* Right Column - Order Summary *}
        <div class="opc-col-right">
            <div class="opc-order-summary" id="opc-order-summary">
                <h2 class="opc-section-title">{l s='Riepilogo ordine' mod='onepagecheckout'}</h2>

                {* Discount Code *}
                <div class="opc-discount-section">
                    <h3 class="opc-subsection-title">{l s='Buoni Regalo e Codici Promozionali' mod='onepagecheckout'}</h3>
                    <div class="opc-discount-form">
                        <input type="text" id="discount_code" name="discount_code" class="opc-input"
                               placeholder="{l s='Inserisci il codice' mod='onepagecheckout'}" />
                        <button type="button" class="opc-btn opc-btn-discount" id="opc-apply-discount">
                            {l s='Utilizza' mod='onepagecheckout'}
                        </button>
                    </div>
                </div>

                {* Products List *}
                <div class="opc-products-section">
                    <table class="opc-products-table">
                        <thead>
                            <tr>
                                <th class="opc-col-product">{l s='prodotti' mod='onepagecheckout'}</th>
                                <th class="opc-col-price">{l s='prezzo' mod='onepagecheckout'}</th>
                            </tr>
                        </thead>
                        <tbody id="opc-products-list">
                            {foreach $cart_summary.products as $product}
                            <tr class="opc-product-row" data-id-product="{$product.id_product}"
                                data-id-product-attribute="{$product.id_product_attribute}">
                                <td class="opc-col-product">
                                    <div class="opc-product-item">
                                        <img src="{$product.image}" alt="{$product.name}" class="opc-product-image" />
                                        <div class="opc-product-details">
                                            <span class="opc-product-qty">{$product.quantity} x</span>
                                            <span class="opc-product-original-price">
                                                {Tools::displayPrice($product.price_wt * 1.3)}
                                            </span>
                                            <span class="opc-product-current-price">
                                                {Tools::displayPrice($product.price_wt)}
                                            </span>
                                            <span class="opc-product-name">{$product.name}</span>
                                            {if $product.reference}
                                                <span class="opc-product-ref">{l s='Codice' mod='onepagecheckout'}: {$product.reference}</span>
                                            {/if}
                                            {if $product.availability_date}
                                                <span class="opc-product-date">{l s='Scadenza' mod='onepagecheckout'}: {$product.availability_date}</span>
                                            {/if}
                                            <span class="opc-product-stock opc-available">
                                                {l s='Disponibile' mod='onepagecheckout'}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="opc-col-price">
                                    {Tools::displayPrice($product.total_wt)}
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                {* Order Totals *}
                <div class="opc-totals-section" id="opc-totals">
                    <div class="opc-total-row">
                        <span class="opc-total-label">{l s='Sub Tot' mod='onepagecheckout'}:</span>
                        <span class="opc-total-value">
                            <span class="opc-original-price">{Tools::displayPrice($cart_summary.subtotal * 1.3)}</span>
                            <span id="opc-subtotal">{$cart_summary.subtotal_formatted}</span>
                        </span>
                    </div>
                    <div class="opc-total-row">
                        <span class="opc-total-label">{l s='Spese di Spedizione' mod='onepagecheckout'}:</span>
                        <span class="opc-total-value" id="opc-shipping">{$cart_summary.shipping_formatted}</span>
                    </div>
                    {if $cart_summary.discounts > 0}
                    <div class="opc-total-row opc-discount-row">
                        <span class="opc-total-label">{l s='Sconti' mod='onepagecheckout'}:</span>
                        <span class="opc-total-value" id="opc-discounts">-{$cart_summary.discounts_formatted}</span>
                    </div>
                    {/if}
                    <div class="opc-total-row opc-grand-total">
                        <span class="opc-total-label">{l s='Totale ordine' mod='onepagecheckout'}:</span>
                        <span class="opc-total-value" id="opc-total">{$cart_summary.total_formatted}</span>
                    </div>
                </div>

                {* Order Notes *}
                <div class="opc-notes-section">
                    <h3 class="opc-subsection-title">{l s='Note ordine' mod='onepagecheckout'}</h3>
                    <p class="opc-notes-desc">{l s='Specifica eventuali tue indicazioni in merito all\'ordine' mod='onepagecheckout'}</p>
                    <textarea id="order_message" name="order_message" class="opc-textarea"
                              placeholder="{l s='Le tue note...' mod='onepagecheckout'}"></textarea>
                </div>

                {* Submit Button *}
                <div class="opc-submit-section">
                    <button type="button" class="opc-btn opc-btn-checkout" id="opc-submit-order">
                        {l s='Conferma ordine' mod='onepagecheckout'}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{* Loading Overlay *}
<div class="opc-loading-overlay" id="opc-loading" style="display: none;">
    <div class="opc-spinner"></div>
    <span class="opc-loading-text">{l s='Elaborazione in corso...' mod='onepagecheckout'}</span>
</div>

{* Error Modal *}
<div class="opc-modal" id="opc-error-modal" style="display: none;">
    <div class="opc-modal-content">
        <span class="opc-modal-close">&times;</span>
        <div class="opc-modal-body" id="opc-error-content"></div>
    </div>
</div>
{/block}
