<?php

namespace backend\components\sync\actions;

use yii\base\Event;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;

class IndexAction extends \yii\rest\IndexAction
{
    protected function beforeRun()
    {
        /** @var \yii\db\BaseActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $model = $modelClass::instantiate([]);

        if ($model->canGetProperty('timestampColumn')) {
            Event::on(ActiveQuery::className(), ActiveQuery::EVENT_INIT, [$this, 'latestOnly'], $model, false);
        }

        return parent::beforeRun();
    }

    /**
     * Increase range for page size value in parent dataProvider
     * It allows to set large page size on client-side
     *
     * @return \yii\data\ActiveDataProvider
     */
    protected function prepareDataProvider()
    {
        $dataProvider = parent::prepareDataProvider();

        $dataProvider->pagination = [
            'pageSizeLimit' => [1, 1000]
        ];
        
        return $dataProvider;
    }

    /**
     * Applies latest only condition for syncable behaved models
     * 
     * @param Event $event
     */
    public function latestOnly(Event $event)
    {
        /** @var \yii\db\BaseActiveRecord|\backend\components\sync\behavior\Syncable $model */
        $model = $event->data;
        /** @var ActiveRecordInterface $modelClass */
        $modelClass = $this->modelClass;
        /** @var ActiveQuery $activeQuery */
        $activeQuery = $event->sender;

        $updatedAfterDate = \Yii::$app->request->getQueryParam($model->timestampQueryParam);

        $orderBy = [$model->timestampColumn => SORT_ASC];
        foreach ($modelClass::primaryKey() as $column) {
            $orderBy[$column] = SORT_ASC;
        }
        
        $activeQuery
            ->andFilterWhere(['>', $model->timestampColumn, $updatedAfterDate])
            ->addOrderBy($orderBy);
    }
}