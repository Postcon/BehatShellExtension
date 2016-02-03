<?php

namespace Postcon\BehatShellExtension;

use Behat\Behat\Context\Context;

class ShellContext implements Context
{
    /** @var array */
    private $config;

    public function init(array $config)
    {
        $this->config = $config;
    }
}
