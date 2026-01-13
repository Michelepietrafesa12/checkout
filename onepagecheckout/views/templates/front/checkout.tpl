{**
 * One Page Checkout - Smart UX Layout
 *}

{extends file='module:onepagecheckout/views/templates/layouts/layout-checkout.tpl'}

{block name='content'}
<div class="opc-checkout-container">
    <div class="opc-row">
        {* Left Column - Form Data *}
        <div class="opc-col-left">
            {* Customer Data *}
            <div class="opc-section" id="opc-customer-section">
                <h2 class="opc-section-title">{l s='Dati di spedizione' mod='onepagecheckout'}</h2>
                <form id="opc-checkout-form" class="opc-form-inline">
                    {* Saved addresses for logged users *}
                    {if $is_logged && count($addresses) > 0}
                    <div class="opc-field">
                        <label for="select_address">{l s='Indirizzo salvato' mod='onepagecheckout'}</label>
                        <div class="opc-field-input">
                            <select id="select_address" name="select_address" class="opc-select">
                                <option value="">{l s='-- Nuovo indirizzo --' mod='onepagecheckout'}</option>
                                {foreach $addresses as $address}
                                    <option value="{$address.id_address}" data-address='{$address|json_encode}'
                                            {if $address.id_address == $selected_address_id}selected{/if}>
                                        {$address.alias} - {$address.address1}, {$address.city}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    {/if}

                    {* Name Fields *}
                    <div class="opc-field-row">
                        <div class="opc-field opc-field-half">
                            <label for="firstname">{l s='Nome' mod='onepagecheckout'} <span class="required">*</span></label>
                            <div class="opc-field-input">
                                <input type="text" id="firstname" name="firstname" class="opc-input"
                                       value="{$customer_data.firstname|escape:'html':'UTF-8'}" required />
                            </div>
                        </div>
                        <div class="opc-field opc-field-half">
                            <label for="lastname">{l s='Cognome' mod='onepagecheckout'} <span class="required">*</span></label>
                            <div class="opc-field-input">
                                <input type="text" id="lastname" name="lastname" class="opc-input"
                                       value="{$customer_data.lastname|escape:'html':'UTF-8'}" required />
                            </div>
                        </div>
                    </div>

                    {* Email *}
                    {if !$is_logged}
                    <div class="opc-field">
                        <label for="email">{l s='Email' mod='onepagecheckout'} <span class="required">*</span></label>
                        <div class="opc-field-input">
                            <input type="email" id="email" name="email" class="opc-input" required />
                            <div id="opc-login-prompt" class="opc-field-hint opc-error" style="display: none;">
                                <span>{l s='Email già registrata.' mod='onepagecheckout'}</span>
                                <a href="#" id="opc-show-login">{l s='Accedi' mod='onepagecheckout'}</a>
                            </div>
                            <div id="opc-login-inline" class="opc-login-box" style="display: none;">
                                <input type="password" id="login_password" name="login_password" class="opc-input" placeholder="{l s='Password' mod='onepagecheckout'}" />
                                <button type="button" class="opc-btn opc-btn-small" id="opc-login-btn">{l s='Accedi' mod='onepagecheckout'}</button>
                            </div>
                        </div>
                    </div>
                    {else}
                    <div class="opc-field">
                        <label>{l s='Email' mod='onepagecheckout'}</label>
                        <div class="opc-field-input">
                            <input type="text" class="opc-input opc-input-disabled" value="{$customer_data.email|escape:'html':'UTF-8'}" disabled />
                        </div>
                    </div>
                    {/if}

                    {* Phone *}
                    <div class="opc-field">
                        <label for="phone">{l s='Telefono' mod='onepagecheckout'}</label>
                        <div class="opc-field-input">
                            <input type="tel" id="phone" name="phone" class="opc-input"
                                   value="{if isset($address_data.phone)}{$address_data.phone|escape:'html':'UTF-8'}{/if}" />
                        </div>
                    </div>

                    {* Address *}
                    <div class="opc-field">
                        <label for="address1">{l s='Indirizzo' mod='onepagecheckout'} <span class="required">*</span></label>
                        <div class="opc-field-input">
                            <input type="text" id="address1" name="address1" class="opc-input"
                                   value="{if isset($address_data.address1)}{$address_data.address1|escape:'html':'UTF-8'}{/if}" required />
                        </div>
                    </div>

                    {* City and ZIP *}
                    <div class="opc-field-row">
                        <div class="opc-field opc-field-small">
                            <label for="postcode">{l s='CAP' mod='onepagecheckout'} <span class="required">*</span></label>
                            <div class="opc-field-input">
                                <input type="text" id="postcode" name="postcode" class="opc-input"
                                       value="{if isset($address_data.postcode)}{$address_data.postcode|escape:'html':'UTF-8'}{/if}" required />
                            </div>
                        </div>
                        <div class="opc-field opc-field-large">
                            <label for="city">{l s='Città' mod='onepagecheckout'} <span class="required">*</span></label>
                            <div class="opc-field-input">
                                <input type="text" id="city" name="city" class="opc-input"
                                       value="{if isset($address_data.city)}{$address_data.city|escape:'html':'UTF-8'}{/if}" required />
                            </div>
                        </div>
                    </div>

                    {* Country and Province *}
                    <div class="opc-field-row">
                        <div class="opc-field opc-field-half">
                            <label for="id_country">{l s='Nazione' mod='onepagecheckout'} <span class="required">*</span></label>
                            <div class="opc-field-input">
                                <select id="id_country" name="id_country" class="opc-select" required>
                                    {foreach $countries as $country}
                                        <option value="{$country.id_country}"
                                                data-contains-states="{if isset($country.contains_states)}{$country.contains_states}{else}0{/if}"
                                                {if (isset($address_data.id_country) && $address_data.id_country == $country.id_country) || (!isset($address_data.id_country) && $country.id_country == $default_country)}selected{/if}>
                                            {$country.name}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="opc-field opc-field-half" id="opc-state-container" {if empty($states)}style="display:none;"{/if}>
                            <label for="id_state">{l s='Provincia' mod='onepagecheckout'} <span class="required">*</span></label>
                            <div class="opc-field-input">
                                <select id="id_state" name="id_state" class="opc-select">
                                    <option value="">{l s='-- Seleziona --' mod='onepagecheckout'}</option>
                                    {if isset($states) && $states}
                                        {foreach $states as $state}
                                            <option value="{$state.id_state}"
                                                    {if isset($address_data.id_state) && $address_data.id_state == $state.id_state}selected{/if}>
                                                {$state.name}
                                            </option>
                                        {/foreach}
                                    {/if}
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {* Shipping *}
            <div class="opc-section" id="opc-shipping-section">
                <h2 class="opc-section-title">{l s='Metodo di spedizione' mod='onepagecheckout'}</h2>
                <div class="opc-carriers-list" id="opc-carriers-list">
                    {if count($carriers) > 0}
                        {foreach $carriers as $carrier}
                        <label class="opc-option-card {if $carrier.selected}selected{/if}">
                            <input type="radio" name="id_carrier" value="{$carrier.id_carrier}" {if $carrier.selected}checked{/if} />
                            <span class="opc-option-content">
                                <span class="opc-option-name">{$carrier.name}</span>
                            </span>
                            <span class="opc-option-price">{$carrier.price_formatted}</span>
                        </label>
                        {/foreach}
                    {else}
                        <p class="opc-empty-message">{l s='Inserisci l\'indirizzo per vedere le opzioni di spedizione' mod='onepagecheckout'}</p>
                    {/if}
                </div>
            </div>

            {* Payment *}
            <div class="opc-section" id="opc-payment-section">
                <h2 class="opc-section-title">{l s='Metodo di pagamento' mod='onepagecheckout'}</h2>
                <div class="opc-payment-list" id="opc-payment-list">
                    {if count($payment_options) > 0}
                        {foreach $payment_options as $key => $option}
                        <div class="opc-payment-card {if $key == 0}selected{/if}" data-module="{$option.module_name}">
                            <label class="opc-option-card">
                                <input type="radio" name="payment_module" value="{$option.module_name}" {if $key == 0}checked{/if} />
                                <span class="opc-option-content">
                                    {if $option.logo}<img src="{$option.logo}" alt="" class="opc-payment-logo" />{/if}
                                    <span class="opc-option-name">{$option.call_to_action_text}</span>
                                </span>
                            </label>
                            {if $option.additional_information}
                            <div class="opc-payment-extra" style="display: {if $key == 0}block{else}none{/if};">
                                {$option.additional_information nofilter}
                            </div>
                            {/if}
                        </div>
                        {/foreach}
                    {else}
                        <p class="opc-empty-message">{l s='Nessun metodo di pagamento disponibile' mod='onepagecheckout'}</p>
                    {/if}
                </div>
            </div>
        </div>

        {* Right Column - Summary & Actions *}
        <div class="opc-col-right">
            <div class="opc-summary-box">
                <h2 class="opc-section-title">{l s='Il tuo ordine' mod='onepagecheckout'}</h2>

                {* Products *}
                <div class="opc-cart-items" id="opc-products-list">
                    {foreach $cart_summary.products as $product}
                    <div class="opc-cart-item">
                        <img src="{$product.image}" alt="" class="opc-cart-item-img" />
                        <div class="opc-cart-item-info">
                            <span class="opc-cart-item-name">{$product.name}</span>
                            <span class="opc-cart-item-qty">Qtà: {$product.quantity}</span>
                        </div>
                        <span class="opc-cart-item-price">{Tools::displayPrice($product.total_wt)}</span>
                    </div>
                    {/foreach}
                </div>

                {* Discount Code *}
                <div class="opc-promo-box">
                    <div class="opc-promo-form">
                        <input type="text" id="discount_code" class="opc-input" placeholder="{l s='Codice sconto' mod='onepagecheckout'}" />
                        <button type="button" class="opc-btn opc-btn-promo" id="opc-apply-discount">{l s='Applica' mod='onepagecheckout'}</button>
                    </div>
                    <div id="opc-applied-discounts" class="opc-applied-codes">
                        {foreach $cart_summary.cart_rules as $rule}
                        <div class="opc-applied-code" data-id="{$rule.id_cart_rule}">
                            <span class="opc-code-name">{$rule.name}</span>
                            <span class="opc-code-value">-{$rule.value}</span>
                            <button type="button" class="opc-code-remove" data-id="{$rule.id_cart_rule}">&times;</button>
                        </div>
                        {/foreach}
                    </div>
                </div>

                {* Totals *}
                <div class="opc-summary-totals" id="opc-totals">
                    <div class="opc-summary-line">
                        <span>{l s='Subtotale' mod='onepagecheckout'}</span>
                        <span id="opc-subtotal">{$cart_summary.subtotal_formatted}</span>
                    </div>
                    <div class="opc-summary-line">
                        <span>{l s='Spedizione' mod='onepagecheckout'}</span>
                        <span id="opc-shipping-cost">{$cart_summary.shipping_formatted}</span>
                    </div>
                    {if $cart_summary.discounts > 0}
                    <div class="opc-summary-line opc-line-discount">
                        <span>{l s='Sconto' mod='onepagecheckout'}</span>
                        <span id="opc-discounts">-{$cart_summary.discounts_formatted}</span>
                    </div>
                    {/if}
                    <div class="opc-summary-line opc-line-total">
                        <span>{l s='Totale' mod='onepagecheckout'}</span>
                        <span id="opc-total">{$cart_summary.total_formatted}</span>
                    </div>
                </div>

                {* Order Notes *}
                <div class="opc-notes-section">
                    <label for="order_notes">{l s='Note ordine' mod='onepagecheckout'}</label>
                    <textarea id="order_notes" name="order_notes" class="opc-textarea" placeholder="{l s='Istruzioni speciali per la consegna o note aggiuntive...' mod='onepagecheckout'}"></textarea>
                </div>

                {* Terms & Confirm *}
                <div class="opc-actions-section">
                    {if $show_terms}
                    <div class="opc-terms-box">
                        <label class="opc-checkbox">
                            <input type="checkbox" id="terms-and-conditions" name="terms" value="1" required />
                            <span class="opc-checkbox-mark"></span>
                            <span class="opc-checkbox-text">{l s='Accetto le' mod='onepagecheckout'} <a href="{$termsLink}" target="_blank">{l s='condizioni di vendita' mod='onepagecheckout'}</a></span>
                        </label>
                    </div>
                    {/if}

                    <button type="button" class="opc-btn-order" id="opc-submit-order">
                        {l s='Conferma ordine' mod='onepagecheckout'}
                    </button>

                    <p class="opc-secure-note">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0110 0v4"></path>
                        </svg>
                        {l s='Pagamento sicuro e protetto' mod='onepagecheckout'}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{* Payment Frame *}
<div class="opc-modal" id="opc-payment-frame-container" style="display: none;">
    <div class="opc-modal-header">
        <h3>{l s='Pagamento' mod='onepagecheckout'}</h3>
        <button type="button" class="opc-modal-close" id="opc-payment-frame-close">&times;</button>
    </div>
    <div class="opc-modal-body">
        <iframe id="opc-payment-frame" name="opc-payment-frame"></iframe>
    </div>
</div>

{* Loading Overlay *}
<div class="opc-loading" id="opc-loading" style="display: none;">
    <div class="opc-loading-spinner"></div>
    <span class="opc-loading-text">{l s='Elaborazione in corso...' mod='onepagecheckout'}</span>
</div>

{* Toast Notifications *}
<div class="opc-toast" id="opc-toast" style="display: none;">
    <span id="opc-toast-message"></span>
    <button type="button" class="opc-toast-close">&times;</button>
</div>

{* Success Modal *}
<div class="opc-success-modal" id="opc-order-success" style="display: none;">
    <div class="opc-success-icon">
        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#c8e600" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
    </div>
    <h2>{l s='Ordine confermato!' mod='onepagecheckout'}</h2>
    <p>{l s='Grazie per il tuo acquisto.' mod='onepagecheckout'}</p>
    <p class="opc-order-ref">{l s='Ordine' mod='onepagecheckout'}: <strong id="opc-order-reference"></strong></p>
    <a href="{$urls.pages.history}" class="opc-btn-order">{l s='I miei ordini' mod='onepagecheckout'}</a>
</div>
{/block}
