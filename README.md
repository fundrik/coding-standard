# Fundrik Coding Standard

*A PHP_CodeSniffer standard for Fundrik PHP projects.*

[![Checks](https://github.com/fundrik/coding-standard/actions/workflows/checks.yml/badge.svg?branch=main)](https://github.com/fundrik/coding-standard/actions/workflows/checks.yml?query=branch%3Amain)
![License](https://img.shields.io/github/license/fundrik/coding-standard)
![Packagist](https://img.shields.io/packagist/v/fundrik/coding-standard)
![PHP Version](https://img.shields.io/badge/PHP-8.3+-blue)
![PHPUnit](https://img.shields.io/badge/PHPUnit-100%25%20coverage-brightgreen)

Fundrik Coding Standard combines WordPress Coding Standards, PHPCompatibilityWP, and selected Slevomat Coding Standard rules with Fundrik-specific sniffs. It is intended for Fundrik projects and other modern PHP 8.3+ WordPress codebases that want the same conventions.

## Requirements

- PHP 8.3 or later.
- Composer 2.

## Installation

Install the standard as a development dependency:

```bash
composer require --dev fundrik/coding-standard
```

The package uses `dealerdirect/phpcodesniffer-composer-installer`, so Composer registers `FundrikStandard` with PHP_CodeSniffer automatically. Composer may ask you to allow that plugin during installation.

## Usage

Create a `phpcs.xml` in your project:

```xml
<?xml version="1.0"?>
<ruleset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" name="Project Name" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/squizlabs/PHP_CodeSniffer/master/phpcs.xsd">
	<exclude-pattern>vendor/</exclude-pattern>

	<arg value="sp"/>
	<arg name="basepath" value="."/>
	<arg name="colors"/>
	<arg name="extensions" value="php"/>
	<!-- Adjust to the number of CPU cores available. -->
	<arg name="parallel" value="8"/>

	<config name="testVersion" value="8.3-"/>

	<rule ref="FundrikStandard"/>
</ruleset>
```

Run PHP_CodeSniffer through the project's installed binary:

```bash
vendor/bin/phpcs .
vendor/bin/phpcbf .
```

`phpcs` reports violations. `phpcbf` automatically fixes violations supported by the underlying sniff; rules that require design decisions remain manual fixes.

## Included rules

The aggregate `FundrikStandard` ruleset enables:

- selected Slevomat rules for modern PHP structure, type declarations, control flow, namespaces, and formatting;
- PHPCompatibilityWP checks for the configured target PHP version;
- WordPress Coding Standards, with targeted exclusions documented in the ruleset;
- Fundrik-specific sniffs:
  - `FundrikStandard.Classes.AbstractClassMustBeReadonly` requires abstract classes to be declared `readonly`;
  - `FundrikStandard.Classes.FinalClassMustBeReadonly` requires final classes to be declared `readonly`;
  - `FundrikStandard.Classes.RequireAbstractOrFinal` requires classes to be declared `abstract` or `final`;
  - `FundrikStandard.Commenting.SinceTagRequired` requires `@since` in docblocks for classes, interfaces, traits, enums, functions, and methods;
  - `FundrikStandard.Functions.FunctionBodyEmptyLineBefore` requires an empty line between a function body's opening brace and its first statement.

The complete, authoritative rule list is in [`FundrikStandard/ruleset.xml`](FundrikStandard/ruleset.xml).

## Configuring class exclusions

`AbstractClassMustBeReadonly`, `FinalClassMustBeReadonly`, and `RequireAbstractOrFinal` support `excludedClasses` and `excludedParentClasses`. Use these properties for framework types or other classes that cannot satisfy the default rule:

```xml
<rule ref="FundrikStandard.Classes.FinalClassMustBeReadonly">
	<properties>
		<property name="excludedClasses" type="array">
			<element value="Acme\Legacy\MutableService"/>
		</property>
		<property name="excludedParentClasses" type="array">
			<element value="RuntimeException"/>
		</property>
	</properties>
</rule>
```

The `excludedParentClasses` setting also applies to implemented interfaces where supported by the sniff.

## Development

The Composer scripts are the source of truth for project checks:

```bash
composer run lint
composer run rector -- --dry-run
composer run test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

## License

Fundrik Coding Standard is released under the [MIT License](LICENSE).
