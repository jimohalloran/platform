<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Entity;
use Oro\Bundle\LocaleBundle\Validator\Constraints;
use Oro\Bundle\LocaleBundle\Validator\Constraints\LocalizationValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LocalizationValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Constraints\Localization */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var LocalizationValidator */
    protected $validator;

    protected function setUp(): void
    {
        $this->constraint = new Constraints\Localization();
        $this->context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new LocalizationValidator();
        $this->validator->initialize($this->context);
    }

    protected function tearDown(): void
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'oro_locale.localization_validator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testValidateWithoutCircularReference()
    {
        $this->context->expects($this->never())->method('buildViolation');
        $localization1 = $this->createLocalization('loca1', 1);
        $localization2 = $this->createLocalization('loca2', 2);
        $localization1->setParentLocalization($localization2);

        $this->validator->validate($localization1, $this->constraint);
    }

    public function testValidateWithCircularReference()
    {
        $this->expectViolation();

        $localization1 = $this->createLocalization('loca1', 1);
        $localization2 = $this->createLocalization('loca2', 2);
        $localization3 = $this->createLocalization('loca3', 3);

        $localization1->setParentLocalization($localization2);
        $localization1->addChildLocalization($localization3);

        $localization2->setParentLocalization($localization3);
        $localization2->addChildLocalization($localization1);

        $localization3->setParentLocalization($localization3);
        $localization3->addChildLocalization($localization2);

        $this->validator->validate($localization3, $this->constraint);
    }

    public function testValidateSelfParent()
    {
        $this->expectViolation();

        $localization1 = $this->createLocalization('loca1', 1);
        $localization1->setParentLocalization($localization1);

        $this->validator->validate($localization1, $this->constraint);
    }

    public function testUnexpectedValue()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\LocaleBundle\Entity\Localization", "string" given'
        );

        $this->validator->validate('test', $this->constraint);
    }

    public function testUnexpectedClass()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\LocaleBundle\Entity\Localization", "stdClass" given'
        );
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    private function expectViolation()
    {
        $violationBuilder = $this
            ->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('parentLocalization')
            ->willReturnSelf();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->messageCircularReference)
            ->willReturn($violationBuilder);
    }

    /**
     * @param string $name
     * @param int $id
     * @return Entity\Localization
     */
    private function createLocalization($name, $id)
    {
        $localization = new Entity\Localization();
        $localization->setName($name);
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }
}
