<?php

/**
 * Converts number to german format.
 * @param $number
 * @return string
 */
function formatDecimalGerman($number)
{
	return str_replace(",0", "", number_format((float) $number, 1, ',', '.'));
}
