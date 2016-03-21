<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://wen.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */

namespace app\modules\def;

use app\core\base\Wen;
use app\core\controller\CoreController;

use app\modules\xhprof\Config;
use app\modules\xhprof\XHProfUI;
use app\modules\xhprof\Utils;


class AppController extends CoreController
{
	public function index()
	{
		$no_xhprof = false;
		if (!extension_loaded('xhprof')) {
			$no_xhprof = true;
		}

		$headerData = array('unit_conversion'=>Wen::t('unit conversion tips') );
		extract( $headerData );

		include ROOT.'/view/header.php';

		$xhprof_config = new Config();

		$xhprof_ui = new XHProfUI(
			array(
				'run'       => array(Utils::STRING_PARAM, ''),
				'compare'   => array(Utils::STRING_PARAM, ''),
				'wts'       => array(Utils::STRING_PARAM, ''),
				'fn'        => array(Utils::STRING_PARAM, ''),
				'sort'      => array(Utils::STRING_PARAM, 'wt'),
				'run1'      => array(Utils::STRING_PARAM, ''),
				'run2'      => array(Utils::STRING_PARAM, ''),
				'namespace' => array(Utils::STRING_PARAM, 'xhprof'),
				'all'       => array(Utils::UINT_PARAM, 0),
			),
			$xhprof_config,
			Wen::app()->config['fileDir']
		);


		if (!$xhprof_report = $xhprof_ui->generate_report()) {
			Utils::list_runs($xhprof_ui->dir);
		}else{	
			$xhprof_report->render();
		}

		include ROOT.'/view/footer.php';

	}

	public function page404()
	{
		echo 'page not found!';
	}
}