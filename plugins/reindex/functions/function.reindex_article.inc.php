<?php
function a587_rexsearch_reindex_article($_params)
{
  global $REX, $I18N;
  
  $get = $_GET;
  
  if(!array_key_exists('article_id', $get)) $get['article_id'] = rex_request('article_id', 'int', $REX['ARTICLE_ID']);
  if(!array_key_exists('clang', $get)) $get['clang'] = rex_request('clang', 'int', $REX['CUR_CLANG']);
  if(!array_key_exists('ctype', $get) AND array_key_exists('ctype', $_REQUEST)) $get['ctype'] = rex_request('ctype');
  if(!array_key_exists('mode', $get) AND array_key_exists('mode', $_REQUEST)) $get['mode'] = rex_request('mode');
  
  $get['func'] = 'reindex';
  
  $_params['subject'][] = '<a href="index.php?'.http_build_query($get, null, '&amp;').'" class="rex-active"'.rex_tabindex().'>'.$I18N->msg('a587_reindex_article').'</a>';
  
  return $_params['subject'];
}
