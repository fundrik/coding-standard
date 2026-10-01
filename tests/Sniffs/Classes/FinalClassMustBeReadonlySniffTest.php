<?php

declare(strict_types=1);

// @todo Add test for excludedParentClasses argument.

namespace FundrikStandard\Tests\Sniffs\Classes;

use FundrikStandard\Sniffs\Classes\AbstractClassMustBeReadonlySniff;
use FundrikStandard\Sniffs\Classes\FinalClassMustBeReadonlySniff;
use FundrikStandard\Sniffs\Classes\RequireAbstractOrFinalSniff;
use FundrikStandard\Sniffs\Commenting\SinceTagRequiredSniff;
use FundrikStandard\Sniffs\Functions\FunctionBodyEmptyLineBeforeSniff;
use FundrikStandard\Tests\AbstractSniffTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( FinalClassMustBeReadonlySniff::class )]
#[UsesClass( AbstractClassMustBeReadonlySniff::class )]
#[UsesClass( RequireAbstractOrFinalSniff::class )]
#[UsesClass( SinceTagRequiredSniff::class )]
#[UsesClass( FunctionBodyEmptyLineBeforeSniff::class )]
final class FinalClassMustBeReadonlySniffTest extends AbstractSniffTestCase {

	protected function get_sniff_class(): string {

		return FinalClassMustBeReadonlySniff::class;
	}

	#[Test]
	public function detects_final_class_without_readonly(): void {

		$this->assert_sniff_error_code( 7, 'FundrikStandard.Classes.FinalClassMustBeReadonly.FinalClassNotReadonly' );
	}

	#[Test]
	public function does_not_report_final_readonly_class(): void {

		$this->assert_sniff_ok( 13 );
	}
}
