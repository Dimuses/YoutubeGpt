<?php

use common\models\Comments;
use frontend\assets\CommentsAsset;
use frontend\models\search\CommentSearch;
use yii\bootstrap5\Modal;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use yii\widgets\Pjax;

/* @var $comments Comments[] */
/* @var $pagination yii\data\Pagination */
/* @var $assistants common\models\Assistant[] */

\yidas\yii\fontawesome\FontawesomeAsset::register($this);
CommentsAsset::register($this);
?>

<?php Pjax::begin(['id' => 'comments-pjax', 'timeout' => 10000]); ?>

<div class="comments-section">
    <h2>
        <?= Yii::t('video', 'Comments') ?>
        <?= Html::a(Yii::t('video', 'Refresh comments'), ['comment/get-comments', 'videoId' => $model->video_id], [
            'class'     => 'btn btn-primary',
            'data-pjax' => 1
        ]) ?>
        <?= Html::button(Yii::t('video', 'Generate for all'), ['class' => 'btn btn-warning', 'data-toggle' => 'modal', 'id' => 'showModalButton']) ?>
    </h2>
    <br>
    <div class="comments-filter">
        <?= Html::a(Yii::t('video', 'With my replies'), Url::current(['#' => 'description_tab', 'filter' => CommentSearch::WITH_REPLIES]), ['class' => 'btn btn-primary', 'data-pjax' => 1]) ?>
        <?= Html::a(Yii::t('video', 'Without my reply'), Url::current(['#' => 'description_tab', 'filter' => CommentSearch::WITHOUT_REPLIES]), ['class' => 'btn btn-secondary', 'data-pjax' => 1]) ?>
    </div>

    <br>
    <?php foreach ($comments as $comment): ?>
        <?= displayComment($comment) ?>

        <?php if (isset($comment->replies)): ?>
            <div class="replies-container" style="margin-left: 60px;">

                <?php $visibleReplies = array_slice($comment->replies, 0, 1); ?>
                <?php foreach ($visibleReplies as $reply): ?>
                    <?= displayComment($reply, true) ?>
                <?php endforeach; ?>

                <?php if (count($comment->replies) > 1): ?>
                    <div class="hidden-replies">
                        <?php $hiddenReplies = array_slice($comment->replies, 1); ?>
                        <?php foreach ($hiddenReplies as $reply): ?>
                            <?= displayComment($reply, true) ?>
                        <?php endforeach; ?>
                    </div>
                    <a href="#" class="toggle-replies" data-expanded="0">
                        <?= Yii::t('video', 'Show more ({count})', ['count' => count($hiddenReplies)]) ?>
                    </a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <hr>
    <?php endforeach; ?>

    <?= LinkPager::widget([
        'pagination'                    => $pagination,
        'options'                       => ['class' => 'pagination'],
        'linkContainerOptions'          => ['class' => 'page-item'],
        'linkOptions'                   => ['class' => 'page-link'],
        'disabledListItemSubTagOptions' => ['tag' => 'a', 'class' => 'page-link'],
    ]); ?>
</div>

<?php Modal::begin([
    'title' => Yii::t('video', 'Select an assistant'),
    'id'    => 'assistantModal',
]); ?>

<?= Html::label(Yii::t('video', 'Assistants'), 'assistant') ?>
<?= Html::dropDownList('assistant', null, ArrayHelper::map($assistants, 'id', 'name'), ['class' => 'form-control']) ?>
<br>
<?= Html::button(Yii::t('video', 'Next'), ['class' => 'btn btn-primary', 'id' => 'generate-replies-button']) ?>

<?php Modal::end(); ?>

<?php
function displayComment($comment, $isReply = false)
{
    $isLong = mb_strlen($comment->text) > 200;
    $padding = $isReply ? '60px' : '0px';
    ob_start();
    ?>
    <div class="row comment-item" style="margin-left: <?= $padding ?>;">
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-2" style="text-align: center">
                    <?= Html::img($comment->avatar, ['style' => 'border-radius:50%; text-align:center']) ?>
                    <br>
                    <p style="font-size:13px; font-weight: bold"><?= Yii::$app->formatter->asRelativeTime($comment->comment_date) ?></p>
                </div>
                <div class="col-md-10" style="padding-top: 13px">
                    <strong class="comment-author"><?= Html::encode($comment->author) ?>:</strong>
                    <p class="comment-content<?= $isLong ? ' shortened' : '' ?>"
                       data-full-text="<?= str_replace("\"", "'", nl2br($comment->text)) ?>" style="display: contents">
                        <?= $isLong ? nl2br(mb_substr($comment->text, 0, 200)) . '...' : nl2br($comment->text) ?>
                    </p>
                    <?php if ($isLong): ?>
                        <a class="btn btn-link toggle-comment"><?= Yii::t('video', 'Expand') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="reply-form">
                <?= Html::beginForm(['comment/reply-comment'], 'post', ['class' => 'reply-forms']); ?>
                <?= Html::hiddenInput('comment_id', $comment->comment_id) ?>
                <?= Html::hiddenInput('video_id', $comment->video_id) ?>
                <div class="row">
                    <div class="col-md-10">
                        <div class="form-floating">
                            <?php if (count($comment->answers) > 1) {
                                echo Html::button('<i class="fas fa-arrow-left"></i>', ['class' => 'btn btn-secondary prev_reply']);
                                echo Html::button('<i class="fas fa-arrow-right"></i>', ['class' => 'btn btn-secondary next_reply']);
                            } ?>
                            <textarea class="form-control floatingTextarea"
                                      name="reply"
                                      style="height: 100px; padding: 30px 45px;"
                                      placeholder="<?= Yii::t('video', 'Reply to comment...') ?>"
                                      id="floatingTextarea_<?= $comment->id ?>"
                                      data-replies='<?= htmlspecialchars(Json::encode(ArrayHelper::getColumn($comment->answers, 'text'))) ?>'><?= $comment?->generatedReply?->text ?></textarea>
                            <label for="floatingTextarea">
                                <?= Yii::t('video', 'Reply') ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="btn-group" role="group">
                            <?= Html::button('<i class="fas fa-redo-alt"></i>', ['class' => 'btn btn-secondary generate_reply']); ?>
                            <?= Html::submitButton('<i class="fa fa-paper-plane" aria-hidden="true"></i>', ['class' => 'btn btn-primary save_reply']); ?>
                            <?= Html::a('<i class="fas fa-eye"></i>', "https://www.youtube.com/watch?v={$comment->video_id}&lc={$comment->comment_id}", ['class' => 'btn btn-danger', 'target' => '_blank']) ?>
                        </div>
                        <div class="preloader" style="display: none;">
                            <div class="loader"></div>
                        </div>
                    </div>
                </div>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
} ?>
<?php Pjax::end(); ?>