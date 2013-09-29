<?php

$parent = 'rexsearch';
$mypage = 'stats';

$REX['ADDON']['version'][$mypage] = '0.2.1';
$REX['ADDON']['author'][$mypage] = 'Robert Rupf';
$REX['ADDON']['supportpage'][$mypage] = 'forum.redaxo.de';
$REX['EXTRAPERM'][] = 'rexsearch[stats]';

require dirname(__FILE__).'/classes/class.stats.inc.php';
require dirname(__FILE__).'/functions/functions.inc.php';
rex_register_extension('A587_SEARCH_EXECUTED', 'a587_stats_storekeywords');

if(is_object($REX['USER']) AND ($REX['USER']->hasPerm('rexsearch[stats]') OR $REX['USER']->isAdmin()))
{
  if ($REX['REDAXO']) {
    $I18N->appendFile(dirname(__FILE__).'/lang/');
    
    $REX['ADDON']['rexsearch_plugins']['rexsearch'][$mypage]['subpages'][] = array('stats', $I18N->Msg('a587_stats_title'));
  }
  
  rex_register_extension('A587_PAGE_MAINTENANCE', 'a587_stats_addtruncate');
  
  // Including CSS-File for Backend
  if ($REX['REDAXO'] AND rex_request('subpage', 'string') == $mypage)
  {
    function a587_stats_add_css($params)
    {
      $parent = 'rexsearch';
      $mypage = 'stats';
      if(function_exists('str_ireplace'))
        return str_ireplace('</head>',"\t".'<link rel="stylesheet" type="text/css" href="../files/addons/'.$parent.'/plugins/'.$mypage.'/'.$mypage.'.css" />'."\n".'</head>',$params['subject']);
      return str_replace('</head>',"\t".'<link rel="stylesheet" type="text/css" href="../files/addons/'.$parent.'/plugins/'.$mypage.'/'.$mypage.'.css" />'."\n".'</head>',$params['subject']);
    }
    
    rex_register_extension('OUTPUT_FILTER', 'a587_stats_add_css');
  }
  
  if(!file_exists($settingFile = dirname(__FILE__).'/settings.conf'))
  {
    a587_stats_saveSettings(array(
      'maxtopSearchitems' => 10,
      'searchtermselect' => '',
      'searchtermselectmonthcount' => 12
    ));
  }
  
  $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings'] = a587_config_unserialize(rex_get_file_contents($settingFile));
}
