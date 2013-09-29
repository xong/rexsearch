<?php
function a587_getArticleIds($cats = false)
{
  global $REX;
  
  $whereCats = array();
  if(is_array($cats))
  {
    foreach($cats as $catID)
      $whereCats[] = "path LIKE '%|".$catID."|%'";
  }
  
  $return = array();
  $query = 'SELECT id FROM '.$REX['TABLE_PREFIX'].'article';
  if(empty($REX['ADDON']['settings']['rexsearch']['indexoffline']))
    $query .= ' WHERE status = 1';
  if(!empty($whereCats))
    $query .= ' AND ('.implode(' OR ', $whereCats).' OR (id IN ('.implode(',',$cats).') AND startpage = 1))';
  $query .= ' GROUP BY id ORDER BY id';
  
  $sql = new rex_sql();
  foreach($sql->getArray($query) as $art)
  {
    $return[] = $art['id'];
  }
  
  return $return;
}


if(!empty($_GET['do']) AND $_GET['do'] == 'incremental')
{
  echo '<div class="rex-message" style="display:none;" id="rexsearch_generate_cancel"><div class="rex-warning"><p><span>'.$I18N->Msg('a587_settings_generate_cancel').'</span></p></div></div>';
  echo '<div class="rex-message" style="display:none;" id="rexsearch_generate_done"><div class="rex-info"><p><span>'.$I18N->Msg('a587_settings_generate_done').'</span></p></div></div>';
  echo '<div class="rex-message" id="rexsearch_generate_inprogress"><div class="rex-warning" style="background-image:url(../files/addons/rexsearch/loading.gif)"><p><span>'.$I18N->Msg('a587_settings_generate_inprogress').'</span></p></div></div>';
  echo '<h4 class="rex-hl3" id="rexsearch_generate_header">'.$I18N->Msg('a587_settings_generate_incremental').'</h4>';

  $js_output = '';
  $globalcount = 0;
  foreach(a587_getArticleIds() as $id)
  {
    #$js_output .= 'index("art",'.$id.');';
    $js_output .= 'indexArray.push(new Array("art",'.$id.'));';
    $globalcount++;
  }
  
  if(!empty($REX['ADDON']['settings']['rexsearch']['include']) AND is_array($REX['ADDON']['settings']['rexsearch']['include']))
  {
    foreach($REX['ADDON']['settings']['rexsearch']['include'] as $table => $columnArray)
    {
      $sql = new rex_sql();
      $sql->setQuery("SELECT COUNT(*) AS count FROM `".$sql->escape($table)."`");
      $count = intval($sql->getValue('count'));
      $step_width = 100;
      
      for($i = 0; $i < $count; $i += $step_width)
      {
        foreach($columnArray as $column)
        {
          #$js_output .= 'index("col",new Array("'.$table.'","'.$column.'"));';
          $js_output .= 'indexArray.push(new Array("col",new Array("'.$table.'","'.$column.'",'.$i.','.$step_width.')));';
          $globalcount++;
        }
      }
    }
  }
  
  if(!empty($REX['ADDON']['settings']['rexsearch']['indexmediapool']) AND intval($REX['ADDON']['settings']['rexsearch']['indexmediapool']))
  {
    $mediaSQL = new rex_sql();
    $mediaSQL->setTable($REX['TABLE_PREFIX'].'file');
    if($mediaSQL->select('file_id, category_id, filename'))
    {
      foreach($mediaSQL->getArray() as $file)
      {
        
        if(!empty($REX['ADDON']['settings']['rexsearch']['fileextensions']))
        {
          // extract file-extension
          $filenameArray = explode('.', $file['filename']);
          $fileext = $filenameArray[count($filenameArray) - 1];
          
          // check file-extension
          if(!in_array($fileext, $REX['ADDON']['settings']['rexsearch']['fileextensions']))
            continue;
        }
        
        #$js_output .= 'index("mediapool",new Array("'.urlencode($file['filename']).'","'.urlencode($file['file_id']).'","'.urlencode($file['category_id']).'"));';
        $js_output .= 'indexArray.push(new Array("mediapool",new Array("'.urlencode($file['filename']).'","'.urlencode($file['file_id']).'","'.urlencode($file['category_id']).'")));';
        $globalcount++;
      }
    }
  }

  
  if(!empty($REX['ADDON']['settings']['rexsearch']['indexfolders']) AND is_array($REX['ADDON']['settings']['rexsearch']['indexfolders']))
  {
    foreach($REX['ADDON']['settings']['rexsearch']['indexfolders'] as $dir)
    {
      foreach(a587_getFiles($dir, isset($REX['ADDON']['settings']['rexsearch']['fileextensions'])?$REX['ADDON']['settings']['rexsearch']['fileextensions']:array(), true) as $filename)
      {
        if(!empty($REX['ADDON']['settings']['rexsearch']['fileextensions']))
        {
          // extract file-extension
          $filenameArray = explode('.', $filename);
          $fileext = $filenameArray[count($filenameArray) - 1];
          
          // check file-extension
          if(!in_array($fileext, $REX['ADDON']['settings']['rexsearch']['fileextensions']))
            continue;
        }
        
        #$js_output .= 'index("file","'.($filename).'");';
        $js_output .= 'indexArray.push(new Array("file","'.($filename).'"));';
        $globalcount++;
      }
    }
  }
?>
<div id="rexsearch_generate_log" style="border:1px solid #000;padding:0.3em;"></div>
<script type="text/javascript">
// <![CDATA[
var globalcount = 0;
var indexArray = new Array();
var quotient = 0;
var maxProgressbarWidth = jQuery('#rexsearch_generate_inprogress').width();
var startTime = new Date();
var h,m,s,duration,average,timeleft;

<?php echo $js_output; ?>

function index(type,data)
{
  var url;
  if(type == 'art')
    url = 'index.php?page=rexsearch&ajax=generate&do=incremental&type=art&id='+data;
  else if(type == 'col')
    url = 'index.php?page=rexsearch&ajax=generate&do=incremental&type=col&t='+data[0]+'&c='+data[1]+'&s='+data[2]+'&w='+data[3];
  else if(type == 'file')
    url = 'index.php?page=rexsearch&ajax=generate&do=incremental&type=file&name='+data;
  else if(type == 'mediapool')
    url = 'index.php?page=rexsearch&ajax=generate&do=incremental&type=mediapool&name='+data[0]+'&file_id='+data[1]+'&category_id='+data[2];
  
  jQuery.get(url,{},function(data)
  {
    jQuery('#rexsearch_generate_log').prepend(data);
    globalcount++;
    
    quotient = globalcount / <?php echo $globalcount; ?>;
    
    currentDuration = (new Date()) - startTime;
    durationSeconds = Math.floor(currentDuration / 1000);
    h = Math.floor(durationSeconds / 3600);
    m = Math.floor((durationSeconds - (h * 3600)) / 60);
    s = (durationSeconds - h * 3600 - m * 60) % 60;
    duration = ((''+h).length == 1 ? '0' : '') + h + ':' + ((''+m).length == 1 ? '0' : '') + m + ':' + ((''+s).length == 1 ? '0' : '') + s;
    
    average = Math.floor(currentDuration / globalcount * (<?php echo $globalcount; ?> - globalcount) / 1000);
    h = Math.floor(average / 3600);
    m = Math.floor((average - (h * 3600)) / 60);
    s = (average - h * 3600 - m * 60) % 60;
    timeleft = ((''+h).length == 1 ? '0' : '') + h + ':' + ((''+m).length == 1 ? '0' : '') + m + ':' + ((''+s).length == 1 ? '0' : '') + s;
    
    jQuery('#rexsearch_generate_progressbar')
      .css('background-position',(Math.floor(quotient * maxProgressbarWidth) - 5000) + 'px 0')
      .html(  globalcount + '/' + <?php echo $globalcount; ?> +
              ' <span class="duration"><?php echo $I18N->Msg('a587_settings_generate_duration'); ?>' + duration + '<'+'/span>' +
              ' <span class="timeleft"><?php echo $I18N->Msg('a587_settings_generate_timeleft'); ?>' + timeleft + '<'+'/span>' +
              ' <span class="percentage">' + Math.floor(quotient * 100) + '%<'+'/span>');
    
    if(globalcount == <?php echo $globalcount; ?>)
    {
      jQuery('#rexsearch_generate_inprogress').hide();
      jQuery('#rexsearch_generate_done').show();
    }
    else
    {
      index(indexArray[globalcount][0], indexArray[globalcount][1]);
    }
  });
}
<?php
if($globalcount > 0)
{
?>
if(confirm('<?php echo $I18N->Msg('a587_settings_generate_incremental_confirm'); ?>'))
{
  var del = new Image();
  del.src = 'index.php?page=rexsearch&ajax=deleteindex';
  
  index(indexArray[0][0], indexArray[0][1]);
}
else
{
  jQuery('#rexsearch_generate_inprogress').hide();
  jQuery('#rexsearch_generate_cancel').show();
}
<?php
}
else
{
?>
jQuery('#rexsearch_generate_inprogress').hide();
jQuery('#rexsearch_generate_done').show();<?php
}
?>
jQuery('#rexsearch_generate_header').after(
  jQuery('<div>')
    .attr('id','rexsearch_generate_progressbar')
    .html('0/0 <span class="duration"><?php echo $I18N->Msg('a587_settings_generate_duration'); ?>00:00:00<'+'/span> <span class="timeleft"><?php echo $I18N->Msg('a587_settings_generate_timeleft'); ?>00:00:00<'+'/span> <span class="percentage">0%<'+'/span>')
);
// ]]>
</script>
<?php
}
else
{
  if(!empty($_GET['do']))
  {
    switch($_GET['do'])
    {
      case 'done':
        echo '<div class="rex-message"><div class="rex-info"><p><span>'.$I18N->Msg('a587_settings_generate_done').'</span></p></div></div>';
      break;
      
      case 'full':
        $index = new rexsearch();
        $index->generateIndex();
        echo '<div class="rex-message"><div class="rex-info"><p><span>'.$I18N->Msg('a587_settings_generate_done').'</span></p></div></div>';
      break;
      
      case 'deletecache':
        $index = new rexsearch();
        $index->deleteCache();
        echo '<div class="rex-message"><div class="rex-info"><p><span>'.$I18N->Msg('a587_settings_generate_cache_deleted').'</span></p></div></div>';
      break;
      
      case 'deletekeywords':
        $index = new rexsearch();
        $index->deleteKeywords();
        echo '<div class="rex-message"><div class="rex-info"><p><span>'.$I18N->Msg('a587_settings_generate_keywords_deleted').'</span></p></div></div>';
      break;
    }
  }

  $content = '
<div class="rex-area">
<div class="rex-area-content">
<p class="rex-tx1">'.$I18N->Msg('a587_settings_generate_full_text').'</p>
<p class="rex-button"><a href="index.php?page=rexsearch&amp;subpage=generate&amp;do=full" class="rex-button"><span>'.$I18N->Msg('a587_settings_generate_full').'</span></a></p>

<p class="rex-tx1">'.$I18N->Msg('a587_settings_generate_incremental_text').'</p>
<p class="rex-button"><a href="index.php?page=rexsearch&amp;subpage=generate&amp;do=incremental" class="rex-button"><span>'.$I18N->Msg('a587_settings_generate_incremental').'</span></a></p>

<p class="rex-tx1">'.$I18N->Msg('a587_settings_generate_delete_cache_text').'</p>
<p class="rex-button"><a href="index.php?page=rexsearch&amp;subpage=generate&amp;do=deletecache" class="rex-button"><span>'.$I18N->Msg('a587_settings_generate_delete_cache').'</span></a></p>
  
<p class="rex-tx1">'.$I18N->Msg('a587_settings_generate_delete_keywords_text').'</p>
<p class="rex-button"><a onclick="return confirm(\''.$I18N->Msg('a587_settings_generate_delete_keywords_confirm').'\');" href="index.php?page=rexsearch&amp;subpage=generate&amp;do=deletekeywords" class="rex-button"><span>'.$I18N->Msg('a587_settings_generate_delete_keywords').'</span></a></p>';
  
  echo rex_register_extension_point('A587_PAGE_MAINTENANCE', $content);
  
  echo '
</div>
</div>';
}
