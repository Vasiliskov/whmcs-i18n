<?php
/**
 * i18n module
 *
 * A simple module to implement multilingual products description.
 *
 * @package    WHMCS
 * @author     Kirill Vasiliskov <mail@vasiliskov.name>
 */

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function i18n_config() {
    $configarray = array(
    "name" => "i18n",
    "description" => "This module collects fields&amp; values from templates and allows to translate them to different languages.",
    "version" => "1.0",
    "author" => "K. Vasyliskov",
    "language" => "english",
    "fields" => array(),
    );
    return $configarray;
}

function i18n_activate() {

    # Create Custom DB Tables
    $query = "CREATE TABLE `mod_i18n_data` (`id` VARCHAR(40) NOT NULL, `default` TEXT, `data` BLOB, `translated` INT(8), UNIQUE (`id`) )";
    $result = full_query($query);
    $query = "CREATE TABLE `mod_i18n_lang` (`lang` VARCHAR(20) NOT NULL , `enabled` Int, UNIQUE (`lang`) )";
    $result = full_query($query);

    # Return Result
    return array('status'=>'success','description'=>'Database table was successfully created.');
    return array('status'=>'error','description'=>'There was a problem while creating database table.');
    return array('status'=>'info','description'=>'Activation: OK.');

}

function i18n_deactivate() {

    # Remove Custom DB Tables
    $query = "DROP TABLE `mod_i18n_data`";
    $result = full_query($query);
    $query = "DROP TABLE `mod_i18n_lang`";
    $result = full_query($query);

    # Return Result
    return array('status'=>'success','description'=>'Database table was successfully dropped.');
    return array('status'=>'error','description'=>'There was a problem while dropping database table.');
    return array('status'=>'info','description'=>'Deactivation: OK.');

}

function i18n_output($vars) {

    $modulelink = $vars['modulelink'];
    $LANG = $vars['_lang'];
    $version = $vars['version'];
    if (array_key_exists('action', $_GET)) {
        $action = $_GET['action'];
    } else {
        $action = '';
    }
    $result = select_query('tblconfiguration','*', array('setting' => 'Language'));
    $res = mysql_fetch_assoc($result);
    $defaultlang = $res['value'];
    mysql_free_result($result);
    switch ($action) {
        case 'listlanguages':
            $result = select_query('mod_i18n_lang','*','1');
            $langsdb = array();
            while ($row = mysql_fetch_assoc($result)) {
                $langsdb[$row['lang']] = $row['enabled'];
            }
            $langdir = scandir("../lang/");
            foreach ($langdir as $key => $value) {
                if (strpos($value, '.php') > 0) {
                    $lang = str_replace('.php', '', $value);
                    if (array_key_exists($lang, $langsdb) === false) {
                        $newid = insert_query('mod_i18n_lang',array('lang' => $lang, 'enabled' => 0));
                        $langsdb[$lang] = 0;
                    }
                }
            }
            ksort($langsdb);
            $out = $LANG['listlanguages']."<br />\n<form action=\"$modulelink&action=setlanguages\" method=\"POST\">\n";
            foreach ($langsdb as $lang => $enabled) {
                if ($enabled == 1) {
                    $checked = ' checked="checked"';
                } else {
                    $checked = '';
                }
                if ($lang == $defaultlang) {
                    $disabled = ' disabled';
                    $checked = ' checked="checked"';
                } else {
                    $disabled = '';
                }
                $out .= "<input type=\"checkbox\" name=\"$lang\"$checked$disabled> ".ucfirst($lang)."<br /> \n";
            }
            $out .= '<input type="submit" value="'.$LANG['savebutton'].'"></form>';
            break;
        case 'setlanguages':
            $result = select_query('mod_i18n_lang','*','1');
            while ($row = mysql_fetch_assoc($result)) {
                if ($_POST[$row['lang']] == 'on') {
                    $enabled = 1;
                } else {
                    $enabled = 0;
                }
                if ($row['enabled'] != $enabled) {
                    update_query('mod_i18n_lang', array('enabled' => $enabled), array('lang' => $row['lang']));
                    $langsdb[] = ucfirst($row['lang']);
                }
            }
            $out = $LANG['setlanguages'].implode(', ', $langsdb).'. <a href="'.$modulelink.'">'.$LANG['goback'].'</a>';
            break;
        
        case 'listtranslations':
            if (array_key_exists('page', $_GET)) {
                $page = $_GET['page'];
            } else {
                $page = 0;
            }
            $start = $page*10;
            $query = "SELECT COUNT(*) FROM `mod_i18n_data` WHERE 1";
            $result = full_query($query);
            $row = mysql_fetch_assoc($result);
            $pages = ceil($row['COUNT(*)']/10);
            mysql_free_result($result);
            $query = "SELECT `id`,`default`,`translated` FROM `mod_i18n_data` WHERE 1 ORDER BY `translated` ASC LIMIT $start, 20";
            $result = full_query($query);
            $out = $LANG['listtranslations'].'<table>';
            while ($row = mysql_fetch_assoc($result)) {
                $cutstr = substr(str_replace('<', '&lt;', str_replace('>', '&gt;', $row['default'])), 0, 300);
                if ($row['translated'] == 1) {
                    $bgcolor = ' style="background-color: #d0ffd0;"';
                }
                $out .= "<tr><td$bgcolor><a href=$modulelink&action=showtranslation&id=".$row['id'].">$cutstr</a></td></tr>";
            }
            $pagination = '';
            for ($i = 0; $i < $pages; $i++) {
                if ($page != $i) {
                    $pagination .= '<a href="'.$modulelink.'&action=listtranslations&page='.$i.'">'.($i+1).'</a> ';
                } else {
                    $pagination .= ($i+1).' ';
                }
            }
            $out .= '<tr><td>'.$pagination.'</td></tr></table>';
            break;
        case 'showtranslation':
            if (array_key_exists('id', $_GET) === false) {
                $out = $LANG['badinput'];
                break;
            }
            $key = $_GET['id'];
            if (strlen($key) != 32) {
                $out = $LANG['badinput'];
                break;
            }
            $langres = select_query('mod_i18n_lang', '*', array('enabled' => 1));
            while ($row = mysql_fetch_assoc($langres)) {
                $langlist[] = $row['lang'];
            }
            $result = select_query('mod_i18n_data', '*', array('id' => $key));
            $row = mysql_fetch_assoc($result);
            $translations = unserialize($row['data']);
            $out = '<form action="'.$modulelink.'&action=savetranslation&id='.$key.'" method="POST"><table>';
            $out .= '<tr><td>'.ucfirst($defaultlang).'</td><td><textarea name="'.$defaultlang.'" disabled>'.$translations[$defaultlang].'</textarea></td></tr>';
            foreach ($langlist as $lang) {
                if ($lang == $defaultlang) {
                    continue;
                }
                $out .= '<tr><td>'.ucfirst($lang).'</td><td><textarea name="'.$lang.'"'.$disabled.'>'.$translations[$lang].'</textarea></td></tr>';
            }
            if ($row['translated'] != 1) {
                $checkbox = '<tr><td>'.$LANG['translated'].'</td><td><input type="checkbox" name="translated"></td></tr>';
            } else {
                $checkbox = '<tr><td>'.$LANG['translated'].'</td><td><input type="checkbox" name="translated" checked="checked"></td></tr>';
            }
            $out .= $checkbox.'<tr><td colspan="2"><input type="submit" value="'.$LANG['savebutton'].'"></td></tr></table></form>';
           break;
        case 'savetranslation':
            $key = $_GET['id'];
            $result = select_query('mod_i18n_data', '*', array('id' => $key));
            $row = mysql_fetch_assoc($result);
            $default = $row['default'];
            $translations = unserialize($row['data']);
            foreach ($translations as $lang => $data) {
                $translations[$lang] = $_POST[$lang];
            }
            $translations[$defaultlang] = $default;
            if ($_POST['translated'] == 'on') {
                $translated = 1;
            } else {
                $translated = 0;
            }
            update_query('mod_i18n_data', array('data' => serialize($translations), 'translated' => $translated), array('id' => $key));
            $out = $LANG['savetranslation'].'<br /><a href="'.$modulelink.'&action=listtranslations">'.$LANG['gotrans'].'</a><br /><a href="'.$modulelink.'">'.$LANG['goback'].'</a>';
            break;

        default:
            $out = $LANG['description']."<br />\n <a href=\"$modulelink&action=listlanguages\">".$LANG['link_langs']."</a><br />\n <a href=\"$modulelink&action=listtranslations\">".$LANG['link_translations']."</a><br /> ";
            break;
    }
    echo $out;

}

function i18n_sidebar($vars) {
    $LANG = $vars['_lang'];
    $res = mysql_query('SELECT * FROM `mod_i18n_data` WHERE 1;');
    $total = mysql_num_rows($res);
    $res = mysql_query('SELECT * FROM `mod_i18n_data` WHERE `translated` = 0;');
    $pending = mysql_num_rows($res);
    $sidebar = '<span class="header"><img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" /> '.$LANG['sidebar_header'].'</span>
<ul class="menu">
        <li><a href="#">'.$LANG['sidebar_total'].$total.'</a></li>
        <li><a href="#">'.$LANG['sidebar_untranslated'].$pending.'</a></li>
    </ul>';
    return $sidebar;

}