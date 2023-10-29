<?php

namespace common\components;

use Google\Exception;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoLocalization;
use GuzzleHttp\Client;
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
        $client->addScope(Google_Service_YouTube::YOUTUBE_FORCE_SSL);
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

        $searchParams = [
            'channelId'  => ArrayHelper::getValue($params, 'channelId'),
            'type'       => 'video',
            'maxResults' => ArrayHelper::getValue($params, 'maxResults'),
            'order'      => 'date'
        ];

        $searchResponse = $service->search->listSearch($snippet, $searchParams);

        foreach ($searchResponse['items'] as $item) {
            $videoId = $item['id']['videoId'];

            $videoResponse = $service->videos->listVideos('snippet', ['id' => $videoId]);
            if (empty($videoResponse->items)) {
                continue;
            }
            $videoDetail = $videoResponse->items[0];

            $title = $videoDetail['snippet']['title'];
            $thumbnailUrl = $videoDetail['snippet']['thumbnails']['default']['url'];
            $fullDescription = $videoDetail['snippet']['description'];
            $defaultLanguage = $videoDetail['snippet']['defaultLanguage'] ?? $videoDetail['snippet']['defaultAudioLanguage'];

            $localizations = $this->getVideoLocalizations($videoId);

            $videos[] = [
                'videoId'         => $videoId,
                'title'           => $title,
                'thumbnailUrl'    => $thumbnailUrl,
                'description'     => $fullDescription,
                'defaultLanguage' => $defaultLanguage,
                'localizations'   => $localizations
            ];
        }

        return $videos;
    }

    private function getVideoLocalizations($videoId)
    {
        $service = $this->getYoutubeService();
        $localizations = [];

        if (!$videoId) {
            return [];
        }

        $videoResponse = $service->videos->listVideos('localizations', ['id' => $videoId]);
        $video = $videoResponse->items[0];

        if (isset($video['localizations'])) {
            foreach ($video['localizations'] as $language => $data) {
                $localizations[$language] = [
                    'title'       => $data['title'],
                    'description' => $data['description'],
                ];
            }
        }

        return $localizations;
    }


    public function updateVideoLocalizations($videoId, $localizations, $defaultLanguage = null)
    {
        $service = $this->getYoutubeService();
        $video = $service->videos->listVideos('snippet,localizations', ['id' => $videoId]);
        if (empty($video->items)) {
            throw new \Exception("Video not found.");
        }
        $currentVideo = $video->items[0];
        $newLocalizations = [];
        foreach ($localizations as $lang => $data) {
            $newLocalizations[$lang] = new Google_Service_YouTube_VideoLocalization($data);
        }

        if ($defaultLanguage && isset($localizations[$defaultLanguage])) {
            $currentVideo->getSnippet()->setDescription($localizations[$defaultLanguage]['description']);
        }
        $currentVideo->setLocalizations($newLocalizations);


        return $service->videos->update('snippet,localizations', $currentVideo);
    }

    /**
     * @return object[] {
     *     @var string $text
     *     @var string $author
     *     @var string $avatar
     *     @var string $date
     *     @var string $comment_id
     *     @var bool $hasReplyFromAuthor
     * }
     */
    public function commentsListFromVideo($videoId)
    {
        $service = $this->getYoutubeService();
        $comments = [];
        $pageToken = null;

        $channelId = $this->getChannelId();
        if (!$channelId) {
            throw new \Exception("Unable to fetch channel ID.");
        }

        do {
            $params = [
                'videoId'    => $videoId,
                'pageToken'  => $pageToken,
                'textFormat' => 'plainText',
            ];
            $response = $service->commentThreads->listCommentThreads('snippet,replies', $params);
            foreach ($response['items'] as $comment) {
                $snippet = $comment['snippet']['topLevelComment']['snippet'];

                $hasReplyFromAuthor = false;
                $replies = [];

                if (isset($comment['snippet']['totalReplyCount']) && $comment['snippet']['totalReplyCount'] > 0) {
                    if (isset($comment['replies'])) {
                        foreach ($comment['replies']['comments'] as $reply) {
                            if ($reply['snippet']['authorChannelId']['value'] === $channelId) {
                                $hasReplyFromAuthor = true;
                            }
                            $replies[] = (object)[
                                'text'     => $reply['snippet']['textDisplay'],
                                'author'   => $reply['snippet']['authorDisplayName'],
                                'avatar'   => $reply['snippet']['authorProfileImageUrl'],
                                'date'     => $reply['snippet']['publishedAt'],
                                'reply_id' => $reply['id']
                            ];
                        }
                    }
                }

                $comments[] = (object)[
                    'text'               => $snippet['textDisplay'],
                    'author'             => $snippet['authorDisplayName'],
                    'avatar'             => $snippet['authorProfileImageUrl'],
                    'date'               => $snippet['publishedAt'],
                    'comment_id'         => $comment['snippet']['topLevelComment']['id'],
                    'hasReplyFromAuthor' => $hasReplyFromAuthor,
                    'replies'            => $replies
                ];
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken);

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