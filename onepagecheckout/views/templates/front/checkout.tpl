{**
 * One Page Checkout Template - PlusPower Style
 * Single unified checkout page
 *}

{extends file='page.tpl'}

{block name='page_header_container'}{/block}

{block name='page_content'}
<div class="opc-checkout-container">
    <h1 class="opc-title">{l s='CONFERMA D\'ORDINE' mod='onepagecheckout'}</h1>

    <div class="opc-row">
        {* Left Column - All Customer Data in One Form *}
        <div class="opc-col-left">
            <div class="opc-section" id="opc-main-form-section">
                <h2 class="opc-section-title">{l s='I miei dati' mod='onepagecheckout'}</h2>

                <form id="opc-checkout-form" class="opc-form">
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
                    <div id="opc-company-fields" class="opc-company-fields">
                        <div class="opc-form-group">
                            <label for="company">{l s='Ragione Sociale' mod='onepagecheckout'}</label>
                            <input type="text" id="company" name="company" class="opc-input"
                                   value="{if isset($address_data.company)}{$address_data.company|escape:'html':'UTF-8'}{/if}" />
                        </div>
                        <div class="opc-form-group">
                            <label for="vat_number">{l s='Partita IVA' mod='onepagecheckout'}</label>
                            <input type="text" id="vat_number" name="vat_number" class="opc-input"
                                   value="{if isset($address_data.vat_number)}{$address_data.vat_number|escape:'html':'UTF-8'}{/if}" />
                        </div>
                    </div>

                    {* Name *}
                    <div class="opc-form-group">
                        <label for="firstname">{l s='Nome' mod='onepagecheckout'}</label>
                        <input type="text" id="firstname" name="firstname" class="opc-input"
                               value="{$customer_data.firstname|escape:'html':'UTF-8'}" required />
                    </div>

                    {* Surname *}
                    <div class="opc-form-group">
                        <label for="lastname">{l s='Cognome' mod='onepagecheckout'}</label>
                        <input type="text" id="lastname" name="lastname" class="opc-input"
                               value="{$customer_data.lastname|escape:'html':'UTF-8'}" required />
                    </div>

                    {* Birthday *}
                    <div class="opc-form-group opc-birthday-group">
                        <label>{l s='Data di nascita:' mod='onepagecheckout'}</label>
                        <div class="opc-birthday-selects">
                            <select id="birthday_day" name="birthday_day" class="opc-select opc-select-day">
                                {foreach $days as $day}
                                    <option value="{$day|string_format:'%02d'}"
                                            {if isset($customer_birthday.day) && $customer_birthday.day == $day|string_format:'%02d'}selected{/if}>{$day}</option>
                                {/foreach}
                            </select>
                            <select id="birthday_month" name="birthday_month" class="opc-select opc-select-month">
                                {foreach $months as $month}
                                    <option value="{$month.value}"
                                            {if isset($customer_birthday.month) && $customer_birthday.month == $month.value}selected{/if}>{$month.label}</option>
                                {/foreach}
                            </select>
                            <select id="birthday_year" name="birthday_year" class="opc-select opc-select-year">
                                {foreach $years as $year}
                                    <option value="{$year}"
                                            {if isset($customer_birthday.year) && $customer_birthday.year == $year}selected{/if}>{$year}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    {* Email (for guests only, show login if exists) *}
                    {if !$is_logged}
                    <div class="opc-form-group opc-email-group">
                        <label for="email">{l s='Email' mod='onepagecheckout'}</label>
                        <input type="email" id="email" name="email" class="opc-input" required />
                        <div id="opc-login-prompt" class="opc-login-prompt" style="display: none;">
                            <span>{l s='Questa email è già registrata.' mod='onepagecheckout'}</span>
                            <a href="#" id="opc-show-login">{l s='Accedi' mod='onepagecheckout'}</a>
                        </div>
                        <div id="opc-login-inline" class="opc-login-inline" style="display: none;">
                            <div class="opc-form-group">
                                <label for="login_password">{l s='Password' mod='onepagecheckout'}</label>
                                <input type="password" id="login_password" name="login_password" class="opc-input" />
                            </div>
                            <button type="button" class="opc-btn opc-btn-login" id="opc-login-btn">
                                {l s='Accedi' mod='onepagecheckout'}
                            </button>
                        </div>
                    </div>
                    {/if}

                    {* Phone - Optional *}
                    <div class="opc-form-group">
                        <label for="phone">
                            {l s='Telefono' mod='onepagecheckout'}
                            <span class="opc-optional">{l s='Campo non obbligatorio' mod='onepagecheckout'}</span>
                        </label>
                        <input type="tel" id="phone" name="phone" class="opc-input"
                               value="{if isset($address_data.phone)}{$address_data.phone|escape:'html':'UTF-8'}{/if}" />
                    </div>

                    {* Mobile Phone *}
                    <div class="opc-form-group">
                        <label for="phone_mobile">{l s='Cellulare' mod='onepagecheckout'}</label>
                        <input type="tel" id="phone_mobile" name="phone_mobile" class="opc-input"
                               value="{if isset($address_data.phone_mobile)}{$address_data.phone_mobile|escape:'html':'UTF-8'}{/if}" />
                    </div>

                    {* Address *}
                    <div class="opc-form-group">
                        <label for="address1">{l s='Indirizzo e numero civico' mod='onepagecheckout'}</label>
                        <input type="text" id="address1" name="address1" class="opc-input"
                               value="{if isset($address_data.address1)}{$address_data.address1|escape:'html':'UTF-8'}{/if}" required />
                    </div>

                    {* ZIP Code *}
                    <div class="opc-form-group">
                        <label for="postcode">{l s='CAP' mod='onepagecheckout'}</label>
                        <input type="text" id="postcode" name="postcode" class="opc-input"
                               value="{if isset($address_data.postcode)}{$address_data.postcode|escape:'html':'UTF-8'}{/if}" required />
                    </div>

                    {* City *}
                    <div class="opc-form-group">
                        <label for="city">{l s='Città' mod='onepagecheckout'}</label>
                        <input type="text" id="city" name="city" class="opc-input"
                               value="{if isset($address_data.city)}{$address_data.city|escape:'html':'UTF-8'}{/if}" required />
                    </div>

                    {* Country *}
                    <div class="opc-form-group">
                        <label for="id_country">{l s='Nazione' mod='onepagecheckout'}</label>
                        <select id="id_country" name="id_country" class="opc-select" required>
                            {foreach $countries as $country}
                                <option value="{$country.id_country}"
                                        {if (isset($address_data.id_country) && $address_data.id_country == $country.id_country) || (!isset($address_data.id_country) && $country.id_country == $default_country)}selected{/if}>
                                    {$country.name}
                                </option>
                            {/foreach}
                        </select>
                    </div>

                    {* Existing addresses dropdown for logged users *}
                    {if $is_logged && count($addresses) > 0}
                    <div class="opc-form-group opc-address-selector">
                        <label for="select_address">{l s='Oppure seleziona un indirizzo salvato' mod='onepagecheckout'}</label>
                        <select id="select_address" name="select_address" class="opc-select">
                            <option value="">{l s='-- Usa i dati inseriti sopra --' mod='onepagecheckout'}</option>
                            {foreach $addresses as $address}
                                <option value="{$address.id_address}"
                                        data-address='{$address|json_encode}'
                                        {if $address.id_address == $selected_address_id}selected{/if}>
                                    {$address.alias} - {$address.address1}, {$address.postcode} {$address.city}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                    {/if}
                </form>
            </div>

            {* Shipping Method - Compact *}
            <div class="opc-section opc-section-compact" id="opc-shipping-section">
                <h2 class="opc-section-title">{l s='Spedizione' mod='onepagecheckout'}</h2>
                <div class="opc-carriers-list" id="opc-carriers-list">
                    {if count($carriers) > 0}
                        {foreach $carriers as $carrier}
                        <label class="opc-carrier-option {if $carrier.selected}selected{/if}">
                            <input type="radio" name="id_carrier" value="{$carrier.id_carrier}"
                                   {if $carrier.selected}checked{/if} />
                            <span class="opc-carrier-name">{$carrier.name}</span>
                            <span class="opc-carrier-price">{$carrier.price_formatted}</span>
                        </label>
                        {/foreach}
                    {else}
                        <p class="opc-message">{l s='Completa l\'indirizzo per vedere le opzioni di spedizione' mod='onepagecheckout'}</p>
                    {/if}
                </div>
            </div>

            {* Payment Method - Compact *}
            <div class="opc-section opc-section-compact" id="opc-payment-section">
                <h2 class="opc-section-title">{l s='Pagamento' mod='onepagecheckout'}</h2>
                <div class="opc-payment-list" id="opc-payment-list">
                    {if count($payment_options) > 0}
                        {foreach $payment_options as $key => $option}
                        <div class="opc-payment-option">
                            <label class="opc-payment-label">
                                <input type="radio" name="payment_module" value="{$option.module_name}"
                                       {if $key == 0}checked{/if} />
                                {if $option.logo}
                                    <img src="{$option.logo}" alt="{$option.call_to_action_text}" class="opc-payment-logo" />
                                {/if}
                                <span class="opc-payment-name">{$option.call_to_action_text}</span>
                            </label>
                            {if $option.form}
                                <div class="opc-payment-form" style="display: {if $key == 0}block{else}none{/if};">
                                    {$option.form nofilter}
                                </div>
                            {/if}
                        </div>
                        {/foreach}
                    {else}
                        <p class="opc-message">{l s='Nessun metodo di pagamento disponibile' mod='onepagecheckout'}</p>
                    {/if}
                </div>
            </div>

            {* Terms and Conditions - Inline *}
            <div class="opc-terms-inline" id="opc-terms-section">
                {if $show_terms}
                <label class="opc-checkbox-label">
                    <input type="checkbox" id="terms-and-conditions" name="terms" value="1" required />
                    <span>{l s='Accetto le' mod='onepagecheckout'} <a href="{$termsLink}" target="_blank">{l s='condizioni generali di vendita' mod='onepagecheckout'}</a></span>
                </label>
                {/if}
            </div>
        </div>

        {* Right Column - Order Summary *}
        <div class="opc-col-right">
            <div class="opc-order-summary" id="opc-order-summary">
                <h2 class="opc-section-title">{l s='Riepilogo ordine' mod='onepagecheckout'}</h2>

                {* Discount Code *}
                <div class="opc-discount-box">
                    <h3 class="opc-box-title">{l s='Buoni Regalo e Codici Promozionali' mod='onepagecheckout'}</h3>
                    <div class="opc-discount-form">
                        <input type="text" id="discount_code" name="discount_code" class="opc-input"
                               placeholder="{l s='Inserisci il codice' mod='onepagecheckout'}" />
                        <button type="button" class="opc-btn opc-btn-apply" id="opc-apply-discount">
                            {l s='Utilizza' mod='onepagecheckout'}
                        </button>
                    </div>
                    {* Applied discounts *}
                    <div id="opc-applied-discounts" class="opc-applied-discounts">
                        {foreach $cart_summary.cart_rules as $rule}
                        <div class="opc-applied-discount" data-id="{$rule.id_cart_rule}">
                            <span>{$rule.name}</span>
                            <span class="opc-discount-value">-{$rule.value}</span>
                            <button type="button" class="opc-remove-discount" data-id="{$rule.id_cart_rule}">&times;</button>
                        </div>
                        {/foreach}
                    </div>
                </div>

                {* Loyalty Points (if applicable) *}
                {if isset($loyalty_points) && $loyalty_points > 0}
                <div class="opc-loyalty-box">
                    <p>{l s='Hai' mod='onepagecheckout'} <strong>{$loyalty_points}</strong> {l s='punti per un valore di' mod='onepagecheckout'} <strong>{$loyalty_value}</strong></p>
                    <div class="opc-loyalty-form">
                        <input type="text" id="loyalty_points_use" name="loyalty_points_use" class="opc-input"
                               placeholder="{l s='Inserisci i punti' mod='onepagecheckout'}" />
                        <button type="button" class="opc-btn opc-btn-apply" id="opc-apply-points">
                            {l s='Utilizza' mod='onepagecheckout'}
                        </button>
                    </div>
                </div>
                {/if}

                {* Products List *}
                <div class="opc-products-box">
                    <table class="opc-products-table">
                        <thead>
                            <tr>
                                <th>{l s='prodotti' mod='onepagecheckout'}</th>
                                <th class="opc-col-right-align">{l s='prezzo' mod='onepagecheckout'}</th>
                            </tr>
                        </thead>
                        <tbody id="opc-products-list">
                            {foreach $cart_summary.products as $product}
                            <tr class="opc-product-row">
                                <td>
                                    <div class="opc-product-item">
                                        <img src="{$product.image}" alt="{$product.name}" class="opc-product-img" />
                                        <div class="opc-product-info">
                                            <div class="opc-product-price-line">
                                                <span class="opc-qty">{$product.quantity} x</span>
                                                {if isset($product.reduction_percent) && $product.reduction_percent > 0}
                                                <span class="opc-old-price">{$product.regular_price_formatted}</span>
                                                {/if}
                                                <span class="opc-price">{Tools::displayPrice($product.price_wt)}</span>
                                            </div>
                                            <div class="opc-product-name">{$product.name}</div>
                                            {if $product.reference}
                                            <div class="opc-product-ref">{l s='Codice' mod='onepagecheckout'}: {$product.reference}</div>
                                            {/if}
                                            {if $product.attributes}
                                            <div class="opc-product-attr">{$product.attributes}</div>
                                            {/if}
                                            <div class="opc-product-stock">
                                                <span class="opc-stock-dot"></span>
                                                {l s='Disponibile' mod='onepagecheckout'}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="opc-col-right-align opc-product-total">
                                    {Tools::displayPrice($product.total_wt)}
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                {* Totals *}
                <div class="opc-totals-box" id="opc-totals">
                    <div class="opc-total-line">
                        <span>{l s='Sub Tot' mod='onepagecheckout'}:</span>
                        <span id="opc-subtotal">{$cart_summary.subtotal_formatted}</span>
                    </div>
                    <div class="opc-total-line">
                        <span>{l s='Spese di Spedizione' mod='onepagecheckout'}:</span>
                        <span id="opc-shipping-cost">{$cart_summary.shipping_formatted}</span>
                    </div>
                    {if $cart_summary.discounts > 0}
                    <div class="opc-total-line opc-discount-line">
                        <span>{l s='Sconti' mod='onepagecheckout'}:</span>
                        <span id="opc-discounts">-{$cart_summary.discounts_formatted}</span>
                    </div>
                    {/if}
                    <div class="opc-total-line opc-grand-total">
                        <span>{l s='Totale ordine' mod='onepagecheckout'}:</span>
                        <span id="opc-total">{$cart_summary.total_formatted}</span>
                    </div>
                </div>

                {* Order Notes *}
                <div class="opc-notes-box">
                    <h3 class="opc-box-title">{l s='Note ordine' mod='onepagecheckout'}</h3>
                    <p class="opc-notes-hint">{l s='Specifica eventuali tue indicazioni in merito all\'ordine' mod='onepagecheckout'}</p>
                    <textarea id="order_message" name="order_message" class="opc-textarea"
                              placeholder="{l s='Le tue note...' mod='onepagecheckout'}"></textarea>
                </div>

                {* Confirm Button *}
                <button type="button" class="opc-btn opc-btn-confirm" id="opc-submit-order">
                    {l s='Conferma ordine' mod='onepagecheckout'}
                </button>
            </div>
        </div>
    </div>
</div>

{* Loading Overlay *}
<div class="opc-overlay" id="opc-loading" style="display: none;">
    <div class="opc-spinner"></div>
</div>

{* Error Messages *}
<div class="opc-toast" id="opc-toast" style="display: none;">
    <span id="opc-toast-message"></span>
    <button type="button" class="opc-toast-close">&times;</button>
</div>
{/block}
