<?php

$func = '';
$page = '';
$subpage = '';
$stil = '';

if (isset($_GET['stil']) && !empty($_GET['stil']) && is_string($_GET['stil']))
{
    $stil = strval($_GET['stil']);


    $_REQUEST = array();
    $_POST = array();
    $_GET = array();

    chdir('../../../../../redaxo/');

    $REX['GG'] = false;
    $REX['REDAXO'] = false;

    $REX['HTDOCS_PATH'] = '../';
    require 'include/master.inc.php';
    include_once $REX['INCLUDE_PATH'].'/addons.inc.php';


    require_once 'include/addons/rexsearch/plugins/search_highlighter/pages/stil.php';

}
?>


