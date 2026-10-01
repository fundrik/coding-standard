<?php

declare(strict_types=1);

namespace FundrikStandard;

use PHP_CodeSniffer\Files\File;

/**
 * Trait for Sniffs providing utility methods
 * to resolve fully qualified class names and namespaces within
 * a PHP file being analyzed by PHP_CodeSniffer.
 *
 * @since 0.1.0
 */
trait FullyQualifiedNameTrait {

	/**
	 * Resolves the fully qualified class name based on use statements and namespace.
	 *
	 * @since 0.1.0
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int $class_name_pointer The position of the class name token.
	 *
	 * @return string Fully qualified class name.
	 */
	protected function resolve_fully_qualified_name( File $phpcs_file, int $class_name_pointer ): string {

		$tokens = $phpcs_file->getTokens();
		$class_name = $tokens[ $class_name_pointer ]['content'];

		$use_statements = $this->get_use_statements( $phpcs_file );

		if ( isset( $use_statements[ $class_name ] ) ) {
			return ltrim( $use_statements[ $class_name ], '\\' );
		}

		$namespace = $this->get_current_namespace( $phpcs_file );

		if ( $namespace !== '' ) {
			return $namespace . '\\' . $class_name;
		}

		return $class_name;
	}

	/**
	 * Parses use statements in the file to map alias to fully qualified class names.
	 *
	 * @since 0.1.0
	 *
	 * @param File $phpcs_file The file being scanned.
	 *
	 * @return array<string,string> Map of alias => fully qualified class name.
	 */
	protected function get_use_statements( File $phpcs_file ): array {

		$tokens = $phpcs_file->getTokens();
		$uses = [];
		$token_count = count( $tokens );

		for ( $i = 0; $i < $token_count; $i++ ) {

			if ( $tokens[ $i ]['code'] !== T_USE ) {
				continue;
			}

			$end = $phpcs_file->findEndOfStatement( $i );
			$fqcn = '';
			$alias = '';
			$as_found = false;

			for ( $j = $i + 1; $j < $end; $j++ ) {

				if ( $tokens[ $j ]['code'] === T_STRING || $tokens[ $j ]['code'] === T_NS_SEPARATOR ) {

					if ( ! $as_found ) {
						$fqcn .= $tokens[ $j ]['content'];
					} else {
						$alias .= $tokens[ $j ]['content'];
					}
				}

				if ( $tokens[ $j ]['code'] !== T_AS ) {
					continue;
				}

				$as_found = true;
			}

			if ( $fqcn === '' ) {
				continue;
			}

			$key = $alias !== '' ? $alias : basename( str_replace( '\\', '/', $fqcn ) );
			$uses[ $key ] = $fqcn;
		}

		return $uses;
	}

	/**
	 * Retrieves the current namespace declared in the file.
	 *
	 * @since 0.1.0
	 *
	 * @param File $phpcs_file The file being scanned.
	 *
	 * @return string The current namespace (without leading backslash), or empty string if none.
	 */
	protected function get_current_namespace( File $phpcs_file ): string {

		$tokens = $phpcs_file->getTokens();

		foreach ( $tokens as $ptr => $token ) {

			if ( $token['code'] === T_NAMESPACE ) {
				$namespace_parts = [];
				$next = $phpcs_file->findNext( [ T_STRING, T_NS_SEPARATOR ], $ptr + 1, null, false, null, true );

				while ( $next !== false && in_array( $tokens[ $next ]['code'], [ T_STRING, T_NS_SEPARATOR ], true ) ) {
					$namespace_parts[] = $tokens[ $next ]['content'];
					++$next;
				}

				return implode( '', $namespace_parts );
			}
		}

		return '';
	}
}
