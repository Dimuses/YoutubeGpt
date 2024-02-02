<?php
declare(strict_types=1);

namespace common\services;

use common\components\YoutubeClient;
use common\dto\CommentDTO;
use common\models\{
    Comments,
    GeneratedAnswers
};
use common\repositories\CommentsRepository;
use common\repositories\GeneratedAnswersRepository;
use dimuses\chatgpt\dto\Answer;
use Google\Exception;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\{
    ActiveRecord,
    Expression
};
use yii\web\MethodNotAllowedHttpException;

/**
 *
 * @property-write mixed $deleted
 */
class CommentsService
{

    public function __construct(
        private YoutubeClient              $youtubeClient,
        private GeneratedAnswersRepository $generatedAnswersRepository,
        private CommentsRepository $comnentsRepository,
    ){ }

    public function syncComment($videoId, CommentDTO $commentDto, $parentId = null): bool
    {
        $comment = $this->getByCommentId($commentDto->comment_id);
        if (!$comment) {
            $comment = new Comments();
        } else {
            $comment->is_deleted = 0;
        }

        $comment->video_id = $videoId;
        $comment->author = $commentDto->author;
        $comment->text = $commentDto->text;
        $comment->replied = (int)$commentDto->hasReplyFromAuthor;
        $comment->conversation = $parentId ? 1 : 0;
        $comment->avatar = $commentDto->avatar;
        $comment->comment_id = $commentDto->comment_id;
        $comment->comment_date = date('Y-m-d H:i:s', strtotime($commentDto->date));
        $comment->parent_id = $parentId;

        if (!$comment->save()) {
            Yii::error('Ошибка при сохранении комментария: ' . json_encode($comment->getErrors()));
            return false;
        }
        return true;
    }

    /**
     * @param $commentId
     * @param $videoId
     * @param $reply
     * @return void
     * @throws MethodNotAllowedHttpException
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function createAndSaveReply($commentId, $videoId, $reply): void
    {
        if ($commentId && $videoId && $reply) {
            $response = $this->youtubeClient->replyToComment($commentId, $reply);

            if ($response) {
                $replyComment = new Comments();
                $replyComment->video_id = $videoId;
                $replyComment->author = $response['author'];
                $replyComment->text = $response['text'];
                $replyComment->replied = $response['replied'];
                $replyComment->conversation = $response['conversation'];
                $replyComment->avatar = $response['avatar'];
                $replyComment->comment_id = $response['comment_id'];
                $replyComment->comment_date = $response['comment_date'];
                $replyComment->parent_id = $response['parent_id'];

                if (!$replyComment->save()) {
                    Yii::error('Ошибка при сохранении ответа на комментарий: ' . json_encode($replyComment->getErrors()));
                } else {
                    $replyComment->parent->replied = 1;
                    if ($replyComment->parent->save()) {
                        $this->generatedAnswersRepository->deleteAllBy('comment_id', $commentId);
                    }
                }
            }
        } else {
            throw new MethodNotAllowedHttpException();
        }
    }

    /**
     * @param $videoId
     * @return array|Comments[]|ActiveRecord[]
     */
    public function getAllByVideoId($videoId): array
    {
        return Comments::find()->where(['video_id' => $videoId])->all();
    }

    /**
     * @param mixed $commentId
     * @return void
     */
    public function setDeleted(mixed $commentId): void
    {
        Comments::updateAll(['is_deleted' => 1], ['comment_id' => $commentId]);
    }

    /**
     * @param $videoId
     * @return void
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function refreshComments($videoId): void
    {
        $youtubeCommentsData = $this->youtubeClient->commentsListFromVideo($videoId);
        $youtubeCommentsIds = [];

        /** @var CommentDTO $commentDto */
        foreach ($youtubeCommentsData as $commentDto) {
            $youtubeCommentsIds[] = $commentDto->comment_id;
            if (isset($commentDto->replies) && is_array($commentDto->replies)) {
                foreach ($commentDto->replies as $replyData) {
                    $youtubeCommentsIds[] = $replyData->comment_id;
                }
            }
        }
        $existingComments = $this->commentsService->getAllByVideoId($videoId);
        $existingCommentIds = array_column($existingComments, 'comment_id');

        $commentsToDelete = array_diff($existingCommentIds, $youtubeCommentsIds);

        foreach ($commentsToDelete as $commentId) {
            $this->commentsService->setDeleted($commentId);
        }

        foreach ($youtubeCommentsData as $commentDto) {
            $this->commentsService->syncComment($videoId, $commentDto);

            if (isset($commentDto->replies) && is_array($commentDto->replies)) {
                foreach ($commentDto->replies as $replyData) {
                    $this->commentsService->syncComment($videoId, $replyData, $commentDto->comment_id);
                }
            }
        }
    }

    /**
     * @param $commentId
     * @param $videoId
     * @param Answer|null $answer
     * @return void
     */
    public function createAnswer($commentId, $videoId, $text = null): void
    {
        if ($text) {
            $reply = new GeneratedAnswers();
            $reply->comment_id = $commentId;
            $reply->video_id = $videoId;
            $reply->text = $text;
            $reply->tokens = "0"; //TODO
            $reply->generated_at = date('Y-m-d H:i:s');
            $reply->save();
        }
    }

    /**
     * @param $commentId
     * @return Comments|null
     */
    public function getByCommentId($commentId): ?Comments
    {
        return Comments::findOne(['comment_id' => $commentId]);
    }

    /**
     * @param $comment
     * @return mixed|string
     */
    public function filterComment($comment): mixed
    {
        if (rand(0, 2) != 1) {
            $comment = explode(':', $comment);
            $comment = $comment[1];
        }
        return $comment;
    }

    public function getAllWithoutReply($videoId, int|string|null $id)
    {
        return $this->comnentsRepository->getAllWithoutReply($videoId, $userId);
    }


}