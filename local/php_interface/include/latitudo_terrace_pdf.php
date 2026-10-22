<?php
/**
 * PDF расчёта террасы для заявки из калькулятора (/info/kalkulyator-terrasy/, Ирина, 20.10.2026).
 *
 * Подключается только из /local/ajax/nd_terrace_calc_lead.php (в init.php не входит).
 * tFPDF — из модуля sale (им Битрикс печатает счета), шрифт PT Sans — из модуля main,
 * кэш метрик шрифта пишется в /upload/tmp/nd_terrace_calc_fonts/.
 */

/**
 * @param string[] $params строки «Терраса: …», «Укладка: …»
 * @param array    $rows   [['name', 'art' (артикул, может не быть), 'note', 'qty', 'price' => float|null, 'sum' => float|null], …]
 * @param float    $total  итог по позициям с ценой
 * @param string   $plan   JPEG плана (двоичные данные) или ''
 * @return string содержимое PDF или '' при ошибке
 */
function ndTerraceCalcPdf(array $params, array $rows, $total, $plan, $name, $page, $title = 'Расчёт террасы')
{
	if (!defined('FPDF_FONTPATH')) {
		define('FPDF_FONTPATH', $_SERVER['DOCUMENT_ROOT'].'/upload/tmp/nd_terrace_calc_fonts/');
	}
	if (!defined('_SYSTEM_TTFONTS')) {
		define('_SYSTEM_TTFONTS', $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/install/fonts/');
	}
	if (!is_dir(FPDF_FONTPATH)) {
		@mkdir(FPDF_FONTPATH, defined('BX_DIR_PERMISSIONS') ? BX_DIR_PERMISSIONS : 0755, true);
	}
	// tFPDF кэширует метрики в FPDF_FONTPATH и со второго раза ищет сам .ttf там же — кладём копию рядом
	foreach (array('pt_sans-regular.ttf', 'pt_sans-bold.ttf') as $font) {
		if (!file_exists(FPDF_FONTPATH.$font) && file_exists(_SYSTEM_TTFONTS.$font)) {
			@copy(_SYSTEM_TTFONTS.$font, FPDF_FONTPATH.$font);
		}
	}
	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/general/tfpdf/tfpdf.php');

	$money = function ($v) {
		return number_format(round($v), 0, ',', ' ').' руб.';
	};

	try {
		$pdf = new tFPDF('P', 'mm', 'A4');
		$pdf->SetAutoPageBreak(true, 15);
		$pdf->SetMargins(15, 15, 15);
		$pdf->AddFont('PT', '', 'pt_sans-regular.ttf', true);
		$pdf->AddFont('PT', 'B', 'pt_sans-bold.ttf', true);
		$pdf->AddPage();

		$pdf->SetTextColor(200, 22, 29);
		$pdf->SetFont('PT', 'B', 18);
		$pdf->Cell(0, 8, 'LATITUDO', 0, 1);
		$pdf->SetTextColor(110, 106, 101);
		$pdf->SetFont('PT', '', 9);
		$pdf->Cell(0, 5, 'latitudo.ru · +7 (495) 135-93-05 · Террасы, заборы, фасады', 0, 1);
		$pdf->Ln(4);

		$pdf->SetTextColor(28, 27, 26);
		$pdf->SetFont('PT', 'B', 16);
		$pdf->Cell(0, 8, $title, 0, 1);
		$pdf->SetFont('PT', '', 9);
		$pdf->SetTextColor(110, 106, 101);
		$pdf->Cell(0, 5, 'от '.date('d.m.Y').($name !== '' ? ' · для: '.$name : ''), 0, 1);
		$pdf->Ln(2);

		$pdf->SetTextColor(28, 27, 26);
		$pdf->SetFont('PT', '', 10);
		foreach ($params as $line) {
			$pdf->MultiCell(0, 5, $line);
		}
		$pdf->Ln(3);

		if ($plan !== '') {
			$tmp = tempnam(sys_get_temp_dir(), 'ndtc');
			file_put_contents($tmp, $plan);
			$size = @getimagesize($tmp);
			if ($size && $size[0] > 0 && $size[2] === IMAGETYPE_JPEG) {
				$w = 180;
				$h = $w * $size[1] / $size[0];
				if ($h > 100) { $h = 100; $w = $h * $size[0] / $size[1]; }
				$pdf->Image($tmp, 15 + (180 - $w) / 2, $pdf->GetY(), $w, $h, 'JPG');
				$pdf->SetY($pdf->GetY() + $h + 4);
			}
			@unlink($tmp);
		}

		// таблица сметы
		$cols = array(98, 22, 30, 30);
		$pdf->SetFont('PT', 'B', 9);
		$pdf->SetFillColor(245, 244, 242);
		$pdf->Cell($cols[0], 7, 'Позиция', 0, 0, 'L', true);
		$pdf->Cell($cols[1], 7, 'Кол-во', 0, 0, 'R', true);
		$pdf->Cell($cols[2], 7, 'Цена', 0, 0, 'R', true);
		$pdf->Cell($cols[3], 7, 'Сумма', 0, 1, 'R', true);

		// перенос текста по словам под ширину колонки (шрифт должен быть уже выставлен)
		$wrap = function ($text, $width) use ($pdf) {
			$lines = array();
			$cur = '';
			foreach (preg_split('/\s+/u', $text) as $word) {
				$try = $cur === '' ? $word : $cur.' '.$word;
				if ($cur !== '' && $pdf->GetStringWidth($try) > $width - 2) {
					$lines[] = $cur;
					$cur = $word;
				} else {
					$cur = $try;
				}
			}
			if ($cur !== '') $lines[] = $cur;
			return $lines ?: array('');
		};

		$pdf->SetDrawColor(222, 218, 213);
		foreach ($rows as $row) {
			$pdf->SetFont('PT', '', 10);
			$nameLines = $wrap($row['name'], $cols[0]);
			$pdf->SetFont('PT', '', 8);
			$noteLines = $row['note'] !== '' ? $wrap($row['note'], $cols[0]) : array();
			if (($row['art'] ?? '') !== '') {
				array_unshift($noteLines, 'арт. '.$row['art']); // артикул — первой серой строкой под названием (22.10.2026)
			}
			$h = count($nameLines) * 5 + count($noteLines) * 4 + 3;
			if ($pdf->GetY() + $h > 282) {
				$pdf->AddPage();
			}
			$y = $pdf->GetY();
			$pdf->Line(15, $y, 195, $y);
			$pdf->SetXY(15, $y + 1.5);
			$pdf->SetFont('PT', '', 10);
			$pdf->SetTextColor(28, 27, 26);
			foreach ($nameLines as $l) {
				$pdf->Cell($cols[0], 5, $l, 0, 2);
			}
			$pdf->SetFont('PT', '', 8);
			$pdf->SetTextColor(110, 106, 101);
			foreach ($noteLines as $l) {
				$pdf->Cell($cols[0], 4, $l, 0, 2);
			}
			$pdf->SetTextColor(28, 27, 26);
			$pdf->SetXY(15 + $cols[0], $y + 1.5);
			$pdf->SetFont('PT', 'B', 10);
			$pdf->Cell($cols[1], 5, $row['qty'], 0, 0, 'R');
			$pdf->SetFont('PT', '', 10);
			$pdf->Cell($cols[2], 5, $row['price'] !== null ? $money($row['price']) : '—', 0, 0, 'R');
			$pdf->Cell($cols[3], 5, $row['sum'] !== null ? $money($row['sum']) : '—', 0, 0, 'R');
			$pdf->SetY($y + $h);
		}
		$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
		$pdf->Ln(2);
		$pdf->SetFont('PT', 'B', 12);
		$pdf->Cell($cols[0] + $cols[1] + $cols[2], 7, 'Итого по позициям с ценой', 0, 0);
		$pdf->Cell($cols[3], 7, $money($total), 0, 1, 'R');
		$pdf->Ln(4);

		$pdf->SetFont('PT', '', 8);
		$pdf->SetTextColor(110, 106, 101);
		$pdf->MultiCell(0, 4, 'Расчёт ориентировочный: сделан онлайн-калькулятором на '.$page.' по ценам каталога сайта. '
			.'Стык двух досок приходится на лагу, под стыком ставится двойная лага — она учтена в смете. '
			.'Точное количество и стоимость подтвердит менеджер.');

		return $pdf->Output('', 'S');
	} catch (\Throwable $e) {
		return '';
	}
}
