{**
 * One Page Checkout - Minimal Layout
 *}

{extends file='module:onepagecheckout/views/templates/layouts/layout-checkout.tpl'}

{block name='content'}
<div class="opc-checkout-container">
    <div class="opc-row">
        {* Left Column - Customer Data & Shipping *}
        <div class="opc-col-left">
            {* Customer Data *}
            <div class="opc-section" id="opc-customer-section">
                <h2 class="opc-section-title">{l s='I tuoi dati' mod='onepagecheckout'}</h2>
                <form id="opc-checkout-form" class="opc-form">
                    {* Name Row *}
                    <div class="opc-form-row">
                        <div class="opc-form-group opc-half">
                            <label for="firstname">{l s='Nome' mod='onepagecheckout'} *</label>
                            <input type="text" id="firstname" name="firstname" class="opc-input"
                                   value="{$customer_data.firstname|escape:'html':'UTF-8'}" required />
                        </div>
                        <div class="opc-form-group opc-half">
                            <label for="lastname">{l s='Cognome' mod='onepagecheckout'} *</label>
                            <input type="text" id="lastname" name="lastname" class="opc-input"
                                   value="{$customer_data.lastname|escape:'html':'UTF-8'}" required />
                        </div>
                    </div>

                    {* Email *}
                    {if !$is_logged}
                    <div class="opc-form-group">
                        <label for="email">{l s='Email' mod='onepagecheckout'} *</label>
                        <input type="email" id="email" name="email" class="opc-input" required />
                        <div id="opc-login-prompt" class="opc-login-prompt" style="display: none;">
                            <span>{l s='Email già registrata.' mod='onepagecheckout'}</span>
                            <a href="#" id="opc-show-login">{l s='Accedi' mod='onepagecheckout'}</a>
                        </div>
                        <div id="opc-login-inline" class="opc-login-inline" style="display: none;">
                            <input type="password" id="login_password" name="login_password" class="opc-input" placeholder="{l s='Password' mod='onepagecheckout'}" />
                            <button type="button" class="opc-btn opc-btn-login" id="opc-login-btn">{l s='Accedi' mod='onepagecheckout'}</button>
                        </div>
                    </div>
                    {else}
                    <div class="opc-form-group">
                        <label>{l s='Email' mod='onepagecheckout'}</label>
                        <input type="text" class="opc-input" value="{$customer_data.email|escape:'html':'UTF-8'}" disabled />
                    </div>
                    {/if}

                    {* Phone *}
                    <div class="opc-form-group">
                        <label for="phone">{l s='Telefono' mod='onepagecheckout'}</label>
                        <input type="tel" id="phone" name="phone" class="opc-input"
                               value="{if isset($address_data.phone)}{$address_data.phone|escape:'html':'UTF-8'}{/if}" />
                    </div>

                    {* Address *}
                    <div class="opc-form-group">
                        <label for="address1">{l s='Indirizzo' mod='onepagecheckout'} *</label>
                        <input type="text" id="address1" name="address1" class="opc-input"
                               value="{if isset($address_data.address1)}{$address_data.address1|escape:'html':'UTF-8'}{/if}" required />
                    </div>

                    {* City and ZIP Row *}
                    <div class="opc-form-row">
                        <div class="opc-form-group opc-third">
                            <label for="postcode">{l s='CAP' mod='onepagecheckout'} *</label>
                            <input type="text" id="postcode" name="postcode" class="opc-input"
                                   value="{if isset($address_data.postcode)}{$address_data.postcode|escape:'html':'UTF-8'}{/if}" required />
                        </div>
                        <div class="opc-form-group opc-two-thirds">
                            <label for="city">{l s='Città' mod='onepagecheckout'} *</label>
                            <input type="text" id="city" name="city" class="opc-input"
                                   value="{if isset($address_data.city)}{$address_data.city|escape:'html':'UTF-8'}{/if}" required />
                        </div>
                    </div>

                    {* Country and Province Row *}
                    <div class="opc-form-row">
                        <div class="opc-form-group opc-half">
                            <label for="id_country">{l s='Nazione' mod='onepagecheckout'} *</label>
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
                        <div class="opc-form-group opc-half" id="opc-state-container" {if empty($states)}style="display:none;"{/if}>
                            <label for="id_state">{l s='Provincia' mod='onepagecheckout'} *</label>
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

                    {* Saved addresses for logged users *}
                    {if $is_logged && count($addresses) > 0}
                    <div class="opc-form-group">
                        <label for="select_address">{l s='Indirizzi salvati' mod='onepagecheckout'}</label>
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
                    {/if}
                </form>
            </div>

            {* Shipping *}
            <div class="opc-section" id="opc-shipping-section">
                <h2 class="opc-section-title">{l s='Spedizione' mod='onepagecheckout'}</h2>
                <div class="opc-carriers-list" id="opc-carriers-list">
                    {if count($carriers) > 0}
                        {foreach $carriers as $carrier}
                        <label class="opc-carrier-option {if $carrier.selected}selected{/if}">
                            <input type="radio" name="id_carrier" value="{$carrier.id_carrier}" {if $carrier.selected}checked{/if} />
                            <span class="opc-carrier-name">{$carrier.name}</span>
                            <span class="opc-carrier-price">{$carrier.price_formatted}</span>
                        </label>
                        {/foreach}
                    {else}
                        <p class="opc-message">{l s='Inserisci indirizzo' mod='onepagecheckout'}</p>
                    {/if}
                </div>
            </div>

            {* Payment *}
            <div class="opc-section" id="opc-payment-section">
                <h2 class="opc-section-title">{l s='Pagamento' mod='onepagecheckout'}</h2>
                <div class="opc-payment-list" id="opc-payment-list">
                    {if count($payment_options) > 0}
                        {foreach $payment_options as $key => $option}
                        <div class="opc-payment-option {if $key == 0}selected{/if}" data-module="{$option.module_name}">
                            <label class="opc-payment-label">
                                <input type="radio" name="payment_module" value="{$option.module_name}" {if $key == 0}checked{/if} />
                                {if $option.logo}<img src="{$option.logo}" alt="" class="opc-payment-logo" />{/if}
                                <span class="opc-payment-name">{$option.call_to_action_text}</span>
                            </label>
                            {if $option.additional_information}
                            <div class="opc-payment-info" style="display: {if $key == 0}block{else}none{/if};">
                                {$option.additional_information nofilter}
                            </div>
                            {/if}
                        </div>
                        {/foreach}
                    {else}
                        <p class="opc-message">{l s='Nessun metodo disponibile' mod='onepagecheckout'}</p>
                    {/if}
                </div>
            </div>

            {* Terms *}
            {if $show_terms}
            <div class="opc-terms">
                <label class="opc-checkbox-label">
                    <input type="checkbox" id="terms-and-conditions" name="terms" value="1" required />
                    <span>{l s='Accetto le' mod='onepagecheckout'} <a href="{$termsLink}" target="_blank">{l s='condizioni di vendita' mod='onepagecheckout'}</a></span>
                </label>
            </div>
            {/if}
        </div>

        {* Right Column - Summary *}
        <div class="opc-col-right">
            <div class="opc-order-summary">
                <h2 class="opc-section-title">{l s='Riepilogo' mod='onepagecheckout'}</h2>

                {* Products *}
                <div class="opc-products-list" id="opc-products-list">
                    {foreach $cart_summary.products as $product}
                    <div class="opc-product-item">
                        <img src="{$product.image}" alt="" class="opc-product-img" />
                        <div class="opc-product-details">
                            <span class="opc-product-name">{$product.name}</span>
                            <span class="opc-product-qty">x{$product.quantity}</span>
                        </div>
                        <span class="opc-product-price">{Tools::displayPrice($product.total_wt)}</span>
                    </div>
                    {/foreach}
                </div>

                {* Discount *}
                <div class="opc-discount-box">
                    <div class="opc-discount-form">
                        <input type="text" id="discount_code" class="opc-input" placeholder="{l s='Codice sconto' mod='onepagecheckout'}" />
                        <button type="button" class="opc-btn opc-btn-apply" id="opc-apply-discount">{l s='Applica' mod='onepagecheckout'}</button>
                    </div>
                    <div id="opc-applied-discounts" class="opc-applied-discounts">
                        {foreach $cart_summary.cart_rules as $rule}
                        <div class="opc-applied-discount" data-id="{$rule.id_cart_rule}">
                            <span>{$rule.name}</span>
                            <span>-{$rule.value}</span>
                            <button type="button" class="opc-remove-discount" data-id="{$rule.id_cart_rule}">&times;</button>
                        </div>
                        {/foreach}
                    </div>
                </div>

                {* Totals *}
                <div class="opc-totals" id="opc-totals">
                    <div class="opc-total-row">
                        <span>{l s='Subtotale' mod='onepagecheckout'}</span>
                        <span id="opc-subtotal">{$cart_summary.subtotal_formatted}</span>
                    </div>
                    <div class="opc-total-row">
                        <span>{l s='Spedizione' mod='onepagecheckout'}</span>
                        <span id="opc-shipping-cost">{$cart_summary.shipping_formatted}</span>
                    </div>
                    {if $cart_summary.discounts > 0}
                    <div class="opc-total-row opc-discount-row">
                        <span>{l s='Sconto' mod='onepagecheckout'}</span>
                        <span id="opc-discounts">-{$cart_summary.discounts_formatted}</span>
                    </div>
                    {/if}
                    <div class="opc-total-row opc-grand-total">
                        <span>{l s='Totale' mod='onepagecheckout'}</span>
                        <span id="opc-total">{$cart_summary.total_formatted}</span>
                    </div>
                </div>

                {* Confirm *}
                <button type="button" class="opc-btn-confirm" id="opc-submit-order">
                    {l s='Conferma ordine' mod='onepagecheckout'}
                </button>
            </div>
        </div>
    </div>
</div>

{* Payment Frame *}
<div class="opc-payment-frame-container" id="opc-payment-frame-container" style="display: none;">
    <div class="opc-payment-frame-header">
        <h3>{l s='Pagamento' mod='onepagecheckout'}</h3>
        <button type="button" class="opc-payment-frame-close" id="opc-payment-frame-close">&times;</button>
    </div>
    <div class="opc-payment-frame-content">
        <iframe id="opc-payment-frame" name="opc-payment-frame"></iframe>
    </div>
</div>

{* Loading *}
<div class="opc-overlay" id="opc-loading" style="display: none;">
    <div class="opc-spinner"></div>
</div>

{* Toast *}
<div class="opc-toast" id="opc-toast" style="display: none;">
    <span id="opc-toast-message"></span>
    <button type="button" class="opc-toast-close">&times;</button>
</div>

{* Success *}
<div class="opc-order-success" id="opc-order-success" style="display: none;">
    <div class="opc-success-icon">&#10003;</div>
    <h2>{l s='Ordine confermato!' mod='onepagecheckout'}</h2>
    <p>{l s='Grazie per il tuo acquisto.' mod='onepagecheckout'}</p>
    <p class="opc-order-ref">{l s='Ordine' mod='onepagecheckout'}: <strong id="opc-order-reference"></strong></p>
    <a href="{$urls.pages.history}" class="opc-btn-confirm">{l s='I miei ordini' mod='onepagecheckout'}</a>
</div>
{/block}
