<?php
$REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'] = rex_get('term','string','');
$REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselectmonthcount'] = rex_get('monthcount','int',12);
a587_stats_saveSettings($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']);

if($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'] == 'all')
  $term = '';
else
  $term = substr($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'],1);

$basedir = dirname(__FILE__);

require_once $basedir.'/phplot/phplot.php';

$stats = new RexSearchStats();

// fetch data
$bardata = array();
$cumulateddata = array();

$max = 1;
foreach($stats->getTimestats($term,$REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselectmonthcount']) as $month)
{
  $bardata[] = array(
    date('M', mktime(0,0,0,$month['m'],1,2010))."\n".$month['count'],
    $month['count']
  );
  
  if($month['count'] > $max)
    $max = $month['count'];
}

$title = $I18N->Msg(
  'a587_stats_searchterm_timestats_title',
  empty($term)
    ? $I18N->Msg('a587_stats_searchterm_timestats_title0_all')
    : $I18N->Msg(
      'a587_stats_searchterm_timestats_title0_single',
      $term
      ),
  intval($_GET['monthcount'])
);
if(rex_lang_is_utf8())
  $title = utf8_decode($title);

// draw bars
$plot = new PHPlot(700, 240);
$plot->SetImageBorderType('none');
$plot->SetTransparentColor('white');
$plot->SetMarginsPixels(NULL,NULL,26,NULL);

# Make sure Y axis starts at 0:
$plot->SetPlotAreaWorld(NULL, 0, NULL, NULL);

$len = strlen(''.$max);
$plot->SetYTickIncrement(max(1,ceil($max/pow(10,$len-1))*pow(10,$len-2)));

# Main plot title:
$plot->SetTitle($title);
$plot->SetFont('title', 3);

// draw bars
$plot->SetPlotType('bars');
$plot->SetDataType('text-data');
$plot->SetDataValues($bardata);
$plot->SetDataColors(array('#14568a','#2c8ce0','#dfe9e9'));
$plot->SetShading(ceil(48/$REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselectmonthcount']));
$plot->DrawGraph();
?>
