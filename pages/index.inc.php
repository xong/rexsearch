<?php
$mypage = 'rexsearch';

$basedir = dirname(__FILE__);
$page = rex_request('page', 'string');
$subpage = rex_request('subpage', 'string');
$ajax = rex_request('ajax', 'string');
$func = rex_request('func', 'string');

if(!empty($ajax))
{
  ob_end_clean();
  require $basedir .'/ajax.inc.php';
  exit;
}

if($subpage == 'generate')
{
  $_GET['subpage'] = '';
  $_REQUEST['subpage'] = '';
}

$pluginssubpageloaded = false;
if(!empty($REX['ADDON']['rexsearch_plugins'][$mypage]))
{
  foreach($REX['ADDON']['rexsearch_plugins'][$mypage] as $plugin => $pluginsettings)
  {
    if(!empty($pluginsettings['subpages']))
    {   
      foreach($pluginsettings['subpages'] as $pluginsubpage)
      {
        if($pluginsubpage[0] == $subpage)
        {
          require $REX['ADDON']['dir'][$mypage] .'/plugins/'.$plugin.'/pages/index.inc.php';
          $pluginssubpageloaded = true;
          break;
        }
      }
    }
  }
}

if(!$pluginssubpageloaded)
{
  include $REX['INCLUDE_PATH'].'/layout/top.php';
#error_reporting(E_ALL | E_STRICT);

  rex_title($REX['ADDON']['name'][$mypage], $REX['ADDON'][$mypage]['SUBPAGES']);
  
  if(is_object($REX['USER']) AND ($REX['USER']->hasPerm($mypage.'[settings]') OR $REX['USER']->isAdmin()))
  {
    switch($subpage) {
      case "settings":
        require $basedir .'/settings.inc.php';
        break;
      
      case "generate":
        require $basedir .'/generate.inc.php';
        break;
      
      case "help":
        require $basedir .'/help.inc.php';
        break;
      
      default:
          require $basedir .'/generate.inc.php';
    }
  }
  else
  {
    require $basedir .'/generate.inc.php';
  }

#error_reporting(0);

  include $REX['INCLUDE_PATH'].'/layout/bottom.php';
}
