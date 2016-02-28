<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://www.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */

namespace app\core\controller; 


class CoreController
{
	public function render($templateFile, $data)
	{
		$file = ROOT . DS . 'view' . DS . $templateFile;

		if( file_exists( $file ) )
		{
			@extract( $data );
			require( $file );
		}
	}
}