<?php
declare(strict_types=1);

namespace DigitalRevolution\SymfonyRequestValidation\Tests\Unit\Constraint;

use ArrayIterator;
use DigitalRevolution\SymfonyRequestValidation\Constraint\ConstraintResolver;
use DigitalRevolution\SymfonyRequestValidation\Iterator\RecursiveArrayIterator;
use DigitalRevolution\SymfonyRequestValidation\Parser\Rule;
use DigitalRevolution\SymfonyRequestValidation\Parser\RuleSet;
use DigitalRevolution\SymfonyRequestValidation\RequestValidationException;
use DigitalRevolution\SymfonyRequestValidation\Transformer\StringToIntTransformer;
use DigitalRevolution\SymfonyRequestValidation\Transformer\TransformerInterface;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;

/**
 * @coversDefaultClass \DigitalRevolution\SymfonyRequestValidation\Constraint\ConstraintResolver
 */
class ConstraintResolverTest extends TestCase
{
    /** @var ConstraintResolver */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ConstraintResolver();
    }

    public function testPlayground(): void
    {
        $transformer = new StringToIntTransformer();
        $data        = ['a' => ['1', '2']];
        $rules       = [
            'a.0' => [
                'required'    => true,
                'transformer' => $transformer,
                'constraints' => [new Assert\NotBlank(), new Assert\Type('integer'), new Assert\Range(['min' => 1])]],
            'a.1' => [
                'required'    => false,
                'transformer' => $transformer,
                'constraints' => [new Assert\NotBlank(), new Assert\Type('integer'), new Assert\Range(['min' => 3])]
            ]
        ];

        $violationList = new ConstraintViolationList();
        $validator     = Validation::createValidator();

        $callable = static function ($key, $value) use ($rules, $validator, $violationList): ?int {
            if (isset($rules[$key]) === false) {
                throw new RequestValidationException('missing rule for: ' . $key);
            }

            $rule = $rules[$key];
            if (isset($rule['transformer'])) {
                /** @var TransformerInterface $transformer */
                $transformer = $rule['transformer'];

                if ($transformer->transformable($value) === false) {
                    throw new RequestValidationException('unable to transform value to int');
                }

                $value = $transformer->transform($value);
            }

            if (isset($rule['constraints']) && count($rule['constraints']) > 0) {
                $constraints = $rule['constraints'];

                $violations = $validator->validate($value, $constraints);
                $violationList->addAll($violations);
                if (count($violations) > 0) {
                    return null;
                }
            }

            return $value;
        };

        $result = (new RecursiveArrayIterator($data, $callable))->iterate();

        static::assertSame(['a' => [1, 2]], $result);
    }

    public function testPlayground2()
    {
        $validator = Validation::createValidator();

        $input = [
            'name'      => [
                'first_name' => 'Fabien',
                //'last_name'  => 'Potencier',
            ],
            'email'     => 'test@email.tld',
            'simple'    => 'hello',
            'eye_color' => 3,
            'file'      => null,
            'password'  => 'test',
            'tags'      => [
                [
                    'slug'  => 'symfony_doc',
                    'label' => 'symfony doc',
                ],
            ],
        ];

        $groups = new Assert\GroupSequence(['Default', 'custom']);

        $rules = [
            'name'      => [
                'first_name' => 'required|min:6',
                'last_name'  => 'required|min:1',
            ],
            'email'     => 'required|email',
            'simple'    => 'required|min:5',
            'eye_color' => 'required|enum:3,4',
            'file'      => 'required|file',
            'password'  => 'required|min:60',
            'tags'      => [
                [
                    'slug'  => 'required|filled',
                    'label' => 'required|filled'
                ]
            ]
        ];

        $constraint = new Assert\Collection([
            // the keys correspond to the keys in the input array
            'name'      => new Assert\Collection([
                'first_name' => new Assert\Length(['min' => 6]),
                'last_name'  => new Assert\Optional(new Assert\Length(['min' => 1])),
            ]),
            'email'     => new Assert\Email(),
            'simple'    => new Assert\Length(['min' => 5]),
            'eye_color' => new Assert\Choice([3, 4]),
            'file'      => new Assert\File(),
            'password'  => new Assert\Length(['min' => 4]),
            'tags'      => new Assert\Optional([
                new Assert\Type('array'),
                new Assert\Count(['min' => 1]),
                new Assert\All([
                    new Assert\Collection([
                        'slug'  => [
                            new Assert\NotBlank(),
                            new Assert\Type(['type' => 'string'])
                        ],
                        'label' => [
                            new Assert\NotBlank(),
                        ],
                    ]),
                ]),
            ]),
        ]);

        $iterator = new ArrayIterator($input);

        $violations = $validator->validate($iterator, $constraint, $groups);
        static::assertCount(0, $violations);
    }

    /**
     * @covers ::resolveRuleSet
     * @covers ::resolveConstraint
     * @throws RequestValidationException
     */
    public function testResolveRuleSetUnknownRule(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->addRule(new Rule('unknown'));

        $this->expectException(RequestValidationException::class);
        $this->expectExceptionMessage('Unable to resolve rule: unknown');
        $this->resolver->resolveRuleSet($ruleSet);
    }

    /**
     * @dataProvider dataProvider
     * @covers ::resolveRuleSet
     * @covers ::resolveConstraint
     * @param array<Rule|Constraint> $rules
     * @throws RequestValidationException
     */
    public function testResolveRuleSet(Constraint $expected, array $rules): void
    {
        $ruleSet = new RuleSet();
        foreach ($rules as $rule) {
            $ruleSet->addRule($rule);
        }
        static::assertEquals($expected, $this->resolver->resolveRuleSet($ruleSet));
    }

    /**
     * @phpstan-return Generator<string, array<int, Constraint|Rule[]|Constraint[]>>
     */
    public function dataProvider(): Generator
    {
        yield 'constraint' => [new Assert\Optional(new Assert\NotBlank()), [new Assert\NotBlank()]];
        yield 'boolean' => [new Assert\Optional(new Assert\Type('bool')), [new Rule('boolean')]];
        yield 'integer' => [new Assert\Optional(new Assert\Type('integer')), [new Rule('integer')]];
        yield 'float' => [new Assert\Optional(new Assert\Type('float')), [new Rule('float')]];
        yield 'email' => [new Assert\Optional(new Assert\Email()), [new Rule('email')]];
        yield 'regex' => [new Assert\Optional(new Assert\Regex(['pattern' => '/^unittest$/'])), [new Rule('regex', ['/^unittest$/'])]];
        yield 'required' => [new Assert\Required(), [new Rule('required')]];
        yield 'required email' => [new Assert\Required(new Assert\Email()), [new Rule('required'), new Rule('email')]];

        // min/max string or array lengths
        yield 'min length' => [new Assert\Optional(new Assert\Length(['min' => 10])), [new Rule('min', ['10'])]];
        yield 'max length' => [new Assert\Optional(new Assert\Length(['max' => 10])), [new Rule('max', ['10'])]];
        yield 'min/max length' => [new Assert\Optional(new Assert\Length(['min' => 10, 'max' => 20])), [new Rule('between', ['10', '20'])]];

        // min/max integer size
        yield 'min integer' => [
            new Assert\Optional([new Assert\Type('integer'), new Assert\Range(['min' => 10])]),
            [new Rule('integer'), new Rule('min', ['10'])]
        ];
        yield 'max integer' => [
            new Assert\Optional([new Assert\Type('integer'), new Assert\Range(['max' => 20])]),
            [new Rule('integer'), new Rule('max', ['20'])]
        ];
        yield 'min/max integer' => [
            new Assert\Optional([new Assert\Type('integer'), new Assert\Range(['min' => 10, 'max' => 20])]),
            [new Rule('integer'), new Rule('between', ['10', '20'])]
        ];
    }
}
