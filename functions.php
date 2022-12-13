<?php

class functions
{
	// https://www.php.net/manual/en/function.mt-getrandmax.php
	function randomFloat($min = 0, $max = 1) {
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}

	/* swap mutation
	 * returns genome with some values adjusted according to the formula in the slides
	 * on chapter 5, page 12
	 * maybegaussian_mutation(array(array(), array(), array())): array(array(), array(), array())
	 */
	function maybegaussian_mutation($child, $bounds) {
		//$child[0] is the genome array, $child[2] is the sigma array
		$size = count($child[0]);
		$mutation_rate = 0.01;
		$a = 0.18;

		for($i = 0; $i < $size; $i++) {
			if($this->randomFloat() < $mutation_rate) {
				//echo '<pre>';
				//print_r($child);
				// the random function breaks at later uses lol, thanks php
				$random = $this->randomFloat();
				$random2 = $this->randomFloat();

				$child[2][$i] = $child[2][$i] * (1 + $a * $random);
				$child[0][$i] = $child[0][$i] + ($child[2][$i] * $random2);
				if($child[0][$i] > $bounds) $child[0][$i] = $bounds;
				elseif($child[0][$i] < -$bounds) $child[0][$i] = -$bounds;
			}
		}

		return $child;
	}

	/* swap mutation
	 * returns genome with two genes swapped in position
	 * swap_mutation(array()): array()
	 */
	function swap_mutation($genome) {
		$mutation_points = array_rand($genome, 2);
		$temp = $genome[$mutation_points[0]];
		$genome[$mutation_points[0]] = $genome[$mutation_points[1]];
		$genome[$mutation_points[1]] = $temp;
		return $genome;
	}

	/* insert mutation
	 * returns genome with a gene moved left, adjacent to another selected gene
	 * insert_mutation(array()): array()
	 */
	function insert_mutation($genome) {
		$mutation_points = array_rand($genome, 2);
		// if randomly picked two adjacent points... no need to do anything
		if($mutation_points[0] + 1 == $mutation_points[1]) return $genome;
		array_splice($genome, $mutation_points[0] + 1, 0, array_splice($genome, $mutation_points[1], 1));
		return $genome;
	}

	/* inversion mutation
	 * returns genome with a random portion reversed, allowing wrapping (assuming end & start is connected)
	 * inversion_mutation(array()): array()
	 */
	function inversion_mutation($genome) {
		$parent_size = count($genome);
		$crossover_start = rand(0, $parent_size - 1);
		$crossover_length = rand(1, $parent_size);
		// this is needed to know what index to start adding after
		$crossover_end = ($crossover_start + $crossover_length - 1) % $parent_size;

		$index = $crossover_start - 1;
		$reversed_index = $crossover_end + 1;
		for($i = 0; $i < $crossover_length / 2; $i++) {
			$index++;
			if($index == $parent_size) $index -= $parent_size;
			$reversed_index--;
			if($reversed_index < 0) $reversed_index += $parent_size;

			$front_temp = $genome[$index];
			$end_temp = $genome[$reversed_index];
			$genome[$index] = $end_temp;
			$genome[$reversed_index] = $front_temp;
		}
		return $genome;
	}

	function edge_recombination($mother, $father) {
		$parent_size = count($mother);

		$edge_table = [];
		// add first edges
		$edge_table[$mother[0]][] = $mother[$parent_size - 1];
		$edge_table[$mother[0]][] = $mother[1];
		$edge_table[$father[0]][] = $father[$parent_size - 1];
		$edge_table[$father[0]][] = $father[1];
		// add middle edges
		for($i = 1; $i < $parent_size - 1; $i++) {
			$edge_table[$mother[$i]][] = $mother[$i - 1];
			$edge_table[$mother[$i]][] = $mother[$i + 1];
			$edge_table[$father[$i]][] = $father[$i - 1];
			$edge_table[$father[$i]][] = $father[$i + 1];
		}
		// add last edges
		$edge_table[$mother[$parent_size - 1]][] = $mother[$parent_size - 2];
		$edge_table[$mother[$parent_size - 1]][] = $mother[0];
		$edge_table[$father[$parent_size - 1]][] = $father[$parent_size - 2];
		$edge_table[$father[$parent_size - 1]][] = $father[0];

		$delete_from_table = function($value) use(&$edge_table) {
			foreach($edge_table as &$table) {
				$table = array_values(array_diff($table, [$value]));
			}
		};

		$genome = [];
		$iter = 0;
		$genome[0] = rand(0, $parent_size - 1);
		while($iter != $parent_size - 1) {
			$delete_from_table($genome[$iter]);
			if(empty($edge_table[$genome[$iter]])) {
				unset($edge_table[$genome[$iter]]);
				$next_value = array_rand($edge_table);
				goto add_gene;
			}
			$duplicate_count = array_count_values($edge_table[$genome[$iter]]);
			if(max($duplicate_count) != min($duplicate_count)) {
				$max_count_table = array_keys($duplicate_count, max($duplicate_count));
				$next_value = $max_count_table[0];
			} else {
				$count_table = [];
				foreach($edge_table[$genome[$iter]] as $value) {
					$count_table[] = count(array_unique($edge_table[$value]));
				}
				$min_count_table = array_keys($count_table, min($count_table));
				$next_value_key = $min_count_table[array_rand($min_count_table)];
				$next_value = $edge_table[$genome[$iter]][$next_value_key];
			}
			add_gene:
			$genome[] = $next_value;
			$iter++;
		}
		return $genome;
	}

	/* order crossover
	 * returns pair of children after performing order crossover with a mother and father genome
	 * order_crossover(array(), array()): array(array(), array())
	 */
	function order_crossover($mother, $father) {
		$parent_size = count($mother);
		$crossover_start = rand(0, $parent_size - 1);
		$crossover_length = rand(1, $parent_size -  $crossover_start);
		// this is needed to know what index to start adding after
		$crossover_end = $crossover_start + $crossover_length - 1;
		// get random subsection from both parents of length $crossover_length, starting at $crossover_start
		$egg = array_slice($mother, $crossover_start, $crossover_length, true);
		$sperm = array_slice($father, $crossover_start, $crossover_length, true);

		// the magic happens here
		$crossover = function($seed_genome, $other_parent) use($parent_size, $crossover_end) {
			$current_index = $crossover_end + 1;
			$matches = 0;
			while(count($seed_genome) < $parent_size) {
				// ensures we always check a proper index
				$index = $current_index % $parent_size;
				// don't add elements already in the child... ensures permutation
				if(!in_array($other_parent[$index], $seed_genome)) {
					// (($current_index - $matches) % parent_size) is the current empty index of the seed genome
					// this ensures index integrity after we skip matched elements from the $other_parent
					$seed_genome[($current_index - $matches) % $parent_size] = $other_parent[$index];
				} else {
					$matches++;
				}
				$current_index++;
			}
			// sort by keys (otherwise the keys would not start at 0)
			ksort($seed_genome);

			return $seed_genome;
		};

		// do the actual crossover
		$child_1 = $crossover($egg, $father);
		$child_2 = $crossover($sperm, $mother);
		return [$child_1, $child_2];
	}
}
