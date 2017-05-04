<?php
/**
 * 核心模型类
 * User: Dean.Lee
 * Date: 16/9/12
 */

namespace Root;

Class Model {

    Public $db = [];
    Public $config = null;
    Public $dbname = null;

    Public function __construct(string $dbname = null)
    {
        $this->config = \Root::$conf[$dbname?:'DB_CONF'];
        if(empty($this->config) && !is_array($this->config)){
            \Root::error('您的数据库信息尚未配置', '请在config文件夹下的database.ini.php中配置好您的数据库信息！', E_USER_ERROR);
        }else{
            if(isset($this->config['DB_NAME'])){
                $this->dbname = $dbname === null ? $this->config['DB_NAME'] : $dbname;
            }elseif (($dbname !== null && isset($this->config[$dbname]['DB_NAME'])) || ($dbname === null && isset($this->config[0]['DB_NAME']))){
                $this->dbname = $dbname === null ? $this->config[0]['DB_NAME'] : $dbname;
                $this->config = $this->config[$dbname];
            }
            if(!array_key_exists($this->dbname, $this->db)){
                if($this->config['COROUTINE'])
                    $classname = "\\Root\\Db\\{$this->config['DB_TYPE']}Coroutine";
                else
                    $classname = "\\Root\\Db\\{$this->config['DB_TYPE']}PDO";
                $this->db[$this->dbname] = new $classname($this->config);
            }
        }
    }

    /**
     * 选择数据表
     * @param string $table 数据表名
     * @return \Root\Model
     */
    public function table(string $table, string $asWord = '')
    {
        if(strpos($table, ' ') !== false){
            $arr = explode(' ', $table);
            $table = $arr[0];
            if(strtolower($arr[1]) == 'as')$asWord = $arr[2];
            else $asWord = $arr[1];
        }
        $table = preg_replace_callback('/(?!^)[A-Z]{1}/', function($result){
            return '_' . strtolower($result[0]);
        }, $table);
        $table = strtolower($table);
        if($this->db[$this->dbname]->table($table, $asWord)){
            return $this;
        }else{
            return false;
        }
    }

    /**
     * 输出数据表字段信息
     * @param $name 数据表字段名
     * @return string|boolean
     */
    public function __get(string $name)
    {
        return $this->db[$this->dbname]->$name;
    }

    /**
     * 配置字段的值，为insert/update做准备
     * @param string $name
     * @param string $value
     * @return boolean
     */
    public function __set(string $name, $value = null)
    {
        return $this->db[$this->dbname]->$name = $value;
    }

    /**
     * field 字段组装子句
     * @param mixed $field 字段或字段数组 ['字段', '字段' => '别名']
     * @param string $table 字段所在表名
     * @return \Root\Model
     */
    public function field($field, string $table = null)
    {
        $this->db[$this->dbname]->field($field, $table);
        return $this;
    }

    /**
     * 条件判断子句
     * @param mixed $condition 条件字符串或条件数组 ['字段' => ['比较符号', '值']]
     * @return \Root\Model
     */
    public function where($condition, string $table = null)
    {
        $this->db[$this->dbname]->where($condition, $table);
        return $this;
    }

    /**
     * 分组查询group子句
     * @param mixed $field 用于分组的一个字段
     * @param array $having 用于分组筛选的条件
     * @return \Root\Model
     */
    public function group($field, array $having = [])
    {
        $this->db[$this->dbname]->group($field, $having);
        return $this;
    }

    /**
     * 关联组合
     * @param string $table 被关联的表名
     * @param array $condition 关联条件 on ...
     * @param string $type 关联类型,默认内联
     * @return \Root\Model
     */
    public function join(string $table, array $condition = [], string $type = 'inner')
    {
        $this->db[$this->dbname]->join($table, $condition, $type);
        return $this;
    }

    /**
     * order by组装子句
     * @param $order 组装字段
     * @param string $asc 排序方式 asc-desc
     * @return \Root\Model
     */
    public function order(string $order, string $asc = '', string $table = null)
    {
        if(strpos($order, '.') !== false){
            $arr = explode($order, '.');
            $table = $arr[0];
            $order = $arr[1];
        }
        $this->db[$this->dbname]->order($order, $asc, $table);
        return $this;
    }

    /**
     * limit子句
     * @param mixed $limit 偏移开始位置
     * @param int $length 读取条数
     * @return \Root\Model
     */
    public function limit($limit, int $length = null)
    {
        $this->db[$this->dbname]->limit($limit, $length);
        return $this;
    }

    /**
     * 查询记录集
     * @param boolean $cache 是否使用缓存（效率较低的语句建议开启缓存）
     * @return array 结果集数组
     */
    public function select(bool $cache = false)
    {
        $rs = $this->db[$this->dbname]->select($cache);
        return $rs;
    }

    /**
     * 查询单条记录
     * @param boolean $cache 是否使用缓存（效率较低的语句建议开启缓存）
     * @return array 结果集数组
     */
    public function getone(bool $cache = false)
    {
        $rs = $this->db[$this->dbname]->getone($cache);
        return $rs;
    }

    /**
     * 返回单个字段组成的记录集
     * @param $name 字段名
     * @param bool $is_array 是否返回记录集
     * @return array|bool
     */
    public function getField(string $name, bool $is_array = false)
    {
        $table = '';
        if(strpos($name, '.') !== false){
            $arr = explode('.', $name);
            $table = $arr[0];
            $name = $arr[1];
        }
        $this->db[$this->dbname]->field($name, $table);
        if(!$is_array){
            $rs = $this->db[$this->dbname]->getone(false);
            if(isset($rs[$name])){
                return $rs[$name];
            }else{
                return false;
            }
        }else{
            $rs = $this->db[$this->dbname]->select(false);
            $data = [];
            foreach($rs as $row){
                if(isset($row[$name])){
                    $data[] = $row[$name];
                }else{
                    return false;
                }
            }
            return $data;
        }
    }

    /**
     * 插入数据记录
     * @param array $datas 要插入的数据数组 ['字段' => '值',...]
     * @param bool $return 是否返回插入的ID
     * @return array
     */
    public function insert(array $datas = [], bool $return = false)
    {
        $rs = $this->db[$this->dbname]->insert($datas, $return);
        return $rs;
    }

    /**
     * 多条插入记录
     * @param array $dataAll 要插入的数据数组
     * @return array
     */
    public function insertAll(array $dataAll)
    {
        $rs = $this->db[$this->dbname]->insertAll($dataAll);
        return $rs;
    }

    /**
     * 数据更新
     * @param array $datas 要更新的数据数组
     * @return array
     */
    public function update(array $datas = [])
    {
        $rs = $this->db[$this->dbname]->update($datas);
        return $rs;
    }

    /**
     * 数据删除
     * @param mixed $limit 偏移开始位置
     * @param int $length 数据条数
     * @return array
     */
    public function delete(int $limit = null, int $length = null)
    {
        $rs = $this->db[$this->dbname]->delete($limit, $length);
        return $rs;
    }

    /**
     * 统计数量子句
     * @param string $field 被统计的字段
     * @return int|boolean
     */
    public function count(string $field = null)
    {
        $rs = $this->db[$this->dbname]->fun('count', $field ?: '*');
        return $rs;
    }

    /**
     * 统计总量子句
     * @param string $field 被统计的字段
     * @return int|boolean
     */
    public function sum(string $field = null)
    {
        $rs = $this->db[$this->dbname]->fun('sum', $field ?: '*');
        return $rs;
    }

    /**
     * 统计平均值子句
     * @param string $field 被统计的字段
     * @return int|boolean
     */
    public function avg(string $field = null)
    {
        $rs = $this->db[$this->dbname]->fun('avg', $field ?: '*');
        return $rs;
    }

    /**
     * 统计最小值子句
     * @param string $field 被统计的字段
     * @return int|boolean
     */
    public function min(string $field = null)
    {
        $rs = $this->db[$this->dbname]->fun('min', $field ?: '*');
        return $rs;
    }

    /**
     * 统计最大值子句
     * @param string $field 被统计的字段
     * @return int|boolean
     */
    public function max(string $field = null)
    {
        $rs = $this->db[$this->dbname]->fun('max', $field ?: '*');
        return $rs;
    }

    /**
     * 字段自增
     * @param string $field 字段名
     * @param int $num 步长
     * @return int|boolean 影响记录行数
     */
    public function setInc(string $field, int $num = 1)
    {
        $data = [
            $field => '+' . $num
        ];
        $rs = $this->db[$this->dbname]->update($data);
        return $rs;
    }

    /**
     * 字段自减
     * @param string $field 字段名
     * @param int $num 步长
     * @return int|boolean 影响记录行数
     */
    public function setDec(string $field, int $num = 1)
    {
        $data = [
            $field => '-' . $num
        ];
        $rs = $this->db[$this->dbname]->update($data);
        return $rs;
    }

    /**
     * 获取执行的SQL语句
     * @param bool $create 是否重新组装
     * @return string
     */
    Public function _sql(bool $create = false)
    {
        if($create)
            return $this->db[$this->dbname]->_sql();
        else
            return $this->db[$this->dbname]->sql;
    }

    /**
     * 查询sql语句
     * @param $sql 要执行的SQL语句
     * @return array|boolean
     */
    Public function query(string $sql)
    {
        $rs = $this->db[$this->dbname]->query($sql);
        if(!$rs){
            $err = $this->link->errorInfo();
            \Root::error("SQL执行出错！", "错误原因：". join("\n", $err) ."\nSQL语句：{$sql}", E_USER_WARNING);
            return false;
        }else{
            $rs = $rs->fetchall();
            $put = [];
            foreach($rs as $key => $row){
                foreach($row as $k => $r){
                    if(!is_numeric($k))
                        $put[$key][$k] = $r;
                }
            }
            return $put;
        }
    }

    /**
     * 执行SQL语句
     * @param $sql 要执行的SQL语句
     * @return boolean
     */
    Public function execute(string $sql)
    {
        return $this->db[$this->dbname]->execute($sql);
    }

    /**
     * 创建临时表
     * @param bool $tablename 临时表名称
     * @param string $sql 临时表SQL语句
     * @return boolean
     */
    Public function createTmp(string $tablename = null, string $sql = null)
    {
        return $this->db[$this->dbname]->createTmp($tablename, $sql);
    }

    /**
     * 更新临时表
     * @param $tablename 要更新的临时表名
     * @return boolean
     */
    Public function updateTmp(string $tablename)
    {
        return $this->db[$this->dbname]->updateTmp($tablename);
    }

}

