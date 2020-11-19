<?php

declare(strict_types=1);

namespace Gansel\Decimal;

use Gansel\Decimal\Exception\ConversionFailure;
use Gansel\Decimal\Exception\InvalidArgument;

final class Decimal
{
    /**
     * @var string Do not round
     */
    public const ROUND_MODE_NONE = 'none';

    /**
     * @var string Rounding mode to round towards positive infinity
     */
    public const ROUND_MODE_CEILING = 'ceiling';

    /**
     * @var string Rounding mode to round towards zero
     */
    public const ROUND_MODE_DOWN = 'down';

    /**
     * @var string Rounding mode to round towards negative infinity
     */
    public const ROUND_MODE_FLOOR = 'floor';

    /**
     * @var string Rounding mode to round towards "nearest neighbor" unless both
     *             neighbors are equidistant, in which case round down
     */
    public const ROUND_MODE_HALFDOWN = 'halfdown';

    /**
     * @var string Rounding mode to round towards the "nearest neighbor" unless
     *             both neighbors are equidistant, in which case, round towards
     *             the even neighbor
     */
    public const ROUND_MODE_HALFEVEN = 'halfeven';

    /**
     * @var string Rounding mode to round towards "nearest neighbor" unless both
     *             neighbors are equidistant, in which case round up
     */
    public const ROUND_MODE_HALFUP = 'halfup';

    /**
     * @var string Rounding mode to round away from zero
     */
    public const ROUND_MODE_UP = 'up';

    /**
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $scale;

    private function __construct(string $value, int $scale)
    {
        $this->value = $value;
        $this->scale = $scale;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @throws InvalidArgument
     */
    public static function create($arg, ?int $scale = null): self
    {
        if ($arg instanceof self && (null === $scale || $scale === $arg->scale)) {
            return $arg;
        }

        $arg = trim((string) $arg);
        $match = null;

        if (!preg_match('@^\-?[0-9]+(\.[0-9]+)?$@', $arg, $match)) {
            throw new InvalidArgument('#1 arg is not a valid decimal value', 1);
        }

        if (null === $scale) {
            $scale = isset($match[1]) ? \strlen($match[1]) - 1 : 0;
        } elseif ($scale < 0) {
            throw new InvalidArgument('#2 scale must be a non negative integer', 1);
        } else {
            $scale = (int) $scale;
        }

        // bcmul with 1 normalizes the value to the given scale
        return self::createSafe(bcmul($arg, '1', $scale), $scale);
    }

    public static function compare(self $a, self $b): int
    {
        return bccomp($a->value, $b->value, max($a->scale, $b->scale));
    }

    /**
     * @throws InvalidArgument
     */
    public static function createComparator(?int $scale = null): \Closure
    {
        if (null === $scale) {
            return function (self $a, self $b): int {
                return self::compare($a, $b);
            };
        }

        if ($scale < 0) {
            throw new InvalidArgument('#1 scale must be a non negative integer', 1);
        }

        return function (self $a, self $b) use ($scale): int {
            return bccomp($a->value, $b->value, $scale);
        };
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Todo: only ROUND_MODE_NONE supported yet.
     *
     * @throws ConversionFailure
     */
    public function toInteger(string $roundMode = self::ROUND_MODE_NONE): int
    {
        if (!$this->isInteger()) {
            throw new ConversionFailure(sprintf(
                'Can not convert decimal "%s" to an integer without rounding',
                $this
            ));
        }

        $str = explode('.', $this->value, 2)[0];
        $int = (int) $this->value;

        if ($str !== (string) $int) {
            throw new ConversionFailure(sprintf(
                'Conversion of decimal "%s" to an integer failed, because casted integer "%s" does not match the'
                .' decimals integer part "%s". PHP_INT_MAX=%s PHP_INT_MIN=%s',
                $this,
                $int,
                $str,
                PHP_INT_MAX,
                PHP_INT_MIN
            ));
        }

        return $int;
    }

    public function precision(): int
    {
        return \strlen($this->value) - ($this->scale > 0) - ('-' === $this->value[0]);
    }

    public function scale(): int
    {
        return $this->scale;
    }

    public function digits(): string
    {
        return ltrim(str_replace('.', '', $this->value), '0') ?: '0';
    }

    /**
     * @throws ConversionFailure
     */
    public function value(): int
    {
        $str = $this->digits();
        $int = (int) $str;

        if ($str !== (string) $int) {
            throw new ConversionFailure(sprintf(
                'Conversion of value of decimal "%s" to an integer failed, because casted integer "%s" does not match the'
                .' decimals value "%s". PHP_INT_MAX=%s PHP_INT_MIN=%s',
                $this,
                $int,
                $str,
                PHP_INT_MAX,
                PHP_INT_MIN
            ));
        }

        return $int;
    }

    /**
     * Todo: only ROUND_MODE_HALFUP supported yet.
     *
     * @throws InvalidArgument
     */
    public function round(int $scale = 0, string $mode = self::ROUND_MODE_HALFUP): self
    {
        if (self::ROUND_MODE_HALFUP !== $mode) {
            throw new \BadMethodCallException('Not implemented');
        }

        if ($scale < 0) {
            throw new InvalidArgument('#1 scale must be a non negative integer', 1);
        }

        if ($this->scale <= $scale) {
            return self::create($this, $scale);
        }

        $value = \call_user_func(
            $this->isNegative() ? 'bcsub' : 'bcadd',
            $this->value,
            '0.'.str_repeat('0', $scale).'5',
            $scale
        );

        return self::createSafe($value, $scale);
    }

    /**
     * @throws \BadMethodCallException
     */
    public function ceil(int $scale = 0): self
    {
        return $this->round($scale, self::ROUND_MODE_CEILING);
    }

    /**
     * @throws \BadMethodCallException
     */
    public function floor(int $scale = 0): self
    {
        return $this->round($scale, self::ROUND_MODE_FLOOR);
    }

    /**
     * @param int $scale
     *
     * @throws InvalidArgument
     *
     * @return self
     */
    public function add($rightOperand, $scale = null)
    {
        $scale = $this->resolveScale($scale);

        return $this->executeBinaryOperation('bcadd', $rightOperand, $scale);
    }

    /**
     * @param int $scale
     *
     * @throws InvalidArgument
     *
     * @return self
     */
    public function sub($rightOperand, $scale = null)
    {
        $scale = $this->resolveScale($scale);

        return $this->executeBinaryOperation('bcsub', $rightOperand, $scale);
    }

    /**
     * @param int $scale
     *
     * @throws InvalidArgument
     *
     * @return self
     */
    public function mul($rightOperand, $scale = null)
    {
        $scale = $this->resolveScale($scale);

        return $this->executeBinaryOperation('bcmul', $rightOperand, $scale);
    }

    /**
     * @param int $scale
     *
     * @throws InvalidArgument
     *
     * @return self
     */
    public function div($rightOperand, $scale = null)
    {
        $scale = $this->resolveScale($scale);

        return $this->executeBinaryOperation('bcdiv', $rightOperand, $scale);
    }

    /**
     * @param int $scale
     *
     * @throws InvalidArgument
     *
     * @return self
     */
    public function pow($rightOperand, $scale = null)
    {
        $scale = $this->resolveScale($scale);

        return $this->executeBinaryOperation('bcpow', $rightOperand, $scale);
    }

    /**
     * @throws InvalidArgument
     *
     * @return self
     */
    public function mod($rightOperand, int $scale = null)
    {
        $scale = $this->resolveScale($scale);

        return $this->executeBinaryOperation('bcmod', $rightOperand, $scale);
    }

    public function powmod($rightOperand, $modulus, $scale = null)
    {
        $scale = $this->resolveScale($scale);

        $rightOperand = self::create($rightOperand)->value;
        $modulus = self::create($modulus)->value;

        return bcpowmod($this->value, $rightOperand, $modulus, $scale);
    }

    /**
     * @throws InvalidArgument
     */
    public function sqrt(int $scale = null): self
    {
        $scale = $this->resolveScale($scale);

        return self::createSafe(bcsqrt($this->value, $scale), $scale);
    }

    public function abs(): self
    {
        return $this->compareTo('0') < 0 ? $this->neg() : $this;
    }

    public function neg(): self
    {
        return $this->executeBinaryOperation('bcmul', '-1', $this->scale);
    }

    public function inv(): self
    {
        return $this->executeBinaryOperation('bcpow', '-1', $this->scale);
    }

    public function compareTo($rightOperand): int
    {
        return self::compare($this, self::create($rightOperand));
    }

    public function isInteger(): bool
    {
        return 0 === $this->scale || '.' === substr(rtrim($this->value, '0'), -1);
    }

    public function isZero(): bool
    {
        return !\strlen(rtrim($this->value, '.0'));
    }

    public function isPositive(): bool
    {
        return '-' !== $this->value[0] && !$this->isZero();
    }

    public function isNegative(): bool
    {
        return '-' === $this->value[0];
    }

    /**
     * @param mixed $min Lower boundary or null, if unbound
     * @param mixed $max Upper boundary or null, if unbound
     */
    public function limit($min, $max): self
    {
        $value = $this;

        if (null !== $min) {
            $min = self::create($min);

            if (self::compare($this, $min) < 0) {
                $value = $min;
            }
        }

        if (null !== $max) {
            $max = self::create($max);

            if (null !== $min && self::compare($min, $max) > 0) {
                throw new InvalidArgument(sprintf(
                    'Lower boundary "%s" must be lower than or equal to upper boundary "%s"',
                    $min,
                    $max
                ));
            }

            if (self::compare($this, $max) > 0) {
                $value = $max;
            }
        }

        return $value;
    }

    private static function createSafe(string $value, int $scale): self
    {
        return new self($value, $scale);
    }

    /**
     * @throws InvalidArgument
     */
    private function resolveScale(?int $scale): int
    {
        if (null === $scale) {
            $scale = $this->scale;
        } elseif ($scale < 0) {
            throw new InvalidArgument('#1 scale must be a non negative integer', 1);
        } else {
            $scale = (int) $scale;
        }

        return $scale;
    }

    /**
     * @throws InvalidArgument
     */
    private function executeBinaryOperation(string $op, $rightOperand, int $scale): self
    {
        $result = $op($this->value, self::create($rightOperand)->value, $scale);

        return self::createSafe($result, $scale);
    }
}
