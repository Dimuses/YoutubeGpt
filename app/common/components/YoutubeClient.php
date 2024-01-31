<?php
declare(strict_types=1);

namespace common\components;

use common\dto\CommentDTO;
use common\dto\ReplyResponseDto;
use common\dto\VideoDto;
use Google\Exception;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_VideoLocalization;
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
    public function getYoutubeService($deffer = false): Google_Service_YouTube
    {
        $client = $this->getClient();
        if ($deffer){
            $client->setDefer(true);
        }
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
            $cred = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            if (isset($cred['error'])){
                Yii::$app->session->remove('token');
            }else{
                $session['token'] = $client->getAccessToken();
            }
        }
        return $client;
    }

    public function videoListByChannel($snippet, $params)
    {
        $service = $this->getYoutubeService();
        $videos = [];

        $channelId = ArrayHelper::getValue($params, 'channelId');
        $searchParams = [
            'channelId'  => $channelId,
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

            $videos[] = new VideoDto(
                $videoId,
                $title,
                $thumbnailUrl,
                $fullDescription,
                $defaultLanguage,
                $localizations,
                $channelId
            );
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
            $etag = $this->getEtag(__METHOD__, $videoId, $pageToken);

            try {
                $request = $this->getYoutubeService(true)->commentThreads->listCommentThreads('snippet,replies', $params);
                //$request = $request->withHeader("If-None-Match", $etag);
                $response = $this->getClient()->execute($request);
            } catch (\Throwable $e) {
                if ($e->getCode() == 304) {
                    continue;
                } else {
                    throw $e;
                }
            }

            $newEtag = $response->getEtag();
            if ($newEtag) {
                $this->saveEtag(__METHOD__, $videoId, $pageToken, $newEtag);
            }

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

    private function getEtag($method, $entityId, $pageToken)
    {
        $session = Yii::$app->session;
        $etagKey = $this->generateEtagKey($method, $entityId, $pageToken);

        return $session->get($etagKey);
    }

    private function saveEtag($method, $entityId, $pageToken, $etag)
    {
        $session = Yii::$app->session;
        $etagKey = $this->generateEtagKey($method, $entityId, $pageToken);

        $session->set($etagKey, $etag);
    }

    private function generateEtagKey($method, $entityId, $pageToken)
    {
        return "etag_{$method}_{$entityId}_" . md5((string)$pageToken);
    }

    /**
     * Replies to an existing comment.
     * @param string $parentId
     * @param string $text
     * @return ReplyResponseDto
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function replyToComment(string $parentId, string $text): ReplyResponseDto
    {
        $service = $this->getYoutubeService();
        $commentSnippet = new \Google_Service_YouTube_CommentSnippet();
        $commentSnippet->setParentId($parentId);
        $commentSnippet->setTextOriginal($text);

        $reply = new \Google_Service_YouTube_Comment();
        $reply->setSnippet($commentSnippet);

        $response = $service->comments->insert('snippet', $reply);
        return new ReplyResponseDto($response, $parentId);
    }


    /**
     * Creates a new comment on a video.
     * @param string $videoId
     * @param string $text
     * @return \Google\Service\YouTube\CommentThread
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function createComment(string $videoId, string $text): \Google\Service\YouTube\CommentThread
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
        $etagKey = 'channel_id_etag';
        $cachedChannelId = Yii::$app->session->get($etagKey);

        if ($cachedChannelId) {
            return $cachedChannelId;
        }

        $channelsResponse = $service->channels->listChannels('id,snippet', ['mine' => true]);

        $newEtag = $channelsResponse->getEtag();
        $currentEtag = Yii::$app->session->get('channel_etag');

        if ($newEtag === $currentEtag) {
            return Yii::$app->session->get('channel_id');
        }

        if (!empty($channelsResponse->getItems())) {
            $channelId = $channelsResponse->getItems()[0]->getId();
            Yii::$app->session->set('channel_id', $channelId);
            Yii::$app->session->set('channel_etag', $newEtag);

            return $channelId;
        }
        return null;
    }
}