<?php
function a587_rexsearch_reindex($_params)
{
  $columns = array();
  $id = 0;

  if(!empty($_params['xform']))
  {
    $tablename = $_params['form']->params['main_table'];
    //$wherecondition = $_params['sql']->wherevar;
    $wherecondition = $_params['form']->params['main_where'];
  }
  else
  {
    $tablename = $_params['form']->tableName;
    $wherecondition = $_params['form']->whereCondition;
  }
  
  $last_id = intval($_params['sql']->getLastId());
  
  if(!isset($REX['ADDON']['settings']['rexsearch']['include'][$tablename]) OR !is_array($REX['ADDON']['settings']['rexsearch']['include'][$tablename]))
    return true;
  
  if(empty($id))
    $id = $last_id;
  
  $rexsearch = new RexSearch;
  foreach($REX['ADDON']['settings']['rexsearch']['include'][$tablename] as $col)
    $rexsearch->indexColumn($tablename, $col, false, false, false, false, $wherecondition);
  
  return true;
}
