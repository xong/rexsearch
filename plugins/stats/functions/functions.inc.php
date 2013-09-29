<?php
function a587_stats_saveSettings($_settings)
{
  global $REX;
  $parent = 'rexsearch';
  $mypage = 'stats';
  return rex_put_file_contents($REX['INCLUDE_PATH'].'/addons/'.$parent.'/plugins/'.$mypage.'/settings.conf', serialize($_settings));
}


function a587_stats_storekeywords($_params)
{
  $stats = new rexsearchStats();
  $stats->insert($_params['subject']['searchterm'], $_params['subject']['count']);
}

function a587_stats_addtruncate($params)
{
  global $I18N;
  
  if(rex_request('func') == 'truncate')
  {
    $stats = new rexsearchStats();
    $stats->truncate();
    
    a587_stats_saveSettings(array(
      'maxtopSearchitems' => '10',
      'searchtermselect' => '',
      'searchtermselectmonthcount' => '12'
    ));
    
    $params['subject'] = rex_info($I18N->Msg('a587_stats_truncate_done')).$params['subject'];
  }
  
  $params['subject'] .= '<p class="rex-tx1">'.$I18N->Msg('a587_stats_truncate').'</p>
<p class="rex-button"><a onclick="return confirm(\''.$I18N->Msg('a587_stats_truncate_confirm').'\');" href="index.php?page=rexsearch&amp;subpage=generate&amp;func=truncate" class="rex-button"><span>'.$I18N->Msg('a587_stats_truncate_button').'</span></a></p>';

  return $params['subject'];
}

function a587_getStatSection($_id, $_title, $_content)
{
  return '<fieldset id="'.$_id.'" class="rex-form-col-1"><legend>'.$_title.'</legend>
<div class="rex-form-wrapper">
  <div class="rex-form-row">
  <div class="rex-area-content">
    '.$_content.'
  </div>
  </div>
</div>
</fieldset>';
}

function a587_stats_bytesize($_value)
{
  $units = array(
    'Byte',
    'KByte',
    'MByte',
    'GByte',
    'TByte',
    'PByte'
  );
  
  $i = 0;
  if($_value > 0)
  {
    while($_value > 1024)
    {
      $_value /= 1024;
      $i++;
    }
    
    $dec = ($i > 1) ? 2 : 0;
    return number_format($_value, $dec, ',', '').' '.$units[$i];
  }
}
