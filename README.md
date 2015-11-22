# WHMCS Internationalization

This module was created to implement i18n functionality for any template variable (generally for product descriptions). This module takes any content from Smarty variables and allows you to translate it in dedicated section of your WHMCS control panel. You can use it to translate your Cart and Client area items.

The original idea to use Smarty template was taken from this thread: http://forum.whmcs.com/showthread.php?20219-MOD-Multilingual-Products-(in-fact-any-value-you-want)

## Installation

Copy `includes` and `modules` folders to your WHMCS installation root folder and enable as a regular WHMCS module.

## Usage

This module adds 2 smarty functions:
- `i18n` to translate single-line fields
- `i18ndescr` to translate multiline descriptions (like server configs for example)

First, you will have to find all Smarty variables which you would like to translate with abovementioned functions. For example, let's pretend you want to add multilingual support for `$groupname` and `$product` in this piece of code:
``` 
<p><h4>{$LANG.orderproduct}:</h4> {$groupname} - {$product} <span class="label {$rawstatus}">{$status}</span>{if $domain}<div><a href="http://{$domain}" target="_blank">{$domain}</a></div>{/if}</p>
```

You need to replace those vars to make it look like:
```
<p><h4>{$LANG.orderproduct}:</h4> {i18n default=$groupname lang=$language} - {i18n default=$product lang=$language} <span class="label {$rawstatus}">{$status}</span>{if $domain}<div><a href="http://{$domain}" target="_blank">{$domain}</a></div>{/if}</p>
```

So `{$groupname}` becomes `{i18n default=$groupname lang=$language}`. Some product descriptions come to variable in rendered way:
```
Cores: 1
RAM: 1GB
Disk: 20GB
```

You should use `i18ndescr` instead of `i18n` for this kind of fields (it will split the lines, extract key-value pairs and store them separately).

## Translation

When the module is enabled you will see `i18n` menu item in your WHMCS control panel. It will allow you to enable/disable languages that you would like to use and translate the collected field values to those languages one by one.

## Feedback

If you would like to say "Thank you" or report a bug please feel free to drop a message to `mail [at] vasiliskov.name` (or rise an issue on github).