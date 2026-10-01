<?php

declare(strict_types=1);

namespace FundrikStandard\Sniffs\Classes;

use FundrikStandard\FullyQualifiedNameTrait;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
/**
 * Reports an error when a class is declared `abstract` but not `readonly`,
 * unless it extends a class from an excluded list (e.g., Throwable or PHPUnit constraints)
 * or the class itself is in an excluded list.
 *
 * @since 0.1.0
 */
final class AbstractClassMustBeReadonlySniff implements Sniff {

	use FullyQualifiedNameTrait;

	// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	/**
	 * List of base classes that are excluded from readonly requirement.
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
	 * If the class is declared as `abstract`, but not `readonly`, an error is reported.
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

		// Get class name token and resolved FQCN of the current class.
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

		// Skip if class is in excludedClasses.
		if ( in_array( $normalized_class_name, $normalized_excluded_classes, true ) ) {
			return;
		}

		// Check for parent class.
		// phpcs:ignore SlevomatCodingStandard.Functions.RequireSingleLineCall.RequiredSingleLineCall
		$extends_ptr = $phpcs_file->findNext(
			T_EXTENDS,
			$stack_ptr,
			$tokens[ $stack_ptr ]['scope_opener'] ?? null,
		);

		if ( $extends_ptr !== false ) {
			$parent_class_token = $phpcs_file->findNext( T_STRING, $extends_ptr + 1 );
			$parent_class_name = $parent_class_token !== false
				? $tokens[ $parent_class_token ]['content']
				: null;

			if ( $parent_class_name !== null ) {
				$full_parent_name = $this->resolve_fully_qualified_name( $phpcs_file, $parent_class_token );

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				foreach ( $this->excludedParentClasses as $excluded_class ) {

					if (
						$full_parent_name === ltrim( $excluded_class, '\\' )
						|| is_subclass_of( $full_parent_name, ltrim( $excluded_class, '\\' ) )
					) {
						return;
					}
				}
			}
		}

		$is_abstract = false;
		$is_readonly = false;

		// Walk backward to find modifiers.
		for ( $i = $stack_ptr - 1; $i >= 0; --$i ) {
			$code = $tokens[ $i ]['code'];

			if ( in_array( $code, [ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ], true ) ) {
				continue;
			}

			if ( $code === T_ABSTRACT ) {
				$is_abstract = true;
			} elseif ( $code === T_READONLY ) {
				$is_readonly = true;
			} else {
				break;
			}
		}

		if ( ! $is_abstract || $is_readonly ) {
			return;
		}

		// phpcs:ignore SlevomatCodingStandard.Functions.RequireSingleLineCall.RequiredSingleLineCall
		$phpcs_file->addError(
			'Abstract class must also be declared readonly.',
			$stack_ptr,
			'AbstractClassNotReadonly',
		);
	}
}
// phpcs:enable
