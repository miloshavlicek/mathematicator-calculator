<?php

declare(strict_types=1);

namespace Mathematicator;


use Contributte\Psr6\CachePool;
use Contributte\Psr6\ICachePoolFactory;
use Nette\Application\LinkGenerator;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

class NumberHelper
{

	/** @var int[] */
	private static $romanNumber = [
		'm' => 1000000,
		'd' => 500000,
		'c' => 100000,
		'l' => 50000,
		'x' => 10000,
		'v' => 5000,
		'M' => 1000,
		'CM' => 900,
		'D' => 500,
		'CD' => 400,
		'C' => 100,
		'XC' => 90,
		'L' => 50,
		'XL' => 40,
		'X' => 10,
		'IX' => 9,
		'V' => 5,
		'IV' => 4,
		'I' => 1,
	];

	/** @var LinkGenerator */
	private $linkGenerator;

	/** @var CachePool */
	private $cachePool;


	/**
	 * @param LinkGenerator $linkGenerator
	 * @param ICachePoolFactory $cachePoolFactory
	 */
	public function __construct(LinkGenerator $linkGenerator, ICachePoolFactory $cachePoolFactory)
	{
		$this->linkGenerator = $linkGenerator;
		$this->cachePool = $cachePoolFactory->create('number-helper');
	}


	/**
	 * @return string
	 */
	public static function getPiChar(): string
	{
		return 'π';
	}


	/**
	 * @param string $haystack
	 * @return string
	 */
	public static function intToRoman(string $haystack): string
	{
		$return = '';
		if (($int = (int) $haystack) <= 0) {
			return '\text{Nemá řešení}';
		}
		foreach (self::$romanNumber as $key => $val) {
			if (($repeat = (int) floor($int / $val)) > 0) {
				$return .= '\\' . ($val >= 5000
						? 'overline'
						: 'textrm'
					) . '{'
					. Strings::upper(str_repeat($key, $repeat))
					. '}';
			}
			$int %= $val;
		}

		return $return;
	}


	/**
	 * @param string $roman
	 * @return int
	 */
	public static function romanToInt(string $roman): int
	{
		$roman = Strings::upper($roman);
		$romanLength = Strings::length($roman);
		$return = 0;
		for ($i = 0; $i < $romanLength; $i++) {
			$x = self::$romanNumber[$roman[$i]];
			if ($i + 1 < \strlen($roman) && ($nextToken = self::$romanNumber[$roman[$i + 1]]) > $x) {
				$return += $nextToken - $x;
				$i++;
			} else {
				$return += $x;
			}
		}

		return $return;
	}


	/**
	 * @param int $len
	 * @return string
	 */
	public function getPi($len = 16): string
	{
		$pi = '1415926535897932384626433832795028841971693993751058209749445923078164062862089986280348253421170679'
			. '8214808651328230664709384460955058223172535940812848111745028410270193852110555964462294895493038196'
			. '4428810975665933446128475648233786783165271201909145648566923460348610454326648213393607260249141273'
			. '7245870066063155881748815209209628292540917153643678925903600113305305488204665213841469519415116094'
			. '3305727036575959195309218611738193261179310511854807446237996274956735188575272489122793818301194912'
			. '9833673362440656643086021394946395224737190702179860943702770539217176293176752384674818467669405132'
			. '0005681271452635608277857713427577896091736371787214684409012249534301465495853710507922796892589235'
			. '4201995611212902196086403441815981362977477130996051870721134999999837297804995105973173281609631859'
			. '5024459455346908302642522308253344685035261931188171010003137838752886587533208381420617177669147303'
			. '5982534904287554687311595628638823537875937519577818577805321712268066130019278766111959092164201989';

		return '3' . ($len > 0 ? '.' . preg_replace('/(\d{8})/', '$1 ', Strings::substring($pi, 0, $len)) : '');
	}


	/**
	 * @param string $number
	 * @return bool
	 */
	public function isRoman(string $number): bool
	{
		return (bool) preg_match('/[IVXLCDMivxlcdm]{2,}/', $number);
	}


	/**
	 * @param float $n
	 * @param float $tolerance
	 * @return int[]
	 */
	public function floatToFraction(float $n, $tolerance = 1.e-8): array
	{
		$n = abs($n);
		$h1 = 1;
		$h2 = 0;
		$k1 = 0;
		$k2 = 1;
		$b = 1 / $n;
		do {
			$b = 1 / $b;
			$a = floor($b);
			$aux = $h1;
			$h1 = $a * $h1 + $h2;
			$h2 = $aux;
			$aux = $k1;
			$k1 = $a * $k1 + $k2;
			$k2 = $aux;
			$b -= $a;
		} while (abs($n - $h1 / $k1) > $n * $tolerance);

		return [(int) $h1, (int) $k1];
	}


	/**
	 * @param string $n
	 * @return string[]
	 */
	public function getDivisors(string $n): array
	{
		$i = 1;
		$s = bcsqrt($n);
		$a = [];

		while ($i <= $s) {
			if (!bcmod($n, (string) $i)) {
				$a[] = $i;
				if ($i !== $s) {
					$a[] = bcdiv($n, (string) $i);
				}
			}
			++$i;
		}

		return $a;
	}


	/**
	 * @param string $n
	 * @return int[]|string[]
	 */
	public function pfactor(string $n): array
	{
		if ($n === '1' || $n === '2' || $n === '3') {
			return [$n];
		}

		$cacheItem = $this->cachePool->getItem($n);

		if ($cacheItem->isHit()) {
			return $cacheItem->get();
		}

		$num = 0;
		$sqrtN = bcsqrt(ltrim($n, '-'));

		for ($i = 2; $i <= $sqrtN; $i++) {
			if ($n % $i === 0) {
				$num = (string) $i;
				break;
			}
		}

		$return = $num === 0 ? [$n] : array_merge([$num], $this->pfactor((string) ($n / $num)));

		$cacheItem->set($return);
		$this->cachePool->save($cacheItem);

		return $return;
	}


	/**
	 * @param string $x
	 * @param string $y
	 * @param bool $renderAnimation
	 * @return string
	 * @throws \Nette\Application\UI\InvalidLinkException
	 */
	public function getAddStepAsHtml(string $x, string $y, bool $renderAnimation = false): string
	{
		$return = '';
		$animation = '';

		$numberFormat = static function (string $number): string {
			return (string) preg_replace('/\.0*$/', '', preg_replace('/\.(\d*?)0+$/', '.$1', $number));
		};

		$result = $numberFormat(bcadd($x, $y, 10));
		$x = $numberFormat($x);
		$y = $numberFormat($y);

		$lenX = \strlen($x);
		$lenY = \strlen($y) + 2;
		$lenResult = \strlen($result);

		$return .= $x . '<br>';
		$return .= '+&nbsp;' . $y . '<br>';
		$return .= '<span style="border-top:1px solid black;font-family:monospace;text-align:right">'
			. $result
			. '</span>';

		if (
			$renderAnimation === true
			&& $lenX <= 10 && $lenY <= 12
			&& Validators::isNumericInt($x) && Validators::isNumericInt($y)
		) {
			$uniqueId = uniqid('numberHelper', true);
			$left = 'addNumbersLeft' . $uniqueId;
			$right = 'addNumbersRight' . $uniqueId;
			$run = 'addNumbersRun' . $uniqueId;

			$frameworkJs = $this->linkGenerator->link('Front:Content:dataJs', [
				'name' => 'easeljs-0.6.1.min',
			]);

			$addJs = $this->linkGenerator->link('Front:Content:dataJs', [
				'name' => 'addNumbers',
				'id' => 'addNumbersCanvas|' . $uniqueId . ';addNumbersLeft|' . $left . ';addNumbersRight|' . $right . ';addNumbersRun|' . $run,
			]);

			$animation .= '<input type="hidden" id="' . $left . '" value="' . $x . '"><input type="hidden" id="' . $right . '" value="' . $y . '">';
			$animation .= '<canvas id="' . $uniqueId . '" width="800" height="75" style="display:none;margin-top:15px;">Váš prohlížeč nepodporuje Canvas.</canvas>';
			$animation .= '<script type="text/javascript" src="' . $frameworkJs . '"></script>';
			$animation .= '<script type="text/javascript" src="' . $addJs . '"></script>';

			return '<div class="row"><div class="col">'
				. '<div style="width:' . (max([$lenX, $lenY, $lenResult]) * 9) . 'px">'
				. '<div style="font-family:monospace;font-size:12pt;text-align:right">' . $return . '</div>'
				. '</div>'
				. '</div><div class="col-sm-4" style="text-align:right">'
				. '<input type="button" id="' . $run . '" value="Přehrát animaci" class="btn btn-primary" onclick="$(\'#' . $uniqueId . '\').show(500);">'
				. '</div></div>'
				. $animation;
		}

		return '<div style="width:' . (max([$lenX, $lenY, $lenResult]) * 10) . 'px">'
			. '<div style="font-family:monospace;font-size:12pt;text-align:right">' . $return . '</div></div>';
	}
}
