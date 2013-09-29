<?php
$parent = 'rexsearch';
$mypage = 'plaintext';

$basedir = dirname(__FILE__);
$page = rex_request('page', 'string');
$subpage = rex_request('subpage', 'string');
$func = rex_request('func', 'string');

if(isset($_POST['sendit']))
{
  a587_plaintext_saveSettings($_POST['a587_rexsearch_plaintext']);
  
  header('Location: http://'.$_SERVER['HTTP_HOST'].substr($_SERVER["PHP_SELF"],0,-9).'index.php?page='.$parent.'&subpage='.$mypage.'&save=1');
}

include $REX['INCLUDE_PATH'].'/layout/top.php';

rex_title("rexsearch", $REX['ADDON'][$page]['SUBPAGES']);

if(!empty($_GET['save']))
{
  echo rex_info($I18N->Msg('a587_settings_saved'));
}
?>
<div class="rex-addon-output" id="a587-form">
<h2 class="rex-hl2" style="position: relative;"><?php echo $I18N->Msg('a587_plaintext_title'); ?></h2>

<div class="rex-form">
<form method="post" action="index.php?page=<?php echo $parent; ?>&amp;subpage=<?php echo $mypage; ?>" id="a587_<?php echo $mypage; ?>_form">
<?php
echo a587_getSettingsFormSection(
  'a587_rexsearch_plaintext_description',
  $I18N->Msg('a587_rexsearch_plaintext_description_title'),
  array(
    array(
      'type' => 'directoutput',
      'output' => '<div class="rex-form-row"><div class="rex-area-content"><p class="rex-tx1">'.$I18N->Msg('a587_rexsearch_plaintext_description').'</p></div></div>'
    ),
    array(
      'type' => 'hidden',
      'name' => 'a587_rexsearch_plaintext[order]',
      'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['order']) ? htmlspecialchars($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['order']) : ''
    )
  )
);

/*$form_elements = array(
  array(
    'type' => 'directoutput',
    'output' => '<div class="rex-form-row"><div class="rex-area-content"><p class="rex-tx1">'.$I18N->Msg('a587_rexsearch_plaintext_description').'</p></div></div>'
  ),
  array(
    'type' => 'hidden',
    'name' => 'a587_rexsearch_plaintext[order]',
    'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['order']) ? htmlspecialchars($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['order']) : ''
  ),
  array(
    'type' => 'directoutput',
    'output' => '<div id="sortable-elements">'
  )
);*/

echo '<div id="sortable-elements">';

foreach(explode(',', $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['order']) as $elem)
{
  switch($elem)
  {
    case 'selectors':
      /*$form_elements[] = array(
        'type' => 'text',
        'id' => 'a587_rexsearch_plaintext_selectors',
        'name' => 'a587_rexsearch_plaintext[selectors]',
        'label' => $I18N->Msg('a587_rexsearch_plaintext_selectors'),
        'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['selectors']) ? htmlspecialchars($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['selectors']) : ''
      );*/
      echo a587_getSettingsFormSection(
        'a587_rexsearch_plaintext_selectors_fieldset',
        $I18N->Msg('a587_rexsearch_plaintext_selectors'),
        array(
          array(
            'type' => 'text',
            'id' => 'a587_rexsearch_plaintext_selectors',
            'name' => 'a587_rexsearch_plaintext[selectors]',
            'label' => $I18N->Msg('a587_rexsearch_plaintext_selectors_label'),
            'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['selectors']) ? htmlspecialchars($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['selectors']) : ''
          )
        )
      );
    break;
    
    case 'regex':
      /*$form_elements[] = array(
        'type' => 'text',
        'id' => 'a587_rexsearch_plaintext_regex',
        'name' => 'a587_rexsearch_plaintext[regex]',
        'label' => $I18N->Msg('a587_rexsearch_plaintext_regex'),
        'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['regex']) ? htmlspecialchars($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['regex']) : ''
      );*/
      echo a587_getSettingsFormSection(
        'a587_rexsearch_plaintext_regex_fieldset',
        $I18N->Msg('a587_rexsearch_plaintext_regex'),
        array(
          array(
            'type' => 'text',
            'id' => 'a587_rexsearch_plaintext_regex',
            'name' => 'a587_rexsearch_plaintext[regex]',
            'label' => $I18N->Msg('a587_rexsearch_plaintext_regex_label'),
            'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['regex']) ? htmlspecialchars($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['regex']) : ''
          )
        )
      );
    break;
    
    case 'textile':
      echo a587_getSettingsFormSection(
        'a587_rexsearch_plaintext_textile_fieldset',
        $I18N->Msg('a587_rexsearch_plaintext_textile'),
        array(
          array(
            'type' => 'checkbox',
            'id' => 'a587_rexsearch_plaintext_textile',
            'name' => 'a587_rexsearch_plaintext[textile]',
            'label' => $I18N->Msg('a587_rexsearch_plaintext_textile_label'),
            'value' => '1',
            'checked' => !empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['textile'])
          )
        )
      );
    break;
    
    case 'striptags':
      /*$form_elements[] = array(
        'type' => 'checkbox',
        'id' => 'a587_rexsearch_plaintext_striptags',
        'name' => 'a587_rexsearch_plaintext[striptags]',
        'label' => $I18N->Msg('a587_rexsearch_plaintext_striptags'),
        'value' => '1',
        'checked' => !empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['striptags'])
      );*/
      echo a587_getSettingsFormSection(
        'a587_rexsearch_plaintext_striptags_fieldset',
        $I18N->Msg('a587_rexsearch_plaintext_striptags'),
        array(
          array(
            'type' => 'checkbox',
            'id' => 'a587_rexsearch_plaintext_striptags',
            'name' => 'a587_rexsearch_plaintext[striptags]',
            'label' => $I18N->Msg('a587_rexsearch_plaintext_striptags_label'),
            'value' => '1',
            'checked' => !empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['striptags'])
          )
        )
      );
    break;
  }
}

echo '</div>';

echo a587_getSettingsFormSection(
  'a587_rexsearch_plaintext_processparent_fieldset',
  $I18N->Msg('a587_rexsearch_plaintext_processparent'),
  array(
    array(
      'type' => 'checkbox',
      'id' => 'a587_rexsearch_plaintext_processparent',
      'name' => 'a587_rexsearch_plaintext[processparent]',
      'label' => $I18N->Msg('a587_rexsearch_plaintext_processparent_label'),
      'value' => '1',
      'checked' => !empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['processparent'])
    )
  )
);

/*$form_elements[] = array(
  'type' => 'checkbox',
  'id' => 'a587_rexsearch_plaintext_processparent',
  'name' => 'a587_rexsearch_plaintext[processparent]',
  'label' => $I18N->Msg('a587_rexsearch_plaintext_processparent'),
  'value' => '1',
  'checked' => !empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['processparent'])
);

echo a587_getSettingsFormSection(
  'a587_rexsearch_plaintext',
  $I18N->Msg('a587_rexsearch_plaintext'),
  $form_elements
);*/

?>
  <div class="rex-form-row">
    <p class="rex-form-col-a rex-form-submit">
      <input type="submit" value="<?php echo $I18N->Msg('a587_settings_submitbutton'); ?>" name="sendit" class="rex-form-submit"/>
    </p>
  </div>
<script type="text/javascript">
// <![CDATA[
(function($)
{
  $(document).ready(function()
  {
    var mainWidth = jQuery('#a587-form').width();
    var ondrag = false;

    jQuery('#sortable-elements').sortable({
      connectWith: jQuery('#sortable-elements'),
      opacity: 0.9,
      placeholder: 'placeholder',
      forcePlaceholderSize: false,
      containment: '#rex-website',
      start: function(event, ui) {
        ondrag = true;
        jQuery('legend', ui.item).css('color', '#fff');
      },
      stop: function(event, ui) {
        jQuery('legend', ui.item).css('color', '#2C8EC0');
        
        var order = new Array();
        jQuery('#a587_rexsearch_plaintext_selectors,#a587_rexsearch_plaintext_regex,#a587_rexsearch_plaintext_striptags').each(function()
        {
          order.push(this.name.match(/\[([a-zA-Z]+)\]/)[1]);
        });
        jQuery('input[name=a587_rexsearch_plaintext[order]]').attr('value', order.join(','));
        
        setTimeout(function(){ondrag = false;}, 100);
      }
    });

    jQuery('#a587_rexsearch_plaintext_selectors_fieldset legend,#a587_rexsearch_plaintext_regex_fieldset legend,#a587_rexsearch_plaintext_textile_fieldset legend,#a587_rexsearch_plaintext_striptags_fieldset legend').each(function()
    {
      var text = jQuery(this).html();
      jQuery(this).css('cursor', 'move').html('')
      .append(jQuery('<span>')
        .html(text)
        .css('background', 'url(../files/addons/rexsearch/plugins/plaintext/move.png) no-repeat 5px top')
        .css('padding-left', '20px')
        .css('display', 'block')
        .css('line-height', '18px')
      );
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
      .css('width', (mainWidth - parseInt(legend.css('padding-right').replace(/[^0-9]+/,'')) - parseInt(legend.css('padding-left').replace(/[^0-9]+/,''))) + 'px')
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
        if(!ondrag)
          wrapper.slideToggle(speed);
      });
    });
  });
}(jQuery));

// ]]>
</script>
</form>
</div>

</div>
<?php
include $REX['INCLUDE_PATH'].'/layout/bottom.php';
