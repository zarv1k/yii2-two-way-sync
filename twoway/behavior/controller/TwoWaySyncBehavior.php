<?php

namespace zarv1k\sync\twoway\behavior\controller;

use zarv1k\sync\twoway\behavior\action\IndexLatestBehavior;
use zarv1k\sync\twoway\behavior\action\UpdateConflictBehavior;
use zarv1k\sync\twoway\behavior\model\SyncableBehavior;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\rest\Action;
use yii\rest\Controller;
use yii\rest\CreateAction;
use yii\rest\IndexAction;
use yii\rest\UpdateAction;

/**
 * Class TwoWaySyncBehavior
 * @package zarv1k\sync\twoway\behavior\controller
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
                $action->attachBehavior('indexLatest', IndexLatestBehavior::className());
                break;

            case CreateAction::className():
                // nothing to attach on create for now
                // TODO: check is needed to behave something here
                break;
            
            case UpdateAction::className():
                $action->attachBehavior('updateConflict', UpdateConflictBehavior::className());
                break;

            default:
                // TODO: implement custom new so called 'TwoWaySync action' stub for future custom actions
                // TODO: and add method attachSyncBehaviors()
                // $action->attachSyncBehaviors();
                
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