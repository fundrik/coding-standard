<?php

declare(strict_types=1);

namespace FundrikStandard\Sniffs\Classes;

use FundrikStandard\FullyQualifiedNameTrait;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Sniffs\Classes\RequireAbstractOrFinalSniff as BaseSniff;

// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
/**
 * Reports an error when a class is not declared `abstract` or `final`,
 * unless it extends or implements a class/interface from an excluded list,
 * or the class itself is in an excluded list.
 *
 * @since 0.1.0
 *
 * @todo Add test for excludedClasses and excludedParentClasses arguments.
 */
final class RequireAbstractOrFinalSniff extends BaseSniff implements Sniff {

	use FullyQualifiedNameTrait;

	// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	/**
	 * List of specific classes that are excluded from abstract/final requirement.
	 *
	 * This should be set via ruleset.xml as a <property>.
	 *
	 * @var array<int, string>
	 */
	public array $excludedClasses = [];

	/**
	 * List of base classes or interfaces that are excluded from abstract/final requirement.
	 *
	 * This should be set via ruleset.xml as a <property>.
	 *
	 * @var array<int, string>
	 */
	public array $excludedParentClasses = [];
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
	 * Skips classes that are in the exclusion list or extend/implement excluded base types.
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

		$normalized_excluded_parents = array_map(
			static fn ( $c ) => ltrim( $c, '\\' ),
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$this->excludedParentClasses,
		);

		$scope_end = $tokens[ $stack_ptr ]['scope_opener'] ?? null;

		// === Check extends ===
		$extends_ptr = $phpcs_file->findNext( T_EXTENDS, $stack_ptr, $scope_end );

		if ( $extends_ptr !== false ) {
			$parent_class_token = $phpcs_file->findNext( T_STRING, $extends_ptr + 1 );

			if ( $parent_class_token !== false ) {
				$parent_name = $this->resolve_fully_qualified_name( $phpcs_file, $parent_class_token );

				foreach ( $normalized_excluded_parents as $excluded ) {

					if ( $parent_name === $excluded || is_subclass_of( $parent_name, $excluded ) ) {
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

				foreach ( $normalized_excluded_parents as $excluded ) {

					if ( $interface_name === $excluded || is_subclass_of( $interface_name, $excluded ) ) {
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

		parent::process( $phpcs_file, $stack_ptr );
	}
	// phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
}
