<?php 
namespace app\modules\xhprof;

use app\core\base\Wen;

class Config {

	public $possible_metrics =  array(
		'wt'      => array('Wall', '&micro;s', 'walltime'),
		'ut'      => array('User', '&micro;s', 'user cpu time'),
		'st'      => array('Sys', '&micro;s', 'system cpu time'),
		'cpu'     => array('Cpu', '&micro;s', 'cpu time'),
		'mu'      => array('MUse', 'bytes', 'memory usage'),
		'pmu'     => array('PMUse', 'bytes', 'peak memory usage'),
		'samples' => array('Samples', 'samples', 'cpu time')
	);

	// The following column headers are sortable
	public $sortable_columns = array(
		'fn'           => 1,
		'ct'           => 1,
		'wt'           => 1,
		'excl_wt'      => 1,
		'ut'           => 1,
		'excl_ut'      => 1,
		'st'           => 1,
		'excl_st'      => 1,
		'mu'           => 1,
		'excl_mu'      => 1,
		'pmu'          => 1,
		'excl_pmu'     => 1,
		'cpu'          => 1,
		'excl_cpu'     => 1,
		'samples'      => 1,
		'excl_samples' => 1
	);

	// Textual descriptions for column headers in 'single run' mode
	public $descriptions = array(
		'fn'           => 'Function Name',
		'ct'           => 'Calls',
		'wt'           => 'Inc. Wall Time',
		'excl_wt'      => 'Ex. Wall Time',

		'ut'           => 'Inc. User',
		'excl_ut'      => 'Ex. User',

		'st'           => 'Inc. Sys',
		'excl_st'      => 'Ex. Sys',

		'cpu'          => 'Inc. CPU',
		'excl_cpu'     => 'Ex. CPU',

		'mu'           => 'Incl. MemUse',
		'excl_mu'      => 'Excl. MemUse',

		'pmu'          => 'Incl. Peak MemUse',
		'excl_pmu'     => 'Excl. Peak MemUse',

		'samples'      => 'Incl. Samples',
		'excl_samples' => 'Excl. Samples',
	);

	// Formatting Callback Functions...
	public $format_cbk = array(
		'fn'           => '',
		'ct'           => array('count_format'),
		'Calls%'       => array('percent_format'),

		'wt'           => 'number_format',
		'IWall%'       => array('percent_format'),
		'excl_wt'      => 'number_format',
		'EWall%'       => array('percent_format'),

		'ut'           => 'number_format',
		'IUser%'       => array('percent_format'),
		'excl_ut'      => 'number_format',
		'EUser%'       => array('percent_format'),

		'st'           => 'number_format',
		'ISys%'        => array('percent_format'),
		'excl_st'      => 'number_format',
		'ESys%'        => array('percent_format'),

		'cpu'          => 'number_format',
		'ICpu%'        => array('percent_format'),
		'excl_cpu'     => 'number_format',
		'ECpu%'        => array('percent_format'),

		'mu'           => 'number_format',
		'IMUse%'       => array('percent_format'),
		'excl_mu'      => 'number_format',
		'EMUse%'       => array('percent_format'),

		'pmu'          => 'number_format',
		'IPMUse%'      => array('percent_format'),
		'excl_pmu'     => 'number_format',
		'EPMUse%'      => array('percent_format'),

		'samples'      => 'number_format',
		'ISamples%'    => array('percent_format'),
		'excl_samples' => 'number_format',
		'ESamples%'    => array('percent_format'),
	);

	public function __construct() {
		$this->descriptions = $this->initDescriptions();
	}

	private function initDescriptions()
	{
		$rs = [];
		foreach ($this->descriptions as $key => $value) {
			$rs[$key] = Wen::t($value);
		}
		return $rs;
	}


}