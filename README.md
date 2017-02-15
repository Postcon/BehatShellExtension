# BehatShellExtension [![Build Status](https://secure.travis-ci.org/Postcon/BehatShellExtension.png)](http://travis-ci.org/Postcon/BehatShellExtension)

Behat extension for executing shell commands within features. The shell commands can be run on remote
servers using ssh or locally without network. Additionally, local files can be deployed to directories
on remote servers.

## Installation

Using [composer](https://getcomposer.org/download/):

```
composer require postcon/behat-shell-extension dev-master
```

## Usage

```gherkin
# run.feature

Feature: Running commands
  In order to run useful integration tests
  As a tester
  I want to execute shell commands and check their output

  Scenario: Run command on the default shell/server and define expected output
    When I run "pwd"
    Then It should pass
    And I see
    """
    /tmp
    """

  Scenario: Run command on the default shell/server and define expected output in inline-style
    When I run "pwd"
    Then It should pass
    And I see "/tmp"

  Scenario: Run command on the shell/server "app"
    When I run "app/console --env=prod do:mi:mi" on "app"
    Then It should pass
```

```gherkin
# copy.feature

Feature: Copy file
  In order to prepare integration tests
  As a tester
  I want to copy files to directories (on remote servers)

  Scenario: Copy a file to /tmp directory on default server (or at the local filesystem)
    Given I copy file "test.txt" to "/tmp"
    And I run "cat /tmp/test.txt"
    Then it should pass
    And I see
    """
    content of test.txt
    """

  Scenario: Copy a file to /tmp directory on "app" server
    Given I copy file "test.txt" to "/tmp" on "app"
    And I run "cat /tmp/test.txt" on "app"
    Then it should pass
    And I see
    """
    content of test.txt
    """
```

## Configuration

To use the BehatShellExtension, it needs to be configured in the `behat.yml` (or `behat.yml.dist`).
Each server or shell, you want invoke commands on, must be specified.

### Local shell

Following example shows the minimal configuration for a local shell.

```yml
# behat.yml
extensions:
  ShellExtension:
    default:
      type: local
```

It is possible, to give two additional configuration parameters: the command execution`base_dir` and the
`timeout` (in seconds; if the commands does not terminate within this timeout, it gets stopped and the behat feature
fails).

```yml
# behat.yml
extensions:
  ShellExtension:
    default:
      type: local
      base_dir: /tmp
      timeout: 10
```

### Remote server / ssh

For accessing a remote server via ssh, a minimal configuration is like this:

```yml
# behat.yml
extensions:
  ShellExtension:
    ...
    app:
      type: remote
      ssh_hostname: user@shell.example.com
```

The `ssh_hostname` specifies the name of the ssh server and the username.
Using additional parameters, the ssh connection can be configured and the _ssh_ and _scp_ binaries can be specified:

```yml
# behat.yml
extensions:
  ShellExtension:
    ...
    app:
      type: remote
      base_dir: /tmp
      ssh_hostname: user@shell.example.com
      ssh_options: -i ~/.ssh/id_rsa
      ssh_command: /usr/bin/ssh
      scp_command: /usr/bin/scp
      timeout: 20
```

If we have this feature example
```
Scenario:
  Given I copy file "test.txt" to "/tmp" on "app"
  And I run "cat /tmp/test.txt" on "app"
```

then the resulting commands would be this:
```
/usr/bin/scp -i ~/.ssh/id_rsa 'test.txt' 'user@shell.example.com:/tmp'
/usr/bin/ssh -i ~/.ssh/id_rsa user@shell.example.com 'cd /tmp ; cat /tmp/test.txt'

```

### Docker

To execute commands in a _[docker](https://docs.docker.com/) container_, the following minimal configuration is appropriate:

```yml
# behat.yml
extensions:
  ShellExtension:
    ...
    app:
      type: docker
      docker_containername: app
```

Here, we assume to have a docker container like this:

`docker run --name=app -d nginx`

A more extensive configuration is this:

```yml
# behat.yml
extensions:
  ShellExtension:
    ...
    app:
      type: docker
      base_dir: /tmp
      docker_containername: app
      docker_command: /usr/local/bin/docker
      docker_options: -u user
      timeout: 20
```

Here, the location of the _docker executable_ is given and _options_, if needed. 

If we have this feature example
```
Scenario:
  Given I copy file "test.txt" to "/tmp" on "app"
  And I run "cat /tmp/test.txt" on "app"
```

then the resulting commands would be this:
```
/usr/local/bin/docker cp 'test.txt' app:'/tmp'
/usr/local/bin/docker exec -u user app /bin/bash -c 'cd /tmp ; cat /tmp/test.txt'
```

### Docker-Compose

By changing the parameter `docker_command`, instead of a docker container, a _[docker-compose](https://docs.docker.com/compose/) service_ can be used:

```yml
# behat.yml
extensions:
  ShellExtension:
    app:
      type: docker
      base_dir: /tmp
      docker_containername: app
      docker_command: /usr/local/bin/docker-compose
      docker-options: -T
      timeout: 20
```

It is important to specify `docker-options: -T` to »Disable pseudo-tty allocation«.

Here, we assume to have a docker-compose configuration like this:

```yml
# docker-compose.yml
version: '2'
services:
  app:
    image: php:7.1-fpm
```

Right now, **it is not possible to copy** files into a running docker-compose service (i.e. a command 
`docker-compose cp` is missing).

## Internal implementation

A command string `$command` is executed on a shell with `type: local` gets invoked in following way:
```php
$process = new Process($command, $serverConfig['base_dir']);
$process->setTimeout($serverConfig['timeout']);
$process->run();
```

A remote executed command string `$command` is executed this way:
```php
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

// e.g. ssh -i ~/.ssh/id_rsa user@shell.example.com 'cd /var/www ; app/console --env=prod do:mi:mi'

$process = new Process($command);
$process->setTimeout($serverConfig['timeout']);
$process->run();
```

When using docker, a command string `$command` is executed this way:
```php
if ($serverConfig['base_dir']) {
    $command = sprintf('cd %s ; %s', $serverConfig['base_dir'], $command);
}

$command = sprintf(
    '%s exec %s /bin/bash -c %s',
    $serverConfig['docker_command'],
    $serverConfig['docker_containername'],
    escapeshellarg($command)
);

// e.g. docker exec container /bin/bash -c 'cd /var/www ; app/console --env=prod do:mi:mi'

$process = new Process($command);
$process->setTimeout($serverConfig['timeout']);
$process->run();
```

## License

All contents of this package are licensed under the [MIT license](LICENSE).
