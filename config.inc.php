<?php
$mypage = 'rexsearch';

// general settings for rexsearch
$REX['ADDON']['rxid'][$mypage] = '587';
$REX['ADDON']['page'][$mypage] = $mypage;    
$REX['ADDON']['name'][$mypage] = 'RexSearch';
$REX['ADDON']['perm'][$mypage] = $mypage.'[]';
$REX['PERM'][] = $mypage.'[]';
$REX['EXTRAPERM'][] = $mypage.'[settings]';

$REX['ADDON']['version'][$mypage] = '0.7.8';
$REX['ADDON']['author'][$mypage] = 'Robert Rupf';
$REX['ADDON']['dir'][$mypage] = dirname(__FILE__);

// Adding language file
if ($REX['REDAXO']) {
  $I18N->appendFile($REX['ADDON']['dir'][$mypage].'/lang/');
  
  // subpages
  if(is_object($REX['USER']) AND ($REX['USER']->hasPerm($mypage.'[settings]') OR $REX['USER']->isAdmin()))
  {
    $REX['ADDON'][$mypage]['SUBPAGES'] = array (
      array('', $I18N->Msg('a587_title_generate')),
      array('settings', $I18N->Msg('a587_title_settings')),
      array('help', $I18N->Msg('a587_title_help'))
    );
  }
  else
  {
    $REX['ADDON'][$mypage]['SUBPAGES'] = array (
      array('', $I18N->Msg('a587_title_generate'))
    );
  }
  
  // register subpages of plugins
  rex_register_extension(
    'ADDONS_INCLUDED',
    create_function(
      '',
      '
        global $REX;
        if(!empty($REX[\'ADDON\'][\'rexsearch_plugins\'][\''.$mypage.'\']))
        {
          foreach($REX[\'ADDON\'][\'rexsearch_plugins\'][\''.$mypage.'\'] as $plugin => $pluginsettings)
            if(!empty($pluginsettings[\'subpages\']))
              $REX[\'ADDON\'][\''.$mypage.'\'][\'SUBPAGES\'] = array_merge($REX[\'ADDON\'][\''.$mypage.'\'][\'SUBPAGES\'], $pluginsettings[\'subpages\']);
        }
      '
    )
  );
}

require $REX['ADDON']['dir'][$mypage].'/classes/class.rexsearch.inc.php';
require $REX['ADDON']['dir'][$mypage].'/functions/functions.inc.php';
require $REX['ADDON']['dir'][$mypage].'/functions/functions.mb.inc.php';

if(!file_exists($settingFile = dirname(__FILE__).'/settings.conf'))
{
  a587_saveSettings(array(
    'logicalmode' => 'and',
    'textmode' => 'plain',
    'searchmode' => 'like',
    'similarwordsmode' => 7,
    'indexmode' => 1,
    'automaticindex' => 1,
    'surroundtags' => array('<strong>','</strong>'),
    'limit' => array(0,20),
    'maxteaserchars' => '200',
    'maxhighlightchars' => '50',
    'highlight' => 'surroundtextsingle',
    'fileextensions' => array('pdf'),
    'indexmediapool' => '1',
    'dirdepth' => 3
  ));
}

$REX['ADDON']['settings'][$mypage] = a587_config_unserialize(rex_get_file_contents($settingFile));

// automatic indexing
if($REX['REDAXO'] AND $REX['ADDON']['settings'][$mypage]['automaticindex'] == '1')
{
  $extensionPoints = array(
    'ART_DELETED',
    'ART_META_UPDATED',
    'ART_STATUS',
    'ART_ADDED',
    'ART_UPDATED',
    'CAT_DELETED',
    'CAT_STATUS',
    'CAT_ADDED',
    'CAT_UPDATED',
    'MEDIA_ADDED',
    'MEDIA_UPDATED',
    'SLICE_UPDATED'
  );
  
  a587_register_extensionpoints($extensionPoints);
}

// Including CSS-File for Backend
if ($REX['REDAXO'] AND rex_request('page', 'string') == $mypage)
{
  function a587_add_css($params)
  {
    $mypage = 'rexsearch';
    if(function_exists('str_ireplace'))
      return str_ireplace('</head>',"\t".'<link rel="stylesheet" type="text/css" href="../files/addons/'.$mypage.'/'.$mypage.'.css" />'."\n".'</head>',$params['subject']);
    return str_replace('</head>',"\t".'<link rel="stylesheet" type="text/css" href="../files/addons/'.$mypage.'/'.$mypage.'.css" />'."\n".'</head>',$params['subject']);
  }
  
  rex_register_extension('OUTPUT_FILTER', 'a587_add_css');
}

