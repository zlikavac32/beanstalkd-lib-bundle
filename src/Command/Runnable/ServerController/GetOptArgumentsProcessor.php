<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use GetOpt\ArgumentException;
use GetOpt\GetOpt;

class GetOptArgumentsProcessor implements ArgumentsProcessor
{

    /**
     * @throws CommandException
     */
    public function process(Prototype $prototype, string $line): array
    {
        $optionsProcessor = new GetOpt($prototype->options());

        $optionsProcessor->addOperands($prototype->operands());

        try {
            $optionsProcessor->process($line);
        } catch (ArgumentException $e) {
            throw new CommandException($e->getMessage(), $e);
        }

        $processedArguments = [];

        foreach ($prototype->options() as $option) {
            if ($option->getLong()) {
                $processedArguments['--' . $option->getLong()] = $option->getValue();
            }

            if ($option->getShort()) {
                $processedArguments['-' . $option->getShort()] = $option->getValue();
            }
        }

        foreach ($prototype->operands() as $operand) {
            $processedArguments[$operand->getName()] = $operand->getValue();
        }

        return $processedArguments;
    }
}
