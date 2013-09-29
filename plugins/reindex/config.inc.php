<?php

$parent = 'rexsearch';
$mypage = 'reindex';

$REX['ADDON']['version'][$mypage] = '0.1';
$REX['ADDON']['author'][$mypage] = 'Robert Rupf';
$REX['ADDON']['supportpage'][$mypage] = 'forum.redaxo.de';
$REX['EXTRAPERM'][] = $parent.'['.$mypage.']';

include_once dirname(__FILE__).'/functions/function.reindex.inc.php';
include_once dirname(__FILE__).'/functions/function.reindex_article.inc.php';

if($REX['REDAXO'] AND is_object($REX['USER']) AND ($REX['USER']->hasPerm($parent.'['.$mypage.']') OR $REX['USER']->isAdmin()))
{
  $I18N->appendFile(dirname(__FILE__).'/lang/');

  if(rex_get('func') == 'reindex' AND rex_get('article_id', 'int') AND 0 <= rex_get('clang', 'int', -1))
  {
    rex_register_extension('ADDONS_INCLUDED', function()
    {
      global $REX;
      
      $rexsearch = new RexSearch();
      $rexsearch->indexArticle($REX['ARTICLE_ID'], $REX['CUR_CLANG']);
      
      rex_register_extension('PAGE_CONTENT_OUTPUT', function($_params)
      {
        global $I18N;
        
        echo rex_info($I18N->msg('a587_reindex_done'));
      });
    });
  }
}

if(OOAddon::isActivated('rexsearch') OR class_exists('rexsearch'))
{
  rex_register_extension('REX_FORM_SAVED', 'a587_rexsearch_reindex');
  rex_register_extension('REX_XFORM_SAVED', 'a587_rexsearch_reindex');
  rex_register_extension('REX_FORM_DELETED', 'a587_rexsearch_reindex');
  
  rex_register_extension('PAGE_CONTENT_MENU', 'a587_rexsearch_reindex_article');
}
