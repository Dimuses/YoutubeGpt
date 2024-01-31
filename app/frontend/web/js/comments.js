
function initializeCode() {
    $('#showModalButton').on('click', function() {
        $('#assistantModal').modal('show');
    });

    $('.prev_reply').on('click', function() {
        var textarea = $(this).closest('.reply-form').find('textarea');
        var replies = textarea.data('replies');
        var currentReplyIndex = textarea.data('current-reply-index') || 0;

        currentReplyIndex = (currentReplyIndex - 1 + replies.length) % replies.length; // Декремент и циклическое переключение
        textarea.val(replies[currentReplyIndex]).data('current-reply-index', currentReplyIndex);
    });

    $('.next_reply').on('click', function() {
        var textarea = $(this).closest('.reply-form').find('textarea');
        var replies = textarea.data('replies');

        var currentReplyIndex = textarea.data('current-reply-index') || 0;

        currentReplyIndex = (currentReplyIndex + 1) % replies.length; // Инкремент и циклическое переключение
        textarea.val(replies[currentReplyIndex]).data('current-reply-index', currentReplyIndex);
    });

    $(document).off('click', '.toggle-replies').on('click', '.toggle-replies', function(e) {
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
        let form = $(this).closest('form');
        let comment_id = form.find('input[name="comment_id"]').val();
        let video_id = form.find('input[name="video_id"]').val();

        preloader.show();
        let authorComment = $(this).closest('.comment-item').find('.comment-content').data('full-text');
        let author = $(this).closest('.comment-item').find('.comment-author').text();
        $.ajax({
            url: '/comment/generate-reply',
            method: 'POST',
            data: {
                'comment': author + authorComment,
                'comment_id': comment_id,
                'video_id': video_id
            },
            success: function(response) {
                preloader.hide();
                $.pjax.reload({container: '#comments-pjax', timeout: 5000});
            },
            error: function() {
                alert('Произошла ошибка при получении ответа.');
                preloader.hide();
            }
        });
    });

    $('.reply-forms').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serialize();
        let that = this;
        $.ajax({
            url: '/comment/reply-comment',
            type: 'POST',
            data: formData,
            beforeSend: function () {
                $(that).find('.preloader').show();
            },
            success: function (response) {
                console.log('Success:', response);
                $.pjax.reload({container: '#comments-pjax', timeout: 5000});
            },

            error: function (xhr, status, error) {
                console.error('Error:', error);
            },
            complete: function () {
                $('.preloader').hide();
            }
        });
    });
}

$(document).ready(function() {
    initializeCode();
    $(document).on('pjax:complete', function() {
        initializeCode();
    });
});