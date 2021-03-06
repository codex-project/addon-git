<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Codex\Addon\Git\Http\Controllers;

use Codex\Addon\Git\CodexGit;
use Codex\Addon\Git\Console\SyncCommand;
use Codex\Addon\Git\Jobs\SyncJob;
use Codex\Codex;
use Codex\Http\Controllers\Controller;
use Codex\Projects\Project;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Response;
use Sebwite\Support\Arr;

/**
 * This is the GithubController.
 *
 * @package        Codex\Hooks
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class WebhookController extends Controller
{
    use DispatchesJobs;


    /**
     * @var \Codex\Codex
     */
    protected $codex;

    /**
     * @var \Codex\Addon\Git\CodexGit
     */
    protected $git;

    public function __construct(Codex $codex, ViewFactory $view, CodexGit $git)
    {
        parent::__construct($codex, $view);
        $this->git = $git;
    }

    public function bitbucket()
    {
        $this->codex->log('info', 'codex.git.webhook.call', [ 'remote' => 'bitbucket' ]);

        $headers = Arr::only(request()->headers->all(), [
            'x-request-uuid',
            'x-event-key',
            'user-agent',
            'x-hook-uuid',
        ]);
        $data = array_dot(request()->all());

        $valid =
            $headers[ 'user-agent' ][ 0 ] === 'Bitbucket-Webhooks/2.0' &&
            $headers[ 'x-event-key' ][ 0 ] === 'repo:push' &&
            isset($data[ 'repository.name' ]);

        if ( !$valid )
        {
            $this->codex->log('info', 'codex.git.webhook.invalid', [ 'remote' => 'bitbucket' ]);
            return response('Invalid headzors', 500);
        }

        return $this->applyToGitProjects('bitbucket', function () use ($data)
        {
            return $data[ 'repository.full_name' ];
        });
    }

    /**
     * webhook
     *
     * @param $type
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function github()
    {
        $this->codex->log('info', 'codex.git.webhook.call', [ 'remote' => 'github' ]);

        $headers = [
            'delivery'   => request()->header('x-github-delivery'),
            'event'      => request()->header('x-github-event'),
            'user-agent' => request()->header('user-agent'),
            'signature'  => request()->header('x-hub-signature'),
        ];
        $data = array_dot(request()->all());

        return $this->applyToGitProjects('github', function (Project $project) use ($data, $headers)
        {
            $hash = trim(hash_hmac('sha1', file_get_contents("php://input"), $project->config('git.webhook.secret')));
            $valid = $headers[ 'signature' ] === "sha1=$hash";

            $this->codex->log('info', 'codex.git.webhook.call.data', [
                'signature' => $headers[ 'signature' ],
                'secret'    => $project->config('git.webhook.secret'),
                'hash'      => $hash,
                'valid'     => $valid,
            ]);

            if ( $valid )
            {
                return strtolower($data[ 'repository.full_name' ]);
            }
            else
            {
                $this->codex->log('info', 'codex.git.webhook.invalid', [ 'remote' => 'github' ]);
                return response()->json([ 'message' => 'invalid hash' ], 403);
            }
        });
    }

    protected function applyToGitProjects($remote, \Closure $closure)
    {

        foreach ( $this->codex->projects->all() as $project )
        {
            $name = $project->getName();

            if ( ! $project->config('git.enabled', false) || ! $project->config('git.webhook.enabled', false) || $project->config('git.connection') !== $remote)
            {
                continue;
            }

            $projectRepo = $project->config('git.owner') . '/' . $project->config('git.repository');
            $hookRepo = call_user_func_array($closure, [ $project ]);

            if ( $hookRepo instanceof Response )
            {
                return $hookRepo;
            }
            if ( $hookRepo !== $projectRepo )
            {
                continue;
            }

            $this->dispatch(new SyncJob($name));
            $this->codex->log('info', 'codex.git.webhook.success', [ 'remote' => $remote, 'name' => $name ]);

            return response('', 200);
        }
    }
}
