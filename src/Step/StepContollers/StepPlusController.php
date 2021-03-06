<?php

declare(strict_types=1);

namespace Mathematicator\Step\Controller;


use Mathematicator\Engine\Step;
use Mathematicator\NumberHelper;
use Mathematicator\Step\StepFactory;
use Nette\Utils\ArrayHash;
use Nette\Utils\Validators;

final class StepPlusController implements IStepController
{

	/** @var NumberHelper */
	private $number;

	/** @var int */
	private $tolerance = 0;


	/**
	 * @param NumberHelper $number
	 */
	public function __construct(NumberHelper $number)
	{
		$this->number = $number;
	}


	/**
	 * @param ArrayHash $data
	 * @return Step[]
	 */
	public function actionDefault(ArrayHash $data): array
	{
		$steps = [];

		$x = $this->numberToFraction($data->x);
		$y = $this->numberToFraction($data->y);

		if ($x[1] === '1' && $y[1] === '1') {
			$step = StepFactory::addStep();
			$step->setTitle('Sčítání čísel');
			$step->setDescription(
				$this->number->getAddStepAsHtml($x[0], $y[0])
			);

			$steps[] = $step;
		} else {
			$step = StepFactory::addStep();
			$step->setTitle('Sčítání čísel');
			$step->setLatex($this->numberToLatex($x) . ' + ' . $this->numberToLatex($y));
			$steps[] = $step;

			$sp = bcmul($x[1], $y[1], $this->tolerance);
			$step = StepFactory::addStep();
			$step->setTitle('Nalezení společného jmenovatele');
			$step->setLatex($x[1] . '\ \cdot\ ' . $y[1] . ' = ' . $sp);
			$steps[] = $step;

			$left = bcadd(
				bcmul($y[1], $x[0], $this->tolerance),
				bcmul($x[1], $y[0], $this->tolerance),
				$this->tolerance
			);

			$step = StepFactory::addStep();
			$step->setTitle('Převod na jeden zlomek');
			$step->setLatex(
				'\frac{' . $x[0] . '}{' . $x[1] . '}' . ' + \frac{' . $y[0] . '}{' . $y[1] . '}'
				. ' = '
				. '\frac{' . $y[1] . '\ \cdot\ ' . $x[0] . '\ +\ ' . $x[1] . '\ \cdot\ ' . $y[0] . '}{' . $sp . '}'
				. ' = '
				. '\frac{' . $left . '}{' . $sp . '}'
			);
			$steps[] = $step;
		}

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


	/**
	 * @param string[] $number
	 * @return string
	 */
	private function numberToLatex(array $number): string
	{
		if (($number[1] ?? null) === '1') {
			return (string) $number[0];
		}

		return '\frac{' . $number[0] . '}{' . $number[1] . '}';
	}
}
