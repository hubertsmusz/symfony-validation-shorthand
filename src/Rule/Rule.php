<?php
declare(strict_types=1);

namespace DigitalRevolution\SymfonyValidationShorthand\Rule;

class Rule
{
    public const RULE_REQUIRED    = 'required';
    public const RULE_BOOLEAN     = 'boolean';
    public const RULE_INTEGER     = 'integer';
    public const RULE_NUMERIC     = 'numeric';
    public const RULE_FLOAT       = 'float';
    public const RULE_STRING      = 'string';
    public const RULE_ARRAY       = 'array';
    public const RULE_ALPHA       = 'alpha';
    public const RULE_ALPHA_DASH  = 'alpha_dash';
    public const RULE_ALPHA_NUM   = 'alpha_num';
    public const RULE_IN          = 'in';
    public const RULE_DATE        = 'date';
    public const RULE_DATETIME    = 'datetime';
    public const RULE_DATE_FORMAT = 'date_format';
    public const RULE_EMAIL       = 'email';
    public const RULE_URL         = 'url';
    public const RULE_NULLABLE    = 'nullable';
    public const RULE_FILLED      = 'filled';
    public const RULE_REGEX       = 'regex';
    public const RULE_MIN         = 'min';
    public const RULE_MAX         = 'max';
    public const RULE_BETWEEN     = 'between';

    public const ALLOWED_RULES = [
        self::RULE_REQUIRED,
        self::RULE_BOOLEAN,
        self::RULE_INTEGER,
        self::RULE_FLOAT,
        self::RULE_NUMERIC,
        self::RULE_STRING,
        self::RULE_ARRAY,
        self::RULE_ALPHA,
        self::RULE_ALPHA_DASH,
        self::RULE_ALPHA_NUM,
        self::RULE_IN,
        self::RULE_DATE,
        self::RULE_DATETIME,
        self::RULE_DATE_FORMAT,
        self::RULE_EMAIL,
        self::RULE_URL,
        self::RULE_NULLABLE,
        self::RULE_FILLED,
        self::RULE_REGEX,
        self::RULE_MIN,
        self::RULE_MAX,
        self::RULE_BETWEEN,
    ];

    /** @var string */
    private $name;

    /** @var string[] */
    private $parameters;

    /**
     * @param string[] $parameters
     */
    public function __construct(string $name, array $parameters = [])
    {
        $this->name       = $name;
        $this->parameters = $parameters;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws InvalidRuleException
     */
    public function getParameter(int $offset): string
    {
        if (isset($this->parameters[$offset]) === false) {
            throw new InvalidRuleException("Rule '" . $this->getName() . "' expects at least " . $offset . ' parameter(s)');
        }

        return $this->parameters[$offset];
    }

    /**
     * @throws InvalidRuleException
     */
    public function getIntParam(int $offset): int
    {
        $argument = $this->getParameter($offset);
        if ((string)(int)$argument !== $argument) {
            throw new InvalidRuleException(
                "Rule '" . $this->getName() . "' expects parameter #" . $offset . " to be an int. Encountered: '" . $argument . "'"
            );
        }

        return (int)$argument;
    }

    /**
     * @return string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
