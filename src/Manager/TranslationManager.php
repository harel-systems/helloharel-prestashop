<?php
/**
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
 */

namespace HelloHarel\Manager;

use Db;
use DbQuery;
use HelloHarel\Entity\HelloHarelReference;

class TranslationManager extends AbstractManager
{
    const DOMAIN = 'ModulesHelloharelAdmin';
    
    public function install()
    {
        if(!$this->extractTranslations()) {
            return 'Could not extract translations';
        }
        return true;
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
