<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
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
	 * @param string/int $value 待删除的值
     * @param array $array 数组
     * @return array 删除后的数组
	 */
	public static function arrayRemove( $value , $array )
	{
	    return array_diff($array, array($value));
	}
   
	/**
	 * 对二维数组，根据指定的键值进行排序，默认为升序
	 *
	 * array_sort($t_data,'vv','desc');
	 *
	 * @param array $arr 排序前的数组
     * @param string $keys 排序字段
     * @param string $type 升序=asc, 降序=desc
     * @return array 排序后的数组
	 */
	public static function arraySort($arr,$keys,$type='asc')
	{
	    $keysvalue = $new_array = array();
	    if(!empty($arr) && is_array($arr)) {
	        foreach ($arr as $k=>$v) {
	            $keysvalue[$k] = $v[$keys];
	        }
	        if($type == 'asc') {
	            asort($keysvalue);
	        }else if($type == 'desc') {
	            arsort($keysvalue);
	        }
	        reset($keysvalue);
	        foreach ($keysvalue as $k=>$v) {
	            $new_array[$k] = $arr[$k];
	        }
	    }
	    return $new_array;
	}
}
