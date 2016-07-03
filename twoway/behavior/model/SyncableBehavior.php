<?php

namespace zarv1k\sync\twoway\behavior\model;

use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Class SyncableBehavior
 * @package zarv1k\sync\twoway\behavior\model
 */
class SyncableBehavior extends Behavior
{
    /** @var string */
    public $timestampColumn = 'updated_at';
    /** @var string */
    public $timestampQueryParam = 'updated_after';

    /**
     * Attach sync needed behaviors to ActiveRecord
     *
     * @param \yii\db\ActiveRecord $model
     */
    public function attach($model)
    {
        if (!$model instanceof ActiveRecord) {
            throw new InvalidParamException(__CLASS__ . ' can be attached to instance of \yii\db\ActiveRecord only');
        }

        parent::attach($model);

        $model->attachBehaviors([
            'syncableFormattedTimestamps' => FormattedTimestampsBehavior::className(),
            'syncableTimestamp' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => false,
                'updatedAtAttribute' => $this->timestampColumn,
                'value' => function() {
                    // TODO: check this, may be incorrect format for save !!!
                    return \Yii::$app->formatter->asDatetime(time());
                }
            ],
        ]);
    }

    public function detach()
    {
        /** @var ActiveRecord $model */
        $model = $this->owner;
        $model->detachBehavior('syncableFormattedTimestamps');
        $model->detachBehavior('syncableTimestamp');

        parent::detach();
    }
}