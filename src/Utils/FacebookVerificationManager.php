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
 * FacebookVerificationManager
 *
 * @author amira
 */
class FacebookVerificationManager implements SocialVerificationInterface {

    const INFO_REQUEST_URL = 'https://graph.facebook.com/v3.2/me?fields=picture,name';
    const APP_REQUEST_URL = 'https://graph.facebook.com/v3.2/app';
    const REQUEST_METHOD = 'GET';

    private $em;
    private $authFacebookAppId;
    private $errorMessage = null;
    private $statusCode = null;
    private $socialId = null;
    private $userInfo = [];
    protected $translator;

    /**
     * __construct
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, int $authFacebookAppId,TranslatorInterface $translator) {
        $this->em = $em;
        $this->authFacebookAppId = $authFacebookAppId;
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
            $response = $httpClient->request(self::REQUEST_METHOD, self::APP_REQUEST_URL . "?access_token={$socialtoken}");
            $this->statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $body = json_decode($body);
            // check auth facebook app id
            
            if ($this->authFacebookAppId == $body->id) {
                $response = $httpClient->request(self::REQUEST_METHOD, self::INFO_REQUEST_URL . "&access_token={$socialtoken}");
                $this->statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                $body = json_decode($body);
                $this->socialId = $body->id;
                $this->userInfo['name'] = $body->name;
                if(!$body->picture->data->is_silhouette){
                    $this->userInfo['image'] = $body->picture->data->url;
                }
            }else{
                $this->statusCode = 400;
                $this->errorMessage = 'Not valid App Id';
                return false;
            }
            
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $this->statusCode = $e->getResponse()->getStatusCode();
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorBody = json_decode($errorBody);
                $this->errorMessage = $errorBody->error->message;
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
        $user = $this->em->getRepository('App:User')->findOneByFacebookId($socialId);
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
