<?php
/**
 * Part of the Codex Project packages.
 *
 * License and copyright information bundled with this package in the LICENSE file.
 *
 * @author    Robin Radic
 * @copyright Copyright 2016 (c) Codex Project
 * @license   http://codex-project.ninja/license The MIT License
 */
Route::any('github', [ 'as' => 'github', 'uses' => 'WebhookController@github' ]);
Route::any('bitbucket', [ 'as' => 'bitbucket', 'uses' => 'WebhookController@bitbucket' ]);

