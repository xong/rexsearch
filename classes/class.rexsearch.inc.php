<?php
/**
 * Class rexsearch
 * 
 * This class is still being tested.
 * Please report errors at http://forum.redaxo.de.
 * 
 * @author    Robert Rupf
 * @package   rexsearch
 */
 
/**
 * 
 */
define('A587_ART_EXCLUDED',0);
define('A587_ART_IDNOTFOUND',1);
define('A587_ART_GENERATED',2);
define('A587_ART_REDIRECT',3);

define('A587_FILE_NOEXIST',0);
define('A587_FILE_XPDFERR_OPENSRC',1);
define('A587_FILE_XPDFERR_OPENDEST',2);
define('A587_FILE_XPDFERR_PERM',3);
define('A587_FILE_XPDFERR_OTHER',4);
define('A587_FILE_FORBIDDEN_EXTENSION',5);
define('A587_FILE_GENERATED',6);
define('A587_FILE_EMPTY',7);

define('A587_SIMILARWORDS_NONE',0);
define('A587_SIMILARWORDS_SOUNDEX',1);
define('A587_SIMILARWORDS_METAPHONE',2);
define('A587_SIMILARWORDS_COLOGNEPHONE',4);
define('A587_SIMILARWORDS_ALL',7);

/**
 * @package   rexsearch
 */
class RexSearch
{
  var $searchArticles = false;
  var $blacklist = array();
  var $blacklisted = array();
  var $cache = true;
  var $cachedArray = array();
  /**
    * @ignore
    */
  var $ci = true; // case insensitive?
  var $clang = false;
  var $documentRoot;
  var $dontIndexRedirects = true;
  var $ellipsis;
  var $ep_outputfilter = false;
  var $excludeIDs = array();
  var $fileExtensions = array();
  var $groupBy = true;
  var $hashMe = '';
  var $highlightType = 'surroundtext';
  var $includeColumns = array();
  var $includeDirectories = array();
  var $includePath;
  var $generatedPath;
  var $indexUnknownFileExtensions = false;
  var $indexMediapool = false;
  var $indexMissingFileExtensions = false;
  var $indexOffline = false;
  var $indexViaHTTP = false;
  var $indexWithTemplate = false;
  var $languages;
  var $limit = array(0,10);
  var $logicalMode = ' AND ';
  var $maxHighlightedTextChars = 100;
  var $maxTeaserChars = 200;
  var $mediaFolder;
  var $order = array('RELEVANCE587' => 'DESC');
  var $redaxo = false;
  var $searchArray = array();
  var $searchEntities = false;
  var $searchInIDs = array();
  var $searchMode = 'like';
  var $searchString = '';
  var $significantCharacterCount = 3;
  var $similarwords = false;
  var $similarwordsMode = 0;
  var $similarwordsPermanent = false;
  var $stopwords = array();
  var $surroundTags = array('<strong>','</strong>');
  var $tablePrefix;
  var $textMode = 'plain';
  var $whitelist = array();
  var $where = '';
  
  #function __construct($_clang = false, $_loadSettings = true)
  function RexSearch($_clang = false, $_loadSettings = true, $_useStopwords = true)
  {
    global $REX,$I18N;
    
    if($_loadSettings)
    {
      foreach($REX['ADDON']['settings']['rexsearch'] as $key => $value)
      {
        switch($key)
        {
          case 'logicalmode':
            $this->setLogicalMode($value);
          break;
          case 'textmode':
            $this->setTextMode($value);
          break;
          case 'searchmode':
            $this->setSearchMode($value);
          break;
          case 'surroundtags':
            $this->setSurroundTags($value);
          break;
          case 'limit':
            $this->setLimit($value);
          break;
          case 'ci':
            $this->setCI($value);
          break;
          case 'blacklist':
            $this->setBlacklist(is_array($value)?$value:array());
          break;
          case 'exclude_article_ids':
            $this->setExcludeIDs($value);
          break;
          case 'exclude_category_ids':
            if(is_array($value))
            {
              $ids = array();
              foreach($value as $catID)
              {
                foreach(a587_getArticles(array($catID)) as $id => $name)
                  $ids[] = $id;
                
                $this->setExcludeIDs($ids);
              }
            }
          break;
          case 'include':
            $this->setIncludeColumns($value);
          break;
          case 'maxteaserchars':
            $this->setMaxTeaserChars($value);
          break;
          case 'maxhighlightchars':
            $this->setMaxHighlightedTextChars($value);
          break;
          case 'highlight':
            $this->setHighlightType($value);
          break;
          case 'indexmode':
            $this->indexViaHTTP = intval($value) == 0;
            $this->indexWithTemplate = intval($value) == 2;
          break;
          case 'indexoffline':
            $this->indexOffline = $value == '1';
          break;
          case 'similarwordsmode':
            $this->similarwordsMode = intval($value);
            $this->similarwords = !!intval($value);
          break;
          case 'similarwords_permanent':
            $this->similarwordsPermanent = !!intval($value);
          break;
          case 'fileextensions':
            $this->fileExtensions = $value;
          break;
          case 'indexfolders':
            $this->includeDirectories = $value;
          break;
          case 'indexmediapool':
            $this->indexMediapool = !!intval($value);
          break;
          case 'ep_outputfilter':
            $this->ep_outputfilter = !!intval($value);
          break;
        }
      }
    }
    
    $this->setClang($_clang);
    $this->languages = $REX['CLANG'];
    $this->tablePrefix = $REX['TABLE_PREFIX'];
    $this->includePath = $REX['INCLUDE_PATH'];
    $this->generatedPath = $REX['GENERATED_PATH'];
    $this->documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    $this->mediaFolder = $REX['MEDIAFOLDER'];
    
    $locale = 'de_de';
    
    $langfile = new i18n($locale, $REX['INCLUDE_PATH'].'/addons/rexsearch/lang/');
    $this->ellipsis = $langfile->Msg('a587_ellipsis');
    
    // german stopwords
    if($_useStopwords)
    {
      include $this->includePath.'/addons/rexsearch/lang/stopwords.inc.php';
      $this->stopwords = $german_stopwords;
    }
  }
  
  
  /**
   * A function for retrieving the K?lner Phonetik value of a string
   *
   * As described at http://de.wikipedia.org/wiki/K?lner_Phonetik
   * Based on Hans Joachim Postel: Die K?lner Phonetik.
   * Ein Verfahren zur Identifizierung von Personennamen auf der
   * Grundlage der Gestaltanalyse.
   * in: IBM-Nachrichten, 19. Jahrgang, 1969, S. 925-931
   *
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   *
   * @package phonetics
   * @version 1.0
   * @link http://www.einfachmarke.de
   * @license GPL 3.0 <http://www.gnu.org/licenses/>
   * @copyright  2008 by einfachmarke.de
   * @author Nicolas Zimmer <nicolas dot zimmer at einfachmarke.de>
   */

  function cologne_phone($_word)
  {
    /**
    * @param  string  $_word string to be analyzed
    * @return string  $value represents the K?lner Phonetik value
    * @access public
    */

    //prepare for processing
    $_word = strtolower($_word);
    $substitution = array(
      '?'=>'a',
      '?'=>'o',
      '?'=>'u',
      '?'=>'ss',
      'ph'=>'f'
    );

    foreach($substitution as $letter => $substitution)
      $_word = str_replace($letter, $substitution, $_word);
    
    $len = strlen($_word);
    
    //Rule for exeptions
    $exceptionsLeading = array(
      4=>array('ca','ch','ck','cl','co','cq','cu','cx'),
      8=>array('dc','ds','dz','tc','ts','tz')
    );
    
    $exceptionsFollowing = array('sc','zc','cx','kx','qx');
    
    //Table for coding
    $codingTable = array(
      0 => array('a','e','i','j','o','u','y'),
      1 => array('b','p'),
      2 => array('d','t'),
      3 => array('f','v','w'),
      4 => array('c','g','k','q'),
      48 => array('x'),
      5 => array('l'),
      6 => array('m','n'),
      7 => array('r'),
      8 => array('c','s','z')
    );
    
    for($i=0; $i < $len; $i++)
    {
      $value[$i] = '';
      
        //Exceptions
      if($i==0 AND $len > 1 AND $_word[$i].$_word[$i+1] == 'cr')
        $value[$i] = 4;
      
      if($i < ($len - 1))
      {
        foreach($exceptionsLeading as $code=>$letters)
        {
          if(in_array($_word[$i].$_word[$i+1],$letters))
            $value[$i] = $code;
        }
      }
      
      if($i AND in_array($_word[$i-1].$_word[$i], $exceptionsFollowing))
        $value[$i] = 8;
      
      //Normal encoding
      if($value[$i] == '')
      {
        foreach($codingTable as $code => $letters)
        {
          if(in_array($_word[$i], $letters))
            $value[$i] = $code;
        }
      }
    }

    //delete double values
    $len=count($value);

    for($i=1;$i<$len;$i++)
    {
      if($value[$i] == $value[$i-1])
        $value[$i] = '';
    }

    //delete vocals
    for ($i=1;$i>$len;$i++)
    {
      //omitting first characer code and h
      if($value[$i] == 0)
        $value[$i] = '';
    }


    $value = array_filter($value);
    $value = implode('', $value);

    return $value;
  }
  
  
  function doSearchArticles($_bool = false)
  {
    $this->searchArticles = $_bool;
    $this->hashMe .= $_bool;
  }
  
  
  function doGroupBy($_bool = true)
  {
    $this->groupBy = $_bool;
    $this->hashMe .= $_bool;
  }
  
  /** 
    * 
    */
  function setSearchInIDs($_searchInIDs, $_reset = false)
  {
    if($_reset)
      $this->searchInIDs = array();
    
    if(array_key_exists('articles',$_searchInIDs))
    {
      if(!array_key_exists('articles',$this->searchInIDs))
        $this->searchInIDs['articles'] = array();
      
      foreach($_searchInIDs['articles'] as $id)
      {
        if($id = intval($id))
        {
          $this->searchInIDs['articles'][] = $id;
          $this->hashMe .= 'a'.$id;
        }
      }
    }
    
    if(array_key_exists('categories',$_searchInIDs))
    {
      if(!array_key_exists('categories',$this->searchInIDs))
        $this->searchInIDs['categories'] = array();
      
      foreach($_searchInIDs['categories'] as $id)
      {
        if($id = intval($id))
        {
          $this->searchInIDs['categories'][] = $id;
          $this->hashMe .= 'c'.$id;
        }
      }
    }
    
    if(array_key_exists('filecategories',$_searchInIDs))
    {
      if(!array_key_exists('filecategories',$this->searchInIDs))
        $this->searchInIDs['filecategories'] = array();
      
      foreach($_searchInIDs['filecategories'] as $id)
      {
        if($id = intval($id))
        {
          $this->searchInIDs['filecategories'][] = $id;
          $this->hashMe .= 'f'.$id;
        }
      }
    }
    
    if(array_key_exists('db_columns',$_searchInIDs))
    {
      if(!array_key_exists('db_columns',$this->searchInIDs))
        $this->searchInIDs['db_columns'] = array();
      
      foreach($_searchInIDs['db_columns'] as $table => $columnArray)
      {
        $this->hashMe .= $table;
        $tmp = array();
        foreach($columnArray as $column)
        {
           $tmp[] = $column;
           $this->hashMe .= $column;
        }
        
        if(!array_key_exists($table,$this->searchInIDs['db_columns']))
          $this->searchInIDs['db_columns'][$table] = $tmp;
        else
          $this->searchInIDs['db_columns'][$table] = array_merge($this->searchInIDs['db_columns'][$table],$tmp);
      }
    }
  }
  
  
  /**
    * If utf8-encoding is used, the parameter will be appended with an "u".
    * Since there is only UTF-8 supported, it always appends the "u".
    * 
    * @param string $_regex
    * @return string
    */
  function encodeRegex($_regex)
  {
    return $_regex.'u';
  }
  
  /**
    * Simulates the frontend by setting $REX['REDAXO'] to false.
    * The original state is saved in $this->redaxo.
    */
  function beginFrontendMode()
  {
    global $REX;
    $this->redaxo = $REX['REDAXO'];
    $REX['REDAXO'] = false;
  }
  
  
  /**
    * Ends the frontend-mode by setting $REX['REDAXO'] to the original state.
    */
  function endFrontendMode()
  {
    global $REX;
    $REX['REDAXO'] = $this->redaxo;
  }
  
  
  /**
    * Sets the maximum count of letters the teaser of a searched through text may have.
    * 
    * @param int $_count
    */
  function setMaxTeaserChars($_count)
  {
    $this->maxTeaserChars = intval($_count);
    $this->hashMe .= $_count;
  }
  
  
  /**
    * Sets the maximum count of letters around an found search term in the highlighted text.
    * @param int $_count
    */
  function setMaxHighlightedTextChars($_count)
  {
    $this->maxHighlightedTextChars = intval($_count);
    $this->hashMe .= $_count;
  }
  
  /**
    * Generates the full index at once.
    */
  function generateIndex()
  {
    // delete old index
    $delete = new rex_sql();
    $delete->setTable($this->tablePrefix.'587_searchindex');
    $delete->delete();
    $delete2 = new rex_sql();
    $delete2->setTable($this->tablePrefix.'587_searchcacheindex_ids');
    $delete2->delete();
    $delete3 = new rex_sql();
    $delete3->setTable($this->tablePrefix.'587_searchcache');
    $delete3->delete();
    
    // index articles
    $art_sql = new rex_sql();
    $art_sql->setTable($this->tablePrefix.'article');
    if($art_sql->select('id,clang'))
    {
      foreach($art_sql->getArray() as $art)
      {
        $this->indexArticle($art['id'], $art['clang']);
      }
    }
    
    // index columns
    foreach($this->includeColumns as $table => $columnArray)
    {
      foreach($columnArray as $column)
      {
        $this->indexColumn($table, $column);
      }
    }
    
    // index mediapool
    if($this->indexMediapool)
    {
      $mediaSQL = new rex_sql();
      $mediaSQL->setTable($this->tablePrefix.'file');
      if($mediaSQL->select('file_id, category_id, filename'))
      {
        foreach($mediaSQL->getArray() as $file)
        {
          $this->indexFile(str_replace('\\','/',substr($this->mediaFolder, strlen($this->documentRoot))).'/'.$file['filename'], false, false, $file['file_id'], $file['category_id']);
        }
      }
    }
    
    // index files
    foreach($this->includeDirectories as $dir)
    {
      foreach(a587_getFiles($dir, $this->fileExtensions) as $filename)
      {
        $this->indexFile($filename);
      }
    }
  }
  
  
  /**
    * Indexes a certain article.
    * 
    * @param int $_id
    * @param mixed $_clang
    * 
    * @return int
    */
  function indexArticle($_id,$_clang = false)
  {
    global $REX;
    
    if($_clang === false)
      $langs = $this->languages;
    else
      $langs = array(intval($_clang) => $this->languages[intval($_clang)]);
    
    $return = array();
    
    $keywords = array();
    foreach($langs as $langID => $v)
    {
      if(in_array($_id, $this->excludeIDs))
      {
        $return[$v] = A587_ART_EXCLUDED;
        continue;
      }
      
      $REX['CUR_CLANG'] = $langID;
      
      $delete = new rex_sql();
    
      $where = sprintf("ftable = '%s' AND fid = %d AND clang = %d", $delete->escape($this->tablePrefix.'article'), $_id, $langID);
      
      // delete from cache
      $select = new rex_sql();
      $select->setTable($this->tablePrefix.'587_searchindex');
      $select->setWhere($where);
      $select->select('id');
      
      $indexIds = array();
      foreach($select->getArray() as $result)
        $indexIds[] = $result['id'];
      
      $this->deleteCache($indexIds);
      
      // delete old
      $delete->setTable($this->tablePrefix.'587_searchindex');
      $delete->setWhere($where);
      $delete->delete();
      
      // index article
      $article = OOArticle::getArticleById(intval($_id), $langID);
      if(is_object($article) AND ($article->isOnline() OR $this->indexOffline))
      {
        $this->beginFrontendMode();
        
        if(ini_get('allow_url_fopen') AND $this->indexViaHTTP)
        {
          $articleText = @file_get_contents('http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'],0,strpos($_SERVER['PHP_SELF'],'/redaxo/')+1).rex_geturl($_id, $langID, '', '&'));
        }
        elseif ($_id != 0 AND $this->dontIndexRedirects)
        {
          $rex_article = new rex_article(intval($_id), $langID);
          
          $article_content_file = $this->generatedPath.'/articles/'.$_id.'.'.$langID.'.content';
          if(!file_exists($article_content_file))
          {
            include_once ($this->includePath."/functions/function_rex_generate.inc.php");
            $generated = rex_generateArticleContent($_id, $langID);
            if($generated !== true)
            {
              $return[$v] = A587_ART_IDNOTFOUND;
              continue;
            }
          }
          
          if(file_exists($article_content_file) AND preg_match($this->encodeRegex('~(header\s*\(\s*["\']\s*Location\s*:)|(rex_redirect\s*\()~is'), rex_get_file_contents($article_content_file)))
          {
            $return[$v] = A587_ART_REDIRECT;
            continue;
          }
          
          if($this->indexWithTemplate)
            $articleText = $rex_article->getArticleTemplate();
          else
            $articleText = $rex_article->getArticle();
          
          if($this->ep_outputfilter)
          {
            $tmp = array(
              'artid' => $REX['ARTICLE_ID'],
              'clang' => $REX['CUR_CLANG']
            );
            
            $REX['ARTICLE_ID'] = $_id;
            $REX['CUR_CLANG'] = $langID;
            $articleText = rex_register_extension_point('OUTPUT_FILTER', $articleText, array('environment' => 'frontend','sendcharset' => false));
            $REX['ARTICLE_ID'] = $tmp['artid'];
            $REX['CUR_CLANG'] = $tmp['clang'];
          }
        }
        
        $insert = new rex_sql();
        $articleData = array();
        
        $articleData['texttype'] = 'article';
        $articleData['ftable'] = $this->tablePrefix.'article';
        $articleData['fcolumn'] = NULL;
        $articleData['clang'] = $article->getClang();
        $articleData['fid'] = intval($_id);
        $articleData['catid'] = $article->getCategoryId();
        $articleData['unchangedtext'] = $insert->escape($articleText);
        $articleData['plaintext'] = $insert->escape($plaintext = $this->getPlaintext($articleText));
        
        if(array_key_exists($REX['TABLE_PREFIX'].'article', $this->includeColumns))
        {
          $additionalValues = array();
          $select->flush();
          $select->setTable($REX['TABLE_PREFIX'].'article');
          $select->setWhere('id = '.$_id.' AND clang = '.$langID);
          $select->select('`'.implode('`,`', $this->includeColumns[$REX['TABLE_PREFIX'].'article']).'`');
          foreach($this->includeColumns[$REX['TABLE_PREFIX'].'article'] as $col)
          {
            $additionalValues[$col] = $select->getValue($col);
          }
          
          $articleData['values'] = $insert->escape(serialize($additionalValues));
        }
        
        foreach(preg_split($this->encodeRegex('~[[:punct:][:space:]]+~ism'), $plaintext) as $keyword)
        {
          if($this->significantCharacterCount <= mb_strlen($keyword,'UTF-8'))
            $keywords[] = array('search'=>$keyword,'clang'=>$langID);
        }
        
        $articleData['teaser'] = $insert->escape($this->getTeaserText($plaintext));
        
        $insert->setTable($this->tablePrefix.'587_searchindex');
        $insert->setValues($articleData);
        $insert->insert();
        
        $this->endFrontendMode();
        
        $return[$langID] = A587_ART_GENERATED;
      }
    }
    
    $this->storeKeywords($keywords, false);
    
    return $return;
  }
  
  
  /**
    * Indexes a certain column.
    * Returns the number of the indexed rows or false.
    * 
    * @param string $_table
    * @param mixed $_column
    * @param mixed $_idcol
    * @param mixed $_id
    * @param mixed $_start
    * @param mixed $_count
    * 
    * @return mixed
    */
  function indexColumn($_table, $_column, $_idcol = false, $_id = false, $_start = false, $_count = false, $_where = false)
  {
    $delete = new rex_sql();
    
    $where = sprintf(" `ftable` = '%s' AND `fcolumn` = '%s' AND `texttype` = 'db_column'",$delete->escape($_table),$delete->escape($_column));
    //if(is_string($_idcol) AND ($_id !== false))
      //$where .= sprintf(' AND fid = %d',$_id);
    
    // delete from cache
    $select = new rex_sql();
    $select->setTable($this->tablePrefix.'587_searchindex');
    $select->setWhere($where);
    $indexIds = array();
    if($select->select('id'))
    {
      foreach($select->getArray() as $result)
        $indexIds[] = $result['id'];
      $this->deleteCache($indexIds);
    }
    
    // delete old data
    if($_start === 0)
    {
      $delete->setTable($this->tablePrefix.'587_searchindex');
      $delete->setWhere($where);
      $delete->delete();
    }
    
    $sql = new rex_sql();
    
    // get primary key column(s)
    $primaryKeys = array();
    foreach($sql->getArray("SHOW COLUMNS FROM `".$_table."` WHERE `KEY` = 'PRI'") as $col)
      $primaryKeys[] = $col['Field'];
    
    // index column
    $sql->flush();
    $sql->setTable($_table);
    $where = '1 ';
    if(is_string($_idcol) AND $_id)
      $where .= sprintf(' AND (%s = %d)', $_idcol, $_id);
    
    if(!empty($_where) AND is_string($_where))
      $where .= ' AND ('.$_where.')';
    
    if(is_numeric($_start) AND is_numeric($_count))
      $where .= ' LIMIT '.$_start.','.$_count;
    
    $sql->setWhere($where);
    $count = false;
    if($sql->select('*'))
    {
      $this->beginFrontendMode();
      
      $count = 0;
      
      $keywords = array();
      
      foreach($sql->getArray() as $value)
      {
        if(!empty($value[$_column]) AND ($this->indexOffline OR $this->tablePrefix.'article' != $_table OR $value['status'] == '1') AND ($this->tablePrefix.'article' != $_table OR !in_array($value['id'],$this->excludeIDs)))
        {
          $insert = new rex_sql();
          $indexData = array();
          
          $indexData['texttype'] = 'db_column';
          $indexData['ftable'] = $_table;
          $indexData['fcolumn'] = $_column;
          
          if(array_key_exists('clang',$value))
            $indexData['clang'] = $value['clang'];
          else
            $indexData['clang'] = NULL;
          
          $indexData['fid'] = NULL;
          if(is_string($_idcol) AND array_key_exists($_idcol,$value))
          {
            $indexData['fid'] = $value[$_idcol];
          }
          elseif($_table == $this->tablePrefix.'article')
          {
            $indexData['fid'] = $value['id'];
          }
          elseif(count($primaryKeys) == 1)
          {
            $indexData['fid'] = $value[$primaryKeys[0]];
          }
          elseif(count($primaryKeys))
          {
            $fids = array();
            foreach($primaryKeys as $pk)
              $fids[$pk] = $value[$pk];
            $indexData['fid'] = json_encode($fids);
          }
          
          if(is_null($indexData['fid']))
            $indexData['fid'] = $this->getMinFID();
          
          if(array_key_exists('re_id',$value))
          {
            $indexData['catid'] = $value['re_id'];
            if($_table == $this->tablePrefix.'article')
              $indexData['catid'] = intval($value['startpage']) ? $value['id'] : $value['re_id'];
          }
          elseif(array_key_exists('category_id',$value))
            $indexData['catid'] = $value['category_id'];
          else
            $indexData['catid'] = NULL;
          
          $additionalValues = array();
          foreach($this->includeColumns[$_table] as $col)
          {
            $additionalValues[$col] = $value[$col];
          }
          $indexData['values'] = $insert->escape(serialize($additionalValues));
          
          $indexData['unchangedtext'] = $insert->escape((string) $value[$_column]);
          $indexData['plaintext'] = $insert->escape($plaintext = $this->getPlaintext($value[$_column]));
          
          foreach(preg_split($this->encodeRegex('~[[:punct:][:space:]]+~ism'), $plaintext) as $keyword)
          {
            if($this->significantCharacterCount <= mb_strlen($keyword,'UTF-8'))
              $keywords[] = array('search'=>$keyword,'clang'=>is_null($indexData['clang'])?false:$indexData['clang']);
          }
          
          $indexData['teaser'] = '';
          if($this->tablePrefix.'article' == $_table)
          {
            $rex_article = new rex_article(intval($value['id']), intval($value['clang']));
            $teaser = true;
            $article_content_file = $this->generatedPath.'/articles/'.intval($value['id']).'.'.intval($value['clang']).'.content';
            if(!file_exists($article_content_file))
            {
              include_once ($this->includePath."/functions/function_rex_generate.inc.php");
              $generated = rex_generateArticleContent(intval($value['id']), intval($value['clang']));
              if($generated !== true)
              {
                $teaser = false;
                continue;
              }
            }
            
            if(file_exists($article_content_file) AND preg_match($this->encodeRegex('~(header\s*\(\s*["\']\s*Location\s*:)|(rex_redirect\s*\()~is'), rex_get_file_contents($article_content_file)))
            {
              $teaser = false;
            }
            
            $indexData['teaser'] = $teaser ? $insert->escape($this->getTeaserText($this->getPlaintext($rex_article->getArticle()))) : '';
          }
          
          $insert->setTable($this->tablePrefix.'587_searchindex');
          $insert->setValues($indexData);
          $insert->insert();
          
          $count++;
        }
      }
      
      $this->storeKeywords($keywords, false);
      
      $this->endFrontendMode();
    }
    else
    {
      return false;
    }
    
    return $count;
  }
  
  
  /**
    * Indexes a certain file.
    * Returns A587_FILE_GENERATED or an error code.
    * 
    * @param string $_filename
    * @param mixed $_clang
    * @param mixed $_doPlaintext
    * @param mixed $_articleData
    * 
    * @return mixed
    */
  function indexFile($_filename, $_doPlaintext = false, $_clang = false, $_fid = false, $_catid = false)
  {
    // extract file-extension
    $filenameArray = explode('.', $_filename);
    $fileext = $filenameArray[count($filenameArray) - 1];
    
    // check file-extension
    if((!in_array($fileext, $this->fileExtensions) AND !empty($this->fileExtensions)) AND !$this->indexUnknownFileExtensions AND !$this->indexMissingFileExtensions)
      return A587_FILE_FORBIDDEN_EXTENSION;
    
    // delete cache
    $delete = new rex_sql();
    
    $where = sprintf(" `filename` = '%s' AND `texttype` = 'file'", $delete->escape($_filename));
    if(is_int($_clang))
      $where .= sprintf(' AND clang = %d',$_clang);
    if(is_int($_fid))
      $where .= sprintf(' AND fid = %d',$_fid);
    elseif(is_array($_fid))
      $where .= sprintf(" AND fid = '%s'",$delete->escape(json_encode($_fid)));
    if(is_int($_catid))
      $where .= sprintf(' AND catid = %d',$_catid);
    
    // delete from cache
    $select = new rex_sql();
    $select->setTable($this->tablePrefix.'587_searchindex');
    $select->setWhere($where);
    $indexIds = array();
    if($select->select('id'))
    {
      foreach($select->getArray() as $result)
        $indexIds[] = $result['id'];
      $this->deleteCache($indexIds);
    }
    
    // delete old data
    $delete->setTable($this->tablePrefix.'587_searchindex');
    $delete->setWhere($where);
    $delete->delete();
    
    // index file
    $text = '';
    $plaintext = '';
    
    switch($fileext)
    {
      // pdf-files
      case 'pdf':
        
        // try XPDF
        $return = 0;
        $xpdf = false;
        $error = false;
        
        if(function_exists('exec'))
        {
          $tempFile = tempnam($this->generatedPath.'/files/', 'rexsearch');
          
          $encoding = 'UTF-8';
          
          exec('pdftotext '.escapeshellarg($this->documentRoot.'/'.$_filename).' '.escapeshellarg($tempFile).' -enc '.$encoding, $dummy, $return);
          if($return > 0)
          {
            if($return == 1)
              $error = A587_FILE_XPDFERR_OPENSRC;
            if($return == 2)
              $error = A587_FILE_XPDFERR_OPENDEST;
            if($return == 3)
              $error = A587_FILE_XPDFERR_PERM;
            if($return == 99)
              $error = A587_FILE_XPDFERR_OTHER;
          }
          else
          {
            if(false === $text = @file_get_contents($tempFile))
              $error = A587_FILE_NOEXIST;
            else
              $xpdf = true;
          }
          
          unlink($tempFile);
        }
        
        if(!$xpdf)
        {
          // if xpdf returned an error, try pdf2txt via php
          if(false === $pdfContent = @file_get_contents($this->documentRoot.'/'.$_filename))
            $error = A587_FILE_NOEXIST;
          else
          {
            require_once 'class.pdf2txt.inc.php';
            $text = pdf2txt::directConvert($pdfContent);
            $error = false;
          }
        }
        
        if($error !== false)
          return $error;
        elseif(trim($text) == '')
          return A587_FILE_EMPTY;
        
        $plaintext = $this->getPlaintext($text);
      break;
      
      // html- or php-file
      case 'htm':
      case 'html':
      case 'php':
        if(false === $text = @file_get_contents($this->documentRoot.'/'.$_filename))
          return A587_FILE_NOEXIST;
        
        $plaintext = $this->getPlaintext($text);
      
      // other filetype
      default:
        if(false === $text = @file_get_contents($this->documentRoot.'/'.$_filename))
          return A587_FILE_NOEXIST;
    }
    
    $text = @iconv(mb_detect_encoding($text), 'UTF-8', $text); 
    
    // Plaintext
    if(empty($plaintext))
    {
      if($_doPlaintext)
        $plaintext = $this->getPlaintext($text);
      else
        $plaintext = $text;
    }
    
    // index file-content
    $insert = new rex_sql();
    
    $fileData['texttype'] = 'file';
    if($_fid !== false)
      $fileData['ftable'] = $this->tablePrefix.'file';
    $fileData['filename'] = $insert->escape($_filename);
    $fileData['fileext'] = $insert->escape($fileext);;
    if($_clang !== false)
      $fileData['clang'] = intval($_clang);
    if($_fid !== false)
      $fileData['fid'] = intval($_fid);
    else
      $fileData['fid'] = NULL;
          
    if(is_null($fileData['fid']))
      $fileData['fid'] = $this->getMinFID();
    
    if($_catid !== false)
      $fileData['catid'] = intval($_catid);
    $fileData['unchangedtext'] = $insert->escape($text);
    $fileData['plaintext'] = $insert->escape($plaintext);
    
    $keywords = array();
    foreach(preg_split($this->encodeRegex('~[[:punct:][:space:]]+~ism'), $plaintext) as $keyword)
    {
      if($this->significantCharacterCount <= mb_strlen($keyword,'UTF-8'))
        $keywords[] = array('search'=>$keyword,'clang'=>!isset($fileData['clang'])?false:$fileData['clang']);
    }
    $this->storeKeywords($keywords, false);
    
    $fileData['teaser'] = $insert->escape($this->getTeaserText($plaintext));
    
    $insert->setTable($this->tablePrefix.'587_searchindex');
    $insert->setValues($fileData);
    $insert->insert();
    
    return A587_FILE_GENERATED;
  }
  
  function getMinFID()
  {
    $minfid_sql = new rex_sql();
    $minfid_result = $minfid_sql->getArray('SELECT MIN(CONVERT(fid, SIGNED)) as minfid FROM `'.$this->tablePrefix.'587_searchindex`');
    $minfid = intval($minfid_result[0]['minfid']);
    
    return ($minfid < 0) ? --$minfid : -1;
  }
  
  
  /**
    * Excludes an article from the index.
    * 
    * @param int $_id
    * @param mixed $_clang
    */
  function excludeArticle($_id,$_clang = false)
  {
    // exclude article
    $art_sql = new rex_sql();
    $art_sql->setTable($this->tablePrefix.'587_searchindex');
    
    $where = "fid = ".intval($_id)." AND texttype='article'";
    if($_clang !== false)
      $where .= " AND clang='".intval($_clang)."'";
    
    $art_sql->setWhere($where);
    
    $art_sql->delete();
    
    // delete from cache
    $select = new rex_sql();
    $select->setTable($this->tablePrefix.'587_searchindex');
    $select->setWhere($where);
    $select->select('id');
    
    $indexIds = array();
    foreach($select->getArray() as $result)
      $indexIds[] = $result['id'];
    $this->deleteCache($indexIds);
  }
  
  
  /**
    * Deletes the complete search index.
    * 
    */
  function deleteIndex()
  {
    $delete = new rex_sql();
    $delete->setTable($this->tablePrefix.'587_searchindex');
    $delete->delete();
    
    $this->deleteCache();
  }
  
  
  /**
    * Sets the surround-tags for found keywords.
    * 
    * Expects either the start- and the end-tag
    * or an array with both tags.
    */
  function setSurroundTags($_tags, $_endtag = false)
  {
    if(is_array($_tags) AND $_endtag === false)
      $this->surroundTags = $_tags;
    else
      $this->surroundTags = array((string) $_tags, (string) $_endtag);
    
    $this->hashMe .= $this->surroundTags[0].$this->surroundTags[1];
  }
  
  
  /**
    * Sets the maximum count of results.
    * 
    * Expects either the start- and the count-limit
    * or an array with both limits
    * or only the count-limit.
    *
    * example method calls:
    * setLimit(10,10);       // start with 10th result
    * setLimit(20);          // maximum of 20 results starting with the first result
    * setLimit(array(0,20)); // maximum of 20 results starting with the first result
    */
  function setLimit($_limit, $_countLimit = false)
  {
    if(is_array($_limit) AND $_countLimit === false)
      $this->limit = array((int) $_limit[0], (int) $_limit[1]);
    elseif($_countLimit === false)
      $this->limit = array(0, (int) $_limit);
    else
      $this->limit = array((int) $_limit, (int) $_countLimit);
    
    $this->hashMe .= $this->limit[0].$this->limit[1];
  }
  
  
  /**
    * Sets words, which must not be found.
    * 
    * Expects an array with the words as parameters.
    */
  function setBlacklist($_words)
  {
    foreach($_words as $key => $word)
    {
      $this->blacklist[] = $tmpkey = (string) ($this->ci?strtolower($word):$word);
      $this->hashMe .= $tmpkey;
    }
  }
  
  
  /**
    * Exclude Articles with the transfered IDs.
    * 
    * Expects an array with the IDs as parameters.
    */
  function setExcludeIDs($_ids)
  {
    foreach($_ids as $key => $id)
    {
      $this->excludeIDs[] = intval($id);
    }
    
    $this->excludeIDs = array_unique($this->excludeIDs);
  }
  
  
  /**
    * Sets the IDs of the articles which are only to be searched through.
    * 
    * Expects an array with the IDs as parameters.
    */
  function searchInArticles($_ids)
  {
    $this->setSearchInIDs(array('articles' => $_ids));
  }
  
  
  /**
    * Sets the IDs of the categories which are only to be searched through.
    * 
    * Expects an array with the IDs as parameters.
    */
  function searchInCategories($_ids)
  {
    $this->setSearchInIDs(array('categories' => $_ids));
  }
      
      
  /**
    * Sets the IDs of the mediapool-categories which are only to be searched through.
    * 
    * Expects an array with the IDs as parameters.
    */
  function searchInFileCategories($_ids)
  {
    $this->setSearchInIDs(array('filecategories' => $_ids));
  }
      
      
  /**
    * Sets the columns which only should be searched through.
    * 
    * @param string $_table
    * @param string $_column
    */
  function searchInDbColumn($_table, $_column)
  {
    $this->setSearchinIDs(array('db_columns' => array($_table => array($_column))));
  }
  
  
  /**
    * Sets the columns which should be indexed.
    * 
    * @param array $_columns
    */
  function setIncludeColumns($_columns)
  {
    $this->includeColumns = $_columns;
  }
  
  
  function setWhere($_where)
  {
    $this->where = $_where;
    $this->hashMe .= $_where;
  }
  
  
  /** 
    * Sets the mode of how the keywords are logical connected.
    * 
    * Are the keywords to be connected conjunctional or disjunctional?
    * Has each single keyword to be found or is one single keyword sufficient?
    * 
    * @param string $_logicalMode
    * 
    * @return bool
    */
  function setLogicalMode($_logicalMode)
  {
    switch(strtolower($_logicalMode))
    {
      case 'and':
      case 'konj':
      case 'strict':
      case 'sharp':
        $this->logicalMode = ' AND ';
      break;
      
      case 'or':
      case 'disj':
      case 'simple':
      case 'fuzzy':
        $this->logicalMode = ' OR ';
      break;
      
      default:
        $this->logicalMode = ' AND ';
        return false;
    }
    
    $this->hashMe .= $this->logicalMode;
    
    return true;
  }
  
  
  /** 
    * Sets the mode concerning which text is to be searched through.
    * 
    * You can choose between the original text, the plain text or both texts.
    * 
    * @param string $_textMode
    * 
    * @return bool
    */
  function setTextMode($_textMode)
  {
    switch(strtolower($_textMode))
    {
      case 'html':
      case 'xhtml':
      case 'unmodified':
      case 'original':
        $this->textMode = 'unmodified';
      break;
      
      case 'text':
      case 'plain':
      case 'stripped':
      case 'bare':
      case 'simple':
        $this->textMode = 'plain';
      break;
      
      case 'both':
      case 'all':
        $this->textMode = 'both';
      break;
      
      default:
        return false;
    }
    
    $this->hashMe .= $this->textMode;
    
    return true;
  }
  
  
  /** 
    * Sets the MySQL search mode.
    * 
    * You can choose between like and match
    * 
    * @param string $_searchMode
    * 
    * @return bool
    */
  function setSearchMode($_searchMode)
  {
    switch(strtolower($_searchMode))
    {
      case 'like':
      case 'match':
        $this->searchMode = strtolower($_searchMode);
      break;
      
      default:
        return false;
    }
    
    $this->hashMe .= $this->searchMode;
    
    return true;
  }
  
  
  /** 
    * Sets the sort order of the results.
    * 
    * The parameter has to be an array with the columns as keys
    * and the direction (DESC or ASC) as value (e.g.: array['COLUMN'] = 'ASC').
    * 
    * @param array $_order
    * 
    * @return bool
    */
  function setOrder($_order)
  {
    if(!is_array($_order))
    {
      error('Wrong parameter. Expecting an array',E_USER_WARNING);
      return false;
    }
    
    $i = 0;
    $dir2upper = '';
    $col2upper = '';
    foreach($_order as $col => $dir)
    {
      $i++;
      if('RELEVANCE_587' == ($col2upper = strtoupper((string)$col)))
      {
        error(sprintf('Column %d must not be named "RELEVANCE_587". Column %d is ignored for the sort order',$i,$i));
      }
      else
      {
        if(!in_array($dir2upper = strtoupper((string)$dir), array('ASC','DESC')))
        {
          error(sprintf('Column %d has no correct sort order (ASC or DESC). Descending (DESC) sort order is assumed',$i));
          $dir2upper = 'DESC';
        }
        
        $this->order[$col2upper] = $dir2upper;
        $this->hashMe .= $col2upper.$dir2upper;
      }
    }
    
    return true;
  }
  
  
  /** 
    * Sets the type of the text with the highlighted keywords.
    * 
    * @param string $_type
    * 
    * @return bool
    */
  function setHighlightType($_type)
  {
    switch($_type)
    {
      case 'sentence':
      case 'paragraph':
      case 'surroundtext':
      case 'surroundtextsingle':
      case 'teaser':
      case 'array':
        $this->highlightType = $_type;
        return true;
      break;
      
      default:
        $this->highlightType = 'surroundtextsingle';
        return false;
    }
    
    $this->hashMe .= $this->highlightType;
  }


  /** 
    * Converts the search string to an array.
    * 
    * Returns the number of search terms.
    * 
    * @param string $_searchString
    * 
    * @return int
    */
  function parseSearchString($_searchString)
  {
    // reset searchArray
    $this->searchArray = array();
    
    $matches = array();
    preg_match_all($this->encodeRegex('~(?:(\+*)"([^"]*)")|(?:(\+*)(\S+))~is'), $_searchString, $matches, PREG_SET_ORDER);
    
    $count = 0;
    $replaceValues = array();
    $sql = new rex_sql();
    foreach($matches as $match)
    {
      if(count($match) == 5)
      {
				// words without double quotes (foo)
        $word = $match[4];
        $plus = $match[3];
			}
			elseif(!empty($match[2]))
			{
				// words with double quotes ("foo bar")
				$word = $match[2];
				$plus = $match[1];
			}
			else
			{
				continue;
			}
			
			$notBlacklisted = true;
			// blacklisted words are excluded
			foreach($this->blacklist as $blacklistedWord)
			{
				if(preg_match($this->encodeRegex('~\b'.preg_quote($blacklistedWord,'~').'\b~is'), $word))
				{
					$this->blacklisted[] = array($blacklistedWord => $word);
					$notBlacklisted = false;
				}
			}
			
			if($notBlacklisted)
			{
				// whitelisted words get extra weighted
				$this->searchArray[$count] = array( 'search' => $word,
																						'weight' => strlen($plus) + 1 + (array_key_exists($word,$this->whitelist)?$this->whitelist[$word]:0),
																						'clang' => $this->clang
				);
				
				$count++;
			}
    }
    
    return $count;
  }
  
  
  /** 
    * Which words are important?
    * 
    * This method adds weight to special words.
    * If an word already exists, the method adds the weight.
    * Expects an array with the keys containing the words
    * and the values containing the weight to add.
    * 
    * @param array $_whitelist
    * 
    * 
    */
  function addWhitelist($_whitelist)
  {
    foreach($_whitelist as $word => $weight)
    {
      $key = (string)($this->ci?strtolower($word):$word);
      $this->hashMe .= $key;
      $this->whitelist[$key] = intval($this->whitelist[$key]) + intval($weight);
    }
  }
  
  
  /** 
    * Case sensitive or case insensitive?
    * 
    * @param bool $_ci
    * 
    * @ignore
    */
  function setCaseInsensitive($_ci = true)
  {
    setCI($_ci);
  }
  
  
  /** 
    * Case sensitive or case insensitive?
    * 
    * @param bool $_ci
    * 
    * @ignore
    */
  function setCI($_ci = true)
  {
    $this->ci = (bool) $_ci;
  }
  
  
  /** 
    * Sets the language-Id.
    * 
    * @param mixed $_clang
    * 
    * 
    */
  function setClang($_clang)
  {
    if($_clang === false)
      $this->clang = false;
    else
      $this->clang = intval($_clang);
    
    $this->hashMe .= $_clang;
  }
  
  
  /** 
    * Strips the HTML-Tags from a text and replaces them with spaces or line breaks
    * 
    * @param string $_text
    * 
    * @return string
    */
  function getPlaintext($_text)
  {
    $process = true;
    $extensionReturn = rex_register_extension_point('A587_PLAINTEXT', $_text);
    if(is_array($extensionReturn))
    {
      $_text = $extensionReturn['text'];
      
      $process = !empty($extensionReturn['process']);
    }
    elseif(is_string($extensionReturn))
      $_text = $extensionReturn;
    
    if($process)
    {
      $tags2nl = $this->encodeRegex('~</?(address|blockquote|center|del|dir|div|dl|fieldset|form|h1|h2|h3|h4|h5|h6|hr|ins|isindex|menu|noframes|noscript|ol|p|pre|table|ul)[^>]+>~si');
      $_text = trim(strip_tags(preg_replace(array($this->encodeRegex('~<(head|script).+?</(head|script)>~si'), $tags2nl, $this->encodeRegex('~<[^>]+>~si'), $this->encodeRegex('~[\n\r]+~si'), $this->encodeRegex('~[\t ]+~si')), array('',"\n",' ',"\n",' '), $_text)));
    }
    
    return $_text;
  }
  
  
  /** 
    * According to the highlight-type this method will return a string or an array.
    * Found keywords will be highlighted with the surround-tags.
    * 
    * @param string $_text
    * 
    * @return mixed
    */
  function getHighlightedText($_text)
  {
    $tmp_searchArray = $this->searchArray;
    
    if($this->searchEntities)
    {
      foreach($this->searchArray as $keyword)
      {
        $this->searchArray[] = array('search' => htmlentities($keyword['search'], ENT_COMPAT, 'UTF-8'));
      }
    }
    
    switch($this->highlightType)
    {
      case 'sentence':
      case 'paragraph':
        // split text at punctuation marks
        if($this->highlightType == 'sentence')
          $regex = '~(\!|\.|\?|[\n]+)~si';
        
          
        // split text at line breaks
        if($this->highlightType == 'paragraph')
          $regex = '~([\r\n])~si';
          
        $Apieces = preg_split($this->encodeRegex($regex), $_text, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $search = array();
        $replace = array();
        foreach($this->searchArray as $keyword)
        {
          $search[] = preg_quote($keyword['search'],'~');
          $replace[] = $this->encodeRegex('~'.preg_quote($keyword['search'],'~').'~is');
        }
        
        $i = 0;
        for($i = 0; $i < count($Apieces); $i++)
          if(preg_match($this->encodeRegex('~('.implode('|',$search).')~is'), $Apieces[$i]))
            break;
        
        $return = '';
        if($i < count($Apieces))
          $return .= $Apieces[$i];
        
        $cutted = array();
        preg_match($this->encodeRegex('~^.*?('.implode('|',$search).').{0,'.$this->maxHighlightedTextChars.'}~ims'), $return, $cutted);
        
        $needEllipses = false;
        if(strlen($cutted[1]) != strlen($return))
          $needEllipses = true;
        
        $return = preg_replace($replace, $this->surroundTags[0].'$0'.$this->surroundTags[1], substr($cutted[0],0,strrpos($cutted[0],' ')));
        
        if($needEllipses)
          $return .= ' '.$this->ellipsis;
        
        return $return;
      break;
      
      case 'surroundtext':
      case 'surroundtextsingle':
      case 'array':
        $startEllipsis = false;
        $endEllipsis = false;
        $Ahighlighted = array();
        $_text = preg_replace('~\s+~', ' ', $_text);
        $replace = array();
        foreach($this->searchArray as $keyword)
          $replace[] = $this->encodeRegex('~'.preg_quote($keyword['search'],'~').'~is');
        
        $strlen = mb_strlen($_text);
        $positions = array();
        for($i = 0; $i < count($this->searchArray); $i++)
        {
          $hits = array();
          $offset = 0;
          preg_match_all($this->encodeRegex('~((.{0,'.$this->maxHighlightedTextChars.'})'.preg_quote($this->searchArray[$i]['search'],'~').'(.{0,'.$this->maxHighlightedTextChars.'}))~ims'), $_text, $hits, PREG_SET_ORDER);
          
          foreach($hits as $hit)
          {
            $offset = strpos($_text, $hit[0], $offset) + 1;
            $currentposition = ceil(intval(($offset - 1) / (2 * $this->maxHighlightedTextChars)));
            
            if($this->highlightType == 'array' AND !array_key_exists($this->searchArray[$i]['search'], $Ahighlighted))
              $Ahighlighted[$this->searchArray[$i]['search']] = array();
            
            if(trim($hit[1]) != '')
            {
              $surroundText = $hit[1];
              
              if(strlen($hit[2]) > 0 AND false !== strpos($hit[2], ' '))
                $surroundText = substr($surroundText, strpos($surroundText, ' '));
              
              if(strlen($hit[3]) > 0 AND false !== strpos($hit[3], ' '))
                $surroundText = substr($surroundText, 0, strrpos($surroundText,' '));
              
              if($i == 0 AND strlen($hit[2]) > 0)
                $startEllipsis = true;
              
              if($i == (count($this->searchArray) - 1) AND strlen($hit[3]) > 0)
                $endEllipsis = true;
              
              if($this->highlightType == 'array')
                $Ahighlighted[$this->searchArray[$i]['search']][] = preg_replace($replace, $this->surroundTags[0].'$0'.$this->surroundTags[1], trim($surroundText));
              else if(!in_array($currentposition, $positions))
                $Ahighlighted[] = trim($surroundText);
              
              $positions[] = $currentposition;
              
              if($this->highlightType == 'surroundtextsingle')
                break;
            }
          }
        }
        
        if($this->highlightType == 'array')
          return $Ahighlighted;
        
        $return = implode(' '.$this->ellipsis.' ', $Ahighlighted);
        
        if($startEllipsis)
          $return = $this->ellipsis.' '.$return;
        
        if($endEllipsis)
          $return = $return.' '.$this->ellipsis;
        
        $return = preg_replace($replace, $this->surroundTags[0].'$0'.$this->surroundTags[1], $return);
        
        return $return;
      break;
      
      case 'teaser':
        $search = array();
        foreach($this->searchArray as $keyword)
          $search[] = $this->encodeRegex('~'.preg_quote($keyword['search'],'~').'~is');

        return preg_replace($search, $this->surroundTags[0].'$0'.$this->surroundTags[1], $this->getTeaserText($_text));
      break;
    }
    
    $this->searchArray = $tmp_searchArray;
  }
  
  
  /** 
    * Gets the teaser of a text.
    * 
    * @param string $_text
    * 
    * @return string
    */
  function getTeaserText($_text)
  {
    $i = 0;
    $textArray = preg_split($this->encodeRegex('~\s+~si'), $_text, $this->maxTeaserChars);
    
    $return = '';
    $aborted = false;
    foreach($textArray as $word)
    {
      if((($strlen = strlen($word)) + $i) > $this->maxTeaserChars)
      { $aborted = true;
        break;
      }
      
      $return .= $word.' ';
      $i += $strlen + 1;
    }
    
    if($aborted)
      $return .= $this->ellipsis;
    
    return $return;
  }
  
  
  /** 
    * Returns if a search term is already cached.
    * The cached result will be stored in $this->cachedArray.
    * 
    * @param string $_search
    * 
    * @return bool
    */
  function isCached($_search)
  {
    $sql = new rex_sql();
    $sql->setTable($this->tablePrefix.'587_searchcache');
    $sql->setWhere(sprintf("hash = '%s'",$this->cacheHash($_search)));
    
    if($sql->select('returnarray'))
    {
      foreach($sql->getArray() as $value)
      {
        return false !== ($this->cachedArray = unserialize($value['returnarray']));
      }
    }
    
    return false;
  }
  
  
  /** 
    * Calculates the cache hash.
    * 
    * @param string $_searchString
    * 
    * @return string
    */
  function cacheHash($_searchString)
  {
    return md5($_searchString.$this->hashMe);
  }
  
  
  /** 
    * Stores a search result in the cache.
    * 
    * @param string $_result
    * @param array $_indexIds
    * 
    * @return bool
    */
  function cacheSearch($_result, $_indexIds)
  {
    $sql = new rex_sql();
    $sql->setTable($this->tablePrefix.'587_searchcache');
    $sql->setValues(array(
        'hash' => $this->cacheHash($this->searchString),
        'returnarray' => $sql->escape($_result)
      )
    );
    $sql->insert();
    $lastId = $sql->getLastId();
    
    $Ainsert = array();
    foreach($_indexIds as $id)
    {
      $Ainsert[] = sprintf('(%d,%d)',$id,$lastId);
    }
    
    $sql2 = new rex_sql();
    return $sql2->setQuery(
      sprintf(
        'INSERT INTO `%s` (index_id,cache_id) VALUES
        %s;',
        $this->tablePrefix.'587_searchcacheindex_ids',
        implode(',',$Ainsert)
      )
    );
  }
  
  
  /** 
    * Truncates the cache or deletes all data that are concerned with the given index-ids.
    * 
    * @param mixed $_indexIds
    * 
    * 
    */
  function deleteCache($_indexIds = false)
  {
    if($_indexIds === false)
    {
      // delete entire search-chache
      $delete = new rex_sql();
      $delete->setTable($this->tablePrefix.'587_searchcacheindex_ids');
      $delete->delete();
      $delete2 = new rex_sql();
      $delete2->setTable($this->tablePrefix.'587_searchcache');
      $delete2->delete();
    }
    elseif(is_array($_indexIds) AND !empty($_indexIds))
    {
      $sql = new rex_sql();
      
      $query = sprintf('
        SELECT cache_id
        FROM %s
        WHERE index_id IN (%s)',
        $this->tablePrefix.'587_searchcacheindex_ids',
        implode(',',$_indexIds)
      );
      
      $deleteIds = array(0);
      foreach($sql->getArray($query) as $cacheId)
        $deleteIds[] = $cacheId['cache_id'];
      
      // delete from search-cache where indexed IDs exist
      $delete = new rex_sql();
      $delete->setTable($this->tablePrefix.'587_searchcache');
      $delete->setWhere('id IN ('.implode(',',$deleteIds).')');
      $delete->delete();
      
      // delete the cache-ID and index-ID
      $delete2 = new rex_sql();
      $delete2->setTable($this->tablePrefix.'587_searchcacheindex_ids');
      $delete2->setWhere('cache_id IN ('.implode(',',$deleteIds).')');
      $delete2->delete();
      
      // delete all cached searches which had no result (because now they maybe will have)
      $delete3 = new rex_sql();
      $delete3->setTable($this->tablePrefix.'587_searchcache');
      $delete3->setWhere(sprintf('id NOT IN (SELECT cache_id FROM `%s`)',$this->tablePrefix.'587_searchcacheindex_ids'));
      $delete3->delete();
    }
  }
  
  
  function storeKeywords($_keywords, $_doCount = true)
  {
    // store similar words
    $simWordsSQL = new rex_sql();
    $simWords = array();
    foreach($_keywords as $keyword)
    {
      if(
        !in_array(mb_strtolower($keyword['search'], 'UTF-8'), $this->blacklist) AND
        !in_array(mb_strtolower($keyword['search'], 'UTF-8'), $this->stopwords)
      )
      {
        $simWords[] = sprintf(
          "('%s', '%s', '%s', '%s', %s)",
          $simWordsSQL->escape($keyword['search']),
          ($this->similarwordsMode & A587_SIMILARWORDS_SOUNDEX)?soundex($keyword['search']):'',
          ($this->similarwordsMode & A587_SIMILARWORDS_METAPHONE)?metaphone($keyword['search']):'',
          ($this->similarwordsMode & A587_SIMILARWORDS_COLOGNEPHONE)?$this->cologne_phone($keyword['search']):'',
          (isset($keyword['clang']) AND $keyword['clang']!==false)?$keyword['clang']:'-1'
        );
      }
    }
    
    if(!empty($simWords))
    {
      $simWordsSQL->setQuery(
        sprintf("
          INSERT INTO `%s`
          (keyword, soundex, metaphone, colognephone, clang)
          VALUES
          %s
          ON DUPLICATE KEY UPDATE count = count + %d",
          $this->tablePrefix.'587_keywords',
          implode(',', $simWords),
          $_doCount ? 1 : 0
        )
      );
    }
  }
  
  
  function deleteKeywords()
  {
    $kw_sql = new rex_sql();
    return $kw_sql->setQuery(sprintf('TRUNCATE TABLE `%s`', $this->tablePrefix.'587_keywords'));
  }
  
  
  /** 
    * Executes the search.
    * 
    * @param string $_search
    * 
    * @return array
    */
  function search($_search)
  {
    $startTime = microtime(true);
    $this->searchString = trim(stripslashes($_search));
    
    $keywordCount = $this->parseSearchString($this->searchString);
    
    if(empty($this->searchString) OR empty($this->searchArray))
    {
      return array(
        'count' => 0,
        'hits' => array(),
        'keywords' => array(),
        'keywords' => '',
        'sql' => 'No search performed.',
        'blacklisted' => false,
        'hash' => '',
        'simwordsnewsearch' => '',
        'simwords' => array(),
        'time' => 0
      );
    }
    
    // ask cache
    if($this->cache AND $this->isCached($this->searchString))
    {
      $this->cachedArray['time'] = microtime(true) - $startTime;
      
      if($this->similarwords AND $this->cachedArray['count'] > 0)
      {
        $this->storeKeywords($this->searchArray);
      }
      
      // EP registrieren
      rex_register_extension_point('A587_SEARCH_EXECUTED', $this->cachedArray);
      //var_dump($this->cachedArray['sql']);
      return $this->cachedArray;
    }
    
    $return = array();
    $return['simwordsnewsearch'] = '';
    $return['simwords'] = array();
    if($this->similarwords)
    {
      $simwords = array();
      foreach($this->searchArray as $keyword)
      {
        $sounds = array();
        if($this->similarwordsMode & A587_SIMILARWORDS_SOUNDEX)
          $sounds[] = "soundex = '".soundex($keyword['search'])."'";
        
        if($this->similarwordsMode & A587_SIMILARWORDS_METAPHONE)
          $sounds[] = "metaphone = '".metaphone($keyword['search'])."'";
        
        if($this->similarwordsMode & A587_SIMILARWORDS_COLOGNEPHONE)
          $sounds[] = "colognephone = '".$this->cologne_phone($keyword['search'])."'";
        
        $simwords[] = sprintf("
          SELECT
            GROUP_CONCAT(DISTINCT keyword SEPARATOR ' ') as keyword,
            '%s' AS typedin,
            SUM(count) as count
          FROM `%s`
          WHERE 1
            %s
            AND (%s)",
          $keyword['search'],
          $this->tablePrefix.'587_keywords',
          ($this->clang !== false) ? 'AND (clang = '.intval($this->clang).' OR clang IS NULL)' : '',
          implode(' OR ', $sounds)
        );
      }
      
      // simwords
      $simWordsSQL = new rex_sql();
      foreach($simWordsSQL->getArray(sprintf("
        %s
        GROUP BY %s
        ORDER BY SUM(count)",
        implode(' UNION ', $simwords),
        $this->similarwordsPermanent ? "''" : 'keyword, typedin'
        )
      ) as $simword)
      {
        $return['simwords'][$simword['typedin']] = array(
          'keyword' => $simword['keyword'],
          'typedin' => $simword['typedin'],
          'count' => $simword['count'],
        );
      }
      
      $newsearch = array();
      foreach($this->searchArray as $keyword)
      {
        if(preg_match($this->encodeRegex('~\s~is'), $keyword['search']))
          $quotes = '"';
        else
          $quotes = '';
        
        if(array_key_exists($keyword['search'], $return['simwords']))
        {
          $newsearch[] = $quotes.$return['simwords'][$keyword['search']]['keyword'].$quotes;
        }
        else
        {
          $newsearch[] = $quotes.$keyword['search'].$quotes;
        }
      }
      
      $return['simwordsnewsearch'] = implode(' ', $newsearch);
    }
    
    if($this->similarwordsPermanent)
      $keywordCount = $this->parseSearchString($this->searchString.' '.$return['simwordsnewsearch']);
    
    $searchColumns = array();
    switch($this->textMode)
    {
      case 'unmodified':
        $searchColumns[] = 'unchangedtext';
      break;
      
      case 'both':
        $searchColumns[] = 'plaintext';
        $searchColumns[] = 'unchangedtext';
      break;
      
      default:
        $searchColumns[] = 'plaintext';
    }
    
    $sql = new rex_sql();
    
    $Awhere = array();
    $Amatch = array();
    
    foreach($this->searchArray as $keyword)
    {
      // build MATCH-Array
      $match = sprintf("(( MATCH (`%s`) AGAINST ('%s')) * %d)", implode('`,`',$searchColumns), $sql->escape($keyword['search']), $keyword['weight']);
      
      
      if($this->searchEntities)
      {
        $match .= ' + '.sprintf("(( MATCH (`%s`) AGAINST ('%s')) * %d)", implode('`,`',$searchColumns), $sql->escape(htmlentities($keyword['search'], ENT_COMPAT, 'UTF-8')), $keyword['weight']);
      }
      
      $Amatch[] = $match;
      
      // build WHERE-Array
      if($this->searchMode == 'match')
      {
        $AWhere[] = $match;
      }
      else
      {
        $tmpWhere = array();
        foreach($searchColumns as $searchColumn)
        {
          $tmpWhere[] = sprintf("(`%s` LIKE '%%%s%%')", $searchColumn, str_replace(array('%','_'),array('\%','\_'),$sql->escape($keyword['search'])));
          
          if($this->searchEntities)
          {
            $tmpWhere[] = sprintf("(`%s` LIKE '%%%s%%')", $searchColumn, str_replace(array('%','_'),array('\%','\_'),$sql->escape(htmlentities($keyword['search'], ENT_COMPAT, 'UTF-8'))));
          }
        }
        
        $AWhere[] = '('.implode(' OR ',$tmpWhere).')';
      }
      
      /*if($this->logicalMode == ' AND ')
        $Awhere[] = '+*'.$keyword['search'].'*';
      else
        $AWhere[] = '*'.$keyword['search'].'*';*/
    }
    
    // build MATCH-String
    $match = '('.implode(' + ',$Amatch).' + 1)';
    
    // build WHERE-String
    $where = '('.implode($this->logicalMode,$AWhere).')';
    #$where = sprintf("( MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)) > 0",implode(',',$searchColumns),implode(' ',$Awhere));
    
    // language
    if($this->clang !== false)
      $where .= ' AND (clang = '.intval($this->clang).' OR clang IS NULL)';
    
    $AwhereToSearch = array();
    
    if(array_key_exists('articles',$this->searchInIDs) AND count($this->searchInIDs['articles']))
    {
      $AwhereToSearch[] = "texttype = 'article'";
      $AwhereToSearch[] = "(fid IN (".implode(',',$this->searchInIDs['articles'])."))";
    }
    
    if(array_key_exists('categories',$this->searchInIDs) AND count($this->searchInIDs['categories']))
    {
      $AwhereToSearch[] = "(catid IN (".implode(',',$this->searchInIDs['categories']).") AND ftable = '".$sql->escape($this->tablePrefix)."article')";
    }
    
    if(array_key_exists('filecategories',$this->searchInIDs) AND count($this->searchInIDs['filecategories']))
    {
      $AwhereToSearch[] = "(catid IN (".implode(',',$this->searchInIDs['filecategories']).") AND ftable = '".$sql->escape($this->tablePrefix)."file')";
    }
    
    if(array_key_exists('db_columns',$this->searchInIDs) AND count($this->searchInIDs['db_columns']))
    {
      $AwhereToSearch[] = "texttype = 'db_column'";
      
      $Acolumns = array();
      
      foreach($this->searchInIDs['db_columns'] as $table => $colArray)
      {
        foreach($colArray as $column)
        {
          //$Acolumns[] = sprintf("(ftable = '%s' AND fcolumn = '%s' %s)", $table, $column, $strSearchArticles);
          $Acolumns[] = sprintf("(ftable = '%s' AND fcolumn = '%s')", $table, $column);
        }
      }
      
      $AwhereToSearch[] = '('.implode(' OR ',$Acolumns).')';
    }
    
    if(count($AwhereToSearch))
    {
      if($this->searchArticles)
        $where .= " AND ((texttype = 'article') OR (".implode(' AND ',$AwhereToSearch).'))';
      else
        $where .= ' AND ('.implode(' AND ',$AwhereToSearch).')';
    }
    
    if(!empty($this->where))
      $where .= ' AND ('.$this->where.')';
    
    // build ORDER-BY-String
    $Aorder = array();
    foreach($this->order as $col => $dir)
      $Aorder[] = $col.' '.$dir;
    
    $selectFields = array();
    if($this->groupBy)
    {
      $selectFields[] = sprintf('(SELECT SUM%s FROM `%s` summe WHERE summe.fid = r1.fid AND summe.ftable = r1.ftable) AS RELEVANCE587', $match, $this->tablePrefix.'587_searchindex');
      $selectFields[] = sprintf('(SELECT COUNT(*) FROM `%s` summe WHERE summe.fid = r1.fid AND (summe.ftable IS NULL OR summe.ftable = r1.ftable) AND (summe.fcolumn IS NULL OR summe.fcolumn = r1.fcolumn) AND summe.texttype = r1.texttype) AS COUNT587', $this->tablePrefix.'587_searchindex');
    }
    else
    {
      $selectFields[] = $match.' AS RELEVANCE587';
    }
    
    $selectFields[] = '`id`';
    $selectFields[] = '`fid`';
    $selectFields[] = '`catid`';
    $selectFields[] = '`ftable`';
    $selectFields[] = '`fcolumn`';
    $selectFields[] = '`texttype`';
    $selectFields[] = '`clang`';
    $selectFields[] = '`unchangedtext`';
    $selectFields[] = '`plaintext`';
    $selectFields[] = '`teaser`';
    $selectFields[] = '`values`';
    $selectFields[] = '`filename`';
    $selectFields[] = '`fileext`';
    
    if($this->groupBy)
    {
      $query = sprintf('
        SELECT SQL_CALC_FOUND_ROWS %s
        FROM `%s` r1
        WHERE (%s) AND (
          (
            %s = (SELECT MAX%s FROM `%s` r2 WHERE r1.ftable = r2.ftable AND r1.fid = r2.fid %s)
            AND fid IS NOT NULL
          ) OR
          ftable IS NULL
        )
        GROUP BY ftable,fid,clang
        ORDER BY %s
        LIMIT %d,%d',
        
        implode(",\n",$selectFields),
        $this->tablePrefix.'587_searchindex',
        $where,
        $match,
        $match,
        $this->tablePrefix.'587_searchindex',
        ($this->clang !== false) ? 'AND (clang = '.intval($this->clang).' OR clang IS NULL)' : '',
        implode(",\n",$Aorder),
        $this->limit[0],$this->limit[1]
      );
    }
    else
    {
      $query = sprintf('
        SELECT SQL_CALC_FOUND_ROWS %s
        FROM `%s`
        WHERE %s
        ORDER BY %s
        LIMIT %d,%d',
        
        implode(",\n",$selectFields),
        $this->tablePrefix.'587_searchindex',
        $where,
        implode(",\n",$Aorder),
        $this->limit[0],$this->limit[1]
      );
    }
    #echo '<pre>'.$query.'</pre>';
    $sqlResult = $sql->getArray($query);
    
    $indexIds = array();
    $count = 0;
    
    $sqlResultCount = $sql->getArray('SELECT FOUND_ROWS() as count');
    $return['count'] = intval($sqlResultCount[0]['count']);
    
    // hits
    $return['hits'] = array();
    $i = 0;
    foreach($sqlResult as $hit)
    {
      $indexIds[] = $hit['id'];
      $return['hits'][$i] = array();
      $return['hits'][$i]['id'] = $hit['id'];
      $return['hits'][$i]['fid'] = $hit['fid'];
      if(!is_numeric($hit['fid']) AND !is_null($json_decode_fid = json_decode($hit['fid'], true)))
        $return['hits'][$i]['fid'] = $json_decode_fid;
      $return['hits'][$i]['table'] = $hit['ftable'];
      $return['hits'][$i]['column'] = $hit['fcolumn'];
      $return['hits'][$i]['type'] = $hit['texttype'];
      $return['hits'][$i]['clang'] = $hit['clang'];
      $return['hits'][$i]['unchangedtext'] = $hit['unchangedtext'];
      $return['hits'][$i]['plaintext'] = $hit['plaintext'];
      $return['hits'][$i]['teaser'] = $this->getTeaserText($hit['plaintext']);
      $return['hits'][$i]['highlightedtext'] = $this->getHighlightedText($hit['plaintext']);
      $return['hits'][$i]['article_teaser'] = $hit['teaser'];
      $return['hits'][$i]['values'] = a587_config_unserialize($hit['values']);
      $return['hits'][$i]['filename'] = $hit['filename'];
      $return['hits'][$i]['fileext'] = $hit['fileext'];
      $i++;
      
      if($this->groupBy)
        $count += $hit['COUNT587'];
    }
    
    if($this->groupBy)
    {
      $indexIds = array();
      foreach($sql->getArray(
          sprintf('
            SELECT id
            FROM `%s`
            WHERE %s
            LIMIT %d,%d',
            
            $this->tablePrefix.'587_searchindex',
            $where,
            $this->limit[0],$count
          )
        ) as $hit)
      {
        $indexIds[] = $hit['id'];
      }
    }
    
    // keywords, which were searched for
    $return['keywords'] = $this->searchArray;
    $return['searchterm'] = $this->searchString;
    
    // sql
    $return['sql'] = $query;
    
    // was any blacklisted word searched for?
    $return['blacklisted'] = false;
    if(count($this->blacklisted) > 0)
      $return['blacklisted'] = $this->blacklisted;
    
    $return['hash'] = $this->cacheHash($this->searchString);
    
    if($this->similarwords AND $i)
    {
      $this->storeKeywords($this->searchArray);
    }
    
    if($this->cache)
      $this->cacheSearch(serialize($return), $indexIds);
    
    // EP registrieren
    rex_register_extension_point('A587_SEARCH_EXECUTED', $return);
    
    $return['time'] = microtime(true) - $startTime;
    
    return $return;
  }
}
