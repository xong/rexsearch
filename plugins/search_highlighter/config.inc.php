<?php

$parent = 'rexsearch';
$mypage = 'search_highlighter';

$REX['EXTPERM'][] = 'rexsearch['.$mypage.']';

$REX['ADDON']['page'][$mypage] = $mypage;    
#$REX['ADDON']['name'][$mypage] = 'Search Highlighter';
$REX['ADDON']['perm'][$mypage] = 'search_highlighter[]';
$REX['ADDON']['author'][$mypage] = 'Timo Huber [timo.huber]';
$REX['ADDON']['version'][$mypage] = 'beta2';
$REX['PERM'][] = 'search_highlighter[]';
$REX['ADDON']['rxid'][$mypage] = '685';


require dirname(__FILE__).'/functions/functions.inc.php';

if(!file_exists($settingFile = dirname(__FILE__).'/settings.conf'))
{
  a685_search_highlighter_saveSettings(array(
    'tag' => 'span',
    'class' => '',
    'inlineCSS' => '' ,
    'stilEinbinden' => 1,
    'stil' => 'stil1',
    'stil1' => 'font-weight: bold; background-color: #E8E63B; color: #000000;',
    'stil2' => 'font-style: italic; font-size: 1.1em;',
    'stilEigen' => ''
  ));
}

$REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings'] = a587_config_unserialize(rex_get_file_contents($settingFile));


if (rex_request('search_highlighter', 'string', '') != "")
{
    rex_register_extension('OUTPUT_FILTER', 'a685_output');
}


if ($REX['REDAXO'])
{
    // include language-file
    $I18N->appendFile(dirname(__FILE__).'/lang/');

    // register subpage(s)
    $REX['ADDON']['rexsearch_plugins']['rexsearch'][$mypage]['subpages'][] = array('search_highlighter', $I18N->Msg('a685_site_title'));
}




?>
