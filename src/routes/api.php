<?php


Route::group([

    'namespace'=>'beinmedia\payment\Http\Controllers',

    ],
    function(){

        Route::post('/webhookresponse', 'WebhookController@webhookResponse');
        Route::post('/fawry-check', 'FawryController@fawryCheck');

    });
