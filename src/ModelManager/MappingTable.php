<?php
/**
 * Created by PhpStorm.
 * User: yu
 * Date: 2017/11/19
 * Time: 7:44
 */

namespace Liz\ModelManager;


class MappingTable
{

    /**
     * @var string
     */
    protected $table;

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * MappingTable constructor.
     * @param ModelManager $modelManager
     * @param string $table
     */
    public function __construct(ModelManager $modelManager, $table)
    {
        $this->modelManager = $modelManager;
        $this->pdo = $modelManager->getPDO();
        $this->table = $table;
    }

    /**
     * @param array $conditions
     * @param array $orderBy
     * @param int $offset
     * @param int $limit
     * @return array
     */
    protected function generateFindBySQL(array $conditions, array $orderBy=['id'=>'asc'], $offset=12, $limit=0){
        $sqlStart = "SELECT * FROM {$this->table} ";
        $where = " WHERE 1=1 ";
        $params = [];
        foreach ($conditions as $field=>$value){
                $where .= " AND $field = :$field ";
                $params[$field] = $value;
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
     * @return mixed
     */
    protected function findOneByWithArr(array $conditions){
        list($sql, $params) = $this->generateFindBySQL($conditions, ['id'=>'DESC'], 1, 0);
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        return $sth->fetch();
    }

    /**
     * @param array $conditions
     * @param array $orderBy
     * @param int $offset
     * @param int $limit
     * @return array
     */
    protected function findByWithArr(array $conditions, array $orderBy=['id'=>'asc'], $offset=12, $limit=0){
        list($sql, $params) = $this->generateFindBySQL($conditions, $orderBy, $offset, $limit);
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        return $sth->fetchAll();
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
            $where .= " AND $field = :$field ";
            $params[$field] = $value;
        }
        $sql = $sqlStart.$where;
        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        $res = $sth->fetch();
        return $res['cnt'];
    }

    /**
     * @param array $conditions
     * @return int
     */
    public function removeBy(array $conditions){
        $sqlStart = "DELETE FROM {$this->table} ";
        $where = " WHERE 1=1 ";
        $params = [];
        foreach ($conditions as $field=>$value){
            if(is_array($value)){
                $values = "";
                foreach ($value as $k=>$subValue){
                    $values .= ":{$field}$k,";
                    $params[$field.$k] = trim($subValue);
                }
                $where .= " AND {$field} in (".substr($values, 0, -1).")";
            }else{
                $where .= " AND $field = :$field ";
                $params[$field] = $value;
            }
        }
        $sql = $sqlStart.$where;

        $sth = $this->pdo->prepare($sql);
        $sth->execute($params);
        return $sth->rowCount();
    }

    protected function insert(array $saveData){

        $columns = implode(' , ', array_keys($saveData));
        $values = implode(' , :', array_keys($saveData));

        $sql = "INSERT INTO {$this->table} \n ({$columns}) \n VALUES \n (:$values);";
        $sth = $this->pdo->prepare($sql);
        foreach ($saveData as $column=>$value){
            $sth->bindValue($column, $value, \PDO::PARAM_INT);
        }
        $sth->execute();
        $id = $this->pdo->lastInsertId();
        return $id;
    }

    public function mapping($model, $set, $modelID, $setID){
        $ids = array_keys($set);
        $this->removeBy([
            $modelID => $model->getID(),
            $setID   => $ids,
        ]);
        foreach ($set as $item){
            $this->insert([
                $modelID => $model->getID(),
                $setID   => $item->getID(),
            ]);
        }
    }

    public function findSet($model, $setClass, $setID, $withArr=false){
        $set = [];
        $mappings = $this->findByWithArr([
            $setID => $model->getID(),
        ]);
        if($mappings){
            $ids = array_column($mappings, $setID);
            $finder = ModelManager::getInstance()->getFinder($setClass);
            if($withArr){
                $set = $finder->findByWithArr([
                    'id' => $ids
                ]);
            }else{
                $set = $finder->findBy([
                    'id' => $ids
                ]);
            }
        }
        return $set;
    }

}