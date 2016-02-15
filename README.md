# BehatShellExtension [![Build Status](https://secure.travis-ci.org/Postcon/BehatShellExtension.png)](http://travis-ci.org/Postcon/BehatShellExtension)

Behat extension for executing shell commands within features. The shell commands can be run on remote servers using ssh or locally without network.

## Installation

Using [composer](https://getcomposer.org/download/):

```
composer require postcon/behat-shell-extension dev-master
```

## Usage

```gherkin
# test.feature

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

## Configuration

To use the BehatShellExtension, it needs to be configured in the `behat.yml` (or `behat.yml.dist`).
Each server or shell, you want invoke commands on, must be specified.
Following example gives configuration for the local shell (`default`) and a remote server (named `app`).

```yml
# behat.yml
extensions:
  ShellExtension:
    default:
      type: local
      base_dir: /tmp
      timeout: 10
    app:
      type: remote
      base_dir: /var/www/
      ssh_command: /usr/bin/ssh
      ssh_options: -i ~/.ssh/id_rsa shell.example.com
      timeout: 20
```

The type can be `local` or `remote`. The current work directory for executing the command is defined using `base_dir`.
The remote server address, user credentials and other relevant options for ssh are specified using `ssh_options`.

Additionally the location of the ssh executable can be defined using `ssh_command`.
Both, local and remote servers or shells, can have a `timeout` option.
Commands not finishing within `timeout` seconds get stopped.

### Defaults

Without defining any server, `default` is defined automatically by the extension:
```yml
...
    default:
      type: local
      base_dir: ~
      timeout: ~
```

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
    '%s %s %s',
    $serverConfig['ssh_command'],
    $serverConfig['ssh_options'],
    escapeshellarg($command)
);

// e.g. ssh -i ~/.ssh/id_rsa user@shell.example.com 'cd /var/www ; app/console --env=prod do:mi:mi'

$process = new Process($command);
$process->setTimeout($serverConfig['timeout']);
$process->run();
```

## License

All contents of this package are licensed under the [MIT license](LICENSE).
