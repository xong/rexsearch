<?php

if (!function_exists('a685_output'))
{
    function a685_output($content)
    {

        $parent = 'rexsearch';
        $mypage = 'search_highlighter';
        $subject = $content['subject'];
        global $REX;
        $suchbegriffe = rex_request($mypage, 'string', '');
        $ausgabeAnfang = '';
        $ausgabeEnde = '';

        if (!empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stilEinbinden']))
        {
            $subject = str_replace('</head>', '<link rel="stylesheet" type="text/css" href="' . $REX["HTDOCS_PATH"] . 'files/addons/rexsearch/plugins/search_highlighter/stil.php?stil=' . $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil'] . '" media="screen" />'."\n".'</head>', $subject);
        }

        $ausgabeAnfang = '<' . $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['tag'];
//        $ausgabeAnfang .= ' class="' . 'class_search_685';
        $ausgabeAnfang .= (!empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['class'])) ? ' class="' . $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['class'].'"' : '';
//        $ausgabeAnfang .= '"';
        $ausgabeAnfang .= (!empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['inlineCSS'])) ? ' style="' . $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['inlineCSS'] . '"' : '';
        $ausgabeAnfang .= '>';

        $ausgabeEnde = '</' . $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['tag'] . '>';

        $tags = array($ausgabeAnfang, $ausgabeEnde);

        $subject = a685_getHighlightedText($subject, $suchbegriffe, $tags);

        return $subject;

    }
}



function a685_search_highlighter_saveSettings($_settings)
{
  global $REX;
  $parent = 'rexsearch';
  $mypage = 'search_highlighter';
  return rex_put_file_contents($REX['INCLUDE_PATH'].'/addons/'.$parent.'/plugins/'.$mypage.'/settings.conf', serialize($_settings));
}


function a685_search_highlighter_chmod()
{
    @chdir('../files/addons/rexsearch/plugins/search_highlighter/');
    @chmod('stil.php', 0775);

}

/*
function a685_getHighlightedText($subject, $begriffe, $tags)
{

    $matches = preg_split(a685_encodeRegex('~(\s)~'), $begriffe);

    //nur innerhalb des bodys suchen
    $vorkommenBody = stripos($subject, '<body');
    $body1 = substr($subject, 0, $vorkommenBody);
    $endeBody = stripos($subject, '</body');
    $body2 = substr($subject, $vorkommenBody, $endeBody);



    $keyword = "";
    foreach ($matches as $match)
    {
        $match = preg_replace('([^\w\d\+\-\_\.\,])', '', $match);

        if (strlen($match) <= 2) continue;

        preg_match_all('/' . $match . '/i', $body2, $keywords, PREG_SET_ORDER);
        foreach ($keywords as $keyword)
        {
            $body2 = preg_replace('/' . $keyword[0] . '/', $tags[0] . $keyword[0] . $tags[1], $body2);
        }
    }

    return $body1 . $body2;
}
*/
function a685_getHighlightedText($_subject, $_searchString, $_tags)
{
  preg_match_all('~(?:(\+*)"([^"]*)")|(?:(\+*)(\S+))~is', $_searchString, $matches, PREG_SET_ORDER);
 
  $searchterms = array();
  foreach($matches as $match)
  {
    if(count($match) == 5)
      // words without double quotes (foo)
      $word = $match[4];
    elseif(!empty($match[2]))
      // words with double quotes ("foo bar")
      $word = $match[2];
    else
      continue;
   
    $searchterms[] = preg_quote($word, '~');
  }
 
  return preg_replace('~(?<!\<)('.implode('|', $searchterms).')(?![^<]*\>)~ims', $_tags[0].'$1'.$_tags[1], $_subject);
}

function a685_encodeRegex($_regex)
{
    if(rex_lang_is_utf8())
        return utf8_encode($_regex.'u');
    else
        return $_regex;
}
?>
