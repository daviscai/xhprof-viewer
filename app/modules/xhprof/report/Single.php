<?php 
namespace app\modules\xhprof\report;


use app\modules\xhprof\XHProfUI;
use app\modules\xhprof\Config;
use app\modules\xhprof\Utils;
use app\modules\xhprof\Run;
use app\modules\xhprof\Compute;

class Single extends Driver {

	public function __construct(XHProfUI &$ui, array $raw_data) {
		// if we are reporting on a specific function, we can trim down
		// the report(s) to just stuff that is relevant to this function.
		// That way compute_flat_info()/compute_diff() etc. do not have
		// to needlessly work hard on churning irrelevant data.
		if (!empty($ui->fn)) {
			$raw_data = Compute::trim_run($raw_data, array($ui->fn));
		}

		$data = Compute::flat_info($ui, $raw_data);

		if (!empty($ui->fn) && !isset($data[$ui->fn])) {
			throw new \Exception('Function '.$ui->fn.' not found in XHProf run');
		}
		
		foreach($data as $fn => &$info) {
			$info = $info + array('fn' => $fn);
		}
		uasort($data, function($a, $b) use ($ui) {
			return Utils::sort_cbk($a, $b, $ui);
		});
		
		$this->_ui = $ui;
		$this->_data = array($raw_data, $data);
	}

	public function render() {
		$ui = $this->_ui;
		list($raw_data, $data) = $this->_data;

		if (!empty($ui->fn)) {
			include ROOT.'/view/report/single_fn.php';
		} else {
			include ROOT.'/view/report/single.php';
		}
	}
	
}