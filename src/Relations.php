<?php

namespace Database;

trait Relations {

    /**
     * Initiate model relationships
     * @param array $relations
     * @return void
     */
    private function bootRelations($relations){
        foreach ($relations as $relation) {
            $this->$relation();
        }
    }

    /**
     * Get $primaryKey column value for each record of a model
     * @param $records
     * @param $primaryKey
     * @return array
     */
    private function getModelIDs($records,$primaryKey)
    {
        $modelIDs = array();
        foreach ($records as $res) {
            $id = $res->$primaryKey;
            if (!in_array($id, $modelIDs)) {
                array_push($modelIDs, $id);
            }
        }
        return $modelIDs;
    }

    /**
     * Check if model has relations
     * @return boolean
     */
    private function hasRelations(){
        return count($this->with) > 0;
    }

    /**
     * @param array $result
     * @return void
     */
    private function addRelationDataToResult($result){
        foreach ($this->with as $relation) {
            $model = new $relation["model"];
            $foreginKey = $relation["foreignKey"];
            $localKey = $relation["localKey"];
            $modelIDs = $this->getModelIDs($result, $foreginKey);
            $modelIDsString = implode(",",$modelIDs);
            $model->query = "SELECT * FROM $model->table WHERE $model->primaryKey IN ($modelIDsString)";
            $models = $model->get();
            foreach($result as $res){
                $name = $model->table;
                $res->$name = current(array_filter($models, function ($mdl) use ($res, $localKey,$foreginKey) {
                    return $res->$localKey == $mdl->$foreginKey;
                }));
            }
        }
    }

    /**
     * Define a one-to-one relationship
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     */
    public function hasOne($model, $foreignKey = null, $localKey = null)
    {
        array_push($this->with, [
            "model" => $model,
            "foreignKey" => $foreignKey,
            "localKey" => $localKey,
            "type" => "hasOne"
        ]);
    }

    /**
     * Define a one-to-many relationship
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     */
    public function hasMany($model, $foreignKey = null, $localKey = null)
    {
        array_push($this->with, [
            "model" => $model,
            "foreignKey" => $foreignKey,
            "localKey" => $localKey,
            "type" => "hasMany"
        ]);
    }
}
