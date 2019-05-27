<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\TestHelper\phpSpec\Matcher;

use PhpSpec\Exception\Example\FailureException;
use PhpSpec\Matcher\Matcher;
use PhpSpec\Wrapper\DelayedCall;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\Prototype;

class RequiredOperandMatcher implements Matcher
{

    public function supports(string $name, $subject, array $arguments): bool
    {
        return $name === 'haveOperandAsRequired' && count($arguments) > 0;
    }

    public function positiveMatch(string $name, $subject, array $arguments): ?DelayedCall
    {
        return $this->match($name, $subject, $arguments, true);
    }

    public function negativeMatch(string $name, $subject, array $arguments): ?DelayedCall
    {
        return $this->match($name, $subject, $arguments, false);
    }

    private function match(string $name, $subject, array $arguments, bool $positive): ?DelayedCall
    {

        if (!$subject instanceof Prototype) {
            throw new FailureException(
                sprintf('Expected instance of %s but got %s', Prototype::class,
                    is_object($subject) ? get_class($subject) : gettype($subject))
            );
        }

        $expectedName = $arguments[0];

        foreach ($subject->operands() as $operand) {
            if ($operand->getName() !== $expectedName) {
                continue;
            }

            if ($operand->isRequired() !== $positive) {
                throw new FailureException(sprintf('Operand %s expected to be %s but is not', $expectedName,
                    ['optional', 'required'][(int)$positive]));
            }

            return null;
        }

        throw new FailureException(sprintf('Operand %s not found', $expectedName));
    }

    public function getPriority(): int
    {
        return 100;
    }
}
