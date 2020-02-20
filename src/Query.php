<?php

trait Query {

    /**
     * Query statement
     * @var string
     */
    protected $query;

    /**
     * Values to put in query parameters
     * @var array
     */
    protected $values = [];

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
     * Add a where query
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
     * Add a select query
     * @param array|string $columns
     * @return $this
     */
    private function selectQuery($columns)
    {
        $keys = $this->getKeysForSelectQuery($columns);
        $this->query = "SELECT $keys FROM $this->table";
        return $this;
    }

    /**
     * Convert column names to select query keys
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
     * Add a delete query
     * @return $this
     */
    private function deleteQuery()
    {
        $this->query = "DELETE FROM $this->table";
        return $this;
    }

    private function insertQuery(){}

    private function orderQuery(){}
}
