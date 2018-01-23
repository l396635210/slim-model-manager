# Model Manager

after make model by model-console, now use the model-manager 
and model-finder develop.

- use in slim framework   
dependencies.php
```php
...
//injection the container
$container['model_manager'] = function ($c){
    $orm = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__."/orm.yml"));
    $db = $orm['db'];
    $pdo = new PDO("mysql:host=".$db['host'] . ";dbname=".$db['dbname'].
        ";charset=".$db['charset']. ";collate=".$db['collate'],
        $db['user'], $db['pass'], ['port'=>$db['port']]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $modelManager=\Liz\ModelManager\ModelManager::getInstance($pdo,$orm['relations']);
    return $modelManager;
};
...
```    
BaseController
```php
//for a shortcut
...
    /**
     * @var ModelManager
     */
    protected $modelManager;
...    
    public function __construct(Container $container)
    {
        ...
        $this->modelManager = $container->get('model_manager');
        ...
    }
...
```

- example: shortcut for find
  - find list
  ```php
    $this->modelManager->getFinder(ClassNameToString)->findBy($condition);
    $this->modelManager->getFinder(ClassNameToString)->findAll();
  ```     
  - find one
  ```php
    $this->modelManager->getFinder(ClassNameToString)->findOneBy($condition);
    $this->modelManager->getFinder(ClassNameToString)->findOne($id);
  ``` 

- example: insert or update    
    if the model's id is a number will update else insert
  ```php
    $this->modelManager->persist($model)
            ->flush();
  ```     

- example: custom finder
in ModelFinder.php file can custom find methods
  ```php
    // pdo for find
    public function findForIndex(Request $request, $withArr=false){
        $params = [];
        $sql = "SELECT * FROM {$this->table} AS u WHERE 1=1 ";
        if($request->getQueryParam('k')){
            $sql .= " AND (u.mobile LIKE :k OR u.realname LIKE :k)";
            $params['k'] = "%{$request->getQueryParam('k')}%";
        }

        $sth = $this->pdo->prepare($sql);
        if(!$withArr){
            $sth->setFetchMode(\PDO::FETCH_CLASS, $this->modelName);
        }
        $sth->execute($params);
        return $sth->fetchAll();
    }
  
    // base find class for find
    public function findOneForAPI($adCode, $createDate, $hour){
        $res = $this->findBy([
            'ad_code' => $adCode,
            'create_date' => $createDate->format('Y-m-d'),
            'hour'  => $hour,
        ]);
        return $res ? $res[0] : $res;
    }
  ```   