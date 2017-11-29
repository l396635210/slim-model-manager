<?php
/**
 * Created by PhpStorm.
 * User: yu
 * Date: 2017/11/19
 * Time: 7:44
 */

namespace Liz\ModelManager;


class ModelFinder
{

    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var
     */
    protected $modelName;



    public function __construct(ModelManager $modelManager, $relation, $modelToString)
    {
        $this->modelManager = $modelManager;
        $this->pdo = $modelManager->getPDO();
        $this->table = $relation['table'];
        $this->fields = $relation['fields'];
        $this->columns = array_keys($this->fields);
        $this->modelName = $modelToString;
    }

    /**
     * @return array|[PDO]
     */
    protected function initFind(){

        $table = $this->table;
        $fields = implode(',', $this->fields);
        return [
            $table, $fields,
        ];
    }

    /**
     * @return array
     */
    public function findAll(){
        $db = $this->pdo;
        $table = $this->table;
        $columns = implode(',', $this->columns);
        $sql = "SELECT id, {$columns} FROM {$table}";
        $sth = $db->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, $this->modelName);
        $sth->execute();
        return $sth->fetchAll();
    }

    /**
     * @return string
     */
    protected function generateFindOneSQL(){
        $table = $this->table;
        $columns = implode(',', $this->columns);
        $sql = "SELECT id, {$columns} FROM {$table} WHERE id=:id";
        return $sql;
    }

    /**
     * @param $id
     * @return object
     */
    public function findOne($id){
        $db = $this->pdo;
        $sql = $this->generateFindOneSQL();
        $sth = $db->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, $this->modelName);
        $sth->bindParam(':id', $id);
        $sth->execute();
        return $sth->fetch();
    }

    /**
     * @param $id
     * @return array
     */
    public function findOneWithArr($id){
        $db = $this->pdo;
        $sql = $this->generateFindOneSQL();
        $sth = $db->prepare($sql);
        $sth->bindParam(':id', $id);
        $sth->execute();
        return $sth->fetch();
    }

    /**
     * @param array $conditions
     * @param array $orderBy
     * @param int $offset
     * @param int $limit
     * @return array
     */
    protected function generateFindBySQL(array $conditions, array $orderBy=['id'=>'asc'], $offset=12, $limit=0){
        $columns = implode(',', $this->columns);
        $sqlStart = "SELECT id, {$columns} FROM {$this->table} ";
        $where = " WHERE 1=1 ";
        $params = [];
        foreach ($conditions as $field=>$value){
            if(is_array($value)){
                $values = "";
                foreach ($value as $k=>$subValue){
                    $values .= ":{$field}$k,";
                    $params[$field.$k] = $subValue;
                }
                $where .= " AND {$field} in (".substr($values, 0, -1).")";
            }elseif (is_null($value)){
                $where .= " AND $field is :$field ";
                $params[$field] = $value;
            }else{
                $where .= " AND $field = :$field ";
                $params[$field] = $value;
            }
        }
        $sorting = " ORDER BY ";
        foreach ($orderBy as $field=>$sort){
            $sorting .= " $field $sort,";
        }
        $sorting = substr($sorting, 0, -1);
        $limit = " LIMIT {$limit}, {$offset}";
        $sql = $sqlStart.$where.$sorting.$limit;
        return [
            $sql,
            $params,
        ];
    }

    /**
     * @param array $conditions
     * @param array $orderBy
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function findBy(array $conditions, array $orderBy=['id'=>'asc'], $offset=12, $limit=0){
        list($sql, $params) = $this->generateFindBySQL($conditions, $orderBy, $offset, $limit);

        $sth = $this->pdo->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, $this->modelName);
        $sth->execute($params);
        return $sth->fetchAll();
    }

    /**
     * @param array $conditions
     * @param array $orderBy
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function findByWithArr(array $conditions, array $orderBy=['id'=>'asc'], $offset=12, $limit=0){
        list($sql, $params) = $this->generateFindBySQL($conditions, $orderBy, $offset, $limit);
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        return $sth->fetchAll();
    }

    /**
     * @return int
     */
    public function count(){
        $sql = "SELECT COUNT(*) AS cnt FROM {$this->table}";
        $sth = $this->pdo->prepare($sql);
        $sth->execute();
        $res = $sth->fetch();
        return $res['cnt'];
    }

    /**
     * @param array $conditions
     * @return int
     */
    public function countBy(array $conditions){
        $sqlStart = "SELECT COUNT(*) AS cnt FROM {$this->table} ";
        $where = " WHERE 1=1 ";
        $params = [];
        foreach ($conditions as $field=>$value){
            if(is_array($value)){
                $values = "";
                foreach ($value as $k=>$subValue){
                    $values .= ":{$field}$k,";
                    $params[$field.$k] = $subValue;
                }
                $where .= " AND {$field} in (".substr($values, 0, -1).")";
            }elseif (is_null($value)){
                $where .= " AND $field is :$field ";
                $params[$field] = $value;
            }else{
                $where .= " AND $field = :$field ";
                $params[$field] = $value;
            }
        }
        $sql = $sqlStart.$where;
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        $res = $sth->fetch();
        return $res['cnt'];
    }

}