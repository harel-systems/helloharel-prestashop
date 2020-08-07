<?php

if(!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_5($app)
{
    $app->getManager('translation')->extractTranslations();
    
    return true;
}
