<?php

declare(strict_types=1);

namespace Mathematicator\MathFunction;


use Mathematicator\Step\Controller\StepSinController;
use Mathematicator\Step\StepFactory;
use Mathematicator\Tokenizer\Token\InfinityToken;
use Mathematicator\Tokenizer\Token\IToken;
use Mathematicator\Tokenizer\Token\NumberToken;
use Mathematicator\Tokenizer\Token\PiToken;

class SinFunction implements IFunction
{


	/**
	 * @param NumberToken|IToken $token
	 * @return FunctionResult
	 */
	public function process(IToken $token): FunctionResult
	{
		assert($token instanceof NumberToken);
		$result = new FunctionResult();

		$x = $token->getNumber()->getFloat();

		if ($token instanceof PiToken) {
			$sin = 0;
		} else {
			$sin = sin($x);
		}

		$token->getNumber()->setValue((string) $sin);
		$token->setToken((string) $sin);

		$step = StepFactory::addStep();
		$step->setAjaxEndpoint(
			StepFactory::getAjaxEndpoint(StepSinController::class, [
				'x' => $x,
			])
		);

		$result->setStep($step);
		$result->setOutput($token);

		return $result;
	}


	/**
	 * @param IToken $token
	 * @return bool
	 */
	public function isValidInput(IToken $token): bool
	{
		return $token instanceof NumberToken || $token instanceof InfinityToken;
	}
}
