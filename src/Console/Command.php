<?php
/**
 * Part of Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.org
 */
namespace Codex\Addon\Defaults\Git\Console;

use Codex\Addon\Defaults\Git\Git;
use Codex\Core\Contracts\Codex;
use Illuminate\Contracts\Queue\Queue;

/**
 * This is the CoreListCommand class.
 *
 * @package                   Codex\Core
 * @version                   1.0.0
 * @author                    Robin Radic
 * @license                   MIT License
 * @copyright                 2015, Robin Radic
 * @link                      https://github.com/robinradic
 */
abstract class Command extends \Sebwite\Console\Command
{
    /**
     * @var \Codex\Core\Contracts\Codex|\Codex\Core\Codex
     */
    protected $codex;

    /**
     * @var \Illuminate\Contracts\Queue\Queue
     */
    protected $queue;

    /**
     * @var \Codex\Hooks\Git\Contracts\Factory|\Codex\Hooks\Git\Factory
     */
    protected $git;

    /**
     * Command constructor.
     *
     * @param \Codex\Core\Contracts\Codex|\Codex\Core\Factory             $codex
     * @param \Illuminate\Contracts\Queue\Queue                           $queue
     * @param \Codex\Hooks\Git\Contracts\Factory|\Codex\Hooks\Git\Factory $git
     */
    public function __construct(Codex $codex, Queue $queue, Git $git)
    {
        parent::__construct();
        $this->codex = $codex;
        $this->queue = $queue;
        $this->git   = $git;
    }
}
