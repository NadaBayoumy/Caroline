<?php

namespace App\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Doctrine\ORM\EntityManagerInterface;
#use App\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Sylius\Component\User\Model\User;

/**
 * GoogleVerificationManager
 *
 * @author amira
 */
class GoogleVerificationManager implements SocialVerificationInterface {

    const INFO_REQUEST_URL = 'https://www.googleapis.com/plus/v1/people/me';
    const INFO_REQUEST_METHOD = 'GET';
    const APP_REQUEST_URL = 'https://www.googleapis.com/oauth2/v2/tokeninfo';
    const APP_REQUEST_METHOD = 'POST';

    private $em;
    private $clientIds;
    private $errorMessage = null;
    private $statusCode = null;
    private $socialId = null;
    private $userInfo = [];
    protected $translator;

    /**
     * __construct
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, string $authGoogleClientId, TranslatorInterface $translator) {
        $this->em = $em;
        $this->clientIds = explode("|", $authGoogleClientId);
        $this->translator = $translator;
    }

    /**
     * Verify social token
     * @param type $socialtoken
     * @return boolean
     */
    public function verifySocialToken($socialtoken) {
        $httpClient = new Client();
        try {

            $response = $httpClient->request(self::APP_REQUEST_METHOD, self::APP_REQUEST_URL . "?access_token={$socialtoken}");
            $this->statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $body = json_decode($body);
            // check auth google client id
            if (in_array($body->issued_to, $this->clientIds)) {
                $response = $httpClient->request(self::INFO_REQUEST_METHOD, self::INFO_REQUEST_URL, [
                    'headers' => [
                        'Authorization' => "Bearer {$socialtoken}"
                    ]
                ]);
                $this->statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                $body = json_decode($body);
                $this->socialId = $body->id;
                $this->userInfo['name'] = $body->displayName;
                if (!$body->image->isDefault) {
                    $this->userInfo['image'] = $body->image->url;
                }
            } else {
                $this->statusCode = 400;
                $this->errorMessage = 'Not valid client Id';
                return false;
            }
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $this->statusCode = $e->getResponse()->getStatusCode();
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorBody = json_decode($errorBody);
                $this->errorMessage = property_exists($errorBody, 'error_description') ?
                        $errorBody->error_description : $errorBody->error->message;
            }
            return false;
        }
        return true;
    }

    /**
     * Get Social id
     * @return type
     */
    public function getSocialId() {
        return $this->socialId;
    }

    /**
     * Load user using social Id
     * @param type $socialId
     * @return User
     */
    public function loadUserBySocialId($socialId) {
        $user = $this->em->getRepository('App:User')->findOneByGoogleId($socialId);
        if ($user && !$user->isEnabled()) {
            throw new AuthenticationException($this->translator->trans('Account is disabled'));
        }
        return $user;
    }

    /**
     * Get error message
     * @return string 
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * Get status code
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * Get User Info
     * @return array
     */
    public function getUserInfo() {
        return $this->userInfo;
    }

}
