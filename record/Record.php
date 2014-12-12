<?
namespace record;

class Record
{

    public $auth = false;
    public $layout;
    public $controller = null;
    public $lang = null;
    private $langDefault = null;
    private $debug;
    private $root;
    private $request;
    private $args;
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
        $fileName = $this->root.$className.'.php';
        if (is_file($fileName))
            include_once $fileName;
    }

    private function setConf(array $conf)
    {
        self::$_conf = [
            'debug'=>false,
            'lang'=>null,
            'root'=> substr($_SERVER['SCRIPT_FILENAME'],0,-10),
            'path'=>'',
            'views'=>'',
            'models'=>'',
            'controllers'=>'',
            'layout'=>'',
            'callError404'=>'',
            'messageError404'=>'<h1>Error 404!</h1><h3>Page not found.</h3>',
        ];

        foreach ($conf as $key=>$val) {
            self::$_conf[$key]=$val;
        }

        $this->langDefault = $this->lang = self::$_conf['lang'];
        $this->debug = self::$_conf['debug'];
        $this->layout = self::$_conf['layout'];
        $this->root = self::$_conf['root'] = trim(self::$_conf['root'],'/').'/';
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
        if(!empty(self::$_conf['path']))
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
            throw new \RuntimeException('invalid callable');
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
        $path = $this->conf('path');
        $request = $_SERVER['REQUEST_URI'];

        if($this->langDefault !== null){
            $_requestFirst = trim($request,'/');
            if(substr($_requestFirst,2,1)=='/'){
                $this->lang = substr($_requestFirst,0,2);
                $request = substr($_requestFirst,2);
            }
        }

        if(!empty($path) && strpos($request,$path)===0){
            $r = trim($request,'/');
            $p = trim($path,'/');
            $request = '/'.trim(substr($r,strlen($p)),'/');
        }

        $isCalled = false;
        $this->request = $request;

        foreach(self::$_maps as $key=>$val)
        {
            if($isCalled) continue;

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

    public function lang($isLang=null)
    {
        if(!empty($isLang)){
            return ($isLang==$this->lang) ? true : false;
        }else{
            if($this->langDefault !== null){
                return $this->lang;
            }
            else
                return null;
        }
    }

    public function langUrl($link)
    {
        if($this->langDefault !== null && $this->langDefault != $this->lang){
            $link = trim($link,'/');
            return '/'.$this->lang.'/'.$link;
        }
        else
            return $link;
    }

    public function lg()
    {

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
        $layoutPathname = $this->root.trim($this->layout,'/').'.php';

        if (!is_file($layoutPathname))
            throw new \RuntimeException("File `$this->layout.php` does not exist, path `$layoutPathname`");

        $this->outputData = $this->partial($view, $data);

        require($layoutPathname);
    }


    public function partial($view, array $data = [], $returned=true)
    {
        $viewPathname = $this->root.trim($this->conf('views'),'/').'/'.trim($view,'/').'.php';

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

    /**
     * Добавляет переменные в вид.
     * Так же обявленные переменные можно вызвать используя ключ.
     * <pre>
     * $app->value('my_var','some var data');
     * $app->value('my_var',); // return 'some var data';
     *
     * // аналогичто передаст переменную в вид как и массив данных метода render()
     * $app->render('view',[
     *      'my_var'=>'some var data'
     * ]);
     *
     * </pre>
     * @param $a
     * @param null $b
     * @param bool $rewrite
     * @return null
     */
    public function value($a, $b=null, $rewrite=true){
        if(is_array($a)){
            foreach ($a as $key=>$val) {
                $this->value($key,$val,$rewrite);
            }
        }else{
            if($b){
                if(isset($this->partialData[$a]) && $rewrite)
                    return false;
                else
                    $this->partialData[$a] = $b;
                return true;
            }else{
                if(isset($this->partialData[$a]))
                    return $this->partialData[$a];
                else{
                    return null;
                }
            }
        }
    }


    public function redirect($url = null, $code = 302, $delay = 0)
    {
        $url = ($url==null) ? $this->url() : $this->url() . trim($url,'/');

        if ($delay) {
            header('Refresh: ' . $delay . '; url=' . $url, true);
        } else {
            header('Location: ' . $url, true, $code);
        }
        return true;
    }


    # prints out no-cache headers before dumping passed content
    public function nocache($content = null) {

        $stamp = gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME']).' GMT';

        # dump no-cache headers
        header('Expires: Tue, 13 Mar 1979 18:00:00 GMT');
        header('Last-Modified: '.$stamp);
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        # if you have content, dump it
        return $content && strlen($content) && (print $content);
    }

    # maps directly to json_encode, but renders JSON headers as well
    public function json() {

        $json = call_user_func_array('json_encode', func_get_args());
        $err = json_last_error();

        # trigger a user error for failed encodings
        if ($err !== JSON_ERROR_NONE) {
            throw new \RuntimeException("JSON encoding failed [{$err}].", 500);
        }

        header('Content-type: application/json');
        return print $json;
    }

    # shortcut for http_response_code()
    public function status($code) {
        return http_response_code($code);
    }


    # accessor for $_COOKIE when fetching values, or maps directly
    # to setcookie() when setting values.
    public function cookies($name, $value=null)
    {
        $argsNum = func_num_args();
        $argsValues = func_get_args();

        if ($argsNum == 1)
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;

        return call_user_func_array('setcookie', $argsValues);
    }
    # accessor for $_SESSION
    public function session($name, $value = null) {

        if(!isset($_SESSION))
            session_start();

        # session var set
        if (func_num_args() == 2)
            return ($_SESSION[$name] = $value);

        # session var get
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    public function post($name=null) {
        if($name==null)
            return (empty($_POST)) ? false : $_POST;
        else
            return isset($_POST[$name]) ? $_POST[$name] : null;
    }

    public function get($name=null) {
        if($name==null)
            return (empty($_GET)) ? false : $_GET;
        else
            return isset($_GET[$name]) ? $_GET[$name] : null;
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

    private static $_flashStorage;
    /**
     * Выводит или регистрирует флеш сообщения для даной страницы или следующей переадрисации.
     * Указать два аргумента для регистрации сообщения, один для вывода. Если указать претий аргумент
     * в FALSE, сообщение будет удалено поле первого вывода.
     *
     * <pre>
     * Регистрация сообщения:
     * App::flash('edit','Запись в базе данных успешно обновлена!');
     * Вывод после переадрисации:
     * App::flash('edit');
     * </pre>
     *
     * @param string $key Ключ флеш сообщения
     * @param mixed $value Значение
     * @param bool $keep Продлить существования сообщения до следущего реквкста; по умолчанию TRUE
     *
     * @return mixed
     */
    public static function flash($key = null, $value = null, $keep = true)
    {
        if (!isset($_SESSION)) session_start();
        $flash = 'flash';

        if (func_num_args() > 1)
        {
            $old = isset($_SESSION[$flash][$key]) ? $_SESSION[$flash][$key] : null;

            if (isset($value)) {
                $_SESSION[$flash][$key] = $value;

                if ($keep)
                    self::$_flashStorage[$key] = $value;
                else
                    unset(self::$_flashStorage[$key]);

            } else {
                unset(self::$_flashStorage[$key]);
                unset($_SESSION[$flash][$key]);
            }

            return $old;

        }
        else if (func_num_args())
        {
            $flashMessage = isset($_SESSION[$flash][$key]) ? $_SESSION[$flash][$key] : null;
            unset(self::$_flashStorage[$key]);
            unset($_SESSION[$flash][$key]);
            return $flashMessage;
        }
        else
            return self::$_flashStorage;

    }


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

    public static function sendMail($to, $subject, $message, $headers)
    {
        $to      = 'nobody@example.com';
        $subject = 'the subject';
        $message = 'hello';
        $headers = 'From: webmaster@example.com' . "\r\n" .
            'Reply-To: webmaster@example.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);
    }
}





