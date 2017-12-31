<?php
/**
 * Created by PhpStorm.
 * User: yu
 * Date: 2017/11/19
 * Time: 6:21
 */

namespace Liz\ModelManager;

use Liz\ModelManager\Helper\ModelHelper;

class ModelManager
{

    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var array
     */
    protected $relations;

    /**
     * @var ModelFinder
     */
    private $finder;

    /**
     * @var MappingTable
     */
    private $mappingTable;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var ModelHelper
     */
    private $modelHelper;

    /**
     * @var array
     */
    private $models=[];

    /**
     * @var array
     */
    private $removes = [];

    /**
     * @return mixed
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * @return \PDO
     */
    public function getPDO()
    {
        return $this->pdo;
    }

    protected function __construct(\PDO $PDO, array $relations)
    {
        $this->pdo = $PDO;
        $this->relations = $relations;
        $this->modelHelper = new ModelHelper();
    }


    /**
     * @param $modelToString
     * @return ModelFinder
     */
    public function getFinder($modelToString){

        $relation = $this->parseModel($modelToString);

        $finder = $this->getFinderByModelName($modelToString);

        if(class_exists($finder)){
            $this->finder = new $finder($this, $relation, $modelToString);
        }else{
            $this->finder = new ModelFinder($this, $relation, $modelToString);
        }

        return $this->finder;
    }

    public function getMappingTable($tableName){
        if(!$this->mappingTable){
            $this->mappingTable = new MappingTable($this, $tableName);
        }
        return $this->mappingTable;
    }

    protected function getFinderByModelName($model){
        return strtr($model, [
                'Model'=>'Finder',
            ]).'Finder';
    }

    public static function getInstance(\PDO $PDO=null, array $relations=null){
        $self = self::$instance;
        if(!$self){
            self::$instance = new self($PDO, $relations);
        }
        return self::$instance;
    }

    public function persist($model){
        if(!in_array($model, $this->models)){
            $this->models[] = $model;
        }
        return $this;
    }

    public function flush(){
        try{
            $this->pdo->beginTransaction();
            foreach ($this->models as $model){
                $this->submit($model);
            }
            foreach ($this->removes as $remove){
                $sth = $this->pdo->prepare($remove['sql']);
                $sth->bindValue('id', $remove['params']['id'], \PDO::PARAM_INT);
                $sth->execute();
            }
            $this->pdo->commit();
        }catch (\Exception $exception){
            var_dump($exception->getMessage());
            $this->pdo->rollBack();die;
        }

    }


    protected function submit($model){
        if($model->getID()){
            $this->update($model);
        }else{
            $this->insert($model);
        }
        return $model;
    }

    protected function parseModel($model){
        if(is_object($model)){
            $class = get_class($model);
        }else{
            $class = $model;
        }
        $bundle= explode('\model\\',strtolower($class))[0];
        $model = explode('\Model\\',$class)[1];

        $table = $this->relations[$bundle][ModelHelper::hump2_($model)];

        return $table;
    }

    protected function insert(&$model){
        $tableInfo = $this->parseModel($model);
        $table = $tableInfo['table'];
        $fields = $tableInfo['fields'];

        $columns = implode(' , ', array_keys($fields));
        $values = implode(' , :', array_keys($fields));

        $sql = "INSERT INTO {$table} \n ({$columns}) \n VALUES \n (:$values);";
        $sth = $this->pdo->prepare($sql);
        foreach ($fields as $column=>$desc){
            $pdoType = $this->modelHelper->fetchPdoTypeByFieldInfo($desc);
            $getter = 'get'.$this->modelHelper->_2hump($column);
            $value = $model->$getter();
            if($value instanceof \DateTime){
                $value = $value->format('Y-m-d H:i:s');
            }
            $sth->bindValue($column, trim($value), $pdoType);
        }

        $sth->execute();

        $id = $this->pdo->lastInsertId();
        $model->setID($id);
    }

    protected function update(&$model){
        $tableInfo = $this->parseModel($model);
        $table = $tableInfo['table'];
        $fields = $tableInfo['fields'];

        $sqlStart = "UPDATE {$table} SET \n";
        $sqlBody = [];
        $data = [];
        foreach ($fields as $column=>$desc){
            $method = 'get'.ModelHelper::_2hump($column);
            $value = $model->$method();
            if($value!==null || trim($value)!==''){
                $sqlBody[] = $column.' = :'.$column;
                if($value instanceof \DateTime){
                    $value = $value->format('Y-m-d H:i:s');
                }
                $data[':'.$column] = trim($value);
            }
        }
        $sqlEnd = " WHERE id = :id;";
        $sql = $sqlStart.implode(',', $sqlBody).$sqlEnd;
        $sth = $this->pdo->prepare($sql);
        $data[':id'] = $model->getID();
        $sth->execute($data);
    }


    /**
     * @param $model
     * @param bool $hard
     * @param string $field
     * @return bool
     */
    public function remove(&$model, $hard=true, $field='status'){
        if($model->getID()){
            $tableInfo = $this->parseModel($model);
            $table = $tableInfo['table'];
            if($hard){
                $sql = "DELETE FROM {$table} WHERE id = :id LIMIT 1 ";
            }else{
                $sql = "UPDATE {$table} SET {$field} = 0 WHERE id = :id LIMIT 1 ";
            }
            $id = $model->getID();
            $this->removes[] = [
                'sql' => $sql,
                'params' => ['id'=>$id],
            ];
        }
        return $model->getID();
    }

}