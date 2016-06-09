<?php


Route::any('github', [ 'as' => 'github', 'uses' => 'WebhookController@github' ]);
Route::any('bitbucket', [ 'as' => 'bitbucket', 'uses' => 'WebhookController@bitbucket' ]);

