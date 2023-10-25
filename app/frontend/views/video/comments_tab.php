<?php

use common\models\Comments;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\LinkPager;
use yii\bootstrap5\Modal;

/* @var $comments Comments */
/* @var $pagination yii\data\Pagination */
/* @var $assistants common\models\Assistant[] */

?>

<div class="comments-section">
    <h2>
        Комментарии
        <?= Html::a('Обновить комментарии', ['comment/get-comments', 'videoId' => $model->video_id], [
            'class' => 'btn btn-primary',
            'data-pjax' => 1
        ]) ?>
        <?= Html::button('Сгенерировать на все', ['class' => 'btn btn-warning', 'data-toggle' => 'modal', 'id' => 'showModalButton']) ?>
    </h2>
    <?php \yii\widgets\Pjax::begin(['id' => 'comments-pjax']); ?>
    <?php foreach ($comments as $comment): ?>
        <?php $isLong = strlen($comment->text) > 200; ?>
        <div class="row comment-item">
            <div class="col-md-8">

                <div class="row">
                    <div class="col-md-2">
                        <?= Html::img($comment->avatar, ['style' => 'border-radius:50%']) ?>
                    </div>
                    <div class="col-md-10" style="padding-top: 13px">
                        <strong><?= Html::encode($comment->author) ?>:</strong>
                        <p class="comment-content <?= $isLong ? 'shortened' : '' ?>"
                           data-full-text="<?= nl2br($comment->text) ?>" style="display: contents">
                            <?= $isLong ? mb_substr(nl2br($comment->text), 0, 200) . '...' : nl2br($comment->text) ?>

                        </p>
                        <?php if ($isLong): ?>
                            <a class="btn btn-link toggle-comment">Развернуть</a>
                        <?php endif; ?>
                    </div>
            </div>
            </div>
            <div class="col-md-4">
                <div class="reply-form">
                    <?= Html::beginForm(['comment/reply-comment'], 'post'); ?>
                    <?= Html::hiddenInput('comment_id', $comment->comment_id)  ?>
                    <div class="form-floating">
                        <textarea class="form-control" name="reply" placeholder="Ответить на комментарий..." id="floatingTextarea"></textarea>
                        <label for="floatingTextarea">Ответ</label>
                    </div>
                    <div class="btn-group" role="group">
                        <?= Html::submitButton('Сгенерировать ответ', ['class' => 'btn btn-secondary']); ?>
                        <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary']); ?>
                    </div>
                    <?= Html::endForm(); ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>



    <?= \yii\widgets\LinkPager::widget([
        'pagination' => $pagination,
        'options' => ['class' => 'pagination'],
        'linkContainerOptions' => ['class' => 'page-item'],
        'linkOptions' => ['class' => 'page-link'],
        'disabledListItemSubTagOptions' => ['tag' => 'a', 'class' => 'page-link'],
    ]); ?>
    <?php \yii\widgets\Pjax::end(); ?>
</div>



<?php Modal::begin([
    'title' => 'Выберите ассистента',
    'id' => 'assistantModal',
]); ?>

<?= Html::label('Ассистенты', 'assistant') ?>
<?= Html::dropDownList('assistant', null, ArrayHelper::map($assistants, 'id', 'name'), ['class' => 'form-control']) ?>
<br>
<?= Html::button('Далее', ['class' => 'btn btn-primary', 'id' => 'generate-replies-button']) ?>
<?php Modal::end(); ?>
<?php

$this->registerJs(<<<JS

    $('#showModalButton').on('click', function() {
        $('#assistantModal').modal('show');
    });
    
    $('.toggle-comment').on('click', function() {
        var btn = $(this);
        var commentContent = btn.prev('.comment-content');
        var fullText = commentContent.data('full-text');
        
        if (commentContent.hasClass('shortened')) {
            commentContent.html(fullText).removeClass('shortened');
            btn.text('Свернуть');
        } else {
            commentContent.html(fullText.substring(0, 120) + '...').addClass('shortened');
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
JS
, View::POS_READY);
?>