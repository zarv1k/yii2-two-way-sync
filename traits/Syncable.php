<?php

namespace backend\components\sync\traits;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * Trait Syncable
 * @package common\models\traits
 * @property string updated_at
 */
trait Syncable
{
    use FormattedTimestamps;
    protected static $timestamp_query_param = 'updated_after';
    protected static $timestamp_column = 'updated_at';

    /**
     * @return string
     */
    public static function getTimestampQueryParam()
    {
        return static::$timestamp_query_param;
    }

    public static function findLatestChanges(ActiveQuery $query, $updatedAfter = null)
    {
        return $query->andFilterWhere(['>', static::$timestamp_column, $updatedAfter]);
    }

    /**
     * @return string
     */
    public static function getTimestampColumn()
    {
        return static::$timestamp_column;
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'syncable_timestamp' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => false,
                'updatedAtAttribute' => static::$timestamp_column,
                'value' => \Yii::$app->formatter->asDatetime(time()),
            ]
        ]);
    }
}