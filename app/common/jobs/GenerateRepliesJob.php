<?php
namespace common\jobs;

use common\repositories\AssistantRepository;
use common\services\CommentsService;
use common\services\VideoService;
use dimuses\chatgpt\dto\AnswerSetting;
use dimuses\chatgpt\dto\AnswerSettingsList;
use dimuses\chatgpt\dto\Comment;
use dimuses\chatgpt\providers\ChatGptProvider;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\queue\JobInterface;

class GenerateRepliesJob extends BaseObject implements JobInterface
{
    public $comments;
    public mixed $user_id;

    /**
     * @param $comments
     * @param $assistantId
     */
    public function __construct(
        private CommentsService $commentsService,
        private AssistantRepository $assistantRepository,
        private ChatGptProvider $chatGptProvider,
        private VideoService $videoService
    )
    {

        parent::__construct();
    }


    public function execute($queue)
    {
        $assistant = $this->assistantRepository->getById(3);
        $assistant->settings = array_merge(
            (array)$assistant?->parent?->settings,
            (array)$assistant->settings
        );
        $settingsList = new AnswerSettingsList();
        foreach ($assistant->settings as $index => $item) {
            $settingsList->addSetting(new AnswerSetting($item));
        }

        $videoId = ArrayHelper::getValue($this->comments, '0.video_id');
        $video = $this->videoService->findVideoByIdAndUser($videoId, $this->user_id);
        foreach ($this->comments as $index => $comment) {
            $comment = $this->commentsService->filterComment($comment);


            $message = new Comment("Video: {$video?->title}." . $comment);
            $this->chatGptProvider->setSettingList($settingsList);
            $answer = $this->chatGptProvider->generateAnswer($message);
            $this->commentsService->createAnswer($comment->id, $videoId, $answer?->text);

        }
    }
}
