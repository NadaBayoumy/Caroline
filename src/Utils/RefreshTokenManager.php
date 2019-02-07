<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Utils;

use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

/**
 * Description of RefreshTokenManager
 *
 * @author yosra
 */
class RefreshTokenManager {

    protected $refreshTokenSendListener;
    protected $refrshTokenMgr;

    public function __construct($refreshTokenSendListener, $refrshTokenMgr) {
        $this->refreshTokenSendListener = $refreshTokenSendListener;
        $this->refrshTokenMgr = $refrshTokenMgr;
    }

    public function generateFromUser($user) {

        $response = new Response();
        $event = new AuthenticationSuccessEvent([], $user, $response);
        $this->refreshTokenSendListener->attachRefreshToken($event);
        $refreshToken = $this->refrshTokenMgr->getLastFromUsername($user->getUsername());
        if (!empty($refreshToken)) {
            return $refreshToken->getRefreshToken();
        }


        return null;
    }

}
