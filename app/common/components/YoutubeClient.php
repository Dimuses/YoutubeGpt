<?php

namespace common\components;

use common\dto\CommentDTO;
use Google\Exception;
use Google\Service\YouTube\Comment;
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
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function commentsListFromVideo($videoId): array
    {
        $service = $this->getYoutubeService();
        $commentsDTO = [];
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
                $repliesDTO = [];

                if (isset($comment['snippet']['totalReplyCount']) && $comment['snippet']['totalReplyCount'] > 0) {
                    if (isset($comment['replies'])) {
                        foreach ($comment['replies']['comments'] as $reply) {
                            if ($reply['snippet']['authorChannelId']['value'] === $channelId) {
                                $hasReplyFromAuthor = true;
                            }
                            $repliesDTO[] = new CommentDTO(
                                $reply['snippet']['textDisplay'],
                                $reply['snippet']['authorDisplayName'],
                                $reply['snippet']['authorProfileImageUrl'],
                                $reply['snippet']['publishedAt'],
                                $reply['id'],
                                false
                            );
                        }
                    }
                }

                $commentsDTO[] = new CommentDTO(
                    $snippet['textDisplay'],
                    $snippet['authorDisplayName'],
                    $snippet['authorProfileImageUrl'],
                    $snippet['publishedAt'],
                    $comment['snippet']['topLevelComment']['id'],
                    $hasReplyFromAuthor,
                    $repliesDTO
                );
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken);

        return $commentsDTO;
    }
    /**
     * Replies to an existing comment.
     * @param string $parentId
     * @param string $text
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function replyToComment(string $parentId, string $text): array
    {
        $service = $this->getYoutubeService();
        $commentSnippet = new \Google_Service_YouTube_CommentSnippet();
        $commentSnippet->setParentId($parentId);
        $commentSnippet->setTextOriginal($text);

        $reply = new \Google_Service_YouTube_Comment();
        $reply->setSnippet($commentSnippet);

        $response = $service->comments->insert('snippet', $reply);

        return [
            'video_id' => $response->getSnippet()->getVideoId(),
            'author' => $response->getSnippet()->getAuthorDisplayName(),
            'text' => $response->getSnippet()->getTextOriginal(),
            'replied' => 0,
            'conversation' => 0,
            'created_at' => new \yii\db\Expression('NOW()'),
            'updated_at' => new \yii\db\Expression('NOW()'),
            'avatar' => $response->getSnippet()->getAuthorProfileImageUrl(),
            'comment_id' => $response->getId(),
            'comment_date' => date('Y-m-d H:i:s', strtotime($response->getSnippet()->getPublishedAt())),
            'parent_id' => $parentId
        ];
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