<?php


class Model
{
    use Relations;
    use Query;
    use Timestamps;

    /**
     * Database connection
     * @var Connection
     */
    private $connection;

    /**
     * Table name
     * @var string
     */
    protected $table;

    /**
     * Table primary key
     * @var int
     */
    protected $primaryKey;

    /**
     * Table timestamps
     * @var boolean
     */
    protected $timestamps = true;

    /**
     * The relations to load on every query
     * @var array
     */
    private $with = [];

    /**
     * Create new Connection instance
     * @return void
     */
    public function __construct()
    {
        $this->connection = new Connection();
    }

    /**
     * Get timestamps value
     * @return boolean
     */
    public function timestamps()
    {
        return $this->timestamps;
    }

    /**
     * Get model with relationships for every query
     *
     * @param array $relations
     * @return self
     */
    public static function with($relations)
    {
        $INSTANCE = new static;

        $INSTANCE->bootRelations($relations);

        return $INSTANCE;
    }

    /**
     * Update model on database
     *
     * @param int $id
     * @param array|object $data
     * @return integer
     */
    public static function update($id, $data)
    {
        $INSTANCE = new static;
        $INSTANCE->updateQuery($data)->where($INSTANCE->primaryKey, '=', $id);
        return $INSTANCE->excecuteQuery();
    }

    /**
     * Excecute query and convert results to array.
     *
     * @return array
     */
    public function get()
    {
        $results = $this->excecuteQuery();
        $result = array();
        while ($res = $results->fetch_object()) {
            array_push($result, $res);
        }

        if($this->hasRelations()){
            $this->addRelationDataToResult($result);
        }

        return $result;
    }

    /**
     *  Find model on database with id
     *
     * @param int $id
     * @return object
     */
    public static function find($id)
    {
        $INSTANCE = new static;
        $INSTANCE->whereQuery($INSTANCE->primaryKey, '=', $id);
        $result = $INSTANCE->excecuteQuery()->fetch_object();
        if ($result === null) {
            response(get_class($INSTANCE) . ' nuk u gjet', 404);
        }
        return $result;
    }

    /**
     * Add where in query
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn($column, $values)
    {
        return $this->whereInQuery($column,$values);
    }

    /**
     *  Delete model on database based on primaryKey.
     *
     * @param int $id
     */
    public static function destroy($id)
    {
        $INSTANCE = new static;
        $INSTANCE->deleteQuery()->where($INSTANCE->primaryKey, '=', $id)->excecuteQuery();
    }

    /**
     * Delete model on database on a condition.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     */
    public static function deleteWhere($column,$operator,$value)
    {
        $INSTANCE = new static;
        $INSTANCE->deleteQuery()->where($column, $operator, $value)->excecuteQuery();
    }

    /**
     * Save model on database.
     * Returns the inserted model id.
     *
     * @return integer
     */
    public function save()
    {
        $thisArray = get_object_vars($this);
        $data = filterVars($thisArray);
        return $this->insert($data);
    }

    /**
     * Insert model data on database
     *
     * @param array|object $data
     * @return string
     */
    private function insert($data){
        $keys = array_keys($data);
        $values = array_values($data);
        return $this->insertQuery($keys,$values)->excecuteQuery();
    }

    /**
     * Add an order query
     *
     * @param $column
     * @param string $order = "ASC|DESC"
     * @return $this
     */
    public function orderBy($column, $order = 'ASC')
    {
        $this->orderQuery($column,$order);
        return $this;
    }

    /**
     *  Get all records of model on database.
     *
     * @return array
     */
    public static function all()
    {
        return self::select('*')->get();
    }

    /**
     * Get the first record of model on database.
     *
     * @return $this
     */
    public function first()
    {
        return current($this->limitQuery(1)->get());
    }

    /**
     * Get the last record of model on database.
     *
     * @return $this
     */
    public function last()
    {
        return current($this->orderBy($this->primaryKey, "DESC")->limitQuery(1)->get());
    }

    public function __call($function, $arguments)
    {
        if ($function == 'where') {
            return $this->whereQuery($arguments[0], $arguments[1], $arguments[2]);
        } else if ($function == 'select') {
            return $this->selectQuery($arguments[0]);
        }
    }

    public static function __callStatic($function, $arguments)
    {
        $INSTANCE = new static;
        if ($function == 'where') {
            return $INSTANCE->whereQuery($arguments[0], $arguments[1], $arguments[2]);
        } else if ($function == 'select') {
            return $INSTANCE->selectQuery($arguments[0]);
        }
    }

    public static function raw($query){
        $INSTANCE = new self;
        $INSTANCE->query = $query;
        if(strpos(strtolower($query),"select") !== false)
            return $INSTANCE->get();
        else
            return $INSTANCE->excecuteQuery();
    }

    public function paginate($itemsPerPage){}
}
