<?php

namespace Database;

class BaseModel
{
    use Relations;
    use Timestamps;

    /**
     *  Database connection
     * @var Connection
     */
    private $connection;

    /**
     *  Table name
     * @var string
     */
    protected $table;

    /**
     *  Table primary key
     * @var int
     */
    protected $primaryKey;

    /**
     *  Table timestamps
     * @var boolean
     */
    public $timestamps = false;

    /**
     * The relations to load on every query
     * @var array
     */
    private $with = [];

    /**
     *  Query statement
     * @var string
     */
    private $query;

    /**
     *  Values to put in query parameters
     * @var array
     */
    private $values = [];

    /**
     *  Create new Connection instance
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
    private function timestamps()
    {
        return $this->timestamps;
    }

    /**
     * @param mixed ...$values
     * @return void
     */
    private function addValue(...$values)
    {
        foreach ($values as $value) {
            array_push($this->values, $value);
        }
    }

    /**
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return $this
     */
    private function whereQuery($column, $operator, $value)
    {
        if (empty($this->query)) {
            $this->query = "SELECT * FROM $this->table WHERE $column $operator ?";
        } else {
            $this->query .= " WHERE $column $operator ?";
        }
        $this->addValue($value);
        return $this;
    }

    /**
     * @param array|string $columns
     * @return $this
     */
    private function selectQuery($columns)
    {
        if (is_array($columns)) {
            $columnsString = implode(',', $columns);
        } else if (!is_array($columns))
            $columnsString = $columns;
        $this->query = "SELECT $columnsString FROM $this->table";
        return $this;
    }

    /**
     * @return false|\mysqli_result
     */
    private function excecuteQuery()
    {
        $cnn = $this->connection->open();
        $query = $cnn->prepare($this->query);
        $values = $this->values;
        if ($query) {
            if (count($values) > 0) {
                $types = dataTypesToString($values);
                $query->bind_param($types, ...$values);
            }
            $query->execute();
        } else {
            $error = $cnn->error;
            $this->connection->close();
            response($error,500);
        }
        $results = $query->get_result();
        $this->connection->close();
        return $results;
    }

    public static function raw($query)
    {

    }

    /**
     * Get model with relationships for every query
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
     *  Update model on database
     * @param int $id
     * @param object $data
     * @return string
     */
    public static function update($id, $data)
    {
        $INSTANCE = new static;

        $keysString = $INSTANCE->getKeysForUpdateQuery(array_keys($data));

        foreach ($data as $key => $value) {
            $INSTANCE->addValue($value);
        }
        $INSTANCE->updateQuery($keysString)->where($INSTANCE->primaryKey, '=', $id);
        $INSTANCE->excecuteQuery();
        return "success";
    }

    /**
     * Add an update query
     * @param $columnsValues
     * @return $this
     */
    private function updateQuery($columnsValues){
        $this->query = "UPDATE $this->table SET $columnsValues";
        return $this;
    }

    /**
     * Convert columns to update query keys
     * @param array $keys
     * @return string
     */
    private function getKeysForUpdateQuery($keys){
        $keysString = implode(" = ?, ", $keys) . " = ?";
        if ($this->timestamps()) {
            $keysString .= " ,$this->UPDATED_AT = " . date("d-m-Y");
        }
        return $keysString;
    }

    /**
     * Excecute query and convert results to array
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

    public function paginate($itemsPerPage, $currentPage)
    {
        $this->query .= " LIMIT $itemsPerPage OFFSET $currentPage";
        return $this;
    }

    /**
     *  Find model on database with id
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
     *  Delete model on database
     * @param int $id
     * @return string
     */
    public static function delete($id)
    {
        $INSTANCE = new static;
        $INSTANCE->deleteQuery()->where($INSTANCE->primaryKey, '=', $id);
        $INSTANCE->excecuteQuery();
        return "success";
    }

    /**
     * Add a delete query
     * @return $this
     */
    private function deleteQuery()
    {
        $this->query = "DELETE FROM $this->table";
        return $this;
    }

    /**
     *  Save model on database
     * @return string
     */
    public function save()
    {
        $thisArray = get_object_vars($this);
        // Merri te gjitha atributet dinamike te instances perveq atributeve ndihmese
        $data = filterVars($thisArray);
        $keys = array_keys($data);
        $values = array_values($data);
        if ($this->timestamps()) {
            array_push($keys, $this->CREATED_AT, $this->UPDATED_AT);
            $date = date("d-m-Y");
            array_push($values, $date, $date);
        }
        $this->addValue(...$values);
        $keysString = implode(",", $keys);
        $paramSymbols = str_repeat('?,', count($keys) - 1) . '?';
        $this->query = "INSERT INTO $this->table ($keysString) VALUES ($paramSymbols); ";
        $this->excecuteQuery();
        return "success";
    }

    /**
     * Order query
     * @param $column
     * @param string $order = "ASC|DESC"
     * @return $this
     */
    public function orderBy($column, $order = 'ASC')
    {
        if (($order == 'ASC' || $order == 'DESC') && $this->query != "") {
            $this->query .= " ORDER BY $column $order";
        }
        return $this;
    }

    /**
     *  Get all records of model on database
     * @return array
     */
    public static function all()
    {
        return self::select('*')->get();
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
}
