<?php

declare(strict_types=1);

namespace FundrikStandard\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
/**
 * Ensures every class, interface, trait, enum, function, and method has a `@since` tag in its docblock.
 *
 * This promotes consistent documentation across all structural elements and makes it easier
 * to track introduction versions for public API.
 *
 * @since 0.1.0
 */
final class SinceTagRequiredSniff implements Sniff {

	/**
	 * Returns the list of token types this sniff wants to check.
	 *
	 * Includes class-like and function-like constructs.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int>
	 */
	public function register(): array {

		return [ T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION ];
	}

	/**
	 * Processes the token and checks for the presence of a `@since` tag in the associated docblock.
	 *
	 * If a docblock is missing entirely or exists without a `@since` tag, an error is reported.
	 *
	 * @since 0.1.0
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int $stack_ptr The position of the current token in the token stack.
	 */
	public function process( File $phpcs_file, $stack_ptr ): void {

		$tokens = $phpcs_file->getTokens();

		// Find doc comment block before the declaration.
		$doc_ptr = $phpcs_file->findPrevious( T_DOC_COMMENT_CLOSE_TAG, $stack_ptr );

		// Ensure the docblock is immediately before the element.
		if ( $doc_ptr === false || $tokens[ $doc_ptr ]['line'] < $tokens[ $stack_ptr ]['line'] - 3 ) {
			$phpcs_file->addError( 'Missing docblock with @since tag.', $stack_ptr, 'MissingSince' );

			return;
		}

		$start = $tokens[ $doc_ptr ]['comment_opener'];
		$has_since = false;

		for ( $i = $start; $i <= $doc_ptr; $i++ ) {

			if ( $tokens[ $i ]['code'] === T_DOC_COMMENT_TAG && strtolower( $tokens[ $i ]['content'] ) === '@since' ) {
				$has_since = true;
				break;
			}
		}

		if ( $has_since ) {
			return;
		}

		$phpcs_file->addError( 'Missing @since tag in docblock.', $stack_ptr, 'MissingSince' );
	}
}
// phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint