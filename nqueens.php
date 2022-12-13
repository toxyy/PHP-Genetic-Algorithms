<?php

require('functions.php');
$functions = new functions();

$board_size = 30;
$population_size = 10;
$max_evaluations = 10000;
// $first_member = {1, 2, 3, ..., $board_size}
$first_member = array_combine(range(0, $board_size - 1), range(1, $board_size));

/* nqueen fitness, add +1 for every checked queen, per queen
 * return fitness of a child
 * int
 */
$fitness = function($genome) {
	$fitness = 0;
	$genome_size = count($genome);
	for($x = 0; $x < $genome_size; $x++) {
		for($i = 0; $i < $genome_size; $i++) {
			// due to the representation, there can never be a queen on the same row/column
			if($x == $i) continue;
			// |x1 - x2| == |y1 - y2| for intersecting diagonals
			if(abs($i - $x) == abs($genome[$i] - $genome[$x])) $fitness++;
		}
	}
	return $fitness;
};

/* population initialization
 * add $population_size members to $population array, adding the first member to start
 * get each member my shuffling the first member {1, 2, 3, ..., $board_size} each time
 * this ensures a random initialization
 * key == 0: genome, key == 1: fitness
 */
$population = [[$first_member, $fitness($first_member)]];
for($x = 1; $x < $population_size; $x++) {
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
	$children = $functions->order_crossover($mother, $father);
	// sort the population
	array_multisort(array_column($population, 1), SORT_ASC, $population);
	// mutate these two children with swap mutation, calculate their fitness
	// delete worst, but readd it if the child is worse than it was
	foreach($children as &$child) {
		$child = $functions->swap_mutation($child);
		$child_fitness = $fitness($child);
		$worst_member = array_pop($population);
		array_unshift($population, $worst_member[1] < $child_fitness ? $worst_member : [$child, $child_fitness]);
		if($child_fitness == 0) break 2;
	}
	// re-shuffling doesn't affect anything, it seems
	//shuffle($population);
}
// for making the chess board
$color = function($int) {
	return $int & 1 ? 'black' : 'white';
};

// for adding queen pieces to chess board
$add_queen = function($row, $col, $child) {
	return $child[$col] == $row + 1 ? 'piece' : '';
};

$chess_board = $columns = '';
// sort the population by fitness
array_multisort(array_column($population, 1), SORT_ASC, $population);

// pick the best member (sorted by fitness ascending)
$winner = $population[0];

// build the chess board html
for($col = 0; $col < $board_size; $col++):
	$columns = '';
	for($row = 0; $row < $board_size; $row++): $columns .=
		<<<COLUMN
<div class="tile {$color($row + $col)} {$add_queen($col, $row, $winner[0])}">
</div>\n
COLUMN;
	endfor;
	$chess_board .= "<div class='row'>$columns</div>";
endfor;

$css = "
<style>
.board {
	white-space: nowrap;
}
.tile {
	min-width: 20px;
	min-height: 20px;
	max-width: 20px;
	max-height: 20px;
	line-height: 20px;
	border: solid 1px black;
	text-align: center;
}
.row {
	display: flex;
}
.white {
	background-color: white;
	color: black;
}
.black {
	background-color: black;
	color: white;
}
.piece:before {
	content: '\\2655';
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
<div class='board'>
size: $board_size</br>
population: $population_size</br>
max evaluations: $max_evaluations</br></br>
genome: [" . implode(', ', $winner[0]) . "]</br>
fitness: $winner[1]</br>
evaluations: $current_generation
$chess_board
</div>
</body>
</html>
";
