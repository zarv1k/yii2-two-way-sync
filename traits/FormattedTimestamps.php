<?php
namespace backend\components\sync\traits;
use yii\base\Event;

/**
 * Trait FormattedTimestamps
 * @package common\models\traits
 */
trait FormattedTimestamps {
    /** @var  array*/
    protected static $timestampColumns;

    public function init() {
        /** @var \yii\db\ActiveRecord $arClass */
        $arClass = get_class($this);
        if (!isset(static::$timestampColumns) && is_subclass_of($arClass, '\yii\db\ActiveRecord')) {
            /** @var \yii\db\TableSchema $schema */
            $schema = $arClass::getTableSchema();

            foreach ($schema->columns as $columnName => $col) {
                /** @var \yii\db\ColumnSchema $col */
                if ($col->type === 'timestamp') {
                    static::$timestampColumns[] = $columnName;
                }
            }

            Event::on($arClass, \yii\db\BaseActiveRecord::EVENT_AFTER_FIND, [$arClass, 'formatTimestamps'], null, false);
        }

        parent::init();
    }

    /**
     * Format timestamp columns using datetime application formatter
     * @param Event $event
     */
    public static function formatTimestamps(Event $event) {
        /** @var \yii\db\ActiveRecord $model */
        $model = $event->sender;
        foreach (static::$timestampColumns as $columnName) {
            $timestamp = $model->getAttribute($columnName);
            if ($timestamp !== null) {
                $model->setOldAttribute($columnName, \Yii::$app->formatter->asDatetime($timestamp));
                $model->setAttribute($columnName, \Yii::$app->formatter->asDatetime($timestamp));
            }
        }
    }
}