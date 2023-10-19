<?php

namespace common\components;

use Google\Exception;
use Google_Client;
use Google_Service_YouTube;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class YoutubeClient
{

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getYoutubeService(): Google_Service_YouTube
    {
        $client = $this->getClient();
        return new Google_Service_YouTube($client);
    }

    /**
     * @return Google_Client|void|Response
     * @throws Exception|\yii\base\InvalidConfigException
     * @throws InvalidRouteException
     */
    public function getClient()
    {
        $session = Yii::$app->session;
        $client = new Google_Client();
        $client->setAuthConfig(Yii::getAlias('@common/files/client_secret.json'));
        $client->setRedirectUri('https://enhanced-rightly-lemur.ngrok-free.app' . Yii::$app->urlManager->createUrl(['youtube/callback']));
        $client->addScope([Google_Service_YouTube::YOUTUBE_FORCE_SSL, Google_Service_YouTube::YOUTUBE_READONLY]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if (isset($_GET['code'])) {
            if (strval($session->get('state')) !== strval($_GET['state'])) {
                die('The session state did not match.');
            }
            $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            $session['token'] = $accessToken;
        } elseif (isset($session['token']) && !isset($session['token']['error'])) {
            $client->setAccessToken($session['token']);
        } else {
            $state = bin2hex(random_bytes(16));
            $session->set('state', $state);
            $client->setState($state);
            $authUrl = $client->createAuthUrl();
            $session->set('referrer', Yii::$app->request->getUrl());
            $response = Yii::$app->response;
            $response->redirect($authUrl);
            $response->send();
            die;
        }
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $session['token'] = $client->getAccessToken();
        }
        return $client;
    }

    public function videoListByChannel($snippet, $params)
    {
        $service = $this->getYoutubeService();
        $videos = [];

        $params = [
            'channelId' => ArrayHelper::getValue($params, 'channelId'),
            'type' => 'video',
            'maxResults' => ArrayHelper::getValue($params, 'maxResults'),
            'order' => 'date'
        ];

        $searchResponse = $service->search->listSearch($snippet, $params);

        foreach ($searchResponse['items'] as $item) {
            $videoId = $item['id']['videoId'];
            $title = $item['snippet']['title'];
            $thumbnailUrl = $item['snippet']['thumbnails']['default']['url'];
            $fullDescription = $item['snippet']['description'];

            $localizations = $this->getVideoLocalizations($videoId);

            $videos[] = [
                'videoId' => $videoId,
                'title' => $title,
                'thumbnailUrl' => $thumbnailUrl,
                'description' => $fullDescription,
                'localizations' => $localizations
            ];
        }

        return $videos;
    }

    private function getVideoLocalizations($videoId)
    {
        $service = $this->getYoutubeService();
        $localizations = [];

        if (!$videoId){
            return [];
        }

        $videoResponse = $service->videos->listVideos('localizations', ['id' => $videoId]);
        $video = $videoResponse->items[0];

        if (isset($video['localizations'])) {
            foreach ($video['localizations'] as $language => $data) {
                $localizations[$language] = [
                    'title' => $data['title'],
                    'description' => $data['description'],
                ];
            }
        }

        return $localizations;
    }


    public function updateVideoDescription($videoId, $localizations)
    {
        $service = $this->getYoutubeService();

        // Получаем текущий ресурс видео
        $video = $service->videos->listVideos('snippet,localizations', ['id' => $videoId]);
        if (empty($video->items)) {
            throw new \Exception("Video not found.");
        }
        // Если у видео уже есть локализации, объединяем их с предоставленными, иначе просто устанавливаем предоставленные
        if (isset($video->items[0]->localizations)) {
            $video->items[0]->localizations = array_merge($video->items[0]->localizations, $localizations);
        } else {
            $video->items[0]->localizations = $localizations;
        }

        // Обновляем ресурс видео
        $service->videos->update('snippet,localizations', $video->items[0]);
    }



    public function commentsListFromVideo($videoId)
    {
        $service = $this->getYoutubeService();
        $comments = [];

        $channelId = $this->getChannelId();
        if (!$channelId) {
            throw new \Exception("Unable to fetch channel ID.");
        }

        $params = [
            'videoId' => $videoId,
            'maxResults' => 100,
            'textFormat' => 'plainText',
        ];

        $commentsList = $service->commentThreads->listCommentThreads('snippet,replies', $params);

        foreach ($commentsList['items'] as $comment) {
            $snippet = $comment['snippet']['topLevelComment']['snippet'];

            $hasReplyFromAuthor = false;

            if (isset($comment['snippet']['totalReplyCount']) && $comment['snippet']['totalReplyCount'] > 0) {
                if (isset($comment['replies'])) {
                    foreach ($comment['replies']['comments'] as $reply) {
                        if ($reply['snippet']['authorChannelId']['value'] === $channelId) {
                            $hasReplyFromAuthor = true;
                            break;
                        }
                    }
                }
            }

            if (!$hasReplyFromAuthor) {
                $comments[] = [
                    'text' => $snippet['textDisplay'],
                    'author' => $snippet['authorDisplayName'],
                    'avatar' => $snippet['authorProfileImageUrl'],
                    'date' => $snippet['publishedAt']
                ];
            }
        }

        return $comments;
    }

    /**
     * Replies to an existing comment.
     * @param string $parentId
     * @param string $text
     * @return \Google\Service\YouTube\Comment
     */
    public function replyToComment($parentId, $text)
    {
        $service = $this->getYoutubeService();
        $commentSnippet = new \Google_Service_YouTube_CommentSnippet();
        $commentSnippet->setParentId($parentId);
        $commentSnippet->setTextOriginal($text);

        $reply = new \Google_Service_YouTube_Comment();
        $reply->setSnippet($commentSnippet);

        return $service->comments->insert('snippet', $reply);
    }


    /**
     * Creates a new comment on a video.
     * @param string $videoId
     * @param string $text
     * @return mixed
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function createComment($videoId, $text)
    {
        $service = $this->getYoutubeService();
        $commentSnippet = new \Google_Service_YouTube_CommentSnippet();
        $commentSnippet->setVideoId($videoId);
        $commentSnippet->setTextOriginal($text);

        $topLevelComment = new \Google_Service_YouTube_Comment();
        $topLevelComment->setSnippet($commentSnippet);

        $commentThreadSnippet = new \Google_Service_YouTube_CommentThreadSnippet();
        $commentThreadSnippet->setTopLevelComment($topLevelComment);

        $commentThread = new \Google_Service_YouTube_CommentThread();
        $commentThread->setSnippet($commentThreadSnippet);

        return $service->commentThreads->insert('snippet', $commentThread);
    }

    /**
     * Updates an existing comment.
     * @param string $commentId
     * @param string $updatedText
     * @return mixed
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function updateComment($commentId, $updatedText)
    {
        $service = $this->getYoutubeService();
        $comment = $service->comments->get($commentId);

        if (!$comment) {
            throw new \Exception("Comment with ID {$commentId} not found.");
        }

        $comment->snippet->setTextOriginal($updatedText);
        return $service->comments->update('snippet', $comment);
    }

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function getChannelId(): ?string
    {
        $service = $this->getYoutubeService();
        $channelsResponse = $service->channels->listChannels('id', ['mine' => true]);

        if (!empty($channelsResponse->getItems())) {
            return $channelsResponse->getItems()[0]->getId();
        }

        return null;
    }
}