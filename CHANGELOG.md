# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-02-19

### Added
- Conditional `required` support: `JsonSchema::jsonSerialize()` now evaluates the
  `when` callable on `RequiredValidator` against the current model instance.
  Only attributes for which `when` returns `true` (or that have no `when`) are
  included in the JSON Schema `required` array.

### Changed
- Minimum PHP version raised from 7.4 to 8.4.

### Migration
If you rely on conditionally-required attributes always appearing in `required`,
populate the model with the appropriate state before constructing `JsonSchema`,
or remove the `when` callback from the rule.

[Unreleased]: https://github.com/Horat1us/yii2-model-schema/compare/2.0.0...HEAD
[2.0.0]: https://github.com/Horat1us/yii2-model-schema/releases/tag/2.0.0
