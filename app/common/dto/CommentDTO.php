<?php
declare(strict_types=1);

namespace common\dto;
class CommentDTO
{
    public $text;
    public $author;
    public $avatar;
    public $date;
    public $comment_id;
    public $hasReplyFromAuthor;
    public $replies = [];

    public function __construct($text, $author, $avatar, $date, $comment_id, $hasReplyFromAuthor, $replies = [])
    {
        $this->text = $text;
        $this->author = $author;
        $this->avatar = $avatar;
        $this->date = $date;
        $this->comment_id = $comment_id;
        $this->hasReplyFromAuthor = $hasReplyFromAuthor;
        $this->replies = $replies;
    }
}
