<?php
if(isset($_POST['sendit']))
{
  if(!empty($_POST['a587_rexsearch']['blacklist']))
    $_POST['a587_rexsearch']['blacklist'] = explode(',',$_POST['a587_rexsearch']['blacklist']);
  else
    unset($_POST['a587_rexsearch']['blacklist']);
  
  if(!empty($_POST['a587_rexsearch']['include']))
  {
    $returnArray = array();
    foreach($_POST['a587_rexsearch']['include'] as $include)
    {
      $includeArray = explode('`.`',$include);
      if(!array_key_exists($includeArray[0],$returnArray))
      {
        $returnArray[$includeArray[0]] = array();
      }
      
      $returnArray[$includeArray[0]][] = $includeArray[1];
    }
    
    $_POST['a587_rexsearch']['include'] = $returnArray;
  }
  else
    unset($_POST['a587_rexsearch']['include']);
  
  if(!empty($_POST['a587_rexsearch']['fileextensions']))
  {
    $returnArray = array();
    foreach(explode(',', $_POST['a587_rexsearch']['fileextensions']) as $fileext)
    {
      $returnArray[] = trim($fileext);
    }
    
    $_POST['a587_rexsearch']['fileextensions'] = $returnArray;
  }
  else
    unset($_POST['a587_rexsearch']['fileextensions']);
  
  $suggestnewindex = '';
  foreach(array_keys(array_merge(array_diff_assoc($_POST['a587_rexsearch'],$REX['ADDON']['settings']['rexsearch']), array_diff_assoc($REX['ADDON']['settings']['rexsearch'],$_POST['a587_rexsearch']))) as $changed)
  {
    if(in_array($changed, array(
      'indexmode',
      'indexoffline',
      'automaticindex',
      'blacklist',
      'exclude_article_ids',
      'exclude_category_ids',
      'include',
      'fileextensions',
      'indexmediapool',
      'dirdepth',
      'indexfolders',
      'ep_outputfilter'
    ))) $suggestnewindex = '&suggestnewindex=1';
  }
  echo '</pre>';
  
  a587_saveSettings($_POST['a587_rexsearch']);
  
  /*$rexsearch = new rexsearch();
  $rexsearch->deleteCache();*/
  
  header('Location: http://'.$_SERVER['HTTP_HOST'].substr($_SERVER["PHP_SELF"],0,-9).'index.php?page=rexsearch&subpage=settings&save=1'.$suggestnewindex);
}

if(!empty($_GET['save']))
  echo rex_info($I18N->Msg('a587_settings_saved'));

if(!empty($_GET['suggestnewindex']))
  echo rex_warning($I18N->Msg('a587_settings_saved_warning'));
?>

<div class="rex-addon-output" id="a587-form">
<h2 class="rex-hl2">Einstellungen</h2>
<div class="rex-area">

<div class="rex-form">
<form method="post" action="index.php?page=rexsearch&amp;subpage=settings" id="a587_settings_form">
<?php
echo a587_getSettingsFormSection(
  'a587_modi',
  $I18N->Msg('a587_settings_modi_header'),
  array(
    array(
      'type' => 'select',
      'id' => 'a587_rexsearch_logicalmode',
      'name' => 'a587_rexsearch[logicalmode]',
      'label' => $I18N->Msg('a587_settings_logicalmode'),
      'options' => array(
        array(
          'value' => 'and',
          'selected' => $REX['ADDON']['settings']['rexsearch']['logicalmode'] == 'and',
          'name' => $I18N->Msg('a587_settings_logicalmode_and')
        ),
        array(
          'value' => 'or',
          'selected' => $REX['ADDON']['settings']['rexsearch']['logicalmode'] == 'or',
          'name' => $I18N->Msg('a587_settings_logicalmode_or')
        )
      )
    ),
    array(
      'type' => 'select',
      'id' => 'a587_rexsearch_textmode',
      'name' => 'a587_rexsearch[textmode]',
      'label' => $I18N->Msg('a587_settings_textmode'),
      'options' => array(
        array(
          'value' => 'plain',
          'selected' => $REX['ADDON']['settings']['rexsearch']['textmode'] == 'plain',
          'name' => $I18N->Msg('a587_settings_textmode_plain')
        ),
        array(
          'value' => 'html',
          'selected' => $REX['ADDON']['settings']['rexsearch']['textmode'] == 'html',
          'name' => $I18N->Msg('a587_settings_textmode_html')
        ),
        array(
          'value' => 'both',
          'selected' => $REX['ADDON']['settings']['rexsearch']['textmode'] == 'both',
          'name' => $I18N->Msg('a587_settings_textmode_both')
        )
      )
    ),
    array(
      'type' => 'select',
      'id' => 'a587_rexsearch_similarwords_mode',
      'name' => 'a587_rexsearch[similarwordsmode]',
      'label' => $I18N->Msg('a587_settings_similarwords_label'),
      'options' => array(
        array(
          'value' => A587_SIMILARWORDS_NONE,
          'selected' => $REX['ADDON']['settings']['rexsearch']['similarwordsmode'] == A587_SIMILARWORDS_NONE,
          'name' => $I18N->Msg('a587_settings_similarwords_none')
        ),
        array(
          'value' => A587_SIMILARWORDS_SOUNDEX,
          'selected' => $REX['ADDON']['settings']['rexsearch']['similarwordsmode'] == A587_SIMILARWORDS_SOUNDEX,
          'name' => $I18N->Msg('a587_settings_similarwords_soundex')
        ),
        array(
          'value' => A587_SIMILARWORDS_METAPHONE,
          'selected' => $REX['ADDON']['settings']['rexsearch']['similarwordsmode'] == A587_SIMILARWORDS_METAPHONE,
          'name' => $I18N->Msg('a587_settings_similarwords_metaphone')
        ),
        array(
          'value' => A587_SIMILARWORDS_COLOGNEPHONE,
          'selected' => $REX['ADDON']['settings']['rexsearch']['similarwordsmode'] == A587_SIMILARWORDS_COLOGNEPHONE,
          'name' => $I18N->Msg('a587_settings_similarwords_cologne')
        ),
        array(
          'value' => A587_SIMILARWORDS_ALL,
          'selected' => $REX['ADDON']['settings']['rexsearch']['similarwordsmode'] == A587_SIMILARWORDS_ALL,
          'name' => $I18N->Msg('a587_settings_similarwords_all')
        )
      )
    ),
    array(
      'type' => 'checkbox',
      'id' => 'a587_rexsearch_similarwords_permanent',
      'name' => 'a587_rexsearch[similarwords_permanent]',
      'label' => $I18N->Msg('a587_settings_similarwords_permanent'),
      'value' => '1',
      'checked' => !empty($REX['ADDON']['settings']['rexsearch']['similarwords_permanent'])
    ),
    array(
      'type' => 'select',
      'id' => 'a587_rexsearch_searchmode',
      'name' => 'a587_rexsearch[searchmode]',
      'label' => $I18N->Msg('a587_settings_searchmode'),
      'options' => array(
        array(
          'value' => 'like',
          'selected' => $REX['ADDON']['settings']['rexsearch']['searchmode'] == 'like',
          'name' => $I18N->Msg('a587_settings_searchmode_like')
        ),
        array(
          'value' => 'match',
          'selected' => $REX['ADDON']['settings']['rexsearch']['searchmode'] == 'match',
          'name' => $I18N->Msg('a587_settings_searchmode_match')
        )
      )
    )
  )
);


echo a587_getSettingsFormSection(
  'a587_index',
  $I18N->Msg('a587_settings_title_indexmode'),
  array(
    array(
      'type' => 'select',
      'id' => 'a587_settings_indexmode',
      'name' => 'a587_rexsearch[indexmode]',
      'label' => $I18N->Msg('a587_settings_indexmode_label'),
      'options' => array(
        array(
          'value' => '0',
          'name' => $I18N->Msg('a587_settings_indexmode_viahttp'),
          'selected' => $REX['ADDON']['settings']['rexsearch']['indexmode'] == '0',
        ),
        array(
          'value' => '1',
          'name' => $I18N->Msg('a587_settings_indexmode_viacache'),
          'selected' => $REX['ADDON']['settings']['rexsearch']['indexmode'] == '1',
        ),
        array(
          'value' => '2',
          'name' => $I18N->Msg('a587_settings_indexmode_viacachetpl'),
          'selected' => $REX['ADDON']['settings']['rexsearch']['indexmode'] == '2',
        )
      )
    ),
    array(
      'type' => 'checkbox',
      'id' => 'a587_rexsearch_indexoffline',
      'name' => 'a587_rexsearch[indexoffline]',
      'label' => $I18N->Msg('a587_settings_indexoffline'),
      'value' => '1',
      'checked' => !empty($REX['ADDON']['settings']['rexsearch']['indexoffline'])
    ),
    array(
      'type' => 'checkbox',
      'id' => 'a587_rexsearch_automaticindex',
      'name' => 'a587_rexsearch[automaticindex]',
      'label' => $I18N->Msg('a587_settings_automaticindex_label'),
      'value' => '1',
      'checked' => !empty($REX['ADDON']['settings']['rexsearch']['automaticindex'])
    ),
    array(
      'type' => 'checkbox',
      'id' => 'a587_rexsearch_ep_outputfilter',
      'name' => 'a587_rexsearch[ep_outputfilter]',
      'label' => $I18N->Msg('a587_settings_ep_outputfilter_label'),
      'value' => '1',
      'checked' => !empty($REX['ADDON']['settings']['rexsearch']['ep_outputfilter'])
    )
  )
);


$sample = <<<EOT
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.
EOT;
$sampleoutput = '<div id="a587_sample_wrapper" style="padding:5px;clear:left;">
        <h5 class="rex-form-text">'.$I18N->Msg('a587_settings_highlight_sample').':</h5>
        <div id="a587_sample" style="margin-top:5px;padding:5px;border:1px solid #000;background:#fff;color:#000;">';
$rexsearch = new rexsearch();
$rexsearch->searchString = '"velit esse" accusam';
$rexsearch->parseSearchString('"velit esse" accusam');
if($rexsearch->highlightType == 'array')
{
  $sampleoutput .= '<pre style="font-size:1.2em;">';
  $sampleoutput .= print_r($rexsearch->getHighlightedText($sample), true);
  $sampleoutput .= '</pre>';
}
else
  $sampleoutput .= $rexsearch->getHighlightedText($sample);

$sampleoutput .= '</div></div>';

echo a587_getSettingsFormSection(
  'a587_highlight',
  $I18N->Msg('a587_settings_highlight_header'),
  array(
    array(
      'type' => 'string',
      'id' => 'a587_rexsearch_surroundtags_start',
      'name' => 'a587_rexsearch[surroundtags][0]',
      'label' => $I18N->Msg('a587_settings_surroundtags_start'),
      'value' => isset($REX['ADDON']['settings']['rexsearch']['surroundtags'][0]) ? htmlspecialchars($REX['ADDON']['settings']['rexsearch']['surroundtags'][0]) : ''
    ),
    array(
      'type' => 'string',
      'id' => 'a587_rexsearch_surroundtags_end',
      'name' => 'a587_rexsearch[surroundtags][1]',
      'label' => $I18N->Msg('a587_settings_surroundtags_end'),
      'value' => isset($REX['ADDON']['settings']['rexsearch']['surroundtags'][1]) ? htmlspecialchars($REX['ADDON']['settings']['rexsearch']['surroundtags'][1]) : ''
    ),
    array(
      'type' => 'hidden',
      'name' => 'a587_rexsearch[limit][0]',
      'value' => '0'
    ),
    array(
      'type' => 'string',
      'id' => 'a587_rexsearch_limit',
      'name' => 'a587_rexsearch[limit][1]',
      'label' => $I18N->Msg('a587_settings_limit'),
      'value' => isset($REX['ADDON']['settings']['rexsearch']['limit'][1]) ? intval($REX['ADDON']['settings']['rexsearch']['limit'][1]) : ''
    ),
    array(
      'type' => 'string',
      'id' => 'a587_rexsearch_maxteaserchars',
      'name' => 'a587_rexsearch[maxteaserchars]',
      'label' => $I18N->Msg('a587_settings_maxteaserchars'),
      'value' => isset($REX['ADDON']['settings']['rexsearch']['maxteaserchars']) ? intval($REX['ADDON']['settings']['rexsearch']['maxteaserchars']) : ''
    ),
    array(
      'type' => 'string',
      'id' => 'a587_rexsearch_maxhighlightchars',
      'name' => 'a587_rexsearch[maxhighlightchars]',
      'label' => $I18N->Msg('a587_settings_maxhighlightchars'),
      'value' => isset($REX['ADDON']['settings']['rexsearch']['maxhighlightchars']) ? intval($REX['ADDON']['settings']['rexsearch']['maxhighlightchars']) : ''
    ),
    array(
      'type' => 'select',
      'id' => 'a587_rexsearch_highlight',
      'name' => 'a587_rexsearch[highlight]',
      'label' => $I18N->Msg('a587_settings_highlight_label'),
      'options' => array(
        array(
          'value' => 'sentence',
          'selected' => $REX['ADDON']['settings']['rexsearch']['highlight'] == 'sentence',
          'name' => $I18N->Msg('a587_settings_highlight_sentence')
        ),
        array(
          'value' => 'paragraph',
          'selected' => $REX['ADDON']['settings']['rexsearch']['highlight'] == 'paragraph',
          'name' => $I18N->Msg('a587_settings_highlight_paragraph')
        ),
        array(
          'value' => 'surroundtext',
          'selected' => $REX['ADDON']['settings']['rexsearch']['highlight'] == 'surroundtext',
          'name' => $I18N->Msg('a587_settings_highlight_surroundtext')
        ),
        array(
          'value' => 'surroundtextsingle',
          'selected' => $REX['ADDON']['settings']['rexsearch']['highlight'] == 'surroundtextsingle',
          'name' => $I18N->Msg('a587_settings_highlight_surroundtextsingle')
        ),
        array(
          'value' => 'teaser',
          'selected' => $REX['ADDON']['settings']['rexsearch']['highlight'] == 'teaser',
          'name' => $I18N->Msg('a587_settings_highlight_teaser')
        ),
        array(
          'value' => 'array',
          'selected' => $REX['ADDON']['settings']['rexsearch']['highlight'] == 'array',
          'name' => $I18N->Msg('a587_settings_highlight_array')
        ),
      )
    ),
    array(
      'type' => 'directoutput',
      'output' => '<div class="rex-form-row">'.$sampleoutput.'</div>'
    )
  )
);



$categories = array();
foreach(a587_getCategories() as $id => $name)
{
  $categories[] = array(
    'value' => $id,
    'selected' => !empty($REX['ADDON']['settings']['rexsearch']['exclude_category_ids']) AND is_array($REX['ADDON']['settings']['rexsearch']['exclude_category_ids']) AND in_array($id,$REX['ADDON']['settings']['rexsearch']['exclude_category_ids']),
    'name' => $name.' ('.$id.')'
  );
}
$articles = array();
foreach(a587_getArticles() as $id => $name)
{
  $articles[] = array(
    'value' => $id,
    'selected' => !empty($REX['ADDON']['settings']['rexsearch']['exclude_article_ids']) AND is_array($REX['ADDON']['settings']['rexsearch']['exclude_article_ids']) AND in_array($id,$REX['ADDON']['settings']['rexsearch']['exclude_article_ids']),
    'name' => $name.' ('.$id.')'
  );
}
echo a587_getSettingsFormSection(
  'a587_exclude',
  $I18N->Msg('a587_settings_exclude'),
  array(
    array(
      'type' => 'string',
      'id' => 'a587_settings_exclude_blacklist',
      'name' => 'a587_rexsearch[blacklist]',
      'label' => $I18N->Msg('a587_settings_exclude_blacklist'),
      'value' => isset($REX['ADDON']['settings']['rexsearch']['blacklist']) ? htmlspecialchars(implode(',',$REX['ADDON']['settings']['rexsearch']['blacklist'])) : ''
    ),
    array(
      'type' => 'multipleselect',
      'id' => 'a587_rexsearch_exclude_article_ids',
      'name' => 'a587_rexsearch[exclude_article_ids][]',
      'label' => $I18N->Msg('a587_settings_exclude_articles'),
      'size' => 15,
      'options' => $articles
    ),
    array(
      'type' => 'multipleselect',
      'id' => 'a587_rexsearch_exclude_category_ids',
      'name' => 'a587_rexsearch[exclude_category_ids][]',
      'label' => $I18N->Msg('a587_settings_exclude_categories'),
      'size' => 15,
      'options' => $categories
    )
  )
);



$options = array();
$sql_tables = new rex_sql();
foreach($sql_tables->showTables() as $table)
{
  if(false === strpos($table,'587_search') AND false === strpos($table,'587_keywords'))
  {
    $sql_columns = new rex_sql();
    foreach($sql_tables->showColumns($table) as $column)
    {
      /*switch(strtolower(substr($column['type'],0,4)))
      {
        case 'text':
        case 'char':
        case 'varc':*/
          $options[] = array(
            'value' => $table.'`.`'.$column['name'],
            'selected' => in_array($column['name'],(!empty($REX['ADDON']['settings']['rexsearch']['include'][$table]) AND is_array($REX['ADDON']['settings']['rexsearch']['include'][$table]))?$REX['ADDON']['settings']['rexsearch']['include'][$table]:array()),
            'name' => $table.'  .  '.$column['name']
          );
      //}
    }
  }
}
echo a587_getSettingsFormSection(
  'a587_include',
  $I18N->Msg('a587_settings_include'),
  array(
    array(
      'type' => 'multipleselect',
      'id' => 'a587_rexsearch_include',
      'name' => 'a587_rexsearch[include][]',
      'label' => '&lt;table&gt;.&lt;column&gt;',
      'size' => 20,
      'options' => $options
    )
  )
);



$options = array(
  array(
    'value' => '',
    'name' => '',
    'selected' => false,
    'id' => 'a587_optiondummy'
  )
);
if(!empty($REX['ADDON']['settings']['rexsearch']['indexfolders']))
{
  foreach($REX['ADDON']['settings']['rexsearch']['indexfolders'] as $relative)
  {
    $options[] = array(
      'value' => $relative,
      'name' => $relative,
      'selected' => true
    );
  }
}
foreach(range(1,30) as $depth)
{
  $dirdepth_options[] = array(
    'value' => $depth,
    'name' => $depth,
    'selected' => $REX['ADDON']['settings']['rexsearch']['dirdepth'] == $depth
  );
}
echo a587_getSettingsFormSection(
  'a587_files',
  $I18N->Msg('a587_settings_fileext_header'),
  array(
    array(
      'type' => 'string',
      'id' => 'a587_settings_fileext_label',
      'name' => 'a587_rexsearch[fileextensions]',
      'label' => $I18N->Msg('a587_settings_fileext_label'),
      'value' => isset($REX['ADDON']['settings']['rexsearch']['fileextensions']) ? htmlspecialchars(implode(',',$REX['ADDON']['settings']['rexsearch']['fileextensions'])) : ''
    ),
    array(
      'type' => 'checkbox',
      'id' => 'a587_settings_file_mediapool',
      'name' => 'a587_rexsearch[indexmediapool]',
      'label' => $I18N->Msg('a587_settings_file_mediapool'),
      'value' => '1',
      'checked' => !empty($REX['ADDON']['settings']['rexsearch']['indexmediapool'])
    ),
    array(
      'type' => 'select',
      'id' => 'a587_settings_file_dirdepth',
      'name' => 'a587_rexsearch[dirdepth]',
      'label' => $I18N->Msg('a587_settings_file_dirdepth_label'),
      'options' => $dirdepth_options
    ),
    array(
      'type' => 'multipleselect',
      'id' => 'a587_settings_folders',
      'name' => 'a587_rexsearch[indexfolders][]',
      'label' => $I18N->Msg('a587_settings_folders_label'),
      'size' => 10,
      'options' => $options
    )
  )
);


?>
  <div class="rex-form-row">
    <p class="rex-form-col-a rex-form-submit">
      <input type="submit" value="<?php echo $I18N->Msg('a587_settings_submitbutton'); ?>" name="sendit" class="rex-form-submit"/>
    </p>
  </div>

</form>
</div>

</div>
</div>

<script type="text/javascript">
// <![CDATA[
// width of the formular
var mainWidth = jQuery('#a587-form').attr('offsetWidth');

// set loading image for filesearch-config
jQuery('#a587_files legend').append(
  jQuery('<span>')
  .attr('class','loading')
);

// accordion
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


// ajax request for sample-text
jQuery('#a587_rexsearch_highlight')
.change(function()
{
  jQuery.get('index.php?page=rexsearch&ajax=sample&type='+jQuery('#a587_rexsearch_highlight').attr('value'),{},function(data)
  {
    jQuery('#a587_sample').html(data);
  });
});


// directory-selection
function getElementByValue(elements, value) {
  var returnElem = false;
  jQuery.each(elements, function(i, elem)
  {
    if(elem.value == value)
    {
      returnElem = elem;
      return false;
    }
  });
  
  return returnElem;
}

function setDirs()
{
  var depth = 0,dirs = new Array(),found,indexdirs;
  while(document.getElementById('subdirs_'+depth))
  {
    jQuery.each(jQuery('#subdirs_'+depth+' option'), function(i, elem)
    {
      if(elem.selected)
        dirs.push(elem.value);
    });
    
    depth++;
  }
  
  indexdirs = new Array();
  for(var k=0; k < dirs.length; k++)
  {
    found = false;
    for(var i=0; i < dirs.length; i++)
    {
      //if(dirs[k].substring(0,dirs[k].lastIndexOf('/')) == dirs[i])
      if((dirs[i].indexOf(dirs[k]) >= 0) && (i != k))
      {
        found = true;
        //dirs.splice(i,1);
        //break;
      }
    }
    
    if(!found)
      indexdirs.push(dirs[k]);
  }
  
  jQuery('#a587_settings_folders').empty();
  
  jQuery.each(indexdirs, function(i, elem)
  {
    jQuery('#a587_settings_folders')
    .append(
      jQuery('<option>')
      .attr('value', elem)
      .text(elem)
    );
  });
}

function traverseSubdirs(depth, options)
{
  var found,empty,activeOptions = new Array(),elem;
  
  for(var i = 0; i < options.length; i++)
  {
    if((elem = getElementByValue(jQuery('#subdirs_'+(depth-1)+' option'), options[i])) && elem.selected)
      activeOptions.push(options[i]);
  }
  
  while(document.getElementById('subdirs_'+depth))
  {
    empty = true;
    jQuery.each(jQuery('#subdirs_'+depth+' option'), function(i, elem)
    {
      found = false;
      for(var k = 0; k < activeOptions.length; k++)
      {
        found = found || (elem.value.indexOf(activeOptions[k]) >= 0);
      }
      
      if(!found)
        jQuery(elem).remove();
      else
        empty = false;
    });
    
    if(empty)
    {
      jQuery('#subdirs_'+depth).remove();
      jQuery('#subdirselectlabel_'+depth).remove();
    }
    
    depth++;
  }
}

function a587_serialize(a)
{
  var anew = new Array();
  for(var i = 0; i < a.length; i++)
    anew.push('"' + (a[i].replace(/"/g, '\\"')) + '"');
  return '[' + anew.join(',') + ']';
}

function createSubdirSection(depth,autoselect)
{
  var parent,options,startdirstring = '',startdirs = new Array();
  if(depth == 0)
  {
    parent = '#a587_settings_folders';
  }
  else
  {
    parent = '#subdirs_'+(depth-1);
    jQuery.each(jQuery('#subdirs_'+(depth-1)+' option'), function(i, elem)
    {
      if(elem.selected)
      {
        startdirs.push(elem.value);
      }
    });
  }
  
  if(depth > 0 && !startdirs.length)
  {
    var currentDepth = depth;
    while(document.getElementById('subdirs_'+currentDepth))
    {
      jQuery('#subdirs_'+(currentDepth)).remove();
      jQuery('#subdirselectlabel_'+(currentDepth++)).remove();
    }
    
    jQuery('#a587_files .loading').remove();
    
    while(document.getElementById('subdirs_'+(--depth)))
      jQuery('#subdirs_'+(depth--)).removeAttr('disabled');
    
    return false;
  }
  else
  {
    jQuery.post('index.php?page=rexsearch&ajax=getdirs', {'startdirs':a587_serialize(startdirs)}, function(options)
    {
      if(!document.getElementById('subdirs_'+depth) && options.length > 0)
      {
        jQuery(parent)
        .after(
          jQuery('<select>')
          .attr('id','subdirs_'+depth)
          .attr('class','rex-form-text subdirselect')
          .attr('multiple','multiple')
          .attr('size','10')
          .change(function()
          {
            createSubdirSection(depth+1);
            traverseSubdirs(depth+1, options);
            setDirs();
          })
        )
        .after(
          jQuery('<label>')
          .text(('<?php echo $I18N->Msg('a587_settings_folders_dirselect_label'); ?>').replace(/%DEPTH%/, depth))
          .attr('for','subdirs_'+depth)
          .attr('class','subdirselectlabel')
          .attr('id','subdirselectlabel_'+depth)
        );
        
        if(autoselect)
          jQuery('#subdirs_'+depth).attr('disabled','disabled');
      }
      
      for(var i = 0; i < options.length; i++)
      {
        if(!getElementByValue(jQuery('#subdirs_'+depth+' option'), options[i]))
        {
          if(autoselect)
          {
            var found = false;
            jQuery('#a587_settings_folders option').each(function(j, elem)
            {
              found = found || (elem.value.indexOf(options[i]) >= 0);
              
              if(found)
                return false;
            });
            
            if(found)
            {
              jQuery('#subdirs_'+depth)
              .append(
                jQuery('<option>')
                .attr('value', options[i])
                .attr('selected', 'selected')
                .text(options[i])
              );
            }
            else
            {
              jQuery('#subdirs_'+depth)
              .append(
                jQuery('<option>')
                .attr('value', options[i])
                .text(options[i])
              );
            }
          }
          else
          {
            jQuery('#subdirs_'+depth)
            .append(
              jQuery('<option>')
              .attr('value', options[i])
              .text(options[i])
            );
          }
        }
      }
      
      if(autoselect)
      {
        var maxDepth = 0,splitted,current,count;
        jQuery('#a587_settings_folders option').each(function(i, elem)
        {
          if((elem.id != 'a587_optiondummy') && ((count = elem.value.split('/').length-2) > maxDepth))
            maxDepth = count;
        });
        
        if(maxDepth >= depth)
        {
          createSubdirSection(depth+1,true);
        }
        else
        {
          jQuery('#a587_files .loading').remove();
          
          depth = 0;
          while(document.getElementById('subdirs_'+depth))
            jQuery('#subdirs_'+(depth++)).removeAttr('disabled');
          
          depth--;
          
          // adapt width of legend
          var legend = jQuery('#a587_files legend');
          legend.css('padding-right', (mainWidth - legend.attr('offsetWidth') + parseInt(legend.css('padding-right').replace(/[^0-9]+/,''))) + 'px');
        }
      }
    }, 'json');
  
  return true;
  }
}

var options;
// beautifying the indexed folders selectbox and selecting the options in the subdir-selectboxes
jQuery('#a587_settings_folders').attr('disabled','disabled');
jQuery.each(options = jQuery('#a587_settings_folders option'), function(i, elem)
{
  var splitted,current,depth=0;
  
  elem.selected = false;
  
  if(options.length - 1 == i)
    createSubdirSection(depth,true);
});

jQuery('#a587_settings_form').submit(function()
{
  jQuery('#a587_settings_folders').removeAttr('disabled');
  jQuery.each(jQuery('#a587_settings_folders option'), function(i, elem)
  {
    if(elem.value != '')
      elem.selected = true;
  });
  
  return true;
});

// ]]>
</script>