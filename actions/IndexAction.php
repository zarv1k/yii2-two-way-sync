<?php

namespace backend\components\sync\actions;

use yii\data\ActiveDataProvider;

class IndexAction extends \yii\rest\IndexAction
{

    protected function prepareDataProvider()
    {
        /* @var $modelClass \yii\db\BaseActiveRecord|\backend\components\sync\traits\Syncable */
        $modelClass = $this->modelClass;

        if ($this->prepareDataProvider !== null) {
            $dataProvider = call_user_func($this->prepareDataProvider, $this);
        } else {
            $dataProvider = new ActiveDataProvider([
                'query' => $modelClass::find(),
                'pagination' => [
                    'pageSizeLimit' => [1, 1000]
                ]
            ]);
        }

        $traits = class_uses($modelClass);
        if (in_array('backend\components\sync\traits\Syncable', $traits)) {
            $modelClass::findLatestChanges($dataProvider->query, \Yii::$app->request->getQueryParam($modelClass::getTimestampQueryParam()));
        }
        
        return $dataProvider;
    }

}