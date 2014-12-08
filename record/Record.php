<?
namespace record;

class Record
{
    public $debug;
    public $root;
    public $layout;
    public $controller = null;
    public $request;
    public $args;
    private $partialData = [];
    private $outputData;
    private $chunk;
    private $timerStart;
    private $timerEnd;
    private $memoryUsage;

    private static $instance = null;
    private static $argsData;
    private static $_maps = [];
    private static $_laseMap;
    private static $_conf = [];

    public function __construct(array $conf = [])
    {
        $this->timerStart = microtime(true);

        $this->setConf($conf);
        $this->autoloadClasses();
    }

    private function autoloadClasses()
    {
        spl_autoload_register(array($this, 'autoloadAppClasses'));
    }

    private function autoloadAppClasses($className)
    {
        $className = str_replace('\\', '/', $className);

        $fileName = $className.'.php';
        if (is_file($fileName))
            include_once $fileName;
    }

    private function setConf(array $conf)
    {
        foreach ($conf as $key=>$val) {
            self::$_conf[$key]=$val;
        }

        $confDefault = [
            'debug'=>false,
            'lang'=>null,
            'root'=> substr($_SERVER['SCRIPT_FILENAME'],0,-9),
            'path'=>'',
            'views'=>'',
            'models'=>'',
            'controllers'=>'',
            'layout'=>'',
            'callError404'=>'',
            'messageError404'=>'<h1>Error 404!</h1><h3>Page not found.</h3>',
        ];

        if(empty(self::$_conf['debug'])) self::$_conf['debug'] = $confDefault['debug'];
        if(empty(self::$_conf['lang'])) self::$_conf['lang'] = $confDefault['lang'];
        if(empty(self::$_conf['root'])) self::$_conf['root'] = $confDefault['root'];
        if(empty(self::$_conf['path'])) self::$_conf['path'] = $confDefault['path'];
        if(empty(self::$_conf['views'])) self::$_conf['views'] = $confDefault['views'];
        if(empty(self::$_conf['models'])) self::$_conf['models'] = $confDefault['models'];
        if(empty(self::$_conf['controllers'])) self::$_conf['controllers'] = $confDefault['controllers'];
        if(empty(self::$_conf['layout'])) self::$_conf['layout'] = $confDefault['layout'];
        if(empty(self::$_conf['callError404'])) self::$_conf['callError404'] = $confDefault['callError404'];
        if(empty(self::$_conf['messageError404'])) self::$_conf['messageError404'] = $confDefault['messageError404'];

        $this->debug = self::$_conf['debug'];
        $this->layout = self::$_conf['layout'];
        $this->root = self::$_conf['root'];
    }

    public function conf($key, $strong=true)
    {
        if(isset(self::$_conf[$key]))
            return self::$_conf[$key];
        else{
            if($strong)
                throw new \ErrorException();
            else
                return false;
        }
    }

    public function url()
    {
        if(isset(self::$_conf['path']))
            return '/'.trim(self::$_conf['path'],'/').'/';
        else
            return '/';
    }

    public static function instance()
    {
        if(self::$instance==null){
            self::$instance = new Record();
            return self::$instance;
        } else {
            return self::$instance;
        }
    }

    public function map($map,$call)
    {
        if(empty($map) || !is_callable($call)){
            throw new \ErrorException();
        }else{

            $params = $args = null;

            if ($paramPos = strpos($map, '{'))
            {
                $mapBase = $map;
                $map = substr($map, 0, $paramPos - 1);
                $params = substr($mapBase, $paramPos);
            }

            $paramValues = array(' '=>'', '/'=>'\/*', '{n}'=>'(\d*)', '{w}'=>'([a-z_]*)', '{p}'=>'(\w*)',
                '{!n}'=>'(\d+)', '{!w}'=>'([a-zA-Z_]+)', '{!p}'=>'(\w+)','{*}'=>'([\w\/-]*)');

            if(strpos($params,':') !== false){
                preg_match_all('|\:(\w+)|', $params, $result);
                if(!empty($result[0])) {
                    foreach ($result[0] as $_result)
                        $params = str_ireplace($_result, '', $params);
                    foreach ($result[1] as $_result)
                        $args[] = $_result;
                }
            }

            $marReg = '|^'.$map.'\/*'.strtr($params,$paramValues).'$|';

            self::$_maps[$map]['url'] = $map;
            self::$_maps[$map]['regexp'] = $marReg;
            self::$_maps[$map]['call'] = $call;
            self::$_maps[$map]['args'] = $args;
            self::$_laseMap = $map;
        }
        return $this;
    }

    public static function args($key=null){
        if($key){
            if(isset(self::$argsData[$key]))
                return self::$argsData[$key];
            else{
                return null;
            }
        }else{
            return self::$argsData;
        }
    }

    public static function callHeader($event, array $data = [])
    {
        if (isset(self::$hooksData[$event])) {
            if(empty($data)){
                self::apply(self::$hooksData[$event]['call'],self::$hooksData[$event]['data']);
            } else {
                self::apply(self::$hooksData[$event]['call'],$data);
            }
        } else {
            return null;
        }
    }

    /**
     * Выполнение функции с аргуметами массив. При ошибке обрабатывает исключение.
     *
     * @param callable $callable Функция для запуска
     * @param array $args     Аргумент функции массив
     *
     * @throws
     *
     * @return mixed возвращает с результат с функции callable
     */
    public static function apply($callable, $args = [])
    {
        if (!is_callable($callable)) {
            throw new \RuntimeException('invalid callable');
        }
        return call_user_func_array($callable, $args);
    }

    public function run()
    {
        $path = trim($this->conf('path',false),'/');
        $request = ltrim($_SERVER['REQUEST_URI'],'/');
        if(!empty($path) && strpos($request,$path)===0){
            $request = substr($request,strlen($path));
        }
        $isCalled = false;
        $this->request = $request;

        foreach(self::$_maps as $key=>$val)
        {
            if($isCalled)
                continue;
            if(strpos($request,$key)!==false)
            {
                if(preg_match($val['regexp'],$request,$result)){

                    $args = [];
                    array_shift($result);
                    if(!empty($val['args']) && !empty($result))
                        $args = array_combine($val['args'], $result);

                    self::$argsData = $this->args = $args;
                    # Создает метод регистратор собитя до роутинга
                    ($before = $this->before()) && self::apply($before, []);

                    self::apply($val['call'],$args);

                    #Создает метод регистратор собитя после роутинга
                    ($after = $this->after()) && self::apply($after, []);

                    $isCalled = true;
                }
            }
        }

        if(!$isCalled) {
            http_response_code(404);
            $callError = $this->conf('callError404');
            $messageError = $this->conf('messageError404');
            if($callError){
                self::apply($callError);
            }else{
                echo $messageError;
            }
        }

        $this->request = $_SERVER['REQUEST_URI'];
    }

    /**
     * Регистрация функции обратного вызова 'before'. Событие происходит до роутинга
     *
     * @param callable $callback 'before' принимает обработчик события функцию по имени своего аргумента
     * @return mixed
     */
    function before($callback = null)
    {
        static $before;

        if (func_num_args()) {
            $before = $callback;
        } else {
            return $before;
        }
    }


    /**
     * Регистрация функции обратного вызова 'after'. Событие происходит после роутинга
     *
     * @param callable $callback 'after' принимает обработчик события функцию по имени своего аргумента
     * @return mixed
     */
    function after($callback = null)
    {
        static $after;

        if (func_num_args()) {
            $after = $callback;
        } else {
            return $after;
        }
    }

    public static $hooksData;

    public static function addHook($event, $callback, array $data = [])
    {
        self::$hooksData[$event]['call'] = $callback;
        self::$hooksData[$event]['data'] = $data;
    }
    public static function callHook($event, array $data = [])
    {
        if (isset(self::$hooksData[$event])) {
            if(empty($data)){
                return self::apply(self::$hooksData[$event]['call'], self::$hooksData[$event]['data']);
            } else {
                return self::apply(self::$hooksData[$event]['call'],$data);
            }
        } else {
            return null;
        }
    }

    # CONTROLLERS
    # - - - - - - -- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    public function controller($name)
    {
        if(is_string($name))
        {
            $controllerPath = $this->root.$this->conf('controllers').$name.'.php';
            $controllerName = '\\'.trim($this->conf('controllers'),'/').'\\'.$name;

            if(!is_file($controllerPath))
                throw new \Exception(" File not found! ".$controllerPath);

            require($controllerPath);

            if(!class_exists($controllerName))
                throw new \Exception(" Class not found! ".$controllerName);

            /** @var $controllerName $controller */
            $this->controller = new $controllerName($this);
        }
        else
            $this->controller = $name;

        if(method_exists($this->controller,'init'))
            $this->controller->init();

        return $this->controller;
    }

    public function render($view, array $data = [])
    {
        $layoutPathname = $this->root.$this->layout.'.php';

        if (!is_file($layoutPathname))
            throw new \RuntimeException("File `$view` does not exist, path `$layoutPathname`");

        $this->outputData = $this->partial($view, $data);

        require($layoutPathname);
    }


    public function partial($view, array $data = [], $returned=true)
    {
        $viewPathname = $this->root.ltrim($this->conf('views'),'/').$view.'.php';

        if (!is_file($viewPathname))
            throw new \RuntimeException("File `$view` does not exist, path `$viewPathname`");

        $data = array_merge($this->partialData, $data);

        extract($data);

        ob_start();

        require($viewPathname);

        if($returned)
            return ob_get_clean();
        else
            echo ob_get_clean();
    }

    public function redirect($url = null, $code = 302, $delay = 0)
    {
        $url = ($url==null) ? $this->url() : $this->url() . $url;

        if ($delay) {
            header('Refresh: ' . $delay . '; url=' . $url, true);
        } else {
            header('Location: ' . $url, true, $code);
        }
        return true;
    }


    # VIEWS
    # - - - - - - -- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    public function output($echo=true)
    {
        if($echo)
            echo $this->outputData;
        else
            return $this->outputData;

    }

    public function setChunk( $name, $view='', array $data=[], $returned=false )
    {
        if(empty($view))
            return $this->chunk[$name] = '';

        $chunkData = $this->partial($view,$data);

        if(!$returned)
            $this->chunk[$name] = $chunkData;
        else
            return $chunkData;
    }


    /**
     * Вызов зарегистрированного чанка. Первый аргумент имя зарегестрированого чанка
     * второй тип возврата метода по умолчанию ECHO, если FALSE данные будет возвращены
     *
     * <pre>
     * Пример:
     *  $this->chunk("myChunk");
     * </pre>
     *
     * @param  string    $chunkName
     * @param  bool      $e
     * @return bool
     */
    public function chunk( $chunkName, $e=true )
    {
        if(isset($this->chunk[$chunkName])){
            if($e)
                echo $this->chunk[$chunkName];
            else
                return $this->chunk[$chunkName];
        }else{
            if($this->debug)
                throw new \RuntimeException("ERROR Undefined chunk `$chunkName`");
            else
                return null;
        }
    }


    # TIMERS
    # - - - - - - -- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    public function timerStart()
    {
        $this->timerStart = microtime(true);
    }

    /**
     * Sets end microtime
     *
     * @return void
     */
    public function timerStop()
    {
        $this->timerEnd = microtime(true);
        $this->memoryUsage = memory_get_usage(true);
    }

    public function getTimer($round = 3)
    {
        if(!$this->timerEnd)
            $this->timerStop();

        $microtime = $this->timerEnd - $this->timerStart;
        return round($microtime, $round);
    }

    public function getMemoryUsage($readable = false, $format = null)
    {
        return $readable ? $this->memoryUsage : $this->readableSize($this->memoryUsage, $format);
    }




    # HELPERS METHODS
    # - - - - - - -- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    public function thisUrl()
    {
        $url = @($_SERVER["HTTPS"] != 'on') ? 'http://' . $_SERVER["SERVER_NAME"] : 'https://' . $_SERVER["SERVER_NAME"];
        $url .= ($_SERVER["SERVER_PORT"] !== 80) ? ":" . $_SERVER["SERVER_PORT"] : "";
        $url .= $_SERVER["REQUEST_URI"];
        return $url;
    }

    public function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }

    public static function ip()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];

        return $_SERVER['REMOTE_ADDR'];
    }
    /**
     * Returns a human readable memory size
     *
     * @param   int    $size
     * @param   string $format   The format to display (printf format)
     * @param   int    $round
     * @return  string
     */
    public static function readableSize($size, $format = null, $round = 3)
    {
        $mod = 1024;
        if (is_null($format)) {
            $format = '%.2f%s';
        }
        $units = explode(' ','B Kb Mb Gb Tb');
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }
        if (0 === $i) {
            $format = preg_replace('/(%.[\d]+f)/', '%d', $format);
        }
        return sprintf($format, round($size, $round), $units[$i]);
    }


}





