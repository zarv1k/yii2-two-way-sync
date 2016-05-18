<?php

namespace backend\components\sync\behavior\action;

use backend\components\sync\behavior\model\SyncableBehavior;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;
use yii\rest\Action;
use yii\rest\Controller;
use yii\rest\UpdateAction;
use yii\validators\Validator;

class UpdateConflictBehavior extends Behavior
{
    const CONFLICT_MESSAGE_MARKER = 'sync-conflict';

    /** @var string */
    protected $updatedAt;

    public function attach($owner)
    {
        if (!$owner instanceof UpdateAction) {
            throw new InvalidParamException(__CLASS__ . ' behavior can be attached only to instance of \yii\rest\UpdateAction');
        }

        parent::attach($owner);

        Event::on(ActiveRecord::className(), ActiveRecord::EVENT_INIT, [$this, 'onActiveRecordInit']);

        $owner->controller->on(Controller::EVENT_AFTER_ACTION, [$this, 'handleConflict'], null, false);
    }

    public function detach()
    {
        /** @var UpdateAction $owner */
        $owner = $this->owner;
        
        $owner->controller->off(Controller::EVENT_AFTER_ACTION, [$this, 'handleConflict']);
        Event::off(ActiveRecord::className(), ActiveRecord::EVENT_INIT, [$this, 'onActiveRecordInit']);
        
        parent::detach();
    }


    /**
     * Attaches needed event handlers on ActiveRecord Model init
     * @param Event $event
     */
    public function onActiveRecordInit(Event $event)
    {
        /** @var ActiveRecord|SyncableBehavior $model */
        $model = $event->sender;

        $model->on(ActiveRecord::EVENT_AFTER_FIND, [$this, 'addValidators']);
    }

    /**
     * Add needed validators on after find model event
     * @param Event $event
     */
    public function addValidators(Event $event)
    {
        /** @var ActiveRecord|SyncableBehavior $model */
        $model = $event->sender;

        /** @var |\yii\db\ActiveRecord $modelClass */
        $requestUpdatedAt = \Yii::$app->request->getBodyParam($model->timestampColumn);

        // unset timestampColumn value when it does not send by client for trigger required validator 
        if (empty($requestUpdatedAt)) {
            $model->{$model->timestampColumn} = null;
        }

        foreach ($this->createValidators($model) as $validator) {
            $model->validators->append($validator);
        }

        // on this stage it is not needed anymore to handle this event
        Event::off(ActiveRecord::className(), ActiveRecord::EVENT_INIT, [$this, 'onActiveRecordInit']);
    }

    /**
     * Handles conflict
     * 
     * @param ActionEvent $event
     * @throws \yii\web\NotFoundHttpException
     */
    public function handleConflict(ActionEvent $event)
    {
        /** @var ActiveRecord|SyncableBehavior $model */
        $model = $event->result;
        $errors = $model->getErrors($model->timestampColumn);

        if (count($errors) == count($model->getErrors()) 
            && array_search(static::CONFLICT_MESSAGE_MARKER, $errors) !== false
        ) {
            /** @var Action $action */
            $action = $event->action;
            // set 409 Conflict HTTP code
            \Yii::$app->getResponse()->statusCode = 409;
            // return actual model object
            $event->result = $action->findModel($model->getPrimaryKey());
        }
    }

    /**
     * Create validators
     * 
     * @param ActiveRecord|SyncableBehavior $model
     * @return Validator[]
     */
    protected function createValidators(ActiveRecord $model)
    {
        /** @var UpdateAction $action */
        $action = $this->owner;
        return [
            Validator::createValidator('safe', $model, $model->timestampColumn, ['on' => $action->scenario]),
            Validator::createValidator('required', $model, $model->timestampColumn, ['on' => $action->scenario]),
            Validator::createValidator('compare', $model, $model->timestampColumn, [
                'on' => $action->scenario,
                'compareValue' => $model->getOldAttribute($model->timestampColumn),
                'message' => static::CONFLICT_MESSAGE_MARKER,
            ])
        ];
    }
}