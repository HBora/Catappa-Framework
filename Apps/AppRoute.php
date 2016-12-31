<?php

Route::setNothing(function () {
    echo "404 Page not found.";
});

//Route::addMidleWare("/", array("First"));
Route::setAppFrontController(Apps\FrontController::class);

Route::setIncorrectHTTPMethod(function() {
    echo "Incorrect http method.";
});
