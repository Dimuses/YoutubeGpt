<?php

namespace frontend\component;

use common\models\Auth;
use common\models\User;
use Exception;
use Google_Client;
use Google_Service_YouTube;
use Yii;
use yii\authclient\ClientInterface;
use yii\helpers\ArrayHelper;

/**
 * AuthHandler handles successful authentification via Yii auth component
 */
class AuthHandler
{
    const CREDENTIAL_PATH = '@common/files/yt-oauth2.json';
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }


    private function getClient()
    {
        $client = new Google_Client();
        $client->setAccessType('offline');
        $accessToken = $this->client->getAccessToken()->token;
        if ($accessToken){
            $client->setAccessToken($accessToken);
            file_put_contents(Yii::getAlias(self::CREDENTIAL_PATH), $client->getAccessToken());
            // Refresh the token if it's expired.
            if ($client->isAccessTokenExpired()) {
                $client->refreshToken($client->getRefreshToken());
                file_put_contents(Yii::getAlias(self::CREDENTIAL_PATH), $client->getAccessToken());
            }
            return $client;
        }
    }


    public function handle()
    {
        $youtubeClient = $this->getClient();


        $attributes = $this->client->getUserAttributes();
        $email = ArrayHelper::getValue($attributes, 'email');
        $id = ArrayHelper::getValue($attributes, 'id');

        /* @var Auth $auth */
        $auth = Auth::find()->where([
            'source' => $this->client->getId(),
            'source_id' => $id,
        ])->one();

        if (Yii::$app->user->isGuest) {
            if ($auth) { // login
                /* @var User $user */
                $user = $auth->user;
                $this->updateUserInfo($user);
                Yii::$app->user->login($user, 3600);
            } else { // signup
                if ($email !== null && User::find()->where(['email' => $email])->exists()) {
                    Yii::$app->getSession()->setFlash('error', [
                        Yii::t('app', "User with the same email as in {client} account already exists but isn't linked to it. Login using email first to link it.", ['client' => $this->client->getTitle()]),
                    ]);
                } else {
                    $password = Yii::$app->security->generateRandomString(6);
                    $user = new User([
                        'username' => $email,
                        'email' => $email,
                        'password' => $password,
                        'status' => User::STATUS_ACTIVE,
                        // 'status' => User::STATUS_ACTIVE // make sure you set status properly
                    ]);
                    $user->generateAuthKey();
                    $user->generatePasswordResetToken();

                    $transaction = User::getDb()->beginTransaction();

                    if ($user->save()) {
                        $auth = new Auth([
                            'user_id' => $user->id,
                            'source' => $this->client->getId(),
                            'source_id' => (string)$id,
                        ]);
                        if ($auth->save()) {
                            $transaction->commit();
                            Yii::$app->user->login($user, 3600);
                        } else {
                            Yii::$app->getSession()->setFlash('error', [
                                Yii::t('app', 'Unable to save {client} account: {errors}', [
                                    'client' => $this->client->getTitle(),
                                    'errors' => json_encode($auth->getErrors()),
                                ]),
                            ]);
                        }
                    } else {
                        Yii::$app->getSession()->setFlash('error', [
                            Yii::t('app', 'Unable to save user: {errors}', [
                                'client' => $this->client->getTitle(),
                                'errors' => json_encode($user->getErrors()),
                            ]),
                        ]);
                    }
                }
            }
        } else { // user already logged in
            if (!$auth) { // add auth provider
                $auth = new Auth([
                    'user_id' => Yii::$app->user->id,
                    'source' => $this->client->getId(),
                    'source_id' => (string)$attributes['id'],
                ]);
                if ($auth->save()) {
                    /** @var User $user */
                    $user = $auth->user;
                    $this->updateUserInfo($user);
                    Yii::$app->getSession()->setFlash('success', [
                        Yii::t('app', 'Linked {client} account.', [
                            'client' => $this->client->getTitle()
                        ]),
                    ]);
                } else {
                    Yii::$app->getSession()->setFlash('error', [
                        Yii::t('app', 'Unable to link {client} account: {errors}', [
                            'client' => $this->client->getTitle(),
                            'errors' => json_encode($auth->getErrors()),
                        ]),
                    ]);
                }
            } else { // there's existing auth
                Yii::$app->getSession()->setFlash('error', [
                    Yii::t('app',
                        'Unable to link {client} account. There is another user using it.',
                        ['client' => $this->client->getTitle()]),
                ]);
            }
        }
    }

    /**
     * @param User $user
     */
    private function updateUserInfo(User $user)
    {
        $attributes = $this->client->getUserAttributes();
        $github = ArrayHelper::getValue($attributes, 'login');
//        if ($user->github === null && $github) {
//            $user->github = $github;
//            $user->save();
//        }
    }
}
