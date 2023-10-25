<?php
namespace common\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;

class GenerateRepliesJob extends BaseObject implements JobInterface
{
    public $comments;
    public $assistantId;

    public function execute($queue)
    {
        // Здесь вы будете генерировать ответы на комментарии с помощью выбранного ассистента.
        // $this->comments - массив комментариев
        // $this->assistantId - ID выбранного ассистента



    }
}
