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
                {l s='Are you sure you want to unlink PrestaShop from your Hello Harel instance?' d='Modules.Helloharel.Admin'}<br />
                {l s='Your mapping (products, customers, orders) will be kept if you want to link them again later.' d='Modules.Helloharel.Admin'}
                <div class="text-right">
                    <a class="btn btn-default" href="">
                        {l s='Cancel' d='Modules.Helloharel.Admin'}
                    </a>
                    
                    <button class="btn btn-danger" type="submit" name="unlink_confirm">
                        <i class="icon-check"></i>
                        {l s='Confirm' d='Modules.Helloharel.Admin'}
                    </button>
                </div>
            </div>
        {/if}
    {elseif $action.type eq 'translations'}
        <table class="table">
            <thead>
                <tr>
                    <th>Language</th>
                    <th>Source</th>
                    <th>Target</th>
                </tr>
            </thead>
            <tbody>
                {$action.content}
            </tbody>
        </table>
        
        <div class="text-right">
            <a class="btn btn-success" href=""><i class="icon-check"></i> OK</a>
        </div>
    {else}
        <div class="alert alert-success">
            <a class="btn btn-default pull-right" href="{$instanceUrl}">
                <i class="icon-link"></i>
                {l s='Go to your instance' d='Modules.Helloharel.Admin'}
            </a>
            <strong>{l s='Congratulations!' d='Modules.Helloharel.Admin'}</strong>
            {l s='This PrestaShop instance is now controlled by %instanceUrl%.' sprintf=['%instanceUrl%' => $instanceUrl] d='Modules.Helloharel.Admin'}
        </div>

        <div class="well">
            <p>{l s='Your Hello Harel instance now controls PrestaShop. This is an overview of the mapping between PrestaShop and Hello Harel:' d='Modules.Helloharel.Admin'}</p>
            <ul class="icon-ul">
                <li>
                    <i class="icon-barcode icon-li"></i>
                    {l s='%count% product(s)' sprintf=['%count%' => $references.products] d='Modules.Helloharel.Admin'}
                </li>
                <li>
                    <i class="icon-user icon-li"></i>
                    {l s='%count% customer(s)' sprintf=['%count%' => $references.customers] d='Modules.Helloharel.Admin'}
                </li>
                <li>
                    <i class="icon-phone icon-li"></i>
                    {l s='%count% order(s)' sprintf=['%count%' => $references.orders] d='Modules.Helloharel.Admin'}
                </li>
            </ul>
        </div>
        
        <div>
            <button class="btn btn-danger" type="submit" name="unlink">
                <i class="icon-unlink"></i>
                {l s='Unlink instance' d='Modules.Helloharel.Admin'}
            </button>
        </div>
    {/if}
</form>