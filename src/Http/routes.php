<?php


Route::group([ 'as' => 'codex.hooks.git.webhook.', 'prefix' => '_git-webhook' ], function () {
return;
    Route::any('github', [ 'as' => 'github', 'uses' => 'WebhookController@github' ]);
    Route::any('bitbucket', [ 'as' => 'bitbucket', 'uses' => 'WebhookController@bitbucket' ]);
});
