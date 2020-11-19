<?php


use http\Exception\InvalidArgumentException;

trait Query {

    /**
     * Query statement.
     * @var string
     */
    protected $query;

    /**
     * Values to put in query parameters.
     * @var array
     */
    protected $values = [];

    /**
     * If query already has a where command.
     * @var bool
     */
    protected $nestedWhere = false;

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
     * Add an update query
     * @param array|object $data
     * @return $this
     */
    private function updateQuery($data){
        $keys = $this->getKeysForUpdateQuery(array_keys($data));
        foreach ($data as $key => $value) {
            $this->addValue($value);
        }
        $this->query = "UPDATE `$this->table` SET $keys";
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
            $keysString .= " ,$this->UPDATED_AT = '" . date("Y-m-d H:i:s") . "'";
        }
        return $keysString;
    }

    /**
     * Validate user input for sql injection protection
     *
     * @param string $operator
     */
    private function checkComparisonOperator($operator)
    {
        $whitelist = [
            "=","!=","<>","<","<=",">",">=","<=>",
            "IS","is","IS NOT","is not","NOT","not","LIKE","like"
        ];
        if (!in_array($operator, $whitelist)) {
            response("Unsupported mysql operator: '$operator'",500);
        }
    }

    /**
     * Add a where query
     *
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return $this
     */
    private function whereQuery($column, $operator, $value)
    {
        $this->checkComparisonOperator($operator);
        if (empty($this->query)) {
            $this->query = "SELECT * FROM `$this->table` WHERE $column $operator ?";
        } else {
            if($this->nestedWhere)
                $this->query .= " AND `$column` $operator ?";
            else
                $this->query .= " WHERE `$column` $operator ?";
        }
        $this->nestedWhere = true;
        $this->addValue($value);
        return $this;
    }

    /**
     * Add where in query
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    private function whereInQuery($column, $values)
    {
        $this->addValue(...$values);
        $parameters = $this->getParametersForQuery(count($values));
        if(empty($this->query))
            $this->query = "SELECT * FROM `$this->table` WHERE `$column` IN ($parameters)";
        else
            $this->query .= " WHERE `$column` IN ($parameters)";
        return $this;
    }

    /**
     * Add a select query
     *
     * @param array|string $columns
     * @return $this
     */
    private function selectQuery($columns)
    {
        $keys = $this->getKeysForSelectQuery($columns);
        $this->query = "SELECT $keys FROM `$this->table`";
        return $this;
    }

    /**
     * Convert column names to select query keys
     *
     * @param array|string $columns
     * @return string
     */
    private function getKeysForSelectQuery($columns)
    {
        if (is_array($columns))
            $keys = implode(',', $columns);
        else
            $keys = $columns;
        return $keys;
    }

    /**
     *
     * @param integer $count
     * @return string
     */
    private function getParametersForQuery($count){
        return str_repeat('?,', $count - 1) . '?';
    }

    /**
     * Add a delete query
     *
     * @return $this
     */
    private function deleteQuery()
    {
        $this->query = "DELETE FROM `$this->table`";
        return $this;
    }

    /**
     *
     * @return integer|false|\mysqli_result
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
            if($query->error){
                http_response_code(500);
                throw new \mysqli_sql_exception($query->error);
            }
        } else {
            $error = $cnn->error;
            $this->connection->close();
            http_response_code(500);
            throw new \mysqli_sql_exception($error  . ". [QUERY]($this->query)");
        }
        $results = $query->get_result();
        $this->connection->close();

        if($query->insert_id > 0)
            return $query->insert_id;

        return $results;
    }

    /**
     * @param array $keys
     * @param array $values
     * @return $this
     */
    private function insertQuery($keys,$values)
    {
        $this->addValue(...$values);
        if ($this->timestamps()) {
            array_push($keys, $this->CREATED_AT, $this->UPDATED_AT);
            $date = date("Y-m-d H:i:s");
            $this->addValue($date,$date);
        }
        $keysString = implode(",", $keys);
        $parameters = $this->getParametersForQuery(count($keys));
        $this->query = "INSERT INTO `$this->table` ($keysString) VALUES ($parameters); ";
        return $this;
    }

    /**
     * @param string $column
     * @param string $order
     * @return $this
     */
    private function orderQuery($column, $order)
    {
        if (($order == 'ASC' || $order == 'DESC') && !empty($this->query))
            $this->query .= " ORDER BY `$column` $order";
        return $this;
    }

    /**
     * @param integer $number
     * @return $this
     */
    private function limitQuery($number)
    {
        if (!is_numeric($number))
            throw new InvalidArgumentException("SQL LIMIT query accepts only numbers");

        $this->query .= " LIMIT $number";
        return $this;
    }
}
