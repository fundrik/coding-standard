<?php

declare(strict_types=1);

namespace FundrikStandard\Sniffs\Classes;

use FundrikStandard\FullyQualifiedNameTrait;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
/**
 * Reports an error when a class is declared `final` but not `readonly`,
 * unless it extends a class from an excluded list (e.g., Throwable or PHPUnit constraints)
 * or the class itself is in an excluded list.
 *
 * @since 0.1.0
 */
final class FinalClassMustBeReadonlySniff implements Sniff {

	use FullyQualifiedNameTrait;

	// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	/**
	 * List of base classes and interfaces that are excluded from readonly requirement.
	 *
	 * This should be set via ruleset.xml as a <property>.
	 *
	 * @var array<int, string>
	 */
	public array $excludedParentClasses = [];

	/**
	 * List of specific classes that are excluded from readonly requirement.
	 *
	 * This should be set via ruleset.xml as a <property>.
	 *
	 * @var array<int, string>
	 */
	public array $excludedClasses = [];
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Returns the token types this sniff is interested in.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int>
	 */
	public function register(): array {

		return [ T_CLASS ];
	}

	/**
	 * Processes each class declaration.
	 *
	 * If the class is declared as `final`, but not `readonly`, an error is reported.
	 * Skips classes that extend any from `$excludedParentClasses` or
	 * are themselves in `$excludedClasses`.
	 *
	 * @since 0.1.0
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int $stack_ptr The position of the current token in the stack.
	 */
	public function process( File $phpcs_file, $stack_ptr ): void {

		$tokens = $phpcs_file->getTokens();

		$class_name_token = $phpcs_file->findNext( T_STRING, $stack_ptr );

		if ( $class_name_token === false ) {
			return;
		}

		$full_class_name = $this->resolve_fully_qualified_name( $phpcs_file, $class_name_token );
		$normalized_class_name = ltrim( $full_class_name, '\\' );

		$normalized_excluded_classes = array_map(
			static fn ( $c ) => ltrim( $c, '\\' ),
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$this->excludedClasses,
		);

		if ( in_array( $normalized_class_name, $normalized_excluded_classes, true ) ) {
			return;
		}

		$excluded_normalized_parents = array_map(
			static fn ( $c ) => ltrim( $c, '\\' ),
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$this->excludedParentClasses,
		);

		// === Check extends ===
		$scope_end = $tokens[ $stack_ptr ]['scope_opener'] ?? null;
		$extends_ptr = $phpcs_file->findNext( T_EXTENDS, $stack_ptr, $scope_end );

		if ( $extends_ptr !== false ) {
			$parent_class_token = $phpcs_file->findNext( T_STRING, $extends_ptr + 1 );

			if ( $parent_class_token !== false ) {
				$full_parent_name = $this->resolve_fully_qualified_name( $phpcs_file, $parent_class_token );

				foreach ( $excluded_normalized_parents as $excluded_class ) {

					if (
						$full_parent_name === $excluded_class
						|| is_subclass_of( $full_parent_name, $excluded_class )
					) {
						return;
					}
				}
			}
		}

		// === Check interfaces ===
		$implements_ptr = $phpcs_file->findNext( T_IMPLEMENTS, $stack_ptr, $scope_end );

		if ( $implements_ptr !== false ) {
			$interface_ptr = $implements_ptr;

			while ( true ) {
				$interface_ptr = $phpcs_file->findNext( T_STRING, $interface_ptr + 1, $scope_end );

				if ( $interface_ptr === false ) {
					break;
				}

				$interface_name = $this->resolve_fully_qualified_name( $phpcs_file, $interface_ptr );

				foreach ( $excluded_normalized_parents as $excluded_interface ) {

					if (
						$interface_name === $excluded_interface
						|| is_subclass_of( $interface_name, $excluded_interface )
					) {
						return;
					}
				}

				$next_token = $phpcs_file->findNext( [ T_COMMA, T_STRING ], $interface_ptr + 1, $scope_end );

				if ( $next_token === false || $tokens[ $next_token ]['code'] !== T_COMMA ) {
					break;
				}

				$interface_ptr = $next_token;
			}
		}

		$is_final = false;
		$is_readonly = false;

		for ( $i = $stack_ptr - 1; $i >= 0; --$i ) {
			$code = $tokens[ $i ]['code'];

			if ( in_array( $code, [ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ], true ) ) {
				continue;
			}

			if ( $code === T_FINAL ) {
				$is_final = true;
			} elseif ( $code === T_READONLY ) {
				$is_readonly = true;
			} else {
				break;
			}
		}

		if ( ! $is_final || $is_readonly ) {
			return;
		}

		$phpcs_file->addError( 'Final class must also be declared readonly.', $stack_ptr, 'FinalClassNotReadonly' );
	}
}
// phpcs:enable
