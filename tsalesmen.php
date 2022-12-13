<?php

require('functions.php');
$functions = new functions();

$population_size = 35;
$max_evaluations = 175000;

$cities = [
	[9860, 	14152],	[9396, 	14616],	[11252,	14848],	[11020, 13456],	[9512, 	15776],
	[10788, 13804],	[10208, 14384],	[11600,	13456],	[11252, 14036],	[10672, 15080],
	[11136, 14152],	[9860, 	13108],	[10092,	14964],	[9512, 	13340],	[10556, 13688],
	[9628, 	14036],	[10904, 13108],	[11368,	12644],	[11252, 13340],	[10672, 13340],
	[11020, 13108],	[11020, 13340],	[11136,	13572],	[11020, 13688],	[8468, 	11136],
	[8932, 	12064],	[9512, 	12412],	[7772, 	11020],	[8352, 	10672],	[9164, 	12876],
	[9744, 	12528],	[8352, 	10324],	[8236, 	11020],	[8468, 	12876],	[8700, 	14036],
	[8932, 	13688],	[9048, 	13804], [8468, 	12296],	[8352, 	12644], [8236, 	13572],
	[9164, 	13340],	[8004, 	12760], [8584, 	13108],	[7772, 	14732], [7540, 	15080],
	[7424, 	17516],	[8352, 	17052], [7540, 	16820],	[7888, 	17168], [9744, 	15196],
	[9164, 	14964],	[9744, 	16240], [7888, 	16936],	[8236, 	15428], [9512, 	17400],
	[9164, 	16008],	[8700, 	15312], [11716, 16008],	[12992, 14964],	[12412,	14964],
	[12296, 15312],	[12528, 15196],	[15312,  6612],	[11716, 16124],	[11600, 19720],
	[10324, 17516],	[12412, 13340],	[12876, 12180],	[13688, 10904],	[13688, 11716],
	[13688, 12528],	[11484, 13224],	[12296, 12760],	[12064, 12528],	[12644, 10556],
	[11832, 11252],	[11368, 12296],	[11136, 11020],	[10556, 11948],	[10324, 11716],
	[11484,  9512],	[11484,  7540],	[11020,	 7424],	[11484,  9744],	[16936, 12180],
	[17052, 12064],	[16936, 11832],	[17052, 11600],	[13804, 18792],	[12064, 14964],
	[12180, 15544],	[14152, 18908],	[5104, 	14616],	[6496, 	17168],	[5684, 	13224],
	[15660, 10788],	[5336, 	10324], [812, 	 6264],	[14384, 20184],	[11252, 15776],
	[9744,   3132],	[10904,  3480],	[7308, 	14848],	[16472, 16472],	[10440, 14036],
	[10672, 13804],	[1160, 	18560], [10788,	13572],	[15660, 11368],	[15544, 12760],
	[5336, 	18908],	[6264, 	19140], [11832,	17516],	[10672, 14152],	[10208, 15196],
	[12180, 14848],	[11020, 10208],	[7656, 	17052],	[16240,  8352],	[10440, 14732],
	[9164, 	15544],	[8004, 	11020],	[5684, 	11948],	[9512, 	16472], [13688, 17516],
	[11484,  8468],	[3248, 	14152]
];

$number_cities = count($cities);

// $first_member = {0, 1, 2, ..., $number_cities - 1}, each element is the city node# - 1, as arrays start at 0
$first_member = array_combine(range(0, $number_cities - 1), range(0, $number_cities - 1));

$fitness = function($genome) use($cities, $number_cities) {
	$total_distance = 0;
	$genome_size = count($genome);
	for($i = 0; $i < $genome_size; $i++) {
		$this_city = $cities[$genome[$i]];
		$next_city = $cities[$genome[($i + 1) % $genome_size]];
		$total_distance += sqrt(
			pow($next_city[0] - $this_city[0], 2)
			+ pow($next_city[1] - $this_city[1], 2)
		);
	}
	return $total_distance;
};

/* population initialization
 * add $population_size members to $population array, adding the first member to start
 * get each member my shuffling the first member {1, 2, 3, ..., $board_size} each time
 * this ensures a random initialization
 */
$population = [[$first_member, $fitness($first_member)]];
for($x = 0; $x < $population_size; $x++) {
	shuffle($first_member);
	array_push($population, [$first_member, $fitness($first_member)]);
}

/* generation iterator
 * pick 5 random children, make the best 2 parents
 * use order crossover, then swap mutation to make 2 children
 * delete worst 2 members from population, add our 2 children
 */
$current_generation = 0;
for($current_generation; $current_generation < $max_evaluations; $current_generation++) {
	$random_children = [];
	// make an array of 5 members from 5 random keys from the population
	foreach(array_rand($population, 5) as $random_child_key) {
		array_push($random_children, $population[$random_child_key]);
	}
	// sort this array by their fitness, ascending
	array_multisort(array_column($random_children, 1), SORT_ASC, $random_children);

	$mother = $random_children[0][0];
	$father = $random_children[1][0];
	// order crossover with the 2 best from the array of 5 random ones
	//$children = $functions->order_crossover($mother, $father);
	$children = [$functions->edge_recombination($mother, $father)];
	// sort the population
	array_multisort(array_column($population, 1), SORT_ASC, $population);
	// mutate these two children with swap mutation, calculate their fitness
	// delete worst, but readd it if the child is worse than it was
	foreach($children as &$child) {
		$child = $current_generation & 1 ? $functions->inversion_mutation($child) : $functions->insert_mutation($child);
		$child_fitness = $fitness($child);
		$worst_member = array_pop($population);
		array_unshift($population, $worst_member[1] < $child_fitness ? $worst_member : [$child, $child_fitness]);
	}
	// re-shuffling doesn't affect anything, it seems
	//shuffle($population);
}

// sort the population by fitness
array_multisort(array_column($population, 1), SORT_ASC, $population);

// pick the best member (sorted by fitness ascending)
$winner = $population[0];
//$first_member = array_combine(range(0, $number_cities - 1), range(0, $number_cities - 1));
//$winner = [$first_member, $fitness($first_member)];
$circles = $lines = '';
for($i = 0; $i < $number_cities; $i++) {
	$next_index = ($i + 1) % $number_cities;
	$current_member = $cities[$winner[0][$i]];
	$next_member = $cities[$winner[0][$next_index]];
	$current_x = $current_member[0] / 10;
	$current_y = $current_member[1] / 10;
	$next_x = $next_member[0] / 10;
	$next_y = $next_member[1] / 10;
	$circles .= "<circle cx='$current_x' cy='$current_y' r='4' fill='red' />\n
				<text x='" . (int) ($current_x - 1.5 * strlen($i * 2)) . "' y='" . (int) ($current_y + 11) . "' fill='black'>" . (int) ($winner[0][$i]+ 1) . "</text>\n";
	$lines .= "<line x1='$current_x' y1='$current_y' x2='$next_x' y2='$next_y' stroke-width='1' stroke='grey' />\n";
}

$css = "
<style>
text {
	font-size: 7pt;
}
</style>
\n";

// correct genome for correct city node values
foreach($winner[0] as &$gene) {
	$gene++;
}

// build the page
echo "<!DOCTYPE HTML>
<html>
<head>
  <meta charset='UTF-8'>
</head>
<body>
$css
<div class='map'>
size: $number_cities</br>
population: $population_size</br>
max evaluations: $max_evaluations</br>
evaluations: $current_generation</br></br>
genome: [" . implode(', ', $winner[0]) . "]</br>
fitness: $winner[1]</br>
<svg width='2100.0px' height='2100.0px' version='1.1' xmlns='http://www.w3.org/2000/svg'>
$circles
$lines
</svg>
</div>
</body>
</html>
";
