<?php

require('functions.php');
$functions = new functions();

$population_size = 200;
$dimensions = 30;
$bounds = 30;
$max_evaluations = 200000;
$e = exp(1);
$pi = pi();

$fitness = function($genome) use($e, $pi) {
	$squared_sum = $cosine_sum = 0;
	$genome_size = count($genome);
	for($i = 0; $i < $genome_size; $i++) {
		$squared_sum += ($genome[$i] * $genome[$i]);
		$cosine_sum += cos(2 * $pi * $genome[$i]);
	}
	$one_over_size = 1 / $genome_size;
	return (-20 * exp(-0.2 * sqrt($one_over_size) * ($squared_sum))
			- exp($cosine_sum / $genome_size) + 20 + $e);
};

/* population initialization
 */
$makeMember = function() use($dimensions, $fitness, $functions, $bounds) {
	$genome = [];
	$sigmas = [];

	for($i = 0; $i < $dimensions; $i++) {
		$genome[] = $functions->randomFloat(-$bounds, $bounds);
		$sigmas[] = $functions->randomFloat(0, 1);
	}

	return [$genome, $fitness($genome), $sigmas];
};

$population = [$makeMember()];
for($x = 0; $x < $population_size; $x++) {
	$population[] = $makeMember();
}

/* generation iterator
 * pick 10 random children
 * use maybegaussian mutation
 * delete worst 10 members from population, add our 10 children
 */
$current_generation = 0;
for($current_generation; $current_generation < $max_evaluations; $current_generation++) {
	$random_children = [];
	// make an array of 10 members from 10 random keys from the population
	foreach(array_rand($population, 10) as $random_child_key) {
		array_push($random_children, $population[$random_child_key]);
	}
	// sort this array by their fitness, ascending
	array_multisort(array_column($random_children, 1), SORT_ASC, $random_children);

	$children = [];
	// mutate these selected with maybegaussian mutation, calculate their fitness
	// delete worst, but readd it if the child is worse than it was
	foreach($population as &$child) {
		$child = $functions->maybegaussian_mutation($child, $bounds);
		$child_fitness = $fitness($child[0]);
		//$worst_member = array_pop($population);
		$child[1] = $child_fitness;
		$children[] = $child;
		//array_unshift($population, $worst_member[1] < $child_fitness ? $worst_member : $child);
	}
	$population += $children;

	foreach($population as &$child) {
		// the number of wins
		$child[3] = 0;
		foreach($random_children as $opponent) {
			if($child[1] < $opponent[1]) $child[3] += 1;
		}
	}
	// sort the population by number of wins, ascending
	array_multisort(array_column($population, 3), SORT_ASC, $population);
	// remove the worst
	array_slice($population, $population_size);
}

// sort the population by fitness
array_multisort(array_column($population, 1), SORT_ASC, $population);

// pick the best member (sorted by fitness ascending)
$winner = $population[0];

$css = "
<style>
text {
	font-size: 7pt;
}
</style>
\n";

// build the page
echo "<!DOCTYPE HTML>
<html>
<head>
  <meta charset='UTF-8'>
</head>
<body>
$css
<div class='map'>
dimensions: $dimensions</br>
population: $population_size</br>
max evaluations: $max_evaluations</br>
evaluations: $current_generation</br></br>
genome: [" . implode(', ', $winner[0]) . "]</br>
fitness: $winner[1]</br>
</svg>
</div>
</body>
</html>
";
