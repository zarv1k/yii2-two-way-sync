<?php

namespace backend\components\sync\behavior\action;

use yii\base\Behavior;
use yii\base\Event;
use yii\base\InvalidParamException;
use yii\db\ActiveQuery;
use yii\rest\IndexAction;

/**
 * Class LatestChangesBehavior
 * @property IndexAction owner
 * @package backend\components\sync\behavior\action
 */
class LatestChangesBehavior extends Behavior
{
    public function attach($owner)
    {
        if (!$owner instanceof IndexAction) {
            throw new InvalidParamException(__CLASS__.' behavior can be attached only to instance of \yii\rest\IndexAction');
        }

        parent::attach($owner);

        // Increase pagination range that can be set from client-side
        \Yii::$container->set('yii\data\Pagination', ['pageSizeLimit' => [1, 1000]]);
        
        /** @var \yii\db\BaseActiveRecord $modelClass */
        $modelClass = $owner->modelClass;
        /** @var \yii\db\ActiveRecord $model*/
        $model = new $modelClass;

        if ($model->canGetProperty('timestampColumn')) {
            Event::on(ActiveQuery::className(), ActiveQuery::EVENT_INIT, [$this, 'applyLatestOnlyCondition'], $model, false);
        }
    }

    public function detach()
    {
        Event::off(ActiveQuery::className(), ActiveQuery::EVENT_INIT, [$this, 'applyLatestOnlyCondition']);
        
        parent::detach();
    }


    /**
     * Applies latest only condition for syncable behaved models
     *
     * @param Event $event
     */
    public function applyLatestOnlyCondition(Event $event)
    {
        /** @var \yii\db\BaseActiveRecord|\backend\components\sync\behavior\model\SyncableBehavior $model */
        $model = $event->data;
        /** @var \yii\db\ActiveRecordInterface $modelClass */
        $modelClass = $this->owner->modelClass;
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