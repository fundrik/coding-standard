<?php

declare(strict_types=1);

namespace FundrikStandard\Tests\Sniffs\Functions;

use FundrikStandard\Sniffs\Classes\AbstractClassMustBeReadonlySniff;
use FundrikStandard\Sniffs\Classes\FinalClassMustBeReadonlySniff;
use FundrikStandard\Sniffs\Classes\RequireAbstractOrFinalSniff;
use FundrikStandard\Sniffs\Commenting\SinceTagRequiredSniff;
use FundrikStandard\Sniffs\Functions\FunctionBodyEmptyLineBeforeSniff;
use FundrikStandard\Tests\AbstractSniffTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( FunctionBodyEmptyLineBeforeSniff::class )]
#[UsesClass( AbstractClassMustBeReadonlySniff::class )]
#[UsesClass( FinalClassMustBeReadonlySniff::class )]
#[UsesClass( RequireAbstractOrFinalSniff::class )]
#[UsesClass( SinceTagRequiredSniff::class )]
final class FunctionBodyEmptyLineBeforeSniffTest extends AbstractSniffTestCase {

	protected function get_sniff_class(): string {

		return FunctionBodyEmptyLineBeforeSniff::class;
	}

	#[Test]
	public function detects_missing_blank_line_before_body(): void {

		$this->assert_sniff_error_code(
			3,
			'FundrikStandard.Functions.FunctionBodyEmptyLineBefore.MissingBlankLineBeforeBody',
		);
	}

	#[Test]
	public function does_not_report_function_with_blank_line(): void {

		$this->assert_sniff_ok( 8 );
	}

	#[Test]
	public function all_errors_are_fixable(): void {

		$this->assert_all_fixable();
	}
}
