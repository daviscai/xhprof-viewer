<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://www.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */

namespace app\core\helpers;


/**
 * 字符串助手静态类
 *
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class StringHelper
{

	/**
	 * 出于安全考虑，对字符进行检查和转换
	 *
	 * 包括过滤Xss恶意脚本代码
	 * 
	 * @param  array  $aList  需检查的数据
	 * @param  boolean $aIsTopLevel 是否为严格模式，只有在magic_quotes_gpc开启后生效
	 * @return array  返回被转换过的数据
	 */
	public static function transcribe($aList, $aIsTopLevel = true) 
	{
	   $gpcList = array();
	   $isMagic = get_magic_quotes_gpc();
	   if (is_string($aList)) {
	      return static::RemoveXSS($aList);
	   }
	  
	   foreach ($aList as $key => $value) {
	       if (is_array($value)) {
	            $decodedKey = ($isMagic && !$aIsTopLevel)?stripslashes($key):$key;
	            $decodedKey = static::RemoveXSS($decodedKey);
	            $decodedValue = transcribe($value, true);
	       } else {
	            $decodedKey = stripslashes($key);
	            $decodedKey = static::RemoveXSS($decodedKey);
	            $decodedValue = ($isMagic)?stripslashes($value):$value;
	            $decodedValue = static::RemoveXSS($decodedValue);
	       }
	       $gpcList[$decodedKey] = $decodedValue;
	   }
	   return $gpcList;
	}

	/**
	 * 过滤XSS（跨站脚本攻击）的函数
	 *
	 * @param $val 字符串参数，可能包含恶意的脚本代码
	 * @return  处理后的字符串
	 *
	 */
	public static function RemoveXSS($val) 
	{  
	   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed  
	   // this prevents some character re-spacing such as <java\0script>  
	   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs  
	   $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);  
	      
	   // straight replacements, the user should never need these since they're normal characters  
	   // this prevents like <IMG SRC=@avascript:alert('XSS')>  
	   $search = 'abcdefghijklmnopqrstuvwxyz'; 
	   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';  
	   $search .= '1234567890!@#$%^&*()'; 
	   $search .= '~`";:?+/={}[]-_|\'\\'; 
	   for ($i = 0; $i < strlen($search); $i++) 
	   { 
	      // ;? matches the ;, which is optional 
	      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars 
	     
	      // @ @ search for the hex values 
	      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ; 
	      // @ @ 0{0,7} matches '0' zero to seven times  
	      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ; 
	   } 
	     
	   // now the only remaining whitespace attacks are \t, \n, and \r 
	   //$ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'); 
	   $ra1 = Array('javascript', 'vbscript', 'expression', 'script', 'embed', 'object', 'iframe'); 
	   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'); 
	   $ra = array_merge($ra1, $ra2); 
	     
	   $found = true; // keep replacing as long as the previous round replaced something 
	   while ($found == true) 
	   { 
	      $val_before = $val; 
	      for ($i = 0; $i < sizeof($ra); $i++) 
	      { 
	         $pattern = '/'; 
	         for ($j = 0; $j < strlen($ra[$i]); $j++) 
	         { 
	            if ($j > 0) 
	            { 
	               $pattern .= '(';  
	               $pattern .= '(&#[xX]0{0,8}([9ab]);)'; 
	               $pattern .= '|';  
	               $pattern .= '|(&#0{0,8}([9|10|13]);)'; 
	               $pattern .= ')*'; 
	            } 
	            $pattern .= $ra[$i][$j]; 
	         } 
	         $pattern .= '/i';  
	         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag  
	         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags  
	         if ($val_before == $val) 
	         {  
	            // no replacements were made, so exit the loop  
	            $found = false;  
	         }  
	      }  
	   }  
	   return trim($val);  
	}   


	/**
	 * 去除HTML、XML 以及 PHP 的标签。
	 * @param  string $str 目标字符串
	 * @return string 去除标签后的字符串
	 */
	public static function z( $str )
	{
		return strip_tags( $str );
	}


	/**
	 * 判断字符串是否为json格式
	 */
	public static function isJson($str)
	{
	    if( is_array($str) )
	    {
	        return false;
	    }
	    $data = json_decode($str, true);
	    return !empty($data) && is_array($data) ? true : false;
	}

	/**
	 * 判断字符串是否为序列化格式
	 *
	 */
	public static function isSerialized($str) 
	{
	    //如果传递的字符串不可解序列化，则返回 FALSE，并产生一个 E_NOTICE。 
	    return ($str == serialize(false) || @unserialize($str) !== false);
	}


	/**
	 * Validate an email address.
	 * Provide email address (raw input)
	 * Returns true if the email address has the email address format and the domain exists.
	 *
	 */
	public static function validEmail($email)
	{
	   $isValid = true;
	   $atIndex = strrpos($email, "@");
	   if (is_bool($atIndex) && !$atIndex)
	   {
	      $isValid = false;
	   }
	   else
	   {
	      $domain = substr($email, $atIndex+1);
	      $local = substr($email, 0, $atIndex);
	      $localLen = strlen($local);
	      $domainLen = strlen($domain);
	      if ($localLen < 1 || $localLen > 64)
	      {
	         // local part length exceeded
	         $isValid = false;
	      }
	      else if ($domainLen < 1 || $domainLen > 255)
	      {
	         // domain part length exceeded
	         $isValid = false;
	      }
	      else if ($local[0] == '.' || $local[$localLen-1] == '.')
	      {
	         // local part starts or ends with '.'
	         $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $local))
	      {
	         // local part has two consecutive dots
	         $isValid = false;
	      }
	      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
	      {
	         // character not valid in domain part
	         $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $domain))
	      {
	         // domain part has two consecutive dots
	         $isValid = false;
	      }
	      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
	      {
	         // character not valid in local part unless 
	         // local part is quoted
	         if (!preg_match('/^"(\\\\"|[^"])+"$/',
	             str_replace("\\\\","",$local)))
	         {
	            $isValid = false;
	         }
	      }
	      if ( !checkdnsrr($domain) )
	      {
	         // domain not found in DNS
	         $isValid = false;
	      }
	   }
	   return $isValid;
	}

	/**
	* 创建唯一的guid
	* @param  int $len 长度
	*/
	public static function guid($len)
	{
	    $len = $len ? $len : 32;
	    $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678'; // 默认去掉了容易混淆的字符oOLl,9gq,Vv,Uu,I1 
	    $maxPos = strlen($chars); 
	    $pwd = ''; 
	    for( $i = 0; $i < $len; $i++)
	    {
	        $index = floor( rand(0,9)/10 * $maxPos  );
	        $pwd .= substr( $chars, $index ,1 ); 
	    }  
	    return $pwd; 
	}

	/**
	 * 对日期格式化，返回星期几
	 *
	 */
	public static function formatWeek($date)
	{
	    $week = array('日','一','二','三','四','五','六');
	    return  '周'.$week[date('w', strtotime($date))] ;
	}


	/**
	 * 获取UTF-8编码下的字符串长度，UTF-8编码下，中文、中文标点符号长度算为1，英文、英文标点符号、数字长度算为0.5
	 * @param $source_str 原始字符处
	 * @return string 返回字符串长度
	 */
	public static function getStrLen($source_str)
	{
	  if(!$source_str){
	    return 0;
	  }
	  $i=0;
	  $n=0;
	  $str_length = strlen($source_str);//字符串的字节数
	  while ($i<$str_length)
	  {
	    $temp_str=substr($source_str,$i,1);
	    $ascnum=Ord($temp_str); //得到字符串中第$i位字符的ascii码
	    if ($ascnum>=224)       //如果ASCII位高与224，
	    {
	      $i=$i+3;            //实际Byte计为3
	      $n++;               //字串长度计1
	    }
	    elseif ($ascnum>=192)   //如果ASCII位高与192，
	    {
	      $i=$i+2;            //实际Byte计为2
	      $n++;               //字串长度计1
	    }
	    elseif ($ascnum>=65 && $ascnum<=90) //如果是大写字母，
	    {
	      $i=$i+1;            //实际的Byte数仍计1个
	      $n += 0.5;          //经测试，大写字母串MMDDCCFFGGTTLL跟小写字母串mmddccffggttll的长度一样长
	    }
	    else                    //其他情况下，包括小写字母和半角标点符号，
	    {
	      $i=$i+1;            //实际的Byte数计1个
	      $n=$n+0.5;          //小写字母和半角标点等与半个高位字符宽...
	    }
	  }
	  return $n;
	}

	/**
	 * 截取UTF-8编码下的指定长度的字符串(一个中文长度为1，英文字母长度为0.5)
	 * $sourcestr 原始字符处
	 * $cutlength 截取字符串的长度
	 * @return string 返回截取后的字符串
	 */
	public static function cutString($sourcestr, $cutlength)
	{
	  $returnstr='';
	  $i=0;
	  $n=0;
	  $str_length = strlen($sourcestr);    //字符串的字节数
	  while ($i < $str_length)
	  {
	    $temp_str=substr($sourcestr,$i,1);
	    $ascnum=Ord($temp_str);         //得到字符串中第$i位字符的ascii码
	    if ($ascnum>=224)               //如果ASCII位高与224，
	    {
	      $n++;                       //字串长度计1
	      if($n > $cutlength){
	        break;
	      } else {
	        $returnstr=$returnstr.substr($sourcestr,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
	        $i=$i+3;                //实际Byte计为3
	      }
	        
	    }
	    elseif ($ascnum>=192)           //如果ASCII位高与192，
	    {
	      $n++;                       //字串长度计1
	      if($n > $cutlength){
	        break;
	      } else {
	        $returnstr=$returnstr.substr($sourcestr,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
	        $i=$i+2;                //实际Byte计为2
	      }
	    }
	    elseif ($ascnum>=65 && $ascnum<=90) //如果是大写字母，
	    {
	      $n+=0.5;                    //大写字母字串长度计0.5
	      if($n > $cutlength){
	        break;
	      } else {
	        $returnstr=$returnstr.substr($sourcestr,$i,1);
	        $i=$i+1;                //实际的Byte数仍计1个
	      }
	    }
	    else                            //其他情况下，包括小写字母和半角标点符号，
	    {
	      $n+=0.5;                    //小写字母和半角标点等与半个高位字符宽...长度计0.5
	      if($n > $cutlength){
	        break;
	      } else {
	        $returnstr=$returnstr.substr($sourcestr,$i,1);
	        $i=$i+1;                //实际的Byte数仍计1个
	      }
	    }
	  }
	  return $returnstr;
	}

		
	/**
	 *  把字符串的大致一半长度的中间部分替换成星号 *
	 *  
	 */
	public static function starReplace($str)
	{
	    $len = mb_strlen($str, 'utf8');
	    $halfLen = intval($len / 2);
	    $quaterLen = intval($len / 4);
	    return mb_substr($str, 0, $quaterLen, 'utf8') . str_repeat('*', $halfLen) . mb_substr($str, $quaterLen + $halfLen, $len, 'utf8');
	}

	/**
     * Returns the number of bytes in the given string.
     * This method ensures the string is treated as a byte array by using `mb_strlen()`.
     * @param string $string the string being measured for length
     * @return integer the number of bytes in the given string.
     */
    public static function byteLength($string)
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     * This method ensures the string is treated as a byte array by using `mb_substr()`.
     * @param string $string the input string. Must be one character or longer.
     * @param integer $start the starting position
     * @param integer $length the desired portion length. If not specified or `null`, there will be
     * no limit on length i.e. the output will be until the end of the string.
     * @return string the extracted part of string, or FALSE on failure or an empty string.
     * @see http://www.php.net/manual/en/function.substr.php
     */
    public static function byteSubstr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length === null ? mb_strlen($string, '8bit') : $length, '8bit');
    }

    /**
     * Returns the trailing name component of a path.
     * This method is similar to the php function `basename()` except that it will
     * treat both \ and / as directory separators, independent of the operating system.
     * This method was mainly created to work on php namespaces. When working with real
     * file paths, php's `basename()` should work fine for you.
     * Note: this method is not aware of the actual filesystem, or path components such as "..".
     *
     * @param string $path A path string.
     * @param string $suffix If the name component ends in suffix this will also be cut off.
     * @return string the trailing name component of the given path.
     * @see http://www.php.net/manual/en/function.basename.php
     */
    public static function basename($path, $suffix = '')
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) === $suffix) {
            $path = mb_substr($path, 0, -$len);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        if (($pos = mb_strrpos($path, '/')) !== false) {
            return mb_substr($path, $pos + 1);
        }

        return $path;
    }

    /**
     * Returns parent directory's path.
     * This method is similar to `dirname()` except that it will treat
     * both \ and / as directory separators, independent of the operating system.
     *
     * @param string $path A path string.
     * @return string the parent directory's path.
     * @see http://www.php.net/manual/en/function.basename.php
     */
    public static function dirname($path)
    {
        $pos = mb_strrpos(str_replace('\\', '/', $path), '/');
        if ($pos !== false) {
            return mb_substr($path, 0, $pos);
        } else {
            return '';
        }
    }
    
    
}
