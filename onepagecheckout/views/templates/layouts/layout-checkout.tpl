<!doctype html>
<html lang="{$language.iso_code}">
<head>
    {block name='head'}
        {include file='_partials/head.tpl'}
    {/block}
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
