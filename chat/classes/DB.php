<?php

namespace classes;

/**
 * Class DB Simple PDO wrapper
 *
 * http://php.net/manual/ru/book.pdo.php
 *
 * $DBH - $PDO
 * $STH - $PDOStat
 *
 * $DB->PDO()->exec()
 * Доступ к стандартному методу PDO::exec. Запускает SQL запрос на выполнение и возвращает
 * количество задействованых строк
 *
 * $DB->PDO()->query()
 * Доступ к стандартному методу PDO::query. Выполняет SQL запрос и возвращает результирующий
 * набор в виде объекта PDOStatement. Метод отличается от одноименного метода
 * класса, подробней в комментариях метода DB->query()
 *
 * $DB->PDO()->lastInsertId();
 * Возвращает ID последней вставленной строки
 *
 * $DB->PDOStat->rowCount();
 * Возвращает количество затронутых строк при запросах DELETE, INSERT или UPDATE
 *
 */
class DB
{
    /** @var null|\PDO */
    private $PDO = null;

    /** @var null|\PDOStatement  */
    private $PDOStat = null;

    /** @var null|string  */
    private $lastSQL = null;

    /** @var int  */
    private $numExecutes = 0;

    /**
     * Определение настроек соединения с базой данных
     *
     * Параметры $settings аналогичны параметрам класса PDO::__construct()
     * Параметр $settings['opt'] driver_options Массив специфичных для драйвера настроек
     * подключения ключ=>значение. Может быть установлен с помощю метода класса
     * ->setAttribute() или глобальней ->PDO->setAttribute()
     *
     * <pre>
     * например MySQL:
     * $DB = new DB([
     *      'dsn'=>'mysql:host=localhost;dbname=test',
     *      'user'=>'username',
     *      'pass'=>'password',
     *      'opt'=>[PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES \'UTF8\'']
     * ]);
     *
     * SQLite:
     * $DB = new DB([
     *      'dsn'=>'sqlite:db/db1.sqlite',
     *      'opt'=>[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC],
     * ]);
     *
     * MS SQL Server:
     * $DB = new DB([
     *      'dsn'=>'mssql:host=localhost;dbname=test',
     *      'user'=>'username',
     *      'pass'=>'password',
     * ]);
     * </pre>
     * @param array $settings
     */
    public function __construct($settings)
    {
        $dsn  = (empty($settings['dsn']))  ? '' : $settings['dsn'];
        $user = (empty($settings['user'])) ? '' : $settings['user'];
        $pass = (empty($settings['pass'])) ? '' : $settings['pass'];
        $opt  = (empty($settings['opt']))  ? '' : (is_array($settings['opt'])) ? $settings['opt'] : [
            \PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC
        ];

        try{
            $this->PDO = new \PDO($dsn,$user,$pass,$opt);
        }catch(\PDOException $e){
            die("Error: ". $e->getMessage() );
        }
    }

    /**
     * Возвращает обект PDO текущего соединения.
     * <pre>
     * Работа с екземпляром:
     * $PDO = $DB->PDO();
     * $PDOStat = $PDO->prepare('SELECT * FROM users WHERE id=:num');
     * $PDOStat->execute(['num'=>5]);
     * $getUser = $PDOStat->fetch(PDO::FETCH_OBJ);
     * </pre>
     * @return bool|null|\PDO
     */
    public function PDO()
    {
        if($this->PDO instanceof \PDO ){
            return $this->PDO;
        }else{
            return false;
        }
    }

    /**
     * @return bool|null|\PDOStatement
     */
    public function PDOStatement()
    {
        if($this->PDOStat instanceof \PDOStatement ){
            return $this->PDOStat;
        }else{
            return false;
        }
    }

    /**
     * Присвоение атрибута
     *
     * <pre>
     * setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     * setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
     * setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES \'UTF8\'');
     * </pre>
     *
     * @param $attribute
     * @param $value
     */
    public function setAttribute($attribute, $value)
    {
        $this->PDO->setAttribute($attribute, $value);
    }


    /**
     * Метод класса query() отличается от одноименного в обекте PDO, метод класса query() возведенный в себе
     * PDO::prepare() и PDOStatement::execute() и возвращает PDOStatement
     *
     * <pre>
     * Несколько примеров:
     * $DB = new DB(...
     *
     * $result = $DB->query('SELECT * FROM table WHERE id=5')->fetch();
     * $result = $DB->query("SELECT * FROM table WHERE id=:num", [':num'=>10])->fetch();
     * $result = $DB->query("SELECT * FROM table WHERE name like ?',['%Some Name%'])->fetch();
     *
     * $sth = $DB->query("SELECT * FROM table WHERE id-:numId", [':numId'=>184]);
     * $result = $sth->fetch()
     *
     * $DB->query("SELECT * FROM table WHERE id-:numId", [':numId'=>184])
     * $result = $DB->PDOStatement()->fetch([PDO::FETCH_ASSOC]);
     *
     * $rowsUpdate = $DB->query('UPDATE table
     *                          SET link=:link, title=:title
     *                          WHERE id=:num',
     * [
     *      'link'=>'string',
     *      'title'=>'string',
     *      'num'=>10
     * ]);
     *
     *$selectData = $DB->query('SELECT t1.*, t2.email, t2.name
     *                          FROM table t1
     *                          INNER JOIN table2 t2 ON(t2.id = t1.user_id)
     *                          WHERE t2.user_id=:num',
     * [
     *      'num'=>10
     * ])->fetchAll(PDO::FETCH_OBJ);
     *
     * </pre>
     *
     * @param string    $prepare
     * @param array     $params
     * @return null|\PDOStatement извлеч данные можно методами PDO fetch(), fetchAll(), fetchColumn(), fetchObject()
     */
    public function query($prepare, array $params=[])
    {
        try {
            $this->PDOStat = $this->PDO->prepare($prepare);
            $this->PDOStat->execute($params);
            $this->lastSQL = $prepare;
            $this->numExecutes ++;
            return $this->PDOStat;
        } catch(\PDOException $e) {
            die("Error query ".$e->getMessage());
        }
    }

    /**
     * Привязывает параметр запроса к переменной
     * Приближенный PDOStatement::bindParam()
     *
     * @param $parameter
     * @param $variable
     * @param string $type \PDO::PARAM_STR | \PDO::PARAM_INT |
     * @return $this
     */
    public function bindParam($parameter, $variable, $type=null)
    {
        $type = ($type==null) ? \PDO::PARAM_STR : $type;
        $this->PDOStat->bindParam($parameter, $variable, $type);
        return $this;
    }

    /**
     * Возвращает строку последнего запроса
     *
     * @return null|string
     */
    public function lastSQL()
    {
        return $this->lastSQL;
    }

    /**
     * Упрощенный запрос SELECT, дает возможность обращатся только к одной таблице.
     *
     * <pre>
     * $selectData = $DB->select('table','*','id=10')->fetch();
     * $selectData = $DB->select('table','*','id=:num',['num'=>10])->fetch();
     * $selectData = $DB->select('table','title, content','id=:num',['num'=>8])->fetch();
     * $selectData = $DB->select('table',['title','content'],'id=:num',['num'=>8])->fetch();
     * </pre>
     * @param $table
     * @param $columns
     * @param $condition
     * @param array $conditionParams
     * @return null|\PDOStatement
     */
    public function select($table, $columns, $condition=null, array $conditionParams = [])
    {
        $columns = (is_array($columns)) ? join(',',$columns) : $columns;
        $prepare = "SELECT $columns FROM ".trim($table);
        if($condition)
            $prepare .= " WHERE ".$condition;
        $this->query($prepare, $conditionParams);
        return $this->PDOStat;
    }

    /**
     * Упрощенный запрос INSERT
     * <pre>
     * $insertId = $DB->insert('table',
     * [
     *      'title'=>'My Title',
     *      'content'=>'My Content',
     * ]);
     * </pre>
     * @param string $table
     * @param array $params
     * @return string
     */
    public function insert($table, array $params=[])
    {
        if(func_num_args()>1) {
            $prepare = "INSERT INTO ".trim($table);
            $prepare .= " (".join(",",array_keys($params)).")";
            $prepare .= " VALUES('".join("','",$params)."')";
            $this->query($prepare);
            return $this->PDO->lastInsertId();
        }else{
            $prepare = "INSERT INTO ".trim($table);
            $this->buildSqlString = $prepare;
            return $this;
        }
    }

    private $buildSqlString;

    public function from() {}
    public function leftJoin() {}
    public function innerJoin() {}
    public function join() {}
    public function where() {}
    public function orderBy() {}
    public function union() {}
    public function groupBy() {}
    public function limit() {}
    public function set() {}
    public function values() {}

    /**
     * Упрощенный запрос UPDATE
     * <pre>
     * $updateRows = $DB->update('table',
     * [
     *      'title'=>'Update Title',
     *      'content'=>'Update Content',
     * ],
     * "sum < :num",
     * [
     *      'num'=>'8'
     * ]);
     * </pre>
     * UPDATE table SET val1=:val1, val2=:val2, val3=:val4 WHERE id=:num
     *
     * @param string $table
     * @param array $params
     * @param string $condition
     * @param array $conditionParams
     * @return number
     */
    public function update($table, array $params, $condition, array $conditionParams = null)
    {
        $prepare = "UPDATE ".trim($table);
        $prepare .= " SET ".join(",",array_map(function($val){return $val."=:".$val;},array_keys($params)));
        $prepare .= " WHERE ".$condition;
        if($conditionParams)
            $params = array_merge($params,$conditionParams);
        $this->query($prepare, $params);
        return $this->PDOStat->rowCount();
    }

    /**
     * Упрощенный запрос DELETE
     *
     * <pre>
     * $countDelRows = $DB->delete('table','id=:num',['num'=>10]);
     * </pre>
     * @param $table
     * @param $condition
     * @param array $conditionParams
     * @return int
     */
    public function delete($table, $condition, array $conditionParams = [])
    {
        $prepare = "DELETE FROM ".trim($table);
        $prepare .= " WHERE ".$condition;
        $this->query($prepare, $conditionParams);
        return $this->PDOStat->rowCount();
    }

    /**
     * Возвращает количество записей
     * @param $table
     * @param null $condition
     * @param array $conditionParams
     * @return int
     */
    public function count($table, $condition=null, array $conditionParams = [])
    {
        $prepare = "SELECT COUNT(*) as count FROM ".trim($table);
        if($condition)
            $prepare .= " WHERE ".$condition;
        $fetchAll = $this->query($prepare, $conditionParams)->fetch();
        return (int) $fetchAll['count'];
    }


    /**
     * @param $table
     * @param $primaryKey
     * @return int
     */
    public function lastRecord($table, $primaryKey='id')
    {
        $prepare = "SELECT $primaryKey as pkey FROM $table ORDER BY $primaryKey DESC";
        $fetch = $this->PDO->query($prepare)->fetch();
        return (int)$fetch['pkey'];
    }


    /**
     * Транзакция совокупность запросов базу данных.
     * Поле определения метода необходимо подтвердить или опровергнуть транзакцию.
     */
    public function transactionBegin()
    {
        $this->PDO->beginTransaction();
    }

    /**
     * Подтверждение транзакции
     */
    public function transactionCommit()
    {
        $this->PDO->commit();
    }

    /**
     * Отмена и откат запросов транзакции
     */
    public function transactionRollback()
    {
        $this->PDO->rollback();
    }

    public function numExecutes()
    {
        return $this->numExecutes;
    }

}