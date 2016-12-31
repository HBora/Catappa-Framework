<?php

namespace Catappa\Kernel;

use \Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name ExceptionHandler
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Kernel
 * @version Catappa Version 1 PhpFaces Version 3
 * @category Catappa Kernel
 */
Class ExceptionHandler {

    private static $event = null;
    private static $handler = null;

    public static function handleException(\Exception $e, $error = false) {

        if ($e instanceof \Catappa\Exceptions\NotFound)
            \Route::getNothing();

        else if ($e instanceof \Catappa\Exceptions\Redirect)
            RedirectResponse::create($e->getMessage())->send();
        elseif (!$e instanceof \Catappa\Exceptions\AnnotationException)
            include 'handleexception.php';
    }

    static function handleShutdown() {
        $error = error_get_last();

        if ($error !== NULL) {
            $code = (int) $error["type"];

            if ($code != E_STRICT && $code != E_NOTICE && $code != E_WARNING) {
                $error = error_get_last();
                $contens = ob_get_contents();
                ob_end_clean();
                ExceptionHandler::handleException(new \ErrorException($contens, $error["type"], 0, $error['file'], $error['line']), true);
            }
        }
    }

}
