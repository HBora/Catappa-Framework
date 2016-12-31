<?php

namespace Catappa\Http\MiddleWares;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name Crfs
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Http
 * @version 1.0
 * @category Catappa HTTP
 */
use Catappa\Http\MiddleWare;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

class Csrf implements MiddleWare {

    public function next(Request $request) {
        $manager = new CsrfTokenManager();
        $tokenId = $request->get("_csrf_token");
        $storage = new NativeSessionTokenStorage();
        if (!$storage->hasToken($tokenId)) {
            return false;
        }
        $value = $storage->getToken($tokenId);
        $csrf_token = new CsrfToken($tokenId, $value);
        if (!$manager->isTokenValid($csrf_token)) {
            return false;
        }
        $manager->refreshToken($tokenId);
        return true;
    }

}