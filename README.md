# xhprof-viewer
查看XHProf分析报表，比官方提供的界面更简洁友好，基于[Wen框架](https://github.com/daviscai/Wen)实现，


### 运行环境要求
PHP version >= 5.3

### 特点：

1. 更简洁的界面风格，基于bootstrap构建；
2. 支持多国语言设置，xhprof报表字段翻译可以在配置文件里修改；
3. 基于[Wen框架](https://github.com/daviscai/Wen)实现，简单易用。
4. 与xhprof数据生成分离，仅仅作为分析报表的查看。


### 预览

![Xhprof](http://wenzzz.com/storage/xhprof.png)


### 如何使用

  第一步：生成数据文件  
    1. 安装xhprof php扩展，通过pecl install 安装扩展  
    2. 在应用里添加xhprof的统计点, 其中[Wen框架](https://github.com/daviscai/Wen)已默认添加，只需要在配置里开启即可。
    
    //程序开始时候，根据设置是否执行性能分析
    private function monitorProfilerStart()
    {
        $config = isset($this->config['xhprof']) ? $this->config['xhprof'] : '';
        if(isset($config['enable']) && $config['enable'] && extension_loaded('xhprof')) {
            if(mt_rand(1, $config['requestTimes']) === 1 ){
                if(isset($config['noBuiltins']) && $config['noBuiltins']){
                    xhprof_enable(XHPROF_FLAGS_NO_BUILTINS + XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY); 
                }else{
                    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
                }
                $this->enableXHProf = true;
            }
        }
    }
    
    // 程序结束时候，通常在析构函数里执行该方法，结束性能分析，并输出数据到文件里
    private function monitorProfilerEnd()
    {
        if($this->enableXHProf) {
            // stop profiler
            $xhprofData = xhprof_disable();

            $config = isset($this->config['xhprof']) ? $this->config['xhprof'] : '';

            $fileName = $config['fileDir'] . DS . date('YmdHis').mt_rand(100,10000).'.xhprof';
            $file = fopen($fileName, 'w');
            if($file) {
                fwrite($file, serialize($xhprofData));
                fclose($file);
            }else{
                $this->logger->error('save xhprof result faild');
            }
        }
    }
    
    
  第二步：分析数据文件  
  1. 把该项目下载到web 可访问目录下
  
  2. 设置数据文件目录
  <pre>
  // app/config/app.config  
  return array(
    'fileDir' => '/path/to/xhprof_data/', //经过序列化的xhprof数据目录
    // ...
  )
  </pre>

  3. 设置xhprof字段翻译  
  
  <pre>
  // app/config/lang/zh-CN.config , 其他语言，Wen框架会自动根据客户端语言找到对应的语言配置，如 en-US.config  
  return array(
    'unit conversion tips' => '1s(秒) = 1000ms(毫秒) = 1000000μs(微秒)；1KB = 1024 bytes(字节)',
    'Existing runs' => '存在的数据文件',
    'Function Name' => '函数名',
    'Calls'=>'调用次数',
    'Inc. Wall Time'=>'完整耗时(微秒)',
    'Ex. Wall Time'=>'自身耗时(微秒)',
    'Inc. User'=>'Inc. User',
    'Ex. User'=>'Ex. User',
    'Inc. Sys'=>'Inc. Sys',
    'Ex. Sys'=>'Ex. Sys',
    'Inc. CPU'=>'完整CPU耗时(微秒)',
    'Ex. CPU'=>'自身CPU耗时(微秒)',
    'Incl. MemUse'=>'完整使用内存(字节)',
    'Excl. MemUse'=>'自身使用内存(字节)',
    'Incl. Peak MemUse'=>'完整使用内存峰值(字节)',
    'Excl. Peak MemUse'=>'自身使用内存峰值(字节)',
    'Incl. Samples'=>'Incl. Samples',
    'Excl. Samples'=>'Excl. Samples',
  );
  </pre>
  
  如有任何问题，欢迎反馈！
  
