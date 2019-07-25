<?php

namespace Mathematicator\Calculator;

use Mathematicator\Engine\MathematicatorException;
use Mathematicator\Engine\QueryNormalizer;
use Mathematicator\Tokenizer\Token\FactorialToken;
use Mathematicator\Tokenizer\Token\FunctionToken;
use Mathematicator\Tokenizer\Token\IToken;
use Mathematicator\Tokenizer\Token\NumberToken;
use Mathematicator\Tokenizer\Token\SubToken;
use Mathematicator\Tokenizer\Tokenizer;
use Model\Math\Step\StepFactory;

class Calculator
{

	/**
	 * @var StepFactory
	 */
	private $stepFactory;

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * @var TokensCalculator
	 */
	private $tokensCalculator;

	/**
	 * @var QueryNormalizer
	 */
	private $queryNormalizer;

	/**
	 * @param StepFactory $stepFactory
	 * @param Tokenizer $tokenizer
	 * @param TokensCalculator $tokensCalculator
	 * @param QueryNormalizer $queryNormalizer
	 */
	public function __construct(
		StepFactory $stepFactory,
		Tokenizer $tokenizer,
		TokensCalculator $tokensCalculator,
		QueryNormalizer $queryNormalizer
	)
	{
		$this->stepFactory = $stepFactory;
		$this->tokenizer = $tokenizer;
		$this->tokensCalculator = $tokensCalculator;
		$this->queryNormalizer = $queryNormalizer;
	}

	/**
	 * @param IToken[] $tokens
	 * @param int $basicTtl
	 * @return CalculatorResult
	 * @throws MathematicatorException
	 */
	public function calculate(array $tokens, int $basicTtl = 3): CalculatorResult
	{
		$result = new CalculatorResult($tokens);

		if (\count($tokens) === 1 && !($tokens[0] instanceof FunctionToken || $tokens[0] instanceof FactorialToken)) {
			$result->setResultTokens($tokens);
			$result->setSteps([]);

			return $result;
		}

		$iterator = 0;
		$steps = [];

		$interpretStep = $this->stepFactory->create();
		$interpretStep->setTitle('Zadání úlohy');
		$interpretStep->setLatex($this->tokenizer->tokensToLatex($tokens));

		$steps[] = $interpretStep;

		$stepLatexLast = null;
		$ttl = $basicTtl;
		do {
			if ($iterator++ > 128) {
				break;
			}

			$stepLatexLast = $this->tokensSerialize($tokens);
			$process = $this->tokensCalculator->process($tokens);
			$tokens = $process->getResult();

			$stepLatexCurrent = $this->tokenizer->tokensToLatex($tokens);

			$step = $this->stepFactory->create();
			$step->setLatex($stepLatexCurrent);
			$step->setTitle($process->getStepTitle());
			$step->setDescription($process->getStepDescription());
			$step->setAjaxEndpoint($process->getAjaxEndpoint());

			$steps[] = $step;

			if ($this->tokensSerialize($tokens) === $stepLatexLast) {
				$ttl--;

				if ($ttl <= 0) {
					break;
				}
			} else {
				$ttl = $basicTtl;
			}
		} while (true);

		$result->setResultTokens($tokens);
		$result->setSteps($steps);

		return $result;
	}

	/**
	 * Human input and token output.
	 *
	 * @param string $query
	 * @return CalculatorResult
	 * @throws MathematicatorException
	 */
	public function calculateString(string $query): CalculatorResult
	{
		return $this->calculate(
			$this->tokenizer->tokensToObject(
				$this->tokenizer->tokenize(
					$this->queryNormalizer->normalize($query)
				)
			)
		);
	}

	/**
	 * @param string $query
	 * @return IToken[]
	 */
	public function getTokensByString(string $query): array
	{
		$query = $this->queryNormalizer->normalize($query);
		$tokens = $this->tokenizer->tokenize($query);

		return $this->tokenizer->tokensToObject($tokens);
	}

	/**
	 * @param IToken[] $tokens
	 * @return string
	 */
	private function tokensSerialize(array $tokens = null): string
	{
		$tokensToSerialize = '';

		foreach ($tokens ?? [] as $token) {
			$tokensToSerialize .= '<{' . $token->getToken() . '}' . $token->getType() . '|';

			if ($token instanceof SubToken) {
				$tokensToSerialize .= 'SUB:' . $this->tokensSerialize($token->getTokens());
			} elseif ($token instanceof NumberToken) {
				$tokensToSerialize .= $token->getNumber()->getString();
			} else {
				$tokensToSerialize .= $token->getType();
			}

			$tokensToSerialize .= '>';
		}

		return '[' . $tokensToSerialize . ']';
	}

}
