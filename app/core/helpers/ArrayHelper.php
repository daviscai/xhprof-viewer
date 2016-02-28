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
 * 数组助手静态类
 *
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class ArrayHelper
{

	/**
	 * 删掉数组里的某个值
	 * 
	 */
	public static function arrayRemove( $value , $array )
	{
	    return array_diff($array, array($value));
	}
    
	    
	/*
	 * 对二维数组，根据指定的键值进行排序，默认为升序
	 *
	 * array_sort($t_data,'vv','desc');
	 */
	public static function arraySort($arr,$keys,$type='asc')
	{
	    $keysvalue = $new_array = array();
	    if(!empty($arr) && is_array($arr)){
	        foreach ($arr as $k=>$v){
	            $keysvalue[$k] = $v[$keys];
	        }
	        if($type == 'asc' || empty($type)){
	            asort($keysvalue);
	        }else if($type == 'desc'){
	            arsort($keysvalue);
	        }
	        reset($keysvalue);
	        foreach ($keysvalue as $k=>$v){
	            $new_array[$k] = $arr[$k];
	        }
	    }
	    return $new_array;
	}
}
