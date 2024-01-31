<?php
declare(strict_types=1);

namespace common\dto;

class VideoDto
{
    public $videoId;
    public $title;
    public $thumbnailUrl;
    public $description;
    public $defaultLanguage;
    public $localizations;
    public mixed $channelId;

    public function __construct($videoId, $title, $thumbnailUrl, $description, $defaultLanguage, $localizations, $channelId)
    {
        $this->videoId = $videoId;
        $this->title = $title;
        $this->thumbnailUrl = $thumbnailUrl;
        $this->description = $description;
        $this->defaultLanguage = $defaultLanguage;
        $this->localizations = $localizations;
        $this->channelId = $channelId;
    }
}
