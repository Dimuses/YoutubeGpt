<?php
declare(strict_types=1);

namespace frontend\models\search;

use common\models\Comments;
use common\models\Video;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;

/**
 *
 */
class CommentSearch extends Comments
{
    const WITH_REPLIES = 'with-replies';
    const WITHOUT_REPLIES = 'without-replies';

    /**
     * @var
     */
    public $filter;

    /**
     * @param Video $video
     * @param $filter
     */
    public function __construct(public Video $video, $filter)
    {
        $this->filter = $filter;
        parent::__construct();
    }


    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            [['filter'], 'string'],
            [['filter'], 'in', 'range' => [self::WITH_REPLIES, self::WITHOUT_REPLIES]],
        ];
    }

    /**
     * @return array
     */
    public function search(): array
    {
        if (!$this->validate()) {
            return [null, null];
        }
        $query = Comments::find()
            ->alias('c')
            ->with(['replies' => fn($q) => $q->andWhere(['is_deleted' => [0, null]])])
            ->where(['video_id' => $this->video->video_id, 'parent_id' => null, 'is_deleted' => [0, null]])
            ->orderBy(['comment_date' => SORT_DESC]);

        if ($this->filter == self::WITHOUT_REPLIES) {
            $query->andWhere(['replied' => false]);
        }

        $count = $query->count();
        $pagination = new Pagination(['totalCount' => $count, 'defaultPageSize' => 20]);

        $comments = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return array($pagination, $comments);
    }
}