<?php

namespace Postcon\BehatShellExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\Process\Process;

class ShellContext implements Context, SnippetAcceptingContext
{
    /** @var array */
    private $config;

    /** @var Process */
    private $process;

    public function init(array $config)
    {
        $this->config = $config;
    }

    /**
     * @When I run :command
     * @When I run :command on :server
     */
    public function iRun($command, $server = 'default')
    {
        if (!isset($this->config[$server])) {
            throw new \Exception(sprintf('Configuration not found for server "%s"', $server));
        }

        $this->process = $this->createProcess($command, $this->config[$server]);
        $this->process->run();
    }

    /**
     * @Then It should pass
     */
    public function itShouldPass()
    {
        if (false === $this->process->isSuccessful()) {
            throw new \Exception();
        }
    }

    /**
     * @Then I see
     */
    public function iSee(PyStringNode $string = null)
    {
        if (trim($string->getRaw()) !== trim($this->process->getOutput())) {
            throw new \Exception();
        }
    }

    /**
     * @Then I see :output
     */
    public function iSeeInline($output)
    {
        $actual   = trim($this->process->getOutput());
        $expected = trim($output);

        if ($expected !== $actual) {
            throw new \Exception(sprintf('"%s" != "%s"', $actual, $expected));
        }
    }

    /**
     * @param $command
     * @param array $serverConfig
     *
     * @return Process
     */
    private function createProcess($command, array $serverConfig)
    {
        return
            'local' === $serverConfig['type']
                ? $this->createLocalProcess($command, $serverConfig)
                : $this->createRemoteProcess($command, $serverConfig);
    }

    /**
     * @param string $command
     * @param array $serverConfig
     *
     * @return Process
     */
    private function createLocalProcess($command, array $serverConfig)
    {
        return new Process($command, $serverConfig['base_dir']);
    }

    /**
     * @param string $command
     * @param array $serverConfig
     *
     * @return Process
     */
    private function createRemoteProcess($command, array $serverConfig)
    {
        if ($serverConfig['base_dir']) {
            $command = sprintf('cd %s ; %s', $serverConfig['base_dir'], $command);
        }

        $command = sprintf(
            '%s %s %s',
            $serverConfig['ssh_command'],
            $serverConfig['ssh_options'],
            escapeshellarg($command)
        );

        return new Process($command);
    }
}
