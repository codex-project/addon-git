<?php
/**
 * Part of Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.org
 */
namespace Codex\Addon\Git\Console;

use Codex\Codex;
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
abstract class Command extends \Illuminate\Console\Command
{
    /**
     * @var \Codex\Contracts\Codex|\Codex\Codex
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
     * @param \Codex\Contracts\Codex|\Codex\Factory                       $codex
     * @param \Illuminate\Contracts\Queue\Queue                           $queue
     * @param \Codex\Hooks\Git\Contracts\Factory|\Codex\Hooks\Git\Factory $git
     */
    public function __construct(Codex $codex, Queue $queue)
    {
        parent::__construct();
        $this->codex = $codex;
        $this->queue = $queue;
        $this->git   = $codex->git;
    }
}
