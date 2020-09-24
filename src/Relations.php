<?php


trait Relations
{
    /**
     * Initiate model relationships
     *
     * @param array $relations
     * @return void
     */
    private function bootRelations($relations)
    {
        foreach ($relations as $relation) {

            list($mainRelation, $nestedRelations) = $this->getNestedRelations($relation);

            if ($nestedRelations != null) {
                if (method_exists($this, $mainRelation))
                    $this->$mainRelation($nestedRelations);
                else {
                    http_response_code(500);
                    throw new \BadMethodCallException("Relation '$mainRelation' is not found!");
                }
            } else {
                if (method_exists($this, $relation))
                    $this->$relation();
                else {
                    http_response_code(500);
                    throw new \BadMethodCallException("Relation '$relation' is not found!");
                }
            }
        }
    }

    /**
     * @param string $relation
     * @return array|null
     */
    private function getNestedRelations($relation)
    {
        if (strpos($relation, ".") !== false) {
            $nestedRelations = explode(".", $relation);
            $mainRelation = $nestedRelations[0];
            unset($nestedRelations[0]);
            $nestedRelationsString = implode(".", $nestedRelations);
            return [$mainRelation, $nestedRelationsString];
        } else
            return null;
    }

    /**
     * Get $primaryKey column value for each record of a model
     *
     * @param $records
     * @param $primaryKey
     * @return array
     */
    private function getModelIDs($records, $primaryKey)
    {
        $modelIDs = array();
        foreach ($records as $res) {
            $id = $res->$primaryKey;
            if (!in_array($id, $modelIDs) && $id != null) {
                array_push($modelIDs, $id);
            }
        }
        return $modelIDs;
    }

    /**
     * Check if model has relations
     *
     * @return boolean
     */
    private function hasRelations()
    {
        return count($this->with) > 0;
    }

    /**
     * Append relation model data to results
     *
     * @param array $result
     * @return void
     */
    private function addRelationDataToResult($result)
    {
        foreach ($this->with as $relation) {
            $model = new $relation["model"];
            $foreginKey = $relation["foreignKey"];
            $localKey = $relation["localKey"];
            $modelIDs = $this->getModelIDs($result, $localKey);

            if (count($modelIDs) == 0)
                continue;

            $nestedRelations = $relation["nestedRelations"];
            if ($nestedRelations != null) {
                $models = $model::with([$nestedRelations])->whereIn($foreginKey, $modelIDs)->get();
            } else {
                $models = $model->whereIn($foreginKey, $modelIDs)->get();
            }
            foreach ($result as $res) {
                $name = $model->table;
                $relatedModel = array_filter($models, function ($mdl) use ($res, $localKey, $foreginKey) {
                    return $res->$localKey == $mdl->$foreginKey;
                });
                if ($relation["type"] == "hasOne")
                    $res->$name = current($relatedModel);
                else if ($relation["type"] == "hasMany")
                    $res->$name = array_values($relatedModel);
            }
        }
    }

    /**
     * Define a one-to-one relationship
     *
     * @param string $model
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @param array|null $nestedRelations
     * @return void
     */
    public function hasOne($model, $foreignKey = null, $localKey = null, $nestedRelations = null)
    {
        array_push($this->with, [
            "model" => $model,
            "foreignKey" => $foreignKey,
            "localKey" => $localKey,
            "type" => "hasOne",
            "nestedRelations" => $nestedRelations
        ]);
    }

    /**
     * Define a one-to-many relationship
     *
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     * @param array|null $nestedRelations
     * @return void
     */
    public function hasMany($model, $foreignKey = null, $localKey = null, $nestedRelations = null)
    {
        array_push($this->with, [
            "model" => $model,
            "foreignKey" => $foreignKey,
            "localKey" => $localKey,
            "type" => "hasMany",
            "nestedRelations" => $nestedRelations
        ]);
    }
}
