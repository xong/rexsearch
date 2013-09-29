<?php
// This file defines mb-functions for backward compatibility

if(!function_exists('mb_strlen'))
{
  function mb_strlen($str, $encoding = 'UTF-8')
  {
    return strtolower($encoding) == 'utf-8' ? strlen(utf8_decode($str)) : strlen($str);
  }
}

if(!function_exists('mb_strtolower'))
{
  function mb_strtolower($str, $encoding = 'UTF-8')
  {
    return strtolower($encoding) == 'utf-8' ? strtolower(utf8_decode($str)) : strtolower($str);
  }
}

if(!function_exists('mb_internal_encoding'))
{
  function mb_internal_encoding()
  {
    return rex_lang_is_utf8() ? 'UTF-8' : 'ISO-8859-15';
  }
}

if(!function_exists('mb_detect_encoding'))
{
  function mb_detect_encoding($str)
  {
    foreach (array('UTF-8', 'ISO-8859-15', 'WINDOWS-1251') as $encoding)
    {
      $sample = @iconv($encoding, $encoding, $str);
      if(md5($sample) == md5($str))
        return $encoding;
    }
    
    return '8bit';
  }
}

/*
 * Author: Steve
 * Source: http://www.php.net/manual/de/function.json-encode.php#82904
 * Date: 01-May-2008 12:35
 */
if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }

      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
      return '{' . join(',', $result) . '}';
    }
  }
}

/*
 * Author: www at walidator dot info
 * Source: http://de.php.net/manual/de/function.json-decode.php#91216
 * Date: 30-May-2009 02:16
 */
if ( !function_exists('json_decode') ){
function json_decode($json)
{ 
    // Author: walidator.info 2009
    $comment = false;
    $out = '$x=';
   
    for ($i=0; $i<strlen($json); $i++)
    {
        if (!$comment)
        {
            if ($json[$i] == '{')        $out .= ' array(';
            else if ($json[$i] == '}')    $out .= ')';
            else if ($json[$i] == ':')    $out .= '=>';
            else                         $out .= $json[$i];           
        }
        else $out .= $json[$i];
        if ($json[$i] == '"')    $comment = !$comment;
    }
    eval($out . ';');
    return $x;
} 
} 
