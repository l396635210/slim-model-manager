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
            $this->pdo->commit();
        }catch (\Exception $exception){
            echo $exception->getMessage();
            $this->pdo->rollBack();
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
        list($bundle,$model) = explode('\model\\',strtolower($class));

        $table = $this->relations[$bundle][$model];

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

            $sth->bindValue($column, $value, $pdoType);
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
            $sqlBody[] = $column.' = :'.$column;
            $method = 'get'.$column;
            $value = $model->$method();
            if($value instanceof \DateTime){
                $value = $value->format('Y-m-d H:i:s');
            }
            $data[':'.$column] = $value;
        }
        $sqlEnd = " WHERE id = :id;";
        $sql = $sqlStart.implode(',', $sqlBody).$sqlEnd;
        $sth = $this->pdo->prepare($sql);
        $data[':id'] = $model->getID();
        $sth->execute($data);
    }
}