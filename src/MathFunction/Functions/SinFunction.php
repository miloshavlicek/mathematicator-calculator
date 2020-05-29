<?php

declare(strict_types=1);

namespace Mathematicator\Calculator\MathFunction\Functions;


use Mathematicator\Calculator\MathFunction\FunctionResult;
use Mathematicator\Calculator\MathFunction\IFunction;
use Mathematicator\Calculator\Step\Controller\StepSinController;
use Mathematicator\Calculator\Step\StepFactory;
use Mathematicator\Engine\Step\Step;
use Mathematicator\Numbers\NumberException;
use Mathematicator\Tokenizer\Token\InfinityToken;
use Mathematicator\Tokenizer\Token\IToken;
use Mathematicator\Tokenizer\Token\NumberToken;
use Mathematicator\Tokenizer\Token\PiToken;

class SinFunction implements IFunction
{


	/**
	 * @param NumberToken|IToken $token
	 * @return FunctionResult
	 * @throws NumberException
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

		$step = new Step();
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