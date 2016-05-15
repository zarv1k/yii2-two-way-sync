<?php

namespace backend\components\sync\actions;

use yii\base\Event;
use yii\db\ActiveRecord;
use yii\validators\RequiredValidator;
use yii\web\ConflictHttpException;

class UpdateAction extends \yii\rest\UpdateAction
{
    public function run($id)
    {
        /** @var \backend\components\sync\traits\Syncable|\yii\db\BaseActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        if (in_array('backend\components\sync\traits\Syncable', class_uses($modelClass))) {
            Event::on(
                $this->modelClass, $modelClass::EVENT_BEFORE_VALIDATE, 
                [$this, 'addRequiredValidator'],
                [$modelClass::getTimestampColumn() => \Yii::$app->request->getBodyParam($modelClass::getTimestampColumn())],
                false
            );
            Event::on($this->modelClass, $modelClass::EVENT_AFTER_VALIDATE, [$this, 'checkSyncConflict'], null, false);   
        }
        
        try {
            return parent::run($id);
        } catch (ConflictHttpException $e) {
            \Yii::$app->getResponse()->statusCode = $e->statusCode;
            return $this->findModel($id);
        }
        
    }
    
    public function addRequiredValidator(Event $event)
    {
        /** @var ActiveRecord $model */
        $model = $event->sender;
        /** @var \backend\components\sync\traits\Syncable|\yii\db\BaseActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $requestUpdatedAt = $event->data[$modelClass::getTimestampColumn()];
        
        if (empty($requestUpdatedAt)) {
            $model->{$modelClass::getTimestampColumn()} = null;
        }
        
        $model->validators[] = new RequiredValidator([
            'attributes' => [$modelClass::getTimestampColumn()],
            'on' => $this->scenario,
        ]);
    }

    public function checkSyncConflict(Event $event) {
        /** @var ActiveRecord $model */
        $model = $event->sender;
        /** @var \backend\components\sync\traits\Syncable $modelClass */
        $modelClass = $this->modelClass;
        $oldValue = $model->getOldAttribute($modelClass::getTimestampColumn());
        $newValue = $model->getAttribute($modelClass::getTimestampColumn());
        
        if ($oldValue !== null && strtotime($oldValue) != strtotime($newValue)) {
            throw new ConflictHttpException;
        }
    }

}