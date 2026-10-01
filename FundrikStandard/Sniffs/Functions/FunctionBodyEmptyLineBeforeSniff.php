<?php

declare(strict_types=1);

namespace FundrikStandard\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures there is exactly one blank line before the body of functions or methods.
 *
 * This sniff checks that after the opening curly brace of a function/method,
 * there is a blank line before the first statement inside the body.
 * If missing, it reports an error and can automatically fix it by adding a blank line.
 *
 * @since 0.1.0
 */
final class FunctionBodyEmptyLineBeforeSniff implements Sniff {

	/**
	 * Returns the token types that this sniff is interested in.
	 *
	 * We listen for T_FUNCTION tokens (functions and methods).
	 *
	 * @since 0.1.0
	 *
	 * @return array<int>
	 */
	public function register(): array {

		return [ T_FUNCTION ];
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
	/**
	 * Processes the sniff when one of the registered tokens is found.
	 *
	 * Checks that there is one blank line between the opening brace and the first
	 * statement of the function body.
	 * Adds fixable error and inserts blank line if missing.
	 *
	 * @since 0.1.0
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int $stack_ptr The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ) {

		$tokens = $phpcs_file->getTokens();

		// Find the opening brace of the function body.
		$open_brace_ptr = $phpcs_file->findNext( T_OPEN_CURLY_BRACKET, $stack_ptr );

		if ( $open_brace_ptr === false ) {
			// No function body found.
			return;
		}

		$next_ptr = $open_brace_ptr + 1;

		// Skip whitespace, comments or doc comments after the opening brace.
		while (
			isset( $tokens[ $next_ptr ] )
			&& in_array(
				$tokens[ $next_ptr ]['code'],
				[ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ],
				true,
			)
		) {
			++$next_ptr;
		}

		if ( isset( $tokens[ $next_ptr ] ) && $tokens[ $next_ptr ]['code'] === T_CLOSE_CURLY_BRACKET ) {
			return;
		}

		$body_line = $tokens[ $next_ptr ]['line'] ?? null;
		$open_brace_line = $tokens[ $open_brace_ptr ]['line'];

		if ( $body_line === null ) {
			// No content after brace, e.g., broken function.
			return;
		}

		$lines_between = $body_line - $open_brace_line;

		// If already has 1 or more blank lines, do nothing.
		if ( $lines_between >= 2 ) {
			return;
		}

		// Report fixable error: missing blank line before function body.
		$fix = $phpcs_file->addFixableError(
			'There must be one blank line before the function/method body.',
			$open_brace_ptr,
			'MissingBlankLineBeforeBody',
		);

		if ( ! $fix ) {
			return;
		}

		// Perform automatic fix: add a newline after the opening brace.
		$phpcs_file->fixer->beginChangeset();
		$phpcs_file->fixer->addNewline( $open_brace_ptr );
		$phpcs_file->fixer->endChangeset();
	}
	// phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
}
