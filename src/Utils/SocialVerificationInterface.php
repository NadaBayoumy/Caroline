<?php

namespace App\Utils;
use App\Entity\User;

/**
 * SocialVerificationInterface
 * @author amira
 */
interface SocialVerificationInterface {
    /**
     * Verify social token
     * @param type $socialtoken
     * @return boolean
     */
    public function verifySocialToken($socialtoken);
    /**
     * Get social id
     * @return type socialId
     */
    public function getSocialId();
    /**
     * Load user using social Id
     * @param type $socialId
     * @return User
     */
    public function loadUserBySocialId($socialId);
    
    /**
     * Get error message
     * @return string $message
     */
    public function getErrorMessage();
    /**
     * Get status code
     * @return int $code
     */
    public function getStatusCode();
    /**
     * Get UserInfo
     * @param array $userInfo
     */
    public function getUserInfo();
}
