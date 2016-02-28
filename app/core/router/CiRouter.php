<?php
/**
 * Wen, an open source application development framework for PHP
 *
 * @link http://www.wenzzz.com/
 * @copyright Copyright (c) 2015 Wen
 * @license http://opensource.org/licenses/MIT	MIT License
 */

namespace app\core\router;

use Exception;
use \app\core\router\RouterInterface;

/**
 * 类似CodeIgniter路由的具体实现类，提供类似CI的路由方案
 *
 * 实现路由接口类。
 *
 * @author WenXiong Cai <caiwxiong@qq.com>
 * @since 1.0
 */
class CiRouter implements RouterInterface 
{

	/**
	 * 路由配置文件
	 *
	 * @var	string
	 */
	private $configFile;
	
	/**
	 * 路由规则数组
	 *
	 * @var	array
	 */
	private $routes =	array();

	/**
	 * 模块
	 *
	 * @var	string
	 */
	private $module;

	/**
	 * 控制器
	 *
	 * @var	string
	 */
	private $controller = '';

	/**
	 * action
	 *
	 * @var	string
	 */
	private $action = 'index';

	/**
	 * 请求URI
	 *
	 * @var	string
	 */
	private $uri ;

	/**
	 * Translate URI dashes，是否转换URI里的横线分隔符为下划线
	 *
	 * 如果为true，模块，控制器和方法名都会转换
	 *
	 * @var	bool
	 */
	private $translate_uri_dashes = FALSE;
	
	

	/**
	 * 设置路由配置文件；
	 * 路由分发处理
	 *
	 * @return	void
	 */
	public function __construct($routing = NULL)
	{
		$this->setConfigFile($routing);

		$this->routing();
	}

	/**
	 * 获得模块名称
	 *
	 * @return	string
	 */
	public function getModule()
	{
		return $this->module;
	}
	
	/**
	 * 获得控制器
	 *
	 * @return	string
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * 获得方法名
	 *
	 * @return	string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * 处理URL里的XSS代码
	 *
	 * @return	string
	 */
	public function secureUrl()
	{

	}

	/**
	 * 加载路由配置
	 *
	 * @return	void
	 */
	private function setConfigFile($config)
	{
		if (file_exists($config)) {
			include($config);
			$this->routes = $route;
			$this->configFile = $config;

			$this->translate_uri_dashes = isset($this->routes['translate_uri_dashes']) ? $this->routes['translate_uri_dashes'] : false;
		}else{
			throw new Exception('路由配置文件不存在：' . $config . '不存在！', 500);
		}
	}

	/**
	 * 开始执行路由分发
	 *
	 * @return	void
	 */
	private function routing()
	{
		$this->parseRequest();
	}

	/**
	 * 解析当前请求URI;
	 * 根据路由规则匹配，找到对应的控制器和方法
	 *
	 * @return	void
	 */
	private function parseRequest()
	{
		
		//解析请求url, nginx的配置通常为：location / { try_files $uri $uri/ /index.php?$uri&$args; }
		$this->uri = $this->parseRequestUri();

		//根据路由规则匹配，找到对应的控制器和方法
		$this->parseRouter($this->uri);
	}

	/**
	 * 解析当前请求URI
	 *
	 * @return	string
	 */
	private function parseRequestUri()
	{
		if ( ! isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']))
		{
			return '';
		}
		
		// parse_url() returns false if no host is present, but the path or query string
		// contains a colon followed by a number
		$uri = parse_url('http://dummy'.$_SERVER['REQUEST_URI']);
		$query = isset($uri['query']) ? $uri['query'] : '';
		$uri = isset($uri['path']) ? $uri['path'] : '';

		if (isset($_SERVER['SCRIPT_NAME'][0]))
		{
			// $_SERVER['SCRIPT_NAME'] = $_SERVER['REQUEST_URI']
			if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
			{
				// http://domain/index.php  => $uri = ''
				$uri = (string) substr($uri, strlen($_SERVER['SCRIPT_NAME']));
			}
			elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
			{
				// http://domain.com/product => $uri = product 
				$uri = (string) substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
			}
		}

		// This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
		// URI is found, and also fixes the QUERY_STRING server var and $_GET array.
		if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0)
		{
			$query = explode('?', $query, 2);
			$uri = $query[0];
			$_SERVER['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
		}
		else
		{
			$_SERVER['QUERY_STRING'] = $query;
		}

		parse_str($_SERVER['QUERY_STRING'], $_GET);

		if ($uri === '/' OR $uri === '')
		{
			return '/';
		}

		return $this->removeRelativeirectory($uri);
	}

	/**
	 * 移除相对路径，如 (../) 和 (///)
	 *
	 * @param	string	$url
	 * @return	string
	 */
	private function removeRelativeirectory($uri)
	{
		$uris = array();
		$tok = strtok($uri, '/');
		while ($tok !== FALSE)
		{
			if (( ! empty($tok) OR $tok === '0') && $tok !== '..')
			{
				$uris[] = $tok;
			}
			$tok = strtok('/');
		}

		return implode('/', $uris);
	}

	/**
	 * 根据路由规则匹配，找到对应的控制器和方法
	 *
	 * @param	string	$uri
	 * @return	void
	 */
	private function parseRouter($uri)
	{
		// Get HTTP verb
		$http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

		// Loop through the route array looking for wildcards
		foreach ($this->routes as $key => $val)
		{
			// Check if route format is using HTTP verbs
			if (is_array($val))
			{
				$valArr = array_change_key_case($val, CASE_LOWER);
				
				if (isset($valArr[$http_verb]))
				{
					$val = $valArr[$http_verb];
				}
				else
				{
					continue;
				}

			}

			// Convert wildcards to RegEx
			$key = str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key);

			// Does the RegEx match?
			if (preg_match('#^'.$key.'$#', $uri, $matches))
			{
				// Are we using callbacks to process back-references?
				if ( ! is_string($val) && is_callable($val))
				{
					// Remove the original string from the matches array.
					array_shift($matches);

					// Execute the callback using the values in matches as its parameters.
					$val = call_user_func_array($val, $matches);
				}
				// Are we using the default routing method for back-references?
				elseif (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE)
				{
					$val = preg_replace('#^'.$key.'$#', $val, $uri);
				}

				$this->setRequest(explode('/', $val));
				return;
			}
		}

		//到这里说明没有合适的路由规则，将会采用默认的规则，统一处理
		$this->setDefaultController();
	}

	/**
	 *  采用默认模块，控制器和方法
	 *
	 * @return	void
	 */
	private function setDefaultController()
	{
		//如果uri不为空，并且没有匹配到路由规则，显示404页面
		if(isset($this->routes['404_override']) && $this->routes['404_override'] &&  $this->uri !=='/') {
			$this->setRequest(explode('/', $this->routes['404_override']));
		}else{
			$segment[0] = isset($this->routes['default_module']) ? $this->routes['default_module'] : 'def';
			$segment[1] = isset($this->routes['default_controller']) ? ucfirst($this->routes['default_controller']) : 'app';
			$segment[2] = isset($this->routes['default_action']) ? $this->routes['default_action'] : 'index';

			$this->setRequest($segment);
		}
	}

	/**
	 * 设置模块，控制器和方法
	 *
	 * @param	array $segments 包含模块，控制器和方法的数组
	 * @return	void
	 */
	private function setRequest($segments = array())
	{

		if (empty($segments))
		{
			$this->setDefaultController();
			return;
		}

		if ($this->translate_uri_dashes === TRUE)
		{
			$segments[0] = str_replace('-', '_', $segments[0]);
			if (isset($segments[1]))
			{
				$segments[1] = str_replace('-', '_', $segments[1]);
			}
			if (isset($segments[2]))
			{
				$segments[2] = str_replace('-', '_', $segments[2]);
			}
		}

		$this->setModule($segments[0]);

		$this->setController($segments[1]);

		$action = isset($segments[2]) ? $segments[2] : 'index';
		$this->setAction($action);

		//处理url参数，把 def/product/detail/id=$1/page=$2/xxx 转化成参数后放到$_GET数组里
		if(count($segments) > 3) {
			$urlParmeArr = array_slice($segments,3);
			$paramArr = array();
			foreach ($urlParmeArr as $param) {
				$paramArr[] =  str_replace('_', '=', $param);
			}
			parse_str( implode('&',$paramArr) , $_GET);
		}

	}

	/**
	 * 设置模块
	 *
	 * @param	string $module 模块名
	 * @return	void
	 */
	private function setModule($module)
	{
		$this->module = $module;
	}	

	/**
	 * 设置控制器
	 *
	 * @param	string $controller 控制器名称，首字母大写
	 * @return	void
	 */
	private function setController($controller)
	{
		$prefixName = str_replace(array('/', '.'), '', $controller);
		
		$this->controller = ucfirst($prefixName).'Controller';
	}

	/**
	 * 设置方法
	 *
	 * @param	string $action 方法名
	 * @return	void
	 */
	private function setAction($action)
	{
		$this->action = $action;
	}

}
