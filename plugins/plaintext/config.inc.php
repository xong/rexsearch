<?php

$parent = 'rexsearch';
$mypage = 'plaintext';
$dir = dirname(__FILE__);

$REX['ADDON']['version'][$mypage] = '0.2';
$REX['ADDON']['author'][$mypage] = 'Robert Rupf';
$REX['ADDON']['supportpage'][$mypage] = 'forum.redaxo.de';
$REX['EXTRAPERM'][] = $parent.'['.$mypage.']';

require_once $dir.'/functions/functions.inc.php';

if(is_object($REX['USER']) AND ($REX['USER']->hasPerm($parent.'['.$mypage.']') OR $REX['USER']->isAdmin()))
{
  if ($REX['REDAXO']) {
    $I18N->appendFile(dirname(__FILE__).'/lang/');
    
    $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['subpages'][] = array('plaintext', $I18N->Msg('a587_plaintext_title'));
  }
  
  rex_register_extension('A587_PLAINTEXT', 'a587_doPlaintext');
  
  if(!file_exists($settingFile = dirname(__FILE__).'/settings.conf'))
  {
    a587_plaintext_saveSettings(array(
      'order' => 'selectors,regex,textile,striptags',
      'selectors' => "head,\nscript",
      'regex' => '',
      'textile' => true,
      'striptags' => true,
      'processparent' => false
    ));
  }
  
  $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings'] = a587_config_unserialize(rex_get_file_contents($settingFile));
}

// Including CSS-File for Backend
if($REX['REDAXO'] AND (rex_request('page', 'string') == $parent) AND (rex_request('subpage', 'string') == $mypage))
{
  function a587_plaintext_add_css($params)
  {
    $parent = 'rexsearch';
    $mypage = 'plaintext';
    
    if(function_exists('str_ireplace'))
      return str_ireplace('</head>',"\t".'<script type="text/javascript" src="../files/addons/'.$parent.'/plugins/'.$mypage.'/jquery.ui.custom.js"></script>'."\n".'</head>',$params['subject']);
    return str_replace('</head>',"\t".'<script type="text/javascript" src="../files/addons/'.$parent.'/plugins/'.$mypage.'/jquery.ui.custom.js"></script>'."\n".'</head>',$params['subject']);
  }
  
  rex_register_extension('OUTPUT_FILTER', 'a587_plaintext_add_css');
}
