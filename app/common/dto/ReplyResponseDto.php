<?php
declare(strict_types=1);

namespace common\dto;

class ReplyResponseDto
{
    public $video_id;
    public $author;
    public $text;
    public $replied;
    public $conversation;
    public $avatar;
    public $comment_id;
    public $comment_date;
    public $parent_id;

    public function __construct($response, $parentId)
    {
        $this->video_id = $response->getSnippet()->getVideoId();
        $this->author = $response->getSnippet()->getAuthorDisplayName();
        $this->text = $response->getSnippet()->getTextOriginal();
        $this->avatar = $response->getSnippet()->getAuthorProfileImageUrl();
        $this->comment_id = $response->getId();
        $this->comment_date = date('Y-m-d H:i:s', strtotime($response->getSnippet()->getPublishedAt()));
        $this->parent_id = $parentId;
    }

}