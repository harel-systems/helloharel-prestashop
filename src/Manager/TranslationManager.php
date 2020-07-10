<?php

namespace HelloHarel\Manager;

use Configuration;
use Db;
use DbQuery;
use HelloHarel\Entity\HelloHarelReference;
use Translation;
use WebserviceKey;

class TranslationManager extends AbstractManager
{
    const DOMAIN = 'ModulesHelloharelAdmin';
    
    public function install()
    {
        if(!$this->extractTranslations()) {
            $this->app->_errors[] = 'Could not extract translations';
            return false;
        }
        return true;
    }
    
    public function uninstall()
    {
        
    }
    
    public function extractTranslations()
    {
        $content = [];
        
        $directory = __DIR__ . '/../../translations';
        $files = scandir($directory);
        
        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'translation WHERE domain="'. pSQL(self::DOMAIN) . '"');
        
        $query = new DbQuery();
        $query->select('COALESCE(MAX(id_translation), 1)');
        $query->from('translation');
        
        foreach($files as $i => $file) {
            if(is_dir($file)) {
                continue;
            }
            $lang = substr($file, 0, strpos($file, '.'));
            
            $query = new DbQuery();
            $query->select('id_lang');
            $query->from('lang');
            $query->where('language_code = "' . pSQL($lang) . '"');
            $langId = \Db::getInstance()->getValue($query);
            
            if($langId) {
                $content[] = '<tr class="success"><th colSpan=3>Extracting language <code>' . $lang . '</code> with ID ' . $langId . '</th></tr>';
                if(false === $xmldata = simplexml_load_file($directory . '/' . $file)) {
                    return false;
                }
                foreach($xmldata->file->body->children() as $unit) {
                    $result = Db::getInstance()->insert('translation', array(
                        'id_lang' => (int)$langId,
                        'key' => addslashes((string)$unit->source),
                        'translation' => addslashes((string)$unit->target),
                        'domain' => self::DOMAIN,
                    ));
                    
                    $content[] = '<tr class="' . ($result ? 'success' : 'danger') . '"><td>' . $lang . '</td><td>' . (string)$unit->source . '</td><td>' . (string)$unit->target . '</td></tr>';
                }
            } else {
                $content[] = '<tr class="danger"><th colSpan=3>Unregistered language <code>' . $lang . '</code></th></tr>';
            }
        }
        return implode("\n", $content);
    }
}
