<?php
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

function csrf_token() {
    $csrfGenerator = new UriSafeTokenGenerator();
    $csrfManager = new CsrfTokenManager();
    $token = $csrfManager->getToken($csrfGenerator->generateToken());
    return $token->getId();
}
