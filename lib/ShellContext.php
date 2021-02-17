<?php

namespace Postcon\BehatShellExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\ScenarioScope;
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
     * @param array $config
     */
    public function initializeConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @BeforeScenario
     *
     * @param ScenarioScope $scope
     */
    public function initializeFeatureFilePath(ScenarioScope $scope)
    {
        $this->featurePath = dirname($scope->getFeature()->getFile());
    }

    /**
     * @When I run :command
     * @When I run :command on :server
     *
     * @param string $command
     * @param string $server
     *
     * @throws \Exception
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
     *
     * @param string $file
     * @param string $directory
     * @param string $server
     *
     * @throws \Exception
     */
    public function iCopyFileTo($file, $directory, $server = 'default')
    {
        if (!isset($this->config[$server])) {
            throw new \Exception(sprintf('Configuration not found for server "%s"', $server));
        }

        $sourceFile = $this->featurePath . \DIRECTORY_SEPARATOR . ltrim($file, \DIRECTORY_SEPARATOR);

        switch ($this->config[$server]['type']) {
            case 'remote':
                $this->process = $this->createScpProcess($sourceFile, $directory, $this->config[$server]);
                break;

            case 'docker':
                $this->process = $this->createDockerCpProcess($sourceFile, $directory, $this->config[$server]);
                break;

            case 'local':
                $this->process = $this->createLocalCpProcess($sourceFile, $directory);
                break;

            case 'kubectl':
                $this->process = $this->createKubectlCpProcess($sourceFile, $directory, $this->config[$server]);
                break;


            default:
                throw new \Exception(
                    sprintf(
                        'Unknown server type given: %s. Possible values are (remote|docker|local)',
                        $this->config[$server]['type']
                    )
                );
        }

        $this->process->run();
    }

    /**
     * @Then it should pass
     */
    public function itShouldPass()
    {
        if (true !== $this->process->isSuccessful()) {
            throw new \Exception(sprintf(
                    "Process failed: %s\n%s\n%s",
                    $this->process->getCommandLine(),
                    $this->process->getOutput(),
                    $this->process->getErrorOutput()
                ));
        }
    }

    /**
     * @Then it should fail
     */
    public function itShouldFail()
    {
        if (true === $this->process->isSuccessful()) {
            throw new \Exception(sprintf('Process passed: %s', $this->process->getCommandLine()));
        }
    }

    /**
     * @Then I see
     * @Then I see :string
     *
     * @param string|PyStringNode $string
     *
     * @throws \Exception
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
     * @Then I see something like :string
     * @Then I see something like
     *
     * @param string|PyStringNode $string
     *
     * @throws \Exception
     */
    public function iSeeSomethingLike($string)
    {
        $actual   = trim($this->process->getOutput());
        $expected = trim($string);

        if (false === strpos($actual, $expected)) {
            throw new \Exception(sprintf('"%s" does not contain "%s"', $actual, $expected));
        }
    }

    /**
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createProcess($command, array $serverConfig)
    {
        switch ($serverConfig['type']) {
            case 'remote':
                $process = $this->createRemoteProcess($command, $serverConfig);
                break;

            case 'docker':
                $process = $this->createDockerProcess($command, $serverConfig);
                break;

            case 'kubectl':
                $process = $this->createKubectlProcess($command, $serverConfig);
                break;

            default:
                $process = $this->createLocalProcess($command, $serverConfig);
                break;
        }

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
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createDockerProcess($command, array $serverConfig)
    {
        if ($serverConfig['base_dir']) {
            $command = sprintf('cd %s ; %s', $serverConfig['base_dir'], $command);
        }

        $command = sprintf(
            '%s exec %s %s /bin/bash -c %s',
            $serverConfig['docker_command'],
            $serverConfig['docker_options'],
            $serverConfig['docker_containername'],
            escapeshellarg($command)
        );

        return new Process($command);
    }

    /**
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createKubectlProcess($command, array $serverConfig)
    {
       $command = sprintf(
            'kubectl -n %s exec %s -c %s -- /bin/bash -c %s',
            $serverConfig['namespace'],
            getenv('HOSTNAME'),
            $serverConfig['containername'],
            escapeshellarg($command)
        );

        error_log($command);

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

    /**
     * @param string $source
     * @param string $destination
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createDockerCpProcess($source, $destination, array $serverConfig)
    {
        $command = sprintf(
            '%s cp %s %s:%s',
            $serverConfig['docker_command'],
            escapeshellarg($source),
            $serverConfig['docker_containername'],
            escapeshellarg($destination)
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
    private function createKubectlCpProcess($source, $destination, array $serverConfig)
    {
        $command = sprintf(
            'kubectl cp %s/%s:%s -c %s %s',
            $serverConfig['namespace'],
            getenv('HOSTNAME'),
            escapeshellarg($source),
            $serverConfig['containername'],
            escapeshellarg($destination)
        );

        error_log($command);

        return new Process($command);
    }

    /**
     * @param string $source
     * @param string $destination
     * @return Process
     */
    private function createLocalCpProcess($source, $destination)
    {
        $command = sprintf(
            'cp %s %s',
            escapeshellarg($source),
            escapeshellarg($destination)
        );

        return new Process($command);
    }
}
