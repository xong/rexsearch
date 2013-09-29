<?php
function a587_plaintext_saveSettings($_settings)
{
  global $REX;
  $parent = 'rexsearch';
  $mypage = 'plaintext';
  return rex_put_file_contents($REX['INCLUDE_PATH'].'/addons/'.$parent.'/plugins/'.$mypage.'/settings.conf', serialize($_settings));
}


function a587_doPlaintext($_params)
{
  global $REX;
  $_params['subject'] = a587_getPlaintext($_params['subject'],preg_replace('~\s+~ism',' ',$REX['ADDON']['rexsearch_plugins']['rexsearch']['plaintext']['settings']['selectors']));
  return array('text'=>$_params['subject'], 'process' => !empty($REX['ADDON']['rexsearch_plugins']['rexsearch']['plaintext']['settings']['processparent']));
}


require_once $dir.'/classes/class.simple_html_dom.inc.php';
function a587_getPlaintext($_text,$_remove)
{
  global $REX;
  
  foreach(explode(',', $REX['ADDON']['rexsearch_plugins']['rexsearch']['plaintext']['settings']['order']) as $elem)
  {
    switch($elem)
    {
      case 'selectors':
        // remove elements selected by css-selectors
        $html = new simple_html_dom();
        $html->load($_text);
        $html->remove($_remove);
        $html->load($html->outertext);
        $_text = $html->plaintext;
      break;
      
      case 'regex':
        // regex
        if(!empty($REX['ADDON']['rexsearch_plugins']['rexsearch']['plaintext']['settings']['regex']))
        {
          $regex = array();
          $replacement = array();
          $odd = true;
          foreach(explode("\n", $REX['ADDON']['rexsearch_plugins']['rexsearch']['plaintext']['settings']['regex']) as $line)
          {
            if($line != '')
            {
              if($odd)
                $regex[] = trim($line);
              else
                $replacement[] = $line;
              
              $odd = !$odd;
            }
          }
          
          $_text = preg_replace($regex, $replacement, $_text);
        }
      break;
      
      case 'textile':
        // strip HTML-tags
        if(!empty($REX['ADDON']['rexsearch_plugins']['rexsearch']['plaintext']['settings']['textile']) AND function_exists('rex_a79_textile'))
          $_text = rex_a79_textile($_text);
      break;
      
      case 'striptags':
        // strip HTML-tags
        if(!empty($REX['ADDON']['rexsearch_plugins']['rexsearch']['plaintext']['settings']['striptags']))
          $_text = strip_tags($_text);
      break;
    }
  }
  
  return $_text;
}
