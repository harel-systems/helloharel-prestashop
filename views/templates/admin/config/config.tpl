<style>
#content .helloharel * {
    font-size: 14px;
}
.alert .btn.pull-right {
    margin-top: -6px;
}
.icon-ul li {
    margin-bottom: 5px;
}
</style>

<form class="helloharel" method="POST">
    {if $action.type eq 'unlink'}
        {if not $action.confirmed}
            <div class="alert alert-danger">
                {l s='Are you sure you want to unlink PrestaShop from your Hello Harel instance?' mod='Modules.HelloHarel.Admin'}<br />
                {l s='Your mapping (products, customers, orders) will be kept if you want to link them again later.' mod='Modules.HelloHarel.Admin'}
                <div class="text-right">
                    <a class="btn btn-default" href="">
                        {l s='Cancel' mod='Modules.HelloHarel.Admin'}
                    </a>
                    
                    <button class="btn btn-danger" type="submit" name="unlink_confirm">
                        <i class="icon-check"></i>
                        {l s='Confirm' mod='Modules.HelloHarel.Admin'}
                    </button>
                </div>
            </div>
        {/if}
    {else}
        <div class="alert alert-success">
            <a class="btn btn-default pull-right" href="{$instanceUrl}">
                <i class="icon-link"></i>
                {l s='Go to your instance' mod='Modules.HelloHarel.Admin'}
            </a>
            <strong>{l s='Congratulations!' mod='Modules.HelloHarel.Admin'}</strong>
            {l s='This PrestaShop instance is now controlled by %instanceUrl%.' sprintf=['%instanceUrl%' => $instanceUrl] mod='Modules.HelloHarel.Admin'}
        </div>

        <div class="well">
            <p>{l s='Your Hello Harel instance now controls PrestaShop. This is an overview of the mapping between PrestaShop and Hello Harel:' mod='Modules.HelloHarel.Admin'}</p>
            <ul class="icon-ul">
                <li>
                    <i class="icon-barcode icon-li"></i>
                    {l s='%count% product(s)' sprintf=['%count%' => $references.products] mod='Modules.HelloHarel.Admin'}
                </li>
                <li>
                    <i class="icon-user icon-li"></i>
                    {l s='%count% customer(s)' sprintf=['%count%' => $references.customers] mod='Modules.HelloHarel.Admin'}
                </li>
                <li>
                    <i class="icon-phone icon-li"></i>
                    {l s='%count% order(s)' sprintf=['%count%' => $references.orders] mod='Modules.HelloHarel.Admin'}
                </li>
            </ul>
        </div>
        
        <div>
            <button class="btn btn-danger" type="submit" name="unlink">
                <i class="icon-unlink"></i>
                {l s='Unlink instance' mod='Modules.HelloHarel.Admin'}
            </button>
        </div>
    {/if}
</form>
