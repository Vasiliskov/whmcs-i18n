<?php 
/* 
 * Smarty plugin 
 * ————————————————————- 
 * File:     function.i18n.php 
 * Type:     function 
 * Name:     Fields internationalization for WHMCS 
 * Purpose:  Returns only the language data surrounded by language tags 
 * ————————————————————- 
 */ 
function smarty_function_i18n($params, &$smarty){ 
    require_once($_SERVER['DOCUMENT_ROOT']."/init.php");
    $language = $params['lang'];
    $default = $params['default'];
    $key = md5($default);
    $result = select_query('tblconfiguration','*', array('setting' => 'Language'));
    $res = mysql_fetch_assoc($result);
    $defaultlang = $res['value'];
    mysql_free_result($result);
    $result = select_query('mod_i18n_lang', '*', '1');
    while ($row = mysql_fetch_assoc($result)) {
        $langs[$row['lang']] = $row['enabled'];
    }
    mysql_free_result($result);
    if ($langs[$language] == 0) {
        return $default;
    } else {
        $result = select_query('mod_i18n_data', '*', array('id' => $key));
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            $translations = unserialize($row['data']);
            return $translations[$language];
        } else {
            $translations[$defaultlang] = $default;
            foreach ($langs as $lang => $enabled) {
                if ($enabled == 1) {
                    $translations[$lang] = $default;
                }
            }
            $newid = insert_query('mod_i18n_data',array('id' => $key, 'default' => $default, 'data' => serialize($translations), 'translated' => 0));
            return $default;
        }
    }
} 
?>