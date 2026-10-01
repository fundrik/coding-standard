<?php

declare(strict_types=1);

namespace FundrikStandard\Tests\Sniffs\Commenting;

use FundrikStandard\Sniffs\Classes\AbstractClassMustBeReadonlySniff;
use FundrikStandard\Sniffs\Classes\FinalClassMustBeReadonlySniff;
use FundrikStandard\Sniffs\Classes\RequireAbstractOrFinalSniff;
use FundrikStandard\Sniffs\Commenting\SinceTagRequiredSniff;
use FundrikStandard\Sniffs\Functions\FunctionBodyEmptyLineBeforeSniff;
use FundrikStandard\Tests\AbstractSniffTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( SinceTagRequiredSniff::class )]
#[UsesClass( AbstractClassMustBeReadonlySniff::class )]
#[UsesClass( FinalClassMustBeReadonlySniff::class )]
#[UsesClass( RequireAbstractOrFinalSniff::class )]
#[UsesClass( FunctionBodyEmptyLineBeforeSniff::class )]
final class SinceTagRequiredSniffTest extends AbstractSniffTestCase {

	protected function get_sniff_class(): string {

		return SinceTagRequiredSniff::class;
	}

	#[Test]
	public function detects_missing_since_on_class(): void {

		$this->assert_sniff_error_code(
			4,
			'FundrikStandard.Commenting.SinceTagRequired.MissingSince'
		);
	}

	#[Test]
	public function detects_missing_since_on_function(): void {

		$this->assert_sniff_error_code(
			11,
			'FundrikStandard.Commenting.SinceTagRequired.MissingSince'
		);
	}

	#[Test]
	public function detects_missing_since_on_method(): void {

		$this->assert_sniff_error_code(
			15,
			'FundrikStandard.Commenting.SinceTagRequired.MissingSince'
		);
	}

	#[Test]
	public function does_not_report_class_with_since(): void {

		$this->assert_sniff_ok( 26 );
	}

	#[Test]
	public function does_not_report_function_with_since(): void {

		$this->assert_sniff_ok( 33 );
	}

	#[Test]
	public function does_not_report_method_with_since(): void {

		$this->assert_sniff_ok( 39 );
	}
}
