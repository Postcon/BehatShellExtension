# BehatShellExtension

```yml
# behat.yml
extensions:
  ShellExtension:
    appserver:
      type: local
      base_dir: /tmp
    appserver:
      type: remote
      base_dir: /tmp
      ssh_options: -i .ssh/id_rsa shell.example.com
    cronserver:
      type: remote
      base_dir: /tmp
      ssh_options: -i .ssh/id_rsa shell.example.com
```

```gherkin
# test.feature
  Scenario: invoking command
    When I run "apps/kx/console do:mi:mi" on "cronserver"

  Scenario: invoking command
    When I run "apps/kx/console do:mi:mi" on "default"

  Scenario: invoking command
    When I run "apps/kx/console do:mi:mi"
```
