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

/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 7/4/16
 * Time: 3:05 AM
 */

namespace Codex\Addon\Git\Exceptions;


class CodexGitException extends \RuntimeException
{
    public static function because($msg = '')
    {
        return new static($msg);
    }

    public static function missingConfiguration($msg = '')
    {
        return new static('[Missing Configuration] ' . $msg);
    }

    public static function notEnabled($msg = '')
    {
        return new static('[Not Enabled] ' . $msg);
    }
}
