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

    /** @var string */
    private $featurePath;

    /**
     * @param string $featurePath
     */
    public function __construct($featurePath = __DIR__)
    {
        $this->featurePath = rtrim($featurePath, \DIRECTORY_SEPARATOR);;
    }

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
     * @When I copy file :file to :directory
     * @When I copy file :file to :directory on :server
     */
    public function iCopyFileTo($file, $directory, $server = 'default')
    {
        if (!isset($this->config[$server])) {
            throw new \Exception(sprintf('Configuration not found for server "%s"', $server));
        }

        $sourceFile      = $this->featurePath . \DIRECTORY_SEPARATOR . ltrim($file, \DIRECTORY_SEPARATOR);
        $destinationFile = $directory . \DIRECTORY_SEPARATOR . basename($file);

        if ('local' === $this->config[$server]['type']) {
            copy($sourceFile, $destinationFile);
        } else {
            $process = $this->createScpProcess($sourceFile, $directory, $this->config[$server]);
            $process->run();
        }
    }

    /**
     * @Then it should pass
     */
    public function itShouldPass()
    {
        if (false === $this->process->isSuccessful()) {
            throw new \Exception(sprintf('Process failed: %s', $this->process->getCommandLine()));
        }
    }

    /**
     * @Then I see
     * @Then I see :string
     */
    public function iSee($string)
    {
        $actual   = trim($this->process->getOutput());
        $expected = trim($string);

        if ($expected !== $actual) {
            throw new \Exception(sprintf('"%s" != "%s"', $actual, $expected));
        }
    }

    /**
     * @param       $command
     * @param array $serverConfig
     *
     * @return Process
     */
    private function createProcess($command, array $serverConfig)
    {
        $process = 'local' === $serverConfig['type']
            ? $this->createLocalProcess($command, $serverConfig)
            : $this->createRemoteProcess($command, $serverConfig);

        if (null !== $serverConfig['timeout']) {
            $process->setTimeout($serverConfig['timeout']);
        }

        return $process;
    }

    /**
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createLocalProcess($command, array $serverConfig)
    {
        return new Process($command, $serverConfig['base_dir']);
    }

    /**
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createRemoteProcess($command, array $serverConfig)
    {
        if ($serverConfig['base_dir']) {
            $command = sprintf('cd %s ; %s', $serverConfig['base_dir'], $command);
        }

        $command = sprintf(
            '%s %s %s %s',
            $serverConfig['ssh_command'],
            $serverConfig['ssh_options'],
            $serverConfig['ssh_hostname'],
            escapeshellarg($command)
        );

        return new Process($command);
    }

    /**
     * @param string $source
     * @param string $destination
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createScpProcess($source, $destination, array $serverConfig)
    {
        $command = sprintf(
            '%s %s %s %s',
            $serverConfig['scp_command'],
            $serverConfig['ssh_options'],
            escapeshellarg($source),
            escapeshellarg($serverConfig['ssh_hostname'] . ':' . $destination)
        );

        return new Process($command);
    }
}
