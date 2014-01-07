<?php
switch($ajax)
{
  case 'deleteindex':
    $delete = new rexsearch();
    $delete->deleteIndex();
    echo 1;
  break;
  
  case 'generate':
    // index column or article
    $rexsearch = new rexsearch();
    switch($_GET['type'])
    {
      case 'art':
        foreach($rexsearch->indexArticle($_id = intval($_GET['id'])) as $langID => $article)
        {
          switch($article)
          {
            case A587_ART_EXCLUDED:
              echo '<p style="color:#aaa">Article (ID=<strong>'.$_id.'</strong>,<strong>'.$langID.'</strong>) is excluded</p>';
            break;
            case A587_ART_IDNOTFOUND:
              echo '<p style="color:#a55">Article (ID=<strong>'.$_id.'</strong>,<strong>'.$langID.'</strong>) not found</p>';
            break;
            case A587_ART_REDIRECT:
              echo '<p style="color:#aaa">Article (ID=<strong>'.$_id.'</strong>,<strong>'.$langID.'</strong>) is excluded because of a redirect</p>';
            break;
            case A587_ART_GENERATED:
              $article = OOArticle::getArticleById($_id, $langID);
              echo '<p style="color:#5a5">Done: Article <em>"'.htmlspecialchars($article->getName()).'"</em> (ID=<strong>'.$_id.'</strong>,<strong>'.$REX['CLANG'][$langID].'</strong>)</p>';
            break;
          }
        }
      break;
      
      case 'col':
        if(false !== ($count = $rexsearch->indexColumn($_GET['t'], $_GET['c'], false, false, $_GET['s'], $_GET['w'])))
          echo '<p style="color:#5a5">Done: <em>`'.$_GET['t'].'`.`'.$_GET['c'].'` ('.$_GET['s'].' - '.($_GET['s'] + $_GET['w']).')</em> (<strong>'.$count.'</strong> row(s) indexed)</p>';
        else
          echo '<p style="color:#a55">Error: <em>`'.$_GET['t'].'`.`'.$_GET['c'].'`</em> not found</p>';
      break;
      
      case 'file':
      case 'mediapool':
        $additionalOutput = '';
        if($_GET['type'] == 'file')
        {
          $return = $rexsearch->indexFile($_GET['name']);
        }
        else
        {
          $return = $rexsearch->indexFile(str_replace('\\','/',substr($REX['MEDIAFOLDER'], strlen(realpath($_SERVER['DOCUMENT_ROOT'])))).'/'.$_GET['name'], false, false, $_GET['file_id'], $_GET['category_id']);
          $additionalOutput = ' <em>(Mediapool)</em>';
        }
        
        switch($return)
        {
          case A587_FILE_FORBIDDEN_EXTENSION:
            echo '<p style="color:#a55">File'.$additionalOutput.' <strong>"'.htmlspecialchars($_GET['name']).'"</strong> has a forbidden filename extension.</p>';
          break;
          
          case A587_FILE_NOEXIST:
            echo '<p style="color:#a55">File'.$additionalOutput.' <strong>"'.htmlspecialchars($_GET['name']).'"</strong> does not exist.</p>';
          break;
  
          case A587_FILE_XPDFERR_OPENSRC:
            echo '<p style="color:#a55">XPDF-error: Error opening a PDF file. File'.$additionalOutput.': <strong>"'.htmlspecialchars($_GET['name']).'"</strong>.</p>';
          break;
  
          case A587_FILE_XPDFERR_OPENDEST:
            echo '<p style="color:#a55">XPDF-error: Error opening an output file. File'.$additionalOutput.': <strong>"'.htmlspecialchars($_GET['name']).'"</strong>.</p>';
          break;
  
          case A587_FILE_XPDFERR_PERM:
            echo '<p style="color:#a55">XPDF-error: Error related to PDF permissions. File'.$additionalOutput.': <strong>"'.htmlspecialchars($_GET['name']).'"</strong>.</p>';
          break;
  
          case A587_FILE_XPDFERR_OTHER:
            echo '<p style="color:#a55">XPDF-error: Other error. File'.$additionalOutput.': <strong>"'.htmlspecialchars($_GET['name']).'"</strong>.</p>';
          break;
  
          case A587_FILE_EMPTY:
            echo '<p style="color:#a55">File'.$additionalOutput.' <strong>"'.htmlspecialchars($_GET['name']).'"</strong> is empty or could not be extracted.</p>';
          break;
  
          case A587_FILE_GENERATED:
            echo '<p style="color:#5a5">Done: File'.$additionalOutput.' <strong>"'.htmlspecialchars($_GET['name']).'"</strong>';
          break;
        }
      break;
      
      default:
        echo '<p style="color:#a55">Error: <em>Wrong request parameters!</em></p>';
    }
  break;
  
  case 'sample':
  header('Content-Type: text/html; charset=UTF-8');
  
    $sample = <<<EOT
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.
EOT;
    $rexsearch = new rexsearch();
    $rexsearch->searchString = '"velit esse" accusam';
    $rexsearch->setHighlightType($_GET['type']);
    $rexsearch->parseSearchString('"velit esse" accusam');
    
    if($rexsearch->highlightType == 'array')
    {
      echo '<pre style="font-size:1.2em;display:inline;">';
      print_r($rexsearch->getHighlightedText($sample));
      echo '</pre>';
    }
    else
      echo $rexsearch->getHighlightedText($sample);
  break;
  
  case 'getdirs':
    echo '[';
    $str = stripslashes(rex_request('startdirs','string','[]'));
    
    $startdirs = explode('","', substr($str, 2, -2));
    $dirs = array();
    if(!empty($startdirs))
    {
      
      if(is_array($startdirs))
      {
        foreach($startdirs as $dir)
        {
          foreach(a587_getDirs(str_replace('\\"', '"', $dir)) as $absolute => $relative)
          {
            $dirs[] = '"'.addcslashes($relative, '"/\\').'"';
          }
        }
      }
      else
      {
        foreach(a587_getDirs(str_replace('\\"', '"', $startdirs)) as $absolute => $relative)
        {
          $dirs[] = '"'.addcslashes($relative, '"/\\').'"';
        }
      }
    }
    else
    {
      foreach(a587_getDirs() as $absolute => $relative)
        {
          $dirs[] = '"'.addcslashes($relative, '"/\\').'"';
        }
    }
    
    echo implode(',', $dirs);
    
    echo ']';
  break;
}
