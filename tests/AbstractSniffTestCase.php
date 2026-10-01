<?php

declare(strict_types=1);

namespace FundrikStandard\Tests;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Runner;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

abstract class AbstractSniffTestCase extends TestCase {

	abstract protected function get_sniff_class(): string;

	protected function get_test_file_path(): string {

		$reflector = new ReflectionClass( $this );
		$test_file = $reflector->getFileName();
		$test_dir = dirname( $test_file );
		$test_basename = basename( $test_file, '.php' );

		$file_path = $test_dir . DIRECTORY_SEPARATOR . $test_basename . '.inc';

		if ( ! file_exists( $file_path ) ) {
			throw new RuntimeException( "Test data file not found: {$file_path}" );
		}

		return $file_path;
	}

	protected function create_phpcs_file( ?string $file_path = null ): LocalFile {

		$file_path ??= $this->get_test_file_path();

		if ( ! defined( 'PHP_CODESNIFFER_CBF' ) ) {
			define( 'PHP_CODESNIFFER_CBF', false );
		}

		$phpcs = new Runner();
		$phpcs->config = new Config( [ '-q' ] );
		$phpcs->init();

		$sniff_class = $this->get_sniff_class();
		$phpcs->ruleset->sniffs = [
			$sniff_class => new $sniff_class(),
		];
		$phpcs->ruleset->populateTokenListeners();

		$file = new LocalFile( $file_path, $phpcs->ruleset, $phpcs->config );
		$file->process();

		return $file;
	}

	protected function assert_file_has_errors_on_lines( array $expected_lines, ?string $file_path = null ): void {

		$phpcs_file = $this->create_phpcs_file( $file_path );
		$lines_with_errors = array_keys( $phpcs_file->getErrors() );

		$this->assertSame( $expected_lines, $lines_with_errors );
	}

	protected function assert_sniff_error_code( int $line, string $expected_code, ?string $file_path = null ): void {

		$phpcs_file = $this->create_phpcs_file( $file_path );
		$errors = $phpcs_file->getErrors();

		if ( ! array_key_exists( $line, $errors ) ) {

			$lines_with_errors = implode( ', ', array_keys( $errors ) );
			$message = "No error found on line {$line}. Lines with errors: {$lines_with_errors}";
			$this->fail( $message );
		}

		$found = false;
		$codes_found = [];

		foreach ( $errors[ $line ] as $col_errors ) {

			foreach ( $col_errors as $error ) {

				$codes_found[] = $error['source'];

				if ( $error['source'] !== $expected_code ) {
					continue;
				}

				$found = true;
			}
		}

		$codes_found = implode( ', ', $codes_found );

		$this->assertTrue(
			$found,
			"Expected error code '{$expected_code}' not found on line {$line}. Found codes: {$codes_found}",
		);
	}

	protected function assert_sniff_ok( int $line, ?string $file_path = null ): void {

		$phpcs_file = $this->create_phpcs_file( $file_path );
		$errors = $phpcs_file->getErrors();

		if ( array_key_exists( $line, $errors ) ) {

			$codes = [];

			foreach ( $errors[ $line ] as $col_errors ) {

				foreach ( $col_errors as $error ) {
					$codes[] = $error['source'];
				}
			}

			$this->fail(
				'Expected no error on line ' . $line . ', but got: ' . implode( ', ', $codes ),
			);
		}

		$this->assertTrue( true ); // Explicit OK.
	}

	protected function assert_all_fixable( ?string $file_path = null ): void {

		$phpcs_file = $this->create_phpcs_file( $file_path );
		$phpcs_file->disableCaching();
		$phpcs_file->fixer->fixFile();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$expected = file_get_contents( ( $file_path ?? $this->get_test_file_path() ) . '.fixed' );
		$actual = $phpcs_file->fixer->getContents();

		$this->assertSame( $expected, $actual, 'The file was not properly fixed.' );
	}
}
