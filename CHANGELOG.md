# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [3.1.0] - 2022-09-24
### Added
- New `onStart()` method that can be overridden to include code to only run
  once, before `execute()`.

## [3.0.0] - 2021-10-17
### Added
- Type declarations have been added to class properties.
- Exit value returned from implementation's `execute()` method is returned to
  the console. A non-zero value will stop the process iteration.
- Native input option for `DaemonCommand` to set the child log output filename.
  This should remove a common need to override `DaemonCommand::createChildOutput()`.
### Changed
- **BC break**: Reduce visibility of internal methods and properties. These
  members are not part of the public API. No impact to standard use of this
  package. If an implementation has a use case which needs to override these
  members, please submit a pull request explaining the change.
- **BC break**: New parameter added for `DaemonCommand::createChildOutput()`,
  which will be a BC break for implementations that override that method.
### Removed
- **BC break**: Removed support for PHP v7.3 as it is no longer
  [actively supported](https://php.net/supported-versions.php) by the PHP project.

## [2.0.1] - 2021-10-06
### Changed
- Annotate the `BackgroundCommand::shutdown()` method as final.
  This will be marked final in a future major version.

## [2.0.0] - 2021-07-10
### Added
- Add specific support for PHP v8
- Type declarations have been added to all method parameters and return types.
### Changed
- `DaemonCommand::background()` no longer tries to return the result of the
  action it calls, which was always void anyway.
- The return type for `DaemonCommand::createChildOutput()` has been set as
  `OutputInterface` rather than the previous docblock hint of
  `ConsoleOutputInterface`. This is because the default value returned does not
  fit the original requirement, and the method's usage in
  `DaemonCommand::recreateOutput()` allows for `OutputInterface`.
  The new type is a superset of the original so any extensions of
  `DaemonCommand` will be unaffected.
### Removed
- **BC break**: Removed support for PHP versions <= v7.2 as they are no longer
  [actively supported](https://php.net/supported-versions.php) by the PHP project.

## [1.0.2] - 2019-12-10
### Added
- Add a Change Log. Previous releases are shown as date only. See descriptions
  on [project releases page](https://github.com/phlib/beanstalk/releases).
### Changed
- Altered PHP version constraint to use SemVer. This will prevent implicit
  support for PHP v8 when it is released. No BC break as this does not change
  this package's support for current or past PHP versions.

## [1.0.1] - 2018-02-16

## [1.0.0] - 2017-06-13

## [0.7] - 2017-01-03

## [0.6] - 2016-08-02

## [0.5] - 2016-03-22

## [0.4] - 2016-01-21

## [0.3] - 2016-01-20

## [0.2] - 2016-01-08

## [0.1] - 2015-12-07
