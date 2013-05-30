<?php
if (rex_lang_is_utf8())
{
    $charset = "utf-8";
}
else
{
    $charset = "iso-8859-1";
}

header("Content-Type: text/css; charset=" . $charset);


$parent = 'rexsearch';
$mypage = 'search_highlighter';

//css
$basedir = dirname(__FILE__);

echo '.class_search_685 {';
switch ($stil)
{

    case 'stil2':
        echo $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil2'];
        break;

    case 'stilEigen':
        echo $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stilEigen'];
        break;

    case 'stil1':
    default:
        echo $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil1'];
        break;
}

echo '}';

?>

