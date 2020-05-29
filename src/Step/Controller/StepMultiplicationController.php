<?php

declare(strict_types=1);

namespace Mathematicator\Calculator\Step\Controller;


use Mathematicator\Engine\Step\Step;
use Nette\Utils\ArrayHash;
use Nette\Utils\Validators;

final class StepMultiplicationController implements IStepController
{

	/**
	 * @param ArrayHash $data
	 * @return Step[]
	 */
	public function actionDefault(ArrayHash $data): array
	{
		$steps = [];

		$x = $this->numberToFraction($data->x);
		$y = $this->numberToFraction($data->y);

		$step = new Step();
		$step->setTitle('Násobení čísel');
		$step->setDescription(
			'Tuto sekci teprve plánujeme.'
		); // TODO!!!

		$steps[] = $step;

		return $steps;
	}


	/**
	 * @param string $number
	 * @return string[]
	 */
	private function numberToFraction(string $number): array
	{
		if (Validators::isNumericInt($number)) {
			return [$number, '1'];
		}

		return explode('/', $number);
	}
}