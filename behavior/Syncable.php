<?php

namespace backend\components\sync\behavior;

use yii\base\Behavior;
use yii\base\Event;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Class Syncable
 * @package backend\components\sync\behavior
 */
class Syncable extends Behavior
{
    public $timestampColumn = 'updated_at';
    public $timestampQueryParam = 'updated_after';

    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT => 'onInit'
        ];
    }
    
    public function onInit(Event $event) {
        /** @var ActiveRecord $model */
        $model = $event->sender;
        
        $model->attachBehaviors([
            'formattedTimestamps' => '\backend\components\sync\behavior\FormattedTimestamps',
            'syncTimestamp' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => false,
                'updatedAtAttribute' => $this->timestampColumn,
                'value' => \Yii::$app->formatter->asDatetime(time()),
            ],
        ]);
    }
}