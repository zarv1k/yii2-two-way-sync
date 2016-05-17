<?php

namespace backend\components\sync\behavior\controller;

use backend\components\sync\behavior\action\LatestChangesBehavior;
use backend\components\sync\behavior\model\SyncableBehavior;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\rest\Action;
use yii\rest\Controller;
use yii\rest\IndexAction;

/**
 * Class TwoWaySyncBehavior
 * @package backend\components\sync\behavior\controller
 */
class TwoWaySyncBehavior extends Behavior
{
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeRestAction',
        ];
    }

    /**
     * Attach two-way sync needed behaviors for REST action
     * @param ActionEvent $event
     * @return bool
     * @throws NotSupportedException
     */
    public function beforeRestAction(ActionEvent $event)
    {
        /** @var Action $action */
        $action = $event->action;

        Event::on(BaseActiveRecord::className(), BaseActiveRecord::EVENT_INIT, [$this, 'makeModelSyncable'], null, false);

        switch (get_class($action)) {
            case IndexAction::className():
                $action->attachBehavior('latestChanges', LatestChangesBehavior::className());
                break;

            default:
                // TODO: implement this case to run 
                throw new NotSupportedException("Not implemented yet");

                break;
        }

        return $event->isValid;
    }

    /**
     * Makes ActiveRecord model syncable
     * @param Event $event
     */
    public function makeModelSyncable(Event $event)
    {
        /** @var ActiveRecord $model */
        $model = $event->sender;
        $model->attachBehavior('syncable', SyncableBehavior::className());
    }
}