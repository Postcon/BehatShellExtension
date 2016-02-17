<?php

namespace Postcon\BehatShellExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer as ContextInitializerInterface;

class ContextInitializer implements ContextInitializerInterface
{
    const CLASS_NAME = __CLASS__;

    /** @var array */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        if (!isset($this->config['default'])) {
            $this->config['default'] = [
                'type' => 'local',
                'base_dir' => null,
                'timeout' => null,
            ];
        }
    }

    /**
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof ShellContext) {
            $context->initializeConfig($this->config);
        }
    }
}
