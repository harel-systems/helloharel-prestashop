{**
 * Hello Harel PrestaShop integration module
 * Copyright (C) 2020  Harel Systems SAS
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *}

<style>
#content .helloharel * {
    font-size: 14px;
}
#content .helloharel .alert .btn.pull-right {
    margin-top: -6px;
}
#content .helloharel .icon-ul li {
    margin-bottom: 5px;
}
#content .helloharel .form-control {
    height: 34px;
}

#content .helloharel .control-label {
    font-weight: 900;
}

#content .helloharel .btn {
    text-transform: none;
}
</style>

<div class="helloharel">
    {if $action.type eq 'unlink'}
        <div class="alert alert-success">
            {l s='Hello Harel and PrestaShop have been successfully unlinked!' d='Modules.Helloharel.Admin'}
        </div>
    {else}
        <div class="alert alert-info">
            {l s='You are only one last step away from integrating Hello Harel with PrestaShop!' d='Modules.Helloharel.Admin'}
        </div>
    {/if}

    <div class="well">
        <p>{l s='To activate the integration:' d='Modules.Helloharel.Admin'}</p>
        <ol>
            <li>
                {l s='Go to your Hello Harel instance and navigate to:' d='Modules.Helloharel.Admin'}
                <ol class="breadcrumb">
                    <li>{l s='Administration' d='Modules.Helloharel.Admin'}</li>
                    <li>{l s='External applications' d='Modules.Helloharel.Admin'}</li>
                </ol>
            </li>
            
            <li>{l s='Open the PrestaShop app from the list' d='Modules.Helloharel.Admin'}</li>
            
            <li>{l s='Click the "Activate app" button' d='Modules.Helloharel.Admin'}</li>
            
            <li>{l s='Go to the "App settings" tab from the PrestaShop modal' d='Modules.Helloharel.Admin'}</li>
            
            <li>
                {l s='And copy the following information in the form:' d='Modules.Helloharel.Admin'}
                <div class="form-horizontal">
                    <div class="form-group">
                        <label class="control-label col-md-3">{l s='PrestaShop URL' d='Modules.Helloharel.Admin'}</label>
                        <div class="controls col-md-6">
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default copy-to-clipboard" title="{l s='Copy to clipboard' d='Modules.Helloharel.Admin'}">
                                        <i class="icon-copy"></i>
                                    </button>
                                </span>
                                <input type="text" readonly class="form-control" value="{$prestashopUrl}" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-3">{l s='API key' d='Modules.Helloharel.Admin'}</label>
                        <div class="controls col-md-6">
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default copy-to-clipboard" title="{l s='Copy to clipboard' d='Modules.Helloharel.Admin'}">
                                        <i class="icon-copy"></i>
                                    </button>
                                </span>
                                <input type="text" readonly class="form-control" value="{$apiKey}" />
                            </div>
                        </div>
                    </div>
                </div>
            </li>
            
            <li>{l s='Click the "Save options" button.' d='Modules.Helloharel.Admin'}</li>
        </ol>
        
        <div class="text-right">
            <a class="btn btn-success" href=""><i class="icon-check"></i> {l s='It\'s done!' d='Modules.Helloharel.Admin'}</a>
            <a class="btn btn-primary pull-left" href="https://www.helloharel.com/inscription" target="_blank"><i class="icon-external-link"></i> {l s='I don\'t have an instance yet!' d='Modules.Helloharel.Admin'}</a>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $(".copy-to-clipboard").tooltip().click(function() {
        let value = $(this).closest(".input-group").find("input").val();
        const el = document.createElement("textarea");
        el.value = value;
        document.body.appendChild(el);
        el.select();
        document.execCommand("copy");
        document.body.removeChild(el);
    });
});
</script>
