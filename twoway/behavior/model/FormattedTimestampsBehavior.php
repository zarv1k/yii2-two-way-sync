<?php
namespace zarv1k\sync\twoway\behavior\model;

use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * Class FormattedTimestampsBehavior
 * @package zarv1k\sync\twoway\behavior\model
 */
class FormattedTimestampsBehavior extends Behavior
{
    /** @var  array */
    protected static $timestampColumns;

    /** {@inheritdoc} */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'formatTimestamps'
        ];
    }

    /**
     * Format timestamp columns using datetime application formatter
     * @param Event $event
     */
    public function formatTimestamps(Event $event)
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = $event->sender;

        foreach ($this->timestampColumns($model) as $columnName) {
            $timestamp = $model->getAttribute($columnName);
            if ($timestamp !== null) {
                $model->setOldAttribute($columnName, \Yii::$app->formatter->asDatetime($timestamp));
                $model->setAttribute($columnName, \Yii::$app->formatter->asDatetime($timestamp));
            }
        }
    }

    /**
     * @param ActiveRecord $model
     * @return array
     */
    protected function timestampColumns(ActiveRecord $model)
    {
        $modelClass = get_class($model);
        if (!isset(static::$timestampColumns[$modelClass]) && $model instanceof ActiveRecord) {
            static::$timestampColumns[$modelClass] = [];
            /** @var \yii\db\TableSchema $schema */
            $schema = $model::getTableSchema();

            foreach ($schema->columns as $columnName => $col) {
                /** @var \yii\db\ColumnSchema $col */
                if ($col->type === 'timestamp') {
                    static::$timestampColumns[$modelClass][] = $columnName;
                }
            }
        }

        return static::$timestampColumns[$modelClass];
    }
}