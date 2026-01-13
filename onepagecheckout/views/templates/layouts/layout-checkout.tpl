<!doctype html>
<html lang="{$language.iso_code}">
<head>
    {block name='head'}
        {include file='_partials/head.tpl'}
    {/block}
    <style>
        :root {
            --opc-primary: {$opc_colors.primary|default:'#008060'};
            --opc-primary-hover: {$opc_colors.primary_hover|default:'#006e52'};
            --opc-text: {$opc_colors.text|default:'#333333'};
            --opc-text-secondary: {$opc_colors.text_secondary|default:'#6b7177'};
            --opc-border: {$opc_colors.border|default:'#d9d9d9'};
            --opc-background: {$opc_colors.background|default:'#fafafa'};
            --opc-error: {$opc_colors.error|default:'#d72c0d'};
            --opc-success: {$opc_colors.success|default:'#008060'};
            --opc-button-radius: {$opc_colors.button_radius|default:'5px'};
        }
    </style>
</head>
<body id="{$page.page_name}" class="{$page.body_classes|classnames} opc-body">
    {block name='hook_after_body_opening_tag'}
        {hook h='displayAfterBodyOpeningTag'}
    {/block}

    <div class="opc-minimal-header">
        <div class="opc-header-container">
            <a href="{$urls.base_url}" class="opc-logo-link">
                <img src="{$shop.logo}" alt="{$shop.name}" class="opc-logo" />
            </a>
            <span class="opc-header-title">{l s='Conferma Ordine' mod='onepagecheckout'}</span>
            <div class="opc-header-spacer"></div>
        </div>
    </div>

    <main id="content" class="opc-main-content">
        {block name='content'}
            <p>No content</p>
        {/block}
    </main>

    {block name='javascript_bottom'}
        {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
    {/block}

    {block name='hook_before_body_closing_tag'}
        {hook h='displayBeforeBodyClosingTag'}
    {/block}
</body>
</html>
