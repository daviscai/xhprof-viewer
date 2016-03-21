<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2016 Wen
 * @license http://opensource.org/licenses/MIT  MIT License
 */

namespace app\core\controller; 


/**
 * 核心控制器基类，所有控制器都继承此类
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class CoreController
{

	/**
	 * 渲染前端页面，页面文件统一放在view目录下
	 * 
	 * @param string $templateFile 模板页面文件名称，包含目录路径
     * @param array $data 需要传给页面的数据
     * @return void 加载页面
	 */
	public function render($templateFile, $data)
	{
		$file = ROOT . DS . 'view' . DS . $templateFile;

		if(file_exists($file)) {
			extract( $data );
			require( $file );
		}
	}
}