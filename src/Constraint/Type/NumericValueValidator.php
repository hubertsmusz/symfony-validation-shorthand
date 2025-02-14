<?php
declare(strict_types=1);

namespace DigitalRevolution\SymfonyValidationShorthand\Constraint\Type;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NumericValueValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($constraint instanceof NumericValue === false) {
            throw new UnexpectedTypeException($constraint, NumericValue::class);
        }

        if ($value === null || is_int($value) || is_float($value)) {
            return;
        }

        // value should be string
        if (is_string($value) === false) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode($constraint::INVALID_VALUE_TYPE)
                ->addViolation();
            return;
        }

        // value can't be cast to float
        if ($value === '' || $value === '-' || preg_match('/^-?(?:[1-9]\d*|0)?(?:\.\d*)?$/', $value) !== 1) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode($constraint::INVALID_DECIMAL_ERROR)
                ->addViolation();
        }
    }
}
