<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class EnumValueTypeTest extends TypeTestCase
{
    /** @var EnumValueType */
    protected $type;

    /**
     * @var ConstraintValidatorInterface[]
     */
    protected $validators;

    protected function setUp(): void
    {
        $this->type = new EnumValueType($this->getConfigProvider());
        parent::setUp();
    }

    protected function getExtensions()
    {
        $validator = new RecursiveValidator(
            new ExecutionContextFactory(new IdentityTranslator()),
            new LazyLoadingMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory()
        );

        return [
            new PreloadedExtension(
                [
                    EnumValueType::class => $this->type
                ],
                [
                    FormType::class => [
                        new FormTypeValidatorExtension($validator)
                    ]
                ]
            ),
            new ValidatorExtension($this->getValidator()),
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit(array $inputData, array $expectedData)
    {
        $form = $this->factory->create(EnumValueType::class);
        $form->submit($inputData['form']);

        $this->assertEquals($expectedData['valid'], $form->isValid());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitValidDataForNewEnumValue()
    {
        $formData = [
            'label'      => 'Value 1',
            'is_default' => true,
            'priority'   => 1
        ];

        $form = $this->factory->create(EnumValueType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(
            [
                'id'         => null,
                'label'      => 'Value 1',
                'is_default' => true,
                'priority'   => '1'
            ],
            $form->getData()
        );

        $nameConstraints = $form->get('label')->getConfig()->getOption('constraints');
        $this->assertCount(2, $nameConstraints);

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\NotBlank',
            $nameConstraints[0]
        );

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\Length',
            $nameConstraints[1]
        );
        $this->assertEquals(255, $nameConstraints[1]->max);
    }

    public function testSubmitValidDataForExistingEnumValue()
    {
        $formData = [
            'id'         => 'val1',
            'label'      => 'Value 1',
            'is_default' => true,
            'priority'   => 1
        ];

        $form = $this->factory->create(EnumValueType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(
            [
                'id'         => 'val1',
                'label'      => 'Value 1',
                'is_default' => true,
                'priority'   => '1'
            ],
            $form->getData()
        );

        $nameConstraints = $form->get('label')->getConfig()->getOption('constraints');
        $this->assertCount(2, $nameConstraints);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider allowDeleteProvider
     */
    public function testAllowDelete(array $inputData, array $expectedData)
    {
        $configProvider = $this->getConfigProvider(
            $inputData['has_config'],
            $inputData['enum_code'],
            $inputData['immutable']
        );

        $type = new EnumValueType($configProvider);
        $form = $this->factory->create(EnumValueType::class);
        $form->setParent($this->getConfiguredForm());

        $form->submit($inputData['form']);
        $view = new FormView();
        $type->buildView($view, $form, []);

        $this->assertArrayHasKey('allow_delete', $view->vars);
        $this->assertSame($expectedData['allow_delete'], $view->vars['allow_delete']);
    }

    /**
     * @return array
     */
    public function allowDeleteProvider()
    {
        return [
            'no config' => [
                'input' => [
                    'form' => [
                        'id' => 'open',
                        'label' => 'Label',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                    'has_config' => false,
                    'enum_code' => 'task_status',
                    'immutable' => ['open', 'close']
                ],
                'expected' => [
                    'allow_delete' => true,
                ],
            ],
            'no enum code' => [
                'input' => [
                    'form' => [
                        'id' => 'open',
                        'label' => 'Label',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                    'has_config' => true,
                    'enum_code' => '',
                    'immutable' => ['open', 'close']
                ],
                'expected' => [
                    'allow_delete' => true,
                ],
            ],
            'immutable open' => [
                'input' => [
                    'form' => [
                        'id' => 'open',
                        'label' => 'Label',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                    'has_config' => true,
                    'enum_code' => 'task_status',
                    'immutable' => ['open', 'close']
                ],
                'expected' => [
                    'allow_delete' => false,
                ],
            ],
            'immutable close' => [
                'input' => [
                    'form' => [
                        'id' => 'close',
                        'label' => 'Label',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                    'has_config' => true,
                    'enum_code' => 'task_status',
                    'immutable' => ['open', 'close']
                ],
                'expected' => [
                    'allow_delete' => false,
                ],
            ],
            'allow delete' => [
                'input' => [
                    'form' => [
                        'id' => 'deletable',
                        'label' => 'Label',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                    'has_config' => true,
                    'enum_code' => 'task_status',
                    'immutable' => ['open', 'close']
                ],
                'expected' => [
                    'allow_delete' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'valid data' => [
                'input' => [
                    'form' => [
                        'label' => 'Label1',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                ],
                'expected' => [
                    'valid' => true,
                ],
            ],
            'empty label' => [
                'input' => [
                    'form' => [
                        'is_default' => true,
                        'priority' => 2,
                    ],
                ],
                'expected' => [
                    'valid' => false,
                ],
            ],
            'long label' => [
                'input' => [
                    'form' => [
                        'label' => str_repeat('l', 256),
                        'is_default' => true,
                        'priority' => 3,
                    ],
                ],
                'expected' => [
                    'valid' => false,
                ],
            ],
            'incorrect label and empty id' => [
                'input' => [
                    'form' => [
                        'label' => '!@#$',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                ],
                'expected' => [
                    'valid' => false,
                ],
            ],
            'correct label and not empty id' => [
                'input' => [
                    'form' => [
                        'id' => 11,
                        'label' => '!@#$',
                        'is_default' => true,
                        'priority' => 1,
                    ],
                ],
                'expected' => [
                    'valid' => true,
                ],
            ],
        ];
    }

    /**
     * @param array                                    $data
     * @param \PHPUnit\Framework\MockObject\MockObject $form
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFormEvent($data, $form = null)
    {
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        return $event;
    }

    /**
     * @return RecursiveValidator
     */
    protected function getValidator()
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->any())
            ->method('loadClassMetadata')
            ->willReturnCallback(function (ClassMetadata $meta) {
                $this->loadMetadata($meta);
            });

        $validator = new RecursiveValidator(
            new ExecutionContextFactory(new IdentityTranslator()),
            new LazyLoadingMetadataFactory($loader),
            $this->getConstraintValidatorFactory()
        );

        return $validator;
    }

    /**
     * @param ClassMetadata $meta
     */
    protected function loadMetadata(ClassMetadata $meta)
    {
        if (false !== ($configFile = $this->getConfigFile($meta->name))) {
            $loader = new YamlFileLoader($configFile);
            $loader->loadClassMetadata($meta);
        }
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        $factory = $this->createMock(ConstraintValidatorFactoryInterface::class);
        $factory->expects($this->any())
            ->method('getInstance')
            ->willReturnCallback(function (Constraint $constraint) {
                $className = $constraint->validatedBy();

                if (!isset($this->validators[$className])
                    || $className === 'Symfony\Component\Validator\Constraints\CollectionValidator'
                ) {
                    $this->validators[$className] = new $className();
                }

                return $this->validators[$className];
            });

        return $factory;
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getConfigFile($class)
    {
        if (false !== ($path = $this->getBundleRootPath($class))) {
            $path .= '/Resources/config/validation.yml';

            if (!is_readable($path)) {
                $path = false;
            }
        }

        return $path;
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getBundleRootPath($class)
    {
        $rclass = new \ReflectionClass($class);

        $path = false;

        if (false !== ($pos = strrpos($rclass->getFileName(), 'Bundle'))) {
            $path = substr($rclass->getFileName(), 0, $pos) . 'Bundle';
        }

        return $path;
    }

    /**
     * @param boolean $hasConfig
     * @param string $enumCode
     * @param string[] $immutableCodes
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigProvider($hasConfig = false, $enumCode = '', array $immutableCodes = [])
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider->expects($this->any())
            ->method('hasConfigById')
            ->will($this->returnValue($hasConfig));

        if ($hasConfig) {
            $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
                ->disableOriginalConstructor()
                ->getMock();

            $config->expects($this->any())
                ->method('get')
                ->will($this->returnValueMap([
                   ['enum_code', false, null, $enumCode],
                   ['immutable_codes', false, [], $immutableCodes],
                ]));

            $configProvider->expects($this->any())
                ->method('getConfigById')
                ->will($this->returnValue($config));

            $configProvider->expects($this->any())
                ->method('getConfig')
                ->will($this->returnValue($config));
        }

        return $configProvider;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfiguredForm()
    {
        $configId = new FieldConfigId('enum', 'Test\Entity', 'status', 'enum');
        $formConfig = $this->getMockBuilder('Symfony\Component\Form\FormConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $formConfig->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue($configId));

        $form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        return $form;
    }
}
