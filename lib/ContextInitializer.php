<?php

namespace Postcon\BehatShellExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer as ContextInitializerInterface;

class ContextInitializer implements ContextInitializerInterface
{
    /** @var array */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof ShellContext) {
            $context->init($this->config);
        }
    }
}
