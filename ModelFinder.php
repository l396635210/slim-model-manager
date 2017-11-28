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

    public function findOne($id){
        $db = $this->pdo;
        $table = $this->table;
        $columns = implode(',', $this->columns);
        $sql = "SELECT id, {$columns} FROM {$table} WHERE id=:id";
        $sth = $db->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, $this->modelName);
        $sth->bindParam(':id', $id);
        $sth->execute();
        return $sth->fetchAll();
    }

    public function findBy(array $conditions, array $orderBy=['id'=>'asc'], $offset=0, $limit=12){
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
        $limit = " LIMIT {$offset}, {$limit}";
        $sql = $sqlStart.$where.$sorting.$limit;
        $sth = $this->pdo->prepare($sql);
        $sth->setFetchMode(\PDO::FETCH_CLASS, $this->modelName);
        $sth->execute($params);
        return $sth->fetchAll();
    }
}