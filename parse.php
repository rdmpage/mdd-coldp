<?php

// Augment citations

error_reporting(E_ALL);

$pdo = new PDO('sqlite:mdd.db');


//----------------------------------------------------------------------------------------
function do_query($sql)
{
	global $pdo;
	
	$stmt = $pdo->query($sql);

	$data = array();

	while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

		$item = new stdclass;
		
		$keys = array_keys($row);
	
		foreach ($keys as $k)
		{
			if ($row[$k] != '')
			{
				$item->{$k} = $row[$k];
			}
		}
	
		$data[] = $item;
	
	
	}
	
	return $data;	
}

//----------------------------------------------------------------------------------------
function get($url, $format = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
	if ($format != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));	
	}
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	curl_close($ch);
	
	return $response;
}



$sql = 'SELECT DISTINCT authoritySpeciesLink FROM `names` WHERE authoritySpeciesLink IS NOT NULL 
AND (authoritySpeciesLink NOT LIKE "%biodiversitylibrary.org%" AND authoritySpeciesLink NOT LIKE "%.pdf")';

$sql = 'SELECT DISTINCT authoritySpeciesLink FROM `names` WHERE authoritySpeciesLink LIKE "%/doi/%" AND doi IS NULL';
$sql = 'SELECT DISTINCT authoritySpeciesLink FROM `names` WHERE authoritySpeciesLink LIKE "%jstor.org%"';
$sql = 'SELECT DISTINCT authoritySpeciesLink FROM `names` WHERE authoritySpeciesLink LIKE "%academic.oup.com%" AND doi IS NULL';


//$sql = 'SELECT DISTINCT authoritySpeciesLink FROM `names` WHERE authoritySpeciesCitation LIKE "% Acta Chiropterologica%" AND doi IS NULL';
//$sql = 'SELECT DISTINCT authoritySpeciesLink FROM `names` WHERE authoritySpeciesCitation LIKE "% of Mammalogy%" AND doi IS NULL';

$sql = 'SELECT DISTINCT authoritySpeciesCitation FROM `names` WHERE authoritySpeciesCitation LIKE "%doi.org%" AND doi IS NULL';
$sql = 'SELECT DISTINCT authoritySpeciesCitation FROM `names` WHERE authoritySpeciesCitation LIKE "%doi:%" AND doi IS NULL';

// BHL
$sql = 'SELECT DISTINCT authoritySpeciesLink FROM `names` WHERE authoritySpeciesLink LIKE "%biodiversitylibrary.org/page/%" AND bhl IS NULL';
$sql = 'SELECT DISTINCT authoritySpeciesLink FROM `names` WHERE authoritySpeciesLink LIKE "%biodiversitylibrary.org/item/%" AND bhl IS NULL';

// More DOI fixes
$sql = 'SELECT DISTINCT authoritySpeciesLink, doi FROM `names` WHERE doi LIKE "%?%"';


// Handles to DOIs
$sql = 'SELECT DISTINCT authoritySpeciesLink, handle FROM `names` WHERE handle LIKE "10088%" AND doi IS NULL';




$query_result = do_query($sql);

$rows = array();

foreach ($query_result as $data)
{
	//print_r($data);
	
	if (isset($data->authoritySpeciesLink))
	{
	
		echo "-- " . $data->authoritySpeciesLink . "\n";
		
		if (0)
		{
			if (isset($data->doi))
			{
				if (preg_match('/(.*)\?/', $data->doi, $m))
				{
				 	echo 'UPDATE names SET doi="' . $m[1] . '" WHERE doi="' . $data->doi . '";' . "\n";
				}
			}		
		}
		
		if (1)
		{
			if (isset($data->handle))
			{
				$url = 'http://localhost/citation-matching/api/meta.php?URL=' . urlencode('http://hdl.handle.net/' . $data->handle);
	
				$json = get($url);
	
				$doc = json_decode($json);
				
				//print_r($doc);
	
				if (isset($doc->DOI))
				{
					 echo 'UPDATE names SET doi="' . $doc->DOI . '" WHERE authoritySpeciesLink="' . $data->authoritySpeciesLink . '";' . "\n";
				}
			}		
		}
		
	
		if (0)
		{
			$url = 'http://localhost/citation-matching/api/meta.php?URL=' . urlencode($data->authoritySpeciesLink);
	
			$json = get($url);
	
			$doc = json_decode($json);
	
			if (isset($doc->DOI))
			{
				 echo 'UPDATE names SET doi="' . $doc->DOI . '" WHERE authoritySpeciesLink="' . $data->authoritySpeciesLink . '";' . "\n";
			}
		}
	
		if (0)
		{
			if (preg_match('/handle\/(\d+\/\d+)\//', $data->authoritySpeciesLink, $m))
			{
				 echo 'UPDATE names SET handle="' . $m[1] . '" WHERE authoritySpeciesLink="' . $data->authoritySpeciesLink . '";' . "\n";		
			}
		}
		
		if (0)
		{
			if (preg_match('/jstor.org\/stable\/(\d+)/', $data->authoritySpeciesLink, $m))
			{
				 echo 'UPDATE names SET jstor="' . $m[1] . '" WHERE authoritySpeciesLink="' . $data->authoritySpeciesLink . '";' . "\n";		
			}
		}
		
		// simple BHL page link
		if (0)
		{
			if (preg_match('/biodiversitylibrary.org\/page\/(\d+)$/', $data->authoritySpeciesLink, $m))
			{
				 echo 'UPDATE names SET bhl="' . $m[1] . '" WHERE authoritySpeciesLink="' . $data->authoritySpeciesLink . '";' . "\n";		
			}
		}

		// offset BHL link
		if (0)
		{
			if (preg_match('/biodiversitylibrary.org\/(item|page)\/\d+#page/', $data->authoritySpeciesLink, $m))
			{
				$url = 'http://localhost/citation-matching/api/bhlurlpage.php?URL=' . urlencode($data->authoritySpeciesLink);
	
				$json = get($url);
	
				$doc = json_decode($json);
	
				if (isset($doc->PAGEID))
				{
					 echo 'UPDATE names SET bhl="' . $doc->PAGEID . '" WHERE authoritySpeciesLink="' . $data->authoritySpeciesLink . '";' . "\n";
				}
			}
		}
		
		
	}
	
	if (isset($data->authoritySpeciesCitation))
	{
	
		echo "-- " . $data->authoritySpeciesCitation . "\n";
	
	
		if (0)
		{
			if (preg_match('/https?:\/\/(dx\.)?doi.org\/(?<doi>.*)\b/', $data->authoritySpeciesCitation, $m))
			{
				 echo 'UPDATE names SET doi="' . $m['doi'] . '" WHERE authoritySpeciesCitation="' . $data->authoritySpeciesCitation . '";' . "\n";		
			}
		}

		if (1)
		{
			if (preg_match('/doi:\s*(?<doi>10\..*)\b/', $data->authoritySpeciesCitation, $m))
			{
				 echo 'UPDATE names SET doi="' . $m['doi'] . '" WHERE authoritySpeciesCitation="' . $data->authoritySpeciesCitation . '";' . "\n";		
			}
		}

		
	}
	
}

?>
