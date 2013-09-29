<?php
if(isset($_POST['sendit']))
{
  a587_stats_saveSettings($_POST['a587_rexsearch_stats']);
  
  header('Location: http://'.$_SERVER['HTTP_HOST'].substr($_SERVER["PHP_SELF"],0,-9).'index.php?page=rexsearch&subpage=stats');
}

$parent = 'rexsearch';
$mypage = 'stats';

$basedir = dirname(__FILE__);
$page = rex_request('page', 'string');
$subpage = rex_request('subpage', 'string');
$func = rex_request('func', 'string');

if(!empty($func))
{
  switch($func)
  {
    case 'image':
      require $basedir.'/../images/'.rex_request('image', 'string').'.inc.php';
      exit;
    break;
    
    case 'topsearchterms':
      require 'ajax.inc.php';
      exit;
    break;
  }
  
}

include $REX['INCLUDE_PATH'].'/layout/top.php';

rex_title("rexsearch", $REX['ADDON'][$page]['SUBPAGES']);
?>
<div class="rex-addon-output" id="a587-form">
<h2 class="rex-hl2" style="position: relative;"><?php echo $I18N->Msg('a587_stats_title'); ?></h2>

<div class="rex-form">
<form method="post" action="index.php?page=rexsearch&amp;subpage=stats" id="a587_stats_form">
<?php

$stats = new rexsearchStats();
#$stats->createTestData();
#error_reporting(E_ALL);
// general stats
$sql = new rex_sql();
$generalstats = $sql->getArray('SELECT
  ((SELECT COUNT(DISTINCT ftable,fid) as count FROM `'.$REX['TABLE_PREFIX'].'587_searchindex` WHERE ftable IS NOT NULL) + (SELECT COUNT(DISTINCT fid) as count FROM `'.$REX['TABLE_PREFIX'].'587_searchindex` WHERE ftable IS NULL)) AS 010_uniquedatasetcount,
  (SELECT AVG(resultcount) FROM `'.$REX['TABLE_PREFIX'].'587_stats_searchterms`) AS 020_averageresultcount,
  (SELECT COUNT(*) FROM `'.$REX['TABLE_PREFIX'].'587_stats_searchterms` WHERE resultcount > 0) AS 040_successfullsearchescount,
  (SELECT COUNT(*) FROM `'.$REX['TABLE_PREFIX'].'587_stats_searchterms` WHERE resultcount = 0) AS 050_failedsearchescount,
  (SELECT COUNT(DISTINCT term) FROM `'.$REX['TABLE_PREFIX'].'587_stats_searchterms`) AS 060_uniquesearchterms'
);
$generalstats = $generalstats[0];
$generalstats['030_searchescount'] = $generalstats['040_successfullsearchescount'] + $generalstats['050_failedsearchescount'];

$generalstats['100_datalength'] = 0;
$generalstats['110_indexlength'] = 0;
foreach($sql->getArray("SHOW TABLE STATUS LIKE '".$REX['TABLE_PREFIX']."587_%'") as $table)
{
  $generalstats['100_datalength'] += $table['Data_length'];
  $generalstats['110_indexlength'] += $table['Index_length'];
  
  if($table['Name'] == $REX['TABLE_PREFIX'].'587_searchindex')
  {
    $generalstats['080_searchindexdatalength'] = a587_stats_bytesize($table['Data_length']);
    $generalstats['090_searchindexindexlength'] = a587_stats_bytesize($table['Index_length']);
    $generalstats['005_datasetcount'] = $table['Rows'];
  }
  
  if($table['Name'] == $REX['TABLE_PREFIX'].'587_keywords')
    $generalstats['070_keywordcount'] = $table['Rows'];
  
  if($table['Name'] == $REX['TABLE_PREFIX'].'587_searchcache')
    $generalstats['075_cachedsearchcount'] = $table['Rows'];
}

$generalstats['020_averageresultcount'] = number_format($generalstats['020_averageresultcount'], 2, ',', '');
$generalstats['100_datalength'] = a587_stats_bytesize($generalstats['100_datalength']);
$generalstats['110_indexlength'] = a587_stats_bytesize($generalstats['110_indexlength']);

ksort($generalstats);

$odd = true;
$table_general = '<dl id="generalstats-list">';
foreach($generalstats as $key => $value)
{
  $table_general .= '<dt class="'.($odd ? 'odd' : 'even').'">'.$I18N->Msg('a587_stats_generalstats_'.$key).'</dt><dd class="'.($odd ? 'odd' : 'even').'">'.$value.'</dd>';
  $odd = !$odd;
}
$table_general .= '</dl>';

echo a587_getStatSection('generalstats', $I18N->Msg('a587_stats_generalstats_title'), $table_general);

// top search terms
$topsearchtermlist = '';
$topsearchtermselect = '<option value="all">'.htmlspecialchars($I18N->Msg('a587_stats_searchterm_timestats_title0_all')).'</option>';
$topsearchterms = $stats->getTopSearchterms($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['maxtopSearchitems']);
foreach($topsearchterms as $term)
{
  $topsearchtermlist .= '<li class="'.($term['success']=='1'?'rexsearch-stats-success':'rexsearch-stats-fail').'"><strong>'.htmlspecialchars($term['term']).'</strong> <em>('.$term['count'].')</em></li>';
  $topsearchtermselect .= '<option value="_'.htmlspecialchars($term['term']).'"'.(($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect']=='_'.$term['term'])?' selected="selected"':'').'>'.$I18N->Msg('a587_stats_searchterm_timestats_title0_single',htmlspecialchars($term['term'])).'</option>';
}

if(!empty($topsearchterms))
  $topsearchtermlist = '<ol>'.$topsearchtermlist.'</ol>';
else
  $topsearchtermlist = $I18N->Msg('a587_stats_topsearchterms_none');

$selectMaxTopSearchitems = '<select name="a587_rexsearch_stats[maxtopSearchitems]" id="a587_rexsearch_stats_maxTopSearchitems">';
foreach(array(10,20,50,100,200,500,1000) as $option)
  $selectMaxTopSearchitems .= '<option value="'.$option.'"'.((intval($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['maxtopSearchitems'])==$option)?' selected="selected"':'').'>'.$option.'</option>';
$selectMaxTopSearchitems .= '</select>';

echo a587_getStatSection('topsearchterms', $I18N->Msg('a587_stats_topsearchterms_title', $selectMaxTopSearchitems, $stats->getSearchtermCount()), $topsearchtermlist);

// hit-miss-rate
echo a587_getStatSection('general', $I18N->Msg('a587_stats_general_title'), '
  <img src="index.php?page=rexsearch&amp;subpage=stats&amp;func=image&amp;image=rate_success_failure" alt="'.htmlspecialchars($I18N->Msg('a587_stats_rate_success_failure',' ')).'" title="'.htmlspecialchars($I18N->Msg('a587_stats_rate_success_failure',' ',$stats->getMissCount()+$stats->getSuccessCount())).'" /><img src="index.php?page=rexsearch&amp;subpage=stats&amp;func=image&amp;image=general_timestats" alt="'.htmlspecialchars($I18N->Msg('a587_stats_general_timestats',6)).'" title="'.htmlspecialchars($I18N->Msg('a587_stats_general_timestats',6)).'" />
');

// stats for searchterms over time
if(!empty($topsearchtermselect))
  $topsearchtermselect = '<select name="a587_rexsearch_stats[searchtermselect]" id="a587_rexsearch_stats_searchtermselect">'.$topsearchtermselect.'</select>';
else
  $topsearchtermselect = $I18N->Msg('a587_stats_searchterm_timestats_title0');

$searchtermselectmonthcount = '<select name="a587_rexsearch_stats[searchtermselectmonthcount]" id="a587_rexsearch_stats_searchtermselectmonthcount">';
foreach(array(6,9,12,15,18,21,24) as $option)
  $searchtermselectmonthcount .= '<option value="'.$option.'"'.((intval($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselectmonthcount'])==$option)?' selected="selected"':'').'>'.$option.'</option>';
$searchtermselectmonthcount .= '</select>';

echo a587_getStatSection('searchterm_timestats', $I18N->Msg('a587_stats_searchterm_timestats_title', $topsearchtermselect, $searchtermselectmonthcount), '
  <img src="index.php?page=rexsearch&amp;subpage=stats&amp;func=image&amp;image=searchterm_timestats&amp;term='.htmlspecialchars(urlencode($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'] == 'all'?'all':$REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'])).'&amp;monthcount='.intval($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselectmonthcount']).'" alt="'.htmlspecialchars($I18N->Msg('a587_stats_searchterm_timestats_title', $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'] == 'all'?$I18N->Msg('a587_stats_searchterm_timestats_title0_all'):$I18N->Msg('a587_stats_searchterm_timestats_title0_single',substr($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'],1)), intval($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselectmonthcount']))).'" title="'.htmlspecialchars($I18N->Msg('a587_stats_searchterm_timestats_title', $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'] == 'all'?$I18N->Msg('a587_stats_searchterm_timestats_title0_all'):$I18N->Msg('a587_stats_searchterm_timestats_title0_single',substr($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselect'],1)), intval($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselectmonthcount']))).'" />
');
?>
  <div class="rex-form-row">
    <p class="rex-form-col-a rex-form-submit">
      <input type="submit" value="<?php echo $I18N->Msg('a587_settings_submitbutton'); ?>" name="sendit" class="rex-form-submit"/>
    </p>
  </div>
<script type="text/javascript">
// <![CDATA[

var mainWidth = jQuery('#a587-form').attr('offsetWidth');
var getonly = 0;

// display links for showing and hiding all sections
jQuery('#a587-form h2')
.css('position','relative')
.append(
  jQuery('<div>')
  .css('position','absolute')
  .css('top','0')
  .css('right','0')
  .css('padding','5px 1em')
  .css('font-size','0.75em')
  .css('font-weight','900')
  .append(
  jQuery('<a><?php echo $I18N->Msg('a587_settings_show_all'); ?><'+'/a>')
    .css('cursor','pointer')
    .css('padding','0 1em')
    .click(function()
    {
      jQuery.each(jQuery('#a587-form fieldset'), function(i, elem)
      {
        jQuery('.rex-form-wrapper', elem).show();
      })
    })
  )
  .append(
  jQuery('<a><?php echo $I18N->Msg('a587_settings_show_none'); ?><'+'/a>')
    .css('cursor','pointer')
    .click(function()
    {
      jQuery.each(jQuery('#a587-form fieldset'), function(i, elem)
      {
        jQuery('.rex-form-wrapper', elem).hide();
      })
    })
  )
);


function setLoading(show)
{
  if(show)
  {
    jQuery('#topsearchterms legend')
    .append(
      jQuery('<span class="a587_loading" style="background:url(../files/addons/rexsearch/loading_lightblue.gif) no-repeat scroll center center;padding:6px 20px;">')
    );
    
    jQuery('#a587-form legend').each(function(i, elem)
    {
      var legend = jQuery(elem);
      legend.css('padding-right', (mainWidth - legend.attr('offsetWidth') + parseInt(legend.css('padding-right').replace(/[^0-9]+/,''))) + 'px');
    });
  }
  else
  {
    jQuery('.a587_loading').remove();
    
    jQuery('#a587-form legend').each(function(i, elem)
    {
      var legend = jQuery(elem);
      legend.css('padding-right', (mainWidth - legend.attr('offsetWidth') + parseInt(legend.css('padding-right').replace(/[^0-9]+/,''))) + 'px');
    });
  }
}

// top search terms
jQuery('#a587_rexsearch_stats_maxTopSearchitems').change(function()
{
  setLoading(true);
  jQuery.getJSON(
    'index.php?page=rexsearch&subpage=stats&func=topsearchterms&count='+jQuery('#a587_rexsearch_stats_maxTopSearchitems').attr('value')+'&only='+getonly,
    function(data)
    {
      var selected = jQuery('#a587_rexsearch_stats_searchtermselect').attr('value');
      var loaddefault = true;
      
      jQuery('#topsearchterms ol').empty();
      jQuery('#a587_rexsearch_stats_searchtermselect').empty();
      
      jQuery('#a587_rexsearch_stats_searchtermselect').append(
        jQuery('<option value="all">').text('<?php echo htmlspecialchars($I18N->Msg('a587_stats_searchterm_timestats_title0_all')); ?>')
      );
      
      var select = '';
      var cssclass;
      jQuery.each(data, function(i,item)
      {
        if(item.success == '1')
          cssclass = 'rexsearch-stats-success';
        else
          cssclass = 'rexsearch-stats-fail';
        
        // list
        jQuery('#topsearchterms ol').append(
          jQuery('<li class="'+cssclass+'">').html('<strong>'+item.term+'<'+'/strong> <em>('+item.count+')<'+'/em><'+'/li>')
        );
        
        // select
        if(('_'+item.term) == selected)
        {
          select = ' selected="selected"';
          loaddefault = false;
        }
        else
          select = '';
        jQuery('#a587_rexsearch_stats_searchtermselect').append(
          jQuery('<option value="_'+item.term+'"'+select+'>').text('"'+item.term+'"')
        );
      });
      
      if(loaddefault)
      {
        date = new Date();
        jQuery('#searchterm_timestats img').attr(
          'src',
          'index.php?page=rexsearch&subpage=stats&func=image&image=searchterm_timestats&term=all&monthcount=<?php echo intval($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['searchtermselectmonthcount']); ?>&time='+Date.parse(date)
        );
      }
      
      setLoading(false);
    }
  );
});

jQuery('span.rexsearch-stats-all').click(function()
{
  getonly = 0;
  jQuery('#a587_rexsearch_stats_maxTopSearchitems').change();
});

jQuery('span.rexsearch-stats-success').click(function()
{
  getonly = 1;
  jQuery('#a587_rexsearch_stats_maxTopSearchitems').change();
});

jQuery('span.rexsearch-stats-fail').click(function()
{
  getonly = 2;
  jQuery('#a587_rexsearch_stats_maxTopSearchitems').change();
});

// search term time stats
function setOverview(term, count)
{
  date = new Date();
  
  jQuery('#searchterm_timestats img').attr(
    'src',
    'index.php?page=rexsearch&subpage=stats&func=image&image=searchterm_timestats&term='+ term +'&monthcount='+count+'&time='+Date.parse(date)
  );
}

jQuery('#a587_rexsearch_stats_searchtermselect').change(function()
{
  setOverview(jQuery('#a587_rexsearch_stats_searchtermselect').attr('value'), jQuery('#a587_rexsearch_stats_searchtermselectmonthcount').attr('value'));
});

jQuery('#a587_rexsearch_stats_searchtermselectmonthcount').change(function()
{
  setOverview(jQuery('#a587_rexsearch_stats_searchtermselect').attr('value'), jQuery('#a587_rexsearch_stats_searchtermselectmonthcount').attr('value'));
});

jQuery.each(jQuery('#a587-form fieldset'), function(i, elem)
{
  var legend = jQuery('legend', elem);
  var wrapper = jQuery('.rex-form-wrapper', elem);
  var speed = wrapper.attr('offsetHeight');
  
  wrapper.hide();
  
  jQuery(elem)
    .css('border-bottom','1px solid #fff');
  
  legend
  .css('cursor','pointer')
  .css('padding-right', (mainWidth - legend.attr('offsetWidth') + parseInt(legend.css('padding-right').replace(/[^0-9]+/,''))) + 'px')
  .css('border-bottom','1px solid #cbcbcb')
  .mouseover(function()
  {
    if(wrapper.css('display') == 'none')
      jQuery('legend', elem).css('color','#aaa');
  })
  .mouseout(function()
  {
    legend.css('color','#32353A');
  })
  .click(function()
  {
    wrapper.slideToggle(speed);
  });
});

// stop event-bubbling for clicks on select-lists
jQuery('#a587-form legend select,#a587-form legend span').click(function(event)
{
  event.stopPropagation();
})

// ]]>
</script>
</form>
</div>

</div>
<?php
include $REX['INCLUDE_PATH'].'/layout/bottom.php';
