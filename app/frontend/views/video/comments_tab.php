<?php
use common\models\Comments;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\LinkPager;
use yii\bootstrap5\Modal;
use yii\widgets\Pjax;

/* @var $comments Comments[] */
/* @var $pagination yii\data\Pagination */
/* @var $assistants common\models\Assistant[] */


\yidas\yii\fontawesome\FontawesomeAsset::register($this);
?>

<?php Pjax::begin(['id' => 'comments-pjax', 'timeout' => 2000]); ?>

<div class="comments-section">
    <h2>
        Комментарии
        <?= Html::a('Обновить комментарии', ['comment/get-comments', 'videoId' => $model->video_id], [
            'class'     => 'btn btn-primary',
            'data-pjax' => 1
        ]) ?>
        <?= Html::button('Сгенерировать на все', ['class' => 'btn btn-warning', 'data-toggle' => 'modal', 'id' => 'showModalButton']) ?>
    </h2>
    <br>
    <div class="comments-filter">
        <?= Html::a('С моими ответами', Url::current(['filter' => 'with-my-replies']), ['class' => 'btn btn-primary', 'data-pjax' => 1]) ?>
        <?= Html::a('Без моего ответа', Url::current(['filter' => 'without-my-replies']), ['class' => 'btn btn-secondary', 'data-pjax' => 1]) ?>
    </div>


    <br>
    <?php foreach ($comments as $comment): ?>
        <?= displayComment($comment) ?>

        <?php if (isset($comment->replies)): ?>
            <div class="replies-container" style="margin-left: 60px;">  <!-- Добавлен контейнер для ответов -->

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
                        Показать еще (<?= count($hiddenReplies) ?>)
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
    'title' => 'Выберите ассистента',
    'id'    => 'assistantModal',
]); ?>

<?= Html::label('Ассистенты', 'assistant') ?>
<?= Html::dropDownList('assistant', null, ArrayHelper::map($assistants, 'id', 'name'), ['class' => 'form-control']) ?>
<br>
<?= Html::button('Далее', ['class' => 'btn btn-primary', 'id' => 'generate-replies-button']) ?>

<?php Modal::end(); ?>

<!-- JS и CSS код остается прежним -->
<?php

$this->registerJs(<<<JS

    $('#showModalButton').on('click', function() {
        $('#assistantModal').modal('show');
    });

    $(document).on('click', '.toggle-replies', function(e) {
        e.preventDefault();
        const container = $(this).closest('.replies-container');
        const hiddenReplies = container.find('.hidden-replies');
        
        if (hiddenReplies.is(':visible')) {
            hiddenReplies.slideUp();
            $(this).text('Показать еще (' + hiddenReplies.length + ')');
        } else {
            hiddenReplies.slideDown();
            $(this).text('Скрыть ответы');
        }
    });
    
    $('.toggle-comment').on('click', function() {
        var btn = $(this);
        var commentContent = btn.prev('.comment-content');
        var fullText = commentContent.data('full-text');
        
        if (!fullText) {
            console.error("Full text of the comment is missing or undefined.");
            return;
        }
        
        if (commentContent.hasClass('shortened')) {
            commentContent.html(fullText).removeClass('shortened');
            btn.text('Свернуть');
        } else {
            commentContent.html(fullText.substring(0, 200) + '...').addClass('shortened');
            btn.text('Развернуть');
        }
    });
    $('#generate-replies-button').on('click', function() {
        let assistantId = $('#assistant-selector').val();
        $.ajax({
            url: '/comment/generate-replies', 
            type: 'POST',
            data: {
                assistantId: assistantId
            },
            success: function(data) {
                if(data.success) {
                    alert("Задача добавлена в очередь");
                    $('#assistantModal').modal('hide');
                } else {
                    alert("Произошла ошибка");
                }
            }
        });
    });
    
    $('.generate_reply').click(function(e) {
        e.preventDefault(); 
        let preloader = $(this).closest('.reply-form').find('.preloader');
        let textArea = $(this).closest('.reply-form').find('textarea');
       
        preloader.show();
        let authorComment = $(this).closest('.comment-item').find('.comment-content').data('full-text');
        $.ajax({
            url: '/comment/generate-reply', 
            method: 'POST',
            data: {
                'comment': authorComment
            },
            success: function(response) {
                textArea.val(response);
                preloader.hide();
            },
            error: function() {
                alert('Произошла ошибка при получении ответа.');
                preloader.hide();
            }
        });
    });
JS , View::POS_READY);

$this->registerCss(<<<CSS
.reply-form{
    float: right;
}
.preloader {
    position: relative;
    top: -35px;
    left: -264px;
    /*background-color: rgba(255, 255, 255, 0.7); !* Полупрозрачный фон *!*/
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.loader {
    border: 8px solid #f3f3f3;
    border-radius: 50%;
    border-top: 8px solid #3498db; /* Цвет анимированного круга */
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
}

.reply-item {
    margin-top: 5px;
    padding-left: 20px; /* Добавьте отступ слева для визуального разделения */
    font-size: 0.9em; /* Уменьшите размер шрифта для ответов */
}

.hidden-replies {
    display: none; /* Скройте ответы по умолчанию */
}

.toggle-replies {
    cursor: pointer;
    color: #007BFF;
    text-decoration: none;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
CSS
);

?>
<?php Pjax::end(); ?>

<?php
function displayComment($comment, $isReply = false) {
    $isLong = strlen($comment->text) > 200;
    $padding = $isReply ? '60px' : '0px';
    ob_start();
    ?>
    <div class="row comment-item" style="margin-left: <?= $padding ?>;">
        <div class="col-md-7">
            <div class="row">
                <div class="col-md-2" style="text-align: center">
                    <?= Html::img($comment->avatar, ['style' => 'border-radius:50%; text-align:center']) ?>
                    <br>
                    <p style="font-size:13px; font-weight: bold"><?= Yii::$app->formatter->asRelativeTime($comment->comment_date) ?></p>
                </div>
                <div class="col-md-10" style="padding-top: 13px">
                    <strong><?= Html::encode($comment->author) ?>:</strong>
                    <p class="comment-content<?= $isLong ? ' shortened' : '' ?>"
                       data-full-text="<?= str_replace("\"","'", nl2br($comment->text)) ?>" style="display: contents">
                        <?= $isLong ? nl2br(mb_substr($comment->text, 0, 200)) . '...' : nl2br($comment->text) ?>
                    </p>
                    <?php if ($isLong): ?>
                        <a class="btn btn-link toggle-comment">Развернуть</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="reply-form">
                <?= Html::beginForm(['comment/reply-comment'], 'post'); ?>
                <?= Html::hiddenInput('comment_id', $comment->comment_id) ?>
                <?= Html::hiddenInput('video_id', $comment->video_id) ?>
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-floating">
                            <textarea class="form-control floatingTextarea" name="reply" placeholder="Ответить на комментарий..."
                                      id="floatingTextarea_<?= $comment->id ?>"></textarea>
                            <label for="floatingTextarea">Ответ</label>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="btn-group" role="group">
                            <?= Html::submitButton('<i class="fas fa-redo-alt"></i>', ['class' => 'btn btn-secondary generate_reply']); ?>
                            <?= Html::submitButton('<i class="fa fa-paper-plane" aria-hidden="true"></i>', ['class' => 'btn btn-primary save_reply']); ?>
                            <?= Html::a('<i class="fas fa-eye"></i>', "https://www.youtube.com/watch?v={$comment->video_id}&lc={$comment->comment_id}", ['class' => 'btn btn-danger']) ?>
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
