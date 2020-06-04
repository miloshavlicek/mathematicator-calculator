<?php

declare(strict_types=1);

namespace Mathematicator\Calculator\Operation;


use Mathematicator\Calculator\Step\Controller\StepPowController;
use Mathematicator\Calculator\Step\StepFactory;
use Mathematicator\Engine\Entity\Query;
use Mathematicator\Engine\Exception\UndefinedOperationException;
use Mathematicator\Numbers\Latex\MathLatexToolkit;
use Mathematicator\Numbers\SmartNumber;
use Mathematicator\Tokenizer\Token\NumberToken;

class PowNumber
{


	/**
	 * @param NumberToken $left
	 * @param NumberToken $right
	 * @param Query $query
	 * @return NumberOperationResult
	 * @throws UndefinedOperationException
	 */
	public function process(NumberToken $left, NumberToken $right, Query $query): NumberOperationResult
	{
		$leftNumber = $left->getNumber();
		$rightNumber = $right->getNumber();
		$leftFraction = $leftNumber->toFraction();
		$rightFraction = $rightNumber->toFraction();

		$result = null;

		$rightIsInteger = $rightNumber->isInteger();

		if ($rightIsInteger && $leftNumber->isInteger()) {
			if (
				$leftNumber->getInteger()->isEqualTo(0)
				&& $rightNumber->getInteger()->isEqualTo(0)
			) {
				throw new UndefinedOperationException(__METHOD__ . ': Undefined operation.');
			}

			$result = bcpow($left->getToken(), $right->getToken(), $query->getDecimals());
		} elseif ($rightIsInteger === true) {
			$result = bcpow((string) $leftFraction[0], $right->getToken(), $query->getDecimals()) . '/' . bcpow((string) $leftFraction[1], $right->getToken(), $query->getDecimals());
		} else {
			if ($rightNumber->isNegative()) {
				$rightFraction = [
					$rightFraction[1],
					$rightFraction[0],
				];
			}

			$result = pow(
					(float) bcpow((string) $leftFraction[0], (string) $rightFraction[0], $query->getDecimals()),
					(float) bcdiv('1', (string) $rightFraction[1], $query->getDecimals())
				)
				. '/'
				. pow(
					(float) bcpow((string) $leftFraction[1], (string) $rightFraction[0], $query->getDecimals()),
					(float) bcdiv('1', (string) $rightFraction[1], $query->getDecimals())
				);
		}

		$newNumber = new NumberToken(SmartNumber::of($result));
		$newNumber->setToken($newNumber->getNumber()->getString());
		$newNumber->setPosition($left->getPosition());
		$newNumber->setType('number');

		return (new NumberOperationResult)
			->setNumber($newNumber)
			->setTitle('Umocňování čísel ' . $leftNumber->toHumanString() . ' ^ ' . $rightNumber->toHumanString())
			->setDescription($this->renderDescription($leftNumber, $rightNumber, $newNumber->getNumber()))
			->setAjaxEndpoint(
				StepFactory::getAjaxEndpoint(StepPowController::class, [
					'x' => $leftNumber->toHumanString(),
					'y' => $rightNumber->toHumanString(),
					'result' => $newNumber->getNumber()->getString(),
				])
			);
	}


	/**
	 * @param SmartNumber $left
	 * @param SmartNumber $right
	 * @param SmartNumber $result
	 * @return string
	 */
	private function renderDescription(SmartNumber $left, SmartNumber $right, SmartNumber $result): string
	{
		if (!$left->isInteger() && !$right->isInteger()) {
			return 'Umocňování zlomků je zatím experimentální a může poskytnout jen přibližný výsledek.';
		}

		if ($right->isInteger() && $right->getInteger() === '0') {
			return '\({a}^{0}\ =\ 1\) Cokoli na nultou (kromě nuly) je vždy jedna. '
				. 'Umocňování na nultou si lze také představit jako nekonečné odmocňování, '
				. 'proto se limitně blíží k jedné.';
		}

		return (string) MathLatexToolkit::create(
			MathLatexToolkit::pow(
				$left->toHumanString(), $right->toHumanString()
			)->equals($result->getString()),
			'\(', '\)'
		);
	}
}
