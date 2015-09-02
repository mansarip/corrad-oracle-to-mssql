<?php

include('class/SqlFormatter.php');

// # MAIN FUNCTION
function RepairSQL($str) {

	// selagi ada /*
	//while (strpos($str, '/*') !== false) { $str = RemoveSQLInsideBlockComment($str); }

	$str = ReplaceSingleQuoteEscape($str);
	$str = ReplaceConcatEscape($str);

	// selagi ada PIPE
	while (strpos($str, '||') !== false) { $str = ReplacePIPEConcat($str); }

	// selagi ada TO_CHAR
	while (stripos($str, 'TO_CHAR(') !== false) { $str = ReplaceTO_CHAR($str); }

	// selagi ada NVL
	while (strpos($str, 'NVL(') !== false) { $str = ReplaceNVL($str); }
	while (strpos($str, 'nvl(') !== false) { $str = ReplaceNVL($str); }

	// selagi ada SYSDATE
	while (strpos($str, 'SYSDATE') !== false) { $str = ReplaceSYSDATE($str); }
	while (strpos($str, 'sysdate') !== false) { $str = ReplaceSYSDATE($str); }

	// selagi ada TO_DATE
	while (strpos($str, 'TO_DATE(') !== false) { $str = ReplaceTO_DATE($str); }
	while (strpos($str, 'to_date(') !== false) { $str = ReplaceTO_DATE($str); }

	// selagi ada TO_NUMBER
	while (stripos($str, 'TO_NUMBER(') !== false) { $str = ReplaceTO_NUMBER($str); }

	// selagi ada ADD_MONTHS
	while (stripos($str, 'ADD_MONTHS(') !== false) { $str = ReplaceADD_MONTHS($str); }

	// selagi ada FROM DUAL
	while (stripos($str, 'from dual') !== false) { $str = ReplaceFROMDUAL($str); }

	// selagi ada DECODE(
	while (stripos($str, 'DECODE(') !== false) { $str = ReplaceDECODE($str); }

	$str = RestoreConcatEscape($str);
	$str = RestoreSingleQuoteEscape($str);

	return $str;
}

// replace DECODE
function ReplaceDECODE($sql) {
	$offset = stripos($sql, 'decode(');
	$target = substr($sql, $offset);

	$openingbracketpos = strpos($target, '(');
	$target = substr($target, $openingbracketpos);

	$length = strlen($target);
	$bracket = 0;

	for ($c = 0; $c < $length; $c++) {
		if ($target[$c] == '(') $bracket++;
		if ($target[$c] == ')') $bracket--;
		if ($bracket == 0) break;
	}

	$target = substr($target, 0, $c + 1);
	$target = trim($target); #cp

	// buang kurungan ( ) decode
	$content = substr($target, 1);
	$content = substr($content, 0, -1); #cp

	$statements = GetStatementsFromDecodeContent($content);

	$target = 'decode' . $target;

	$final = "CASE ".$statements[0]." WHEN ".$statements[1]." THEN ".$statements[2]." ELSE ".$statements[3]." END";

	$final = str_ireplace($target, $final, $sql);

	return $final;
}

// replace ADD_MONTHS
function ReplaceADD_MONTHS($sql) {
	// target
	$offset = stripos($sql, 'add_months(');
	$target = substr($sql, $offset);

	$openingbracketpos = stripos($target, '(');
	$target = substr($target, $openingbracketpos);
	$length = strlen($target);
	$bracket = 0;

	for ($c = 0; $c < $length; $c++) {
		if ($target[$c] == '(') $bracket++;
		if ($target[$c] == ')') $bracket--;

		if ($bracket == 0) break;
	}

	$target = substr($target, 0, $c + 1);
	$target = 'add_months' . $target;
	$target = trim($target); #cp

	// months
	$months = strrchr($target, ',');
	$months = substr($months, 1);
	$months = substr($months, 0, -1);
	$months = trim($months); #cp

	// convert month (string) kepada number
	// dengan cara buang quote


	// variable
	$comma = strrchr($target, ',');
	$commapos = stripos($target, $comma);
	$variable = substr($target, 0, $commapos);
	$variable = substr($variable, 11);
	$variable = trim($variable); #cp

	// final
	$final = str_ireplace($target, '{{{DATEADD(month, '. $months .', '.$variable.')}}}', $sql);
	$final = trim($final);

	return $final;
}

// replace TO_NUMBER
function ReplaceTO_NUMBER($sql, $type='INT') {
	$offset = stripos($sql, 'to_number(');
	$target = substr($sql, $offset);

	$openingbracketpos = strpos($target, '(');
	$target = substr($target, $openingbracketpos);

	$length = strlen($target);
	$bracket = 0;

	for ($c = 0; $c < $length; $c++) {
		if ($target[$c] == '(') $bracket++;
		if ($target[$c] == ')') $bracket--;
		if ($bracket == 0) break;
	}

	$closingbracketpos = $c;
	$target = substr($target, 0, $closingbracketpos + 1);

	$variable = substr($target, 1);
	$variable = substr($variable, 0, -1);

	$target = 'to_number' . $target;

	$final = '{{{CONVERT('. $type .', '. $variable .')}}}';
	$final = str_replace($target, $final, $sql);
	$final = trim($final);
	return $final;
}

// replace TO_DATE
function ReplaceTO_DATE($sql) {
	$offset = stripos($sql, 'TO_DATE(');

	$target = substr($sql, $offset);
	$openingbracketpos = stripos($target, '(');
	$target2 = substr($target, $openingbracketpos);

	$length = strlen($target2);
	$openbracket = 0;

	for ($c = 0; $c < $length; $c++) {
		if ($target2[$c] == '(') $openbracket++;
		if ($target2[$c] == ')') $openbracket--;

		if ($openbracket == 0) {
			break;
		}
	}

	$closingbracketpos = $c + strlen('TO_DATE');
	$target = substr($target, 0, $closingbracketpos + 1);

	$variable = str_ireplace('to_date(', '', $target);
	$commapos = strpos($variable, ",");
	$variable = substr($variable, 0, $commapos);
	$variable = trim($variable);

	$commapos = strpos($target, ",");
	$format = substr($target, $commapos + 1);
	$format = str_replace(")", "", $format);
	$format = str_replace("'", "", $format);
	$format = str_replace('"', "", $format);
	$format = trim($format);

	$final = "{{{CONVERT(DATETIME, ". $variable .", ". SQLServerDateStyle($format) .")}}}";
	$final = str_replace($target, $final, $sql);
	return $final;
}


// replace PIPE concat
function ReplacePIPEConcat($sql) {
	$final = str_ireplace('||', '{{{+}}}', $sql);
	return $final;
}

// replace SYSDATE
function ReplaceSYSDATE($sql) {
	$final = str_ireplace('SYSDATE', '{{{GETDATE()}}}', $sql);
	return $final;
}

// replace NVL
function ReplaceNVL($sql) {
	$final = str_ireplace('NVL(', '{{{ISNULL(}}}', $sql);
	return $final;
}

// replace from dual
function ReplaceFROMDUAL($sql) {
	$final = str_ireplace('from dual', '', $sql);
	return $final;
}

// replace TO_CHAR
function ReplaceTO_CHAR($sql) {

	// target
	$offset = stripos($sql, 'to_char(');
	$target = substr($sql, $offset);

	$openingbracketpos = strpos($target, '(');
	$target = substr($target, $openingbracketpos);
	$length = strlen($target);
	$bracket = 0;

	for ($c = 0; $c < $length; $c++) {
		if ($target[$c] == '(') $bracket++;
		if ($target[$c] == ')') $bracket--;
		if ($bracket == 0) break;
	}

	$target = substr($target, 0, $c + 1);
	$target = 'to_char' . $target; # cp

	// format
	$format = substr($target, 0, -1);
	$format = trim($format);
	$length = strlen($format);
	$quote = substr($format, -1);

	for ($c = $length-2; $c > 0; $c--) {
		if ($format[$c] == $quote) break;
	}

	$openingquoteformatpos = $c;

	$format = substr($target, $openingquoteformatpos);
	$format = str_replace(')', "", $format);
	$format = trim($format);
	$format = str_replace('"', "", $format);
	$format = str_replace("'", "", $format); # cp

	// variable
	$variable = substr($target, 0, $openingquoteformatpos);
	$variable = trim($variable);
	$variable = substr($variable, 8);
	$variable = substr($variable, 0, -1);
	$variable = trim($variable); # cp

	// final
	$style = SQLServerDateStyle($format);
	
	if ($variable == 'sysdate' && $style == 'MM') {
		$final = '{{{CONVERT(VARCHAR(2), DATEPART(MM, GETDATE()))}}}';
	}
	else {
		$final = '{{{CONVERT(VARCHAR('. strlen($format) .'), '. $variable .', '. $style .')}}}';
	}

	$final = str_ireplace($target, $final, $sql);
	return $final;
}

function SQLServerDateStyle($format) {
	$format = strtolower($format);

	if     ($format == 'dd-mm-yyyy') { $style = 105; }
	elseif ($format == 'yyyy') { $style = 112; }
	elseif ($format == 'rrrr') { $style = 112; }
	elseif ($format == 'mm') { $style = 'MM'; }
	return $style;
}

function Highlight($sql) {
	$sql = str_replace('{{{', '<span class="hl">', $sql);
	$sql = str_replace('}}}', '</span>', $sql);
	return $sql;
}

function RemoveCurlyBraces($sql) {
	$sql = str_replace('{{{', '', $sql);
	$sql = str_replace('}}}', '', $sql);
	return $sql;
}

function ExtractQueryFromBL($bl, $type) {
	$collection = array();

	if ($type == 'select') {
		$pattern = 'select ';

	} elseif ($type == 'insert') {
		$pattern = 'insert into ';

	} elseif ($type == 'udpate') {
		$pattern = 'update ';

	} elseif ($type == 'delete') {
		$pattern = 'delete ';
	}

	while (stripos($bl, $pattern) !== false) {
	
		$selectpos = stripos($bl, $pattern);

		// cari quote samada double atau single
		for ($c = $selectpos; $c > 0; $c--) {
			if ($bl[$c] == '"' || $bl[$c] == "'") {
				$openingbracket = $bl[$c];
				$openingbracketpos = $c;
				break;
			}
		}

		$query = substr($bl, $openingbracketpos);
		$query = trim($query);

		// cari pos penutup quote
		$length = strlen($query);

		for ($c = 1; $c < $length; $c++) {
			if ($query[$c] == $openingbracket && $query[$c-1] != "\\") {
				$closingbracketpos = $c;
				break;
			}
		}

		$query = substr($query, 0, $closingbracketpos);
		$query = trim($query);
		$query = substr($query, 1);

		$bl = str_ireplace($query, '==========', $bl);

		$collection[] = $query;
	}

	return $collection;
}

function ReplaceSingleQuoteEscape($str) {
	$length = strlen($str);
	
	for ($n = 0; $n < $length; $n++) {
		if ($str[$n] == "'" && $str[$n+1] == "'") {
			$str[$n] = '~';
			$str[$n+1] = '~';
		}
	}

	return $str;
}

function RestoreSingleQuoteEscape($str) {
	$str = str_replace('~~', "''", $str);
	return $str;
}

function ReplaceConcatEscape($str) {
	$str = str_replace("'+'", "[###]", $str);
	$str = str_replace("' + '", " [###] ", $str);
	$str = str_replace("'  +  '", "  [###]  ", $str);
	$str = str_replace("'   +   '", "   [###]   ", $str);

	$str = str_replace("'+", "[###", $str);
	$str = str_replace("' + ", " [### ", $str);
	$str = str_replace("'  +  ", "  [###  ", $str);
	$str = str_replace("'   +   ", "   [###   ", $str);

	$str = str_replace("+'", "###]", $str);
	$str = str_replace(" + '", " ###] ", $str);
	$str = str_replace("  +  '", "  ###]  ", $str);
	$str = str_replace("   +   '", "   ###]   ", $str);

	$str = str_replace('"+"', '{###}', $str);
	$str = str_replace('" + "', ' {###} ', $str);
	$str = str_replace('"  +  "', '  {###}  ', $str);
	$str = str_replace('"   +   "', '   {###}   ', $str);

	return $str;
}

function RestoreConcatEscape($str) {
	$str = str_replace("[###]", "'+'", $str);
	$str = str_replace(" [###] ", "' + '", $str);
	$str = str_replace("  [###]  ", "'  +  '", $str);
	$str = str_replace("   [###]   ", "'   +   '", $str);

	$str = str_replace("[###", "'+", $str);
	$str = str_replace(" [### ", "' + ", $str);
	$str = str_replace("  [###  ", "'  +  ", $str);
	$str = str_replace("   [###   ", "'   +   ", $str);

	$str = str_replace("###]", "+'", $str);
	$str = str_replace(" ###] ", " + '", $str);
	$str = str_replace("  ###]  ", "  +  '", $str);
	$str = str_replace("   ###]   ", "   +   '", $str);

	$str = str_replace('{###}', '"+"', $str);
	$str = str_replace(' {###} ', '" + "', $str);
	$str = str_replace('  {###}  ', '"  +  "', $str);
	$str = str_replace('   {###}   ', '"   +   "', $str);
	$str = str_replace('    {###}    ', '"    +    "', $str);

	return $str;
}

function GetStatementsFromDecodeContent($content) {
	$statements = array();
	
	do {
		// kali kedua (dan seterusnya) loop akan dimulai dengan comma
		// jadi buang dulu comma tersebut
		if (substr($content, 0, 1) == ',') $content = substr($content, 1);

		$content = trim($content);

		// jika mula dengan (
		if (substr($content, 0, 1) == '(') {
			$length = strlen($content);
			$bracket = 0;

			for ($n = 0; $n < $length; $n++) {
				if ($content[$n] == '(') $bracket++;
				if ($content[$n] == ')') $bracket--;
				if ($bracket == 0) break;
			}

			$statement = substr($content, 0, $n + 1);
			$statements[] = $statement;
			$content = str_ireplace($statement, '', $content);
			$content = trim($content);

		// jika mula dengan ~~'
		} elseif (substr($content, 0, 3) == "~~'") {
			$content = substr($content, 3);
			$length = strlen($content);

			for ($c = 0; $c < $length; $c++) {
				if ($content[$c] == "'" && $content[$c+1] != "'") break;
			}

			$statement = substr($content, 0, $c + 1);
			$statement = "~~'" . $statement;
			$statement = trim($statement);
			$statements[] = $statement;

			$content = "~~'" . $content;
			$content = str_ireplace($statement, '', $content);
			$content = trim($content);

		// jika mula dengan ~~ sahaja
		} elseif (substr($content, 0, 2) == "~~") {
			$statement = "~~";
			$statements[] = $statement;

			$content = substr($content, 2);
			$content = trim($content);

		// jika mula dengan '''
		} elseif (substr($content, 0, 3) == "'''") {
			$content = substr($content, 3);
			$length = strlen($content);

			for ($c = 0; $c < $length; $c++) {
				if ($content[$c] == "'" && $content[$c+1] != "'") break;
			}

			$statement = substr($content, 0, $c + 1);
			$statement = "'''" . $statement;
			$statement = trim($statement);
			$statements[] = $statement;

			$content = "'''" . $content;
			$content = str_ireplace($statement, '', $content);
			$content = trim($content);

		// jika mula dengan ''
		} elseif (substr($content, 0, 2) == "''") {
			$statement = "''";
			$statements[] = $statement;

			$content = substr($content, 2);
			$content = trim($content);

		// jika mula dengan '
		} elseif (substr($content, 0, 1) == "'") {
			$length = strlen($content);
			
			for ($c = 1; $c < $length; $c++) {
				if ($content[$c] == "'" && $content[$c+1] != "'") {
					break;
				}
			}

			$statement = substr($content, 0, $c + 1);
			$statements[] = $statement;

			$content = str_ireplace($statement, '', $content);
			$content = trim($content);
				
		// jika lain2
		} else {
			// dapatkan satu perkataan terus
			// berhenti bila ada space atau comma
			$length = strlen($content);
			
			for ($c = 0; $c < $length; $c++) {
				if ($content[$c] == ',' || $content[$c] == ' ') break;
			}

			$statement = substr($content, 0, $c);
			$statement = trim($statement);
			$statements[] = $statement;

			$statementlength = strlen($statement);

			$content = substr($content, $statementlength);
			$content = trim($content);
		}

	} while(substr($content, 0, 1) == ','); // selagi jumpa comma

	return $statements;
}

function RemoveSQLInsideBlockComment($sql) {
	$offset = strpos($sql, "/*");
	$target = substr($sql, $offset);

	$length = strlen($target);

	for ($c = 0; $c < $length; $c++) {
		if ($target[$c] == '*' && $target[$c + 1] == '/') break;
	}

	$target = substr($target, 0, $c + 2); #cp
	$sql = str_ireplace($target, '', $sql);

	return $sql;
}

?>