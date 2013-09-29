<?php
function a587_saveSettings($_settings)
{
  global $REX;
  $mypage = 'rexsearch';
  return rex_put_file_contents($REX['INCLUDE_PATH'].'/addons/'.$mypage.'/settings.conf', serialize($_settings));
}


function a587_getArticles($cats = false)
{
  global $REX;
  
  $whereCats = array();
  if(is_array($cats))
  {
    foreach($cats as $catID)
      $whereCats[] = "path LIKE '%|".$catID."|%'";
  }
  
  $return = array();
  $query = 'SELECT id,name,path FROM '.$REX['TABLE_PREFIX'].'article WHERE 1';
  if(empty($REX['ADDON']['settings']['rexsearch']['indexoffline']))
    $query .= ' AND status = 1';
  if(!empty($whereCats))
    $query .= ' AND ('.implode(' OR ', $whereCats).' OR (id IN ('.implode(',',$cats).')))';
  $query .= ' GROUP BY id ORDER BY id';
  
  $sql = new rex_sql();
  foreach($sql->getArray($query) as $art)
  {
    $return[$art['id']] = $art['name'];
  }
  
  return $return;
}


function a587_getCategories($_ignoreoffline = true, $_onlyIDs = false, $_cats = false)
{
  global $REX;
  
  $return = array();
  
  if(!empty($_cats))
  {
    $whereCats = array();
    $sqlCats = array();
    if(is_array($_cats))
    {
      foreach($_cats as $catID)
      {
        $whereCats[] = "path LIKE '%|".intval($catID)."|%'";
        $sqlCats[] = intval($catID);
      }
    }
    
    $return = array();
    $query = 'SELECT id,catname,path FROM '.$REX['TABLE_PREFIX'].'article WHERE startpage = 1';
    if(empty($REX['ADDON']['settings']['rexsearch']['indexoffline']) AND $_ignoreoffline)
      $query .= ' AND status = 1';
    if(!empty($whereCats))
      $query .= ' AND ('.implode(' OR ', $whereCats).' OR (id IN ('.implode(',',$sqlCats).')))';
    $query .= ' GROUP BY id ORDER BY id';
    
    $sql = new rex_sql();
    foreach($sql->getArray($query) as $cat)
    {
      if($_onlyIDs)
        $return[] = $cat['id'];
      else
        $return[$cat['id']] = $cat['catname'];
    }
  }
  else
  {
    $query = 'SELECT id,re_id,catname,path FROM '.$REX['TABLE_PREFIX'].'article WHERE startpage = 1 AND re_id=%d';
    if(empty($REX['ADDON']['settings']['rexsearch']['indexoffline']) AND $_ignoreoffline)
      $query .= ' AND status = 1';
    $query .= ' GROUP BY id ORDER BY catprior,id';

    $sql = new rex_sql();
    $cats = $sql->getArray(sprintf($query,0));
    
    while(!empty($cats))
    {
      $cat = array_shift($cats);
      
      if($_onlyIDs)
        $return[] = $cat['id'];
      else
        $return[$cat['id']] = str_repeat('&nbsp;',substr_count($cat['path'],'|') * 2 - 2).$cat['catname'];
      
      array_splice($cats, 0, 0, $sql->getArray(sprintf($query,$cat['id'])));
    }
  }
  
  return $return;
}


function a587_getDirs($_startDir = '', $_getSubdirs = false)
{
  global $REX;
  
  $startDepth = substr_count($_startDir, '/');
  if(@is_dir($_SERVER['DOCUMENT_ROOT'].$_startDir))
    $dirs2 = array_diff(scandir($_SERVER['DOCUMENT_ROOT'].$_startDir), array( '.', '..' ));
  else
    return array();
  
  $dirs = array();
  foreach($dirs2 as $k => $dir)
    if(@is_dir($_SERVER['DOCUMENT_ROOT'].$_startDir.'/'.$dir))
      $dirs[$_SERVER['DOCUMENT_ROOT'].$_startDir.'/'.$dir] = rex_lang_is_utf8()?utf8_encode($_startDir.'/'.$dir):$_startDir.'/'.$dir;
  
  if(!$_getSubdirs)
    return $dirs;
  
  $return = array();
  while(!empty($dirs))
  {
    $dir = array_shift($dirs);
    
    $depth = substr_count($dir, '/') - $startDepth;
    if(@is_dir($_SERVER['DOCUMENT_ROOT'].$dir) AND $depth <= $REX['ADDON']['settings']['rexsearch']['dirdepth'])
    {
      $return[$_SERVER['DOCUMENT_ROOT'].$dir] = rex_lang_is_utf8()?utf8_encode($dir):$dir;
      $subdirs = array();
      foreach(array_diff(scandir($_SERVER['DOCUMENT_ROOT'].$dir), array( '.', '..' )) as $subdir)
        if(@is_dir($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$subdir))
          $subdirs[] = $dir.'/'.$subdir;
      array_splice($dirs, 0, 0, $subdirs);
    }
  }
  
  return $return;
}


function a587_getFiles($_startDir = '', $_fileexts = array(), $_getSubdirs = false)
{
  global $REX;
  
  $return = array();
  $fileextPattern;
  
  if(!empty($_fileexts))
    $fileextPattern = '~\.('.implode('|', $_fileexts).')$~is';
  else
    $fileextPattern = '~\.([^.]+)$~is';
  
  $startDepth = substr_count($_startDir, '/');
  if(@is_dir($_SERVER['DOCUMENT_ROOT'].$_startDir))
    $dirs2 = array_diff(scandir($_SERVER['DOCUMENT_ROOT'].$_startDir), array( '.', '..' ));
  else
    return array();
  
  $dirs = array();
  foreach($dirs2 as $k => $dir)
  {
    if(@is_dir($_SERVER['DOCUMENT_ROOT'].$_startDir.'/'.$dir))
      $dirs[$_SERVER['DOCUMENT_ROOT'].$_startDir.'/'.$dir] = $_startDir.'/'.$dir;
    elseif(preg_match($fileextPattern, $dir))
      $return[] = rex_lang_is_utf8()?utf8_encode($_startDir.'/'.$dir):$_startDir.'/'.$dir;
  }
  
  if(!$_getSubdirs)
    return $return;
  
  while(!empty($dirs))
  {
    $dir = array_shift($dirs);
    
    $depth = substr_count($dir, '/') - $startDepth;
    if(@is_dir($_SERVER['DOCUMENT_ROOT'].$dir) AND $depth <= $REX['ADDON']['settings']['rexsearch']['dirdepth'])
    {
      $subdirs = array();
      foreach(array_diff(scandir($_SERVER['DOCUMENT_ROOT'].$dir), array( '.', '..' )) as $subdir)
        if(@is_dir($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$subdir))
          $subdirs[] = $dir.'/'.$subdir;
        elseif(preg_match($fileextPattern, $subdir))
          $return[] = rex_lang_is_utf8()?utf8_encode($dir.'/'.$subdir):$dir.'/'.$subdir;
      array_splice($dirs, 0, 0, $subdirs);
    }
    elseif(preg_match($fileextPattern, $subdir))
      $return[] = rex_lang_is_utf8()?utf8_encode($dir):$dir;
  }
  
  return $return;
}


function a587_register_extensionpoints($_Aep)
{
  foreach($_Aep as $ep)
  {
    rex_register_extension($ep, 'a587_handle_extensionpoint');
  }
}


function a587_handle_extensionpoint($_params)
{
  global $REX,$I18N;
  
  $rexsearch = new rexsearch();
  switch($_params['extension_point'])
  {
    // delete article from index
    case 'ART_DELETED':
      $rexsearch->excludeArticle($_params['id']);
    break;
    
    // update meta-infos for article
    case 'ART_META_UPDATED':
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'article')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column, 'id', $_params['id']);
        }
      }
    break;
    
    // exclude (if offline) or index (if online) article
    case 'ART_STATUS':
      if($_params['status'] OR !empty($REX['ADDON']['settings']['rexsearch']['indexoffline']))
        $rexsearch->indexArticle($_params['id'],$_params['clang']);
      else
        $rexsearch->excludeArticle($_params['id'],$_params['clang']);
      
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'article')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column, 'id', $_params['id']);
        }
      }
    break;
    
    case 'ART_ADDED':
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'article')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column, 'id', $_params['id']);
        }
      }
    break;
    
    case 'ART_UPDATED':
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'article')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column, 'id', $_params['id']);
        }
      }
    break;
    
    case 'CAT_DELETED':
      echo rex_warning($I18N->Msg('a587_cat_deleted'));
    break;
    
    case 'CAT_STATUS':
      if($_params['status'] OR !empty($REX['ADDON']['settings']['rexsearch']['indexoffline']))
      {
        foreach(a587_getArticles(array($_params['id'])) as $art_id => $art_name)
          $rexsearch->indexArticle($art_id, $_params['clang']);
      }
      else
      {
        foreach(a587_getArticles(array($_params['id'])) as $art_id => $art_name)
          $rexsearch->excludeArticle($art_id, $_params['clang']);
      }
      
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'article')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column, 'id', $_params['id']);
        }
      }
    break;
    
    case 'CAT_ADDED':
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'article')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column, 'id', $_params['id']);
        }
      }
    break;
    
    case 'CAT_UPDATED':
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'article')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column, 'id', $_params['id']);
        }
      }
    break;
    
    case 'MEDIA_ADDED':
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'file')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column);
        }
      }
    break;
    
    case 'MEDIA_UPDATED':
      foreach($rexsearch->includeColumns as $table => $columnArray)
      {
        if($table == $REX['TABLE_PREFIX'].'file')
        {
          foreach($columnArray as $column)
            $rexsearch->indexColumn($table, $column, 'id', $_params['file_id']);
        }
      }
    break;
    
    case 'SLICE_UPDATED':
      $rexsearch->indexArticle($_params['article_id'],$_params['clang']);
    break;
    
    case 'SLICE_SHOW':
      if(strpos($_params['subject'],'<div class="rex-message"><div class="rex-info">') AND (!empty($_params['function']) OR (!empty($_REQUEST['slice_id']) AND $_REQUEST['slice_id'] == $_params['slice_id'])))
        $rexsearch->indexArticle($_params['article_id'],$_params['clang']); 
    break;
  }
  
  // Cache leeren
  $rexsearch->deleteCache();
}

function a587_getSettingsFormSection($id = '', $title = '&nbsp;', $elements = array())
{
  $return = '<fieldset id="'.$id.'" class="rex-form-col-1"><legend>'.$title.'</legend><div class="rex-form-wrapper">';
  foreach($elements as $element)
  {
    if(($element['type'] != 'hidden') AND ($element['type'] != 'directoutput'))
      $return .= '<div class="rex-form-row">';
    
    switch($element['type'])
    {
      // HIDDEN
      case 'hidden':
        $return .= '
          <input type="hidden" name="'.$element['name'].'" value="'.$element['value'].'" />';
      break;
      
      // STRING
      case 'string':
        $return .= '
          <p class="rex-form-col-a rex-form-text">
          <label for="'.$element['id'].'">'.$element['label'].'</label>
          <input type="text" name="'.$element['name'].'" class="rex-form-text" id="'.$element['id'].'" value="'.$element['value'].'" />
          </p>';
      break;
      
      // TEXT
      case 'text':
        $return .= '
          <p class="rex-form-col-a rex-form-textarea">
          <label for="'.$element['id'].'">'.$element['label'].'</label>
          <textarea name="'.$element['name'].'" class="rex-form-textarea" id="'.$element['id'].'" rows="10" cols="20">'.$element['value'].'</textarea>
          </p>';
      break;
      
      // SELECT
      case 'select':
        $options = '';
        foreach($element['options'] as $option)
        {
          $options .= '<option value="'.$option['value'].'"'.($option['selected'] ? ' selected="selected"' : '').'>'.$option['name'].'</option>';
        }
        
        $return .= '
          <p class="rex-form-col-a rex-form-text">
          <label for="'.$element['id'].'">'.$element['label'].'</label>
          <select class="rex-form-text" id="'.$element['id'].'" size="1" name="'.$element['name'].'">
          '.$options.'
          </select>
          </p>';
      break;
      
      // MULTIPLE SELECT
      case 'multipleselect':
        $options = '';
        foreach($element['options'] as $option)
        {
          $id = !empty($option['id'])?' id="'.$option['id'].'"':'';
          $options .= '<option'.$id.' value="'.$option['value'].'"'.($option['selected'] ? ' selected="selected"' : '').'>'.$option['name'].'</option>';
        }
        
        $return .= '
          <p class="rex-form-col-a rex-form-text">
          <label for="'.$element['id'].'">'.$element['label'].'</label>
          <select class="rex-form-text" id="'.$element['id'].'" name="'.$element['name'].'" multiple="multiple" size="'.$element['size'].'"'.(!empty($element['disabled'])?' disabled="disabled"':'').'>
          '.$options.'
          </select>
          </p>';
      break;
      
      // MULTIPLE CHECKBOXES
      case 'multiplecheckboxes':
        $checkboxes = '';
        foreach($element['options'] as $option)
        {
          $id = !empty($option['id'])?' id="'.$option['id'].'"':'';
          $for = !empty($option['id'])?' for="'.$option['id'].'"':'';
          $checkboxes .= '<div class="checkbox"><input type="checkbox"'.$id.' name="'.$element['name'].'" value="'.$option['value'].'"'.($option['checked'] ? ' checked="checked"' : '').' /> <label'.$for.'>'.$option['name'].'</label></div>';
        }
        
        $return .= '
          <div class="rex-form-col-a rex-form-text">
          '.(!empty($element['label']) ? '<label for="'.$element['id'].'">'.$element['label'].'</label>' : '').'
          <div class="checkboxes">'.$checkboxes.'</div>
          </div>';
      break;
      
      // RADIO
      case 'radio':
        $options = '';
        foreach($element['options'] as $option)
        {
          $options .= '
            <input type="radio" name="'.$element['name'].'" value="'.$option['value'].'" class="rex-form-radio" id="'.$option['id'].'"'.($option['checked'] ? ' checked="checked"' : '').' />
            <label for="'.$option['id'].'">'.$option['label'].'</label>';
        }
        
        $return .= '
          <p class="rex-form-col-a rex-form-radio rex-form-label-right">
          '.$options.'
          </p>';
      break;
      
      // CHECKBOX
      case 'checkbox':
        $return .= '
          <p class="rex-form-col-a rex-form-checkbox">
          <label for="'.$element['id'].'">'.$element['label'].'</label>
          <input class="rex-form-checkbox" type="checkbox" name="'.$element['name'].'" id="'.$element['id'].'" value="'.$element['value'].'"'.($element['checked'] ? ' checked="checked"' : '').' />
          </p>';
      break;
      
      // DIRECT OUTPUT
      case 'directoutput':
        $return .= $element['output'];
      break;
    }
    
    if(($element['type'] != 'hidden') AND ($element['type'] != 'directoutput'))
      $return .= '</div>';
  }
  
  $return .= '</div></fieldset>';
  
  return $return;
}

function a587_config_unserialize($_str)
{
  $conf = unserialize($_str);
  
  if(strpos($_str, '\\"') === false)
    return $conf;
  
  $return = array();
  if(is_array($conf))
  {
    foreach(unserialize($_str) as $k => $v)
    {
      if(is_array($v))
      {
        $return[$k] = array();
        foreach($v as $k2 => $v2)
        {
          if(is_array($v2))
          {
            $return[$k][$k2] = array();
            foreach($v2 as $k3 => $v3)
            {
              if(is_array($v3))
              {
                $return[$k][$k2][$k3] = array();
                foreach($v3 as $k4=> $v4)
                {
                  $return[$k][$k2][$k3][$k4] = stripslashes($v4);
                }
              }
              else
                $return[$k][$k2][$k3] = stripslashes($v3);
            }
          }
          else
            $return[$k][$k2] = stripslashes($v2);
        }
      }
      else
        $return[$k] = stripslashes($v);
    }
  }
  
  return $return;
}
