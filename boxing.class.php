<?php
/*
this some kind of box packing algorithm,
check if n boxes fit in n bigger boxes
i use this for checking if items will fit a packaging box
if this algorithm says true: they do definitely fit
if this algorithm says false: they most probably will not fit, but this is a program, not a cop.

disclaimer: 

this is not an accurate solution for knapsack or any of those fancy np-complete combinatory problems
i am not a mathematican, i have no idea what i am talking about, and also the german wikipedia sucks on this topic
quick and dirty, works for me, public domain, donations very welcome

author: sebastian vollnhals <sebastian at vollnhals dot info>

usage:

$b = new boxing();

$b -> add_outer_box(40,30,30); // our quantum box; l, w, h

$b -> add_inner_box(20,30,40); // schroedingers cat; l, w, h
$b -> add_inner_box(10,5,5); // the poison; l, w, h
$b -> add_inner_box(5,5,10); // some katzenstreu; l, w, h

if ($b -> fits()) {

	// schroedingers cat and schroedingers stuff do fit in the box

}
*/

class boxing {

	private $outer_boxes;
	private $inner_boxes;

	public function boxing() {

		$this -> outer_boxes = array();
		$this -> inner_boxes = array();

		return;

	}

	public function add_outer_box($id=false, $l,$w,$h, $vol=0) {

		if ($l > 0 && $w > 0 && $h > 0) {
			$this -> outer_boxes[] = array(
				"dimensions" 	=> $this -> sort_dimensions($l,$w,$h),
				"packed" 			=> false,
				"weight" 			=> 0,
				"volume" 			=> $vol,
				"name"			=> $id
			);
		}
		return true;

	}

	public function add_inner_box($l,$w,$h, $weight) {

		if ($l > 0 && $w > 0 && $h > 0) {

			$this -> inner_boxes[] = array(

				"dimensions" 	=> $this -> sort_dimensions($l,$w,$h),
				"weight"		 	=> $weight,
				"packed" 			=> false

			);

		}

		return true;

	}


	public function fits() {

		/* first we do a simple volume check, this can save a lot of calculations */

		if (!$this -> fits_volume()) {
			return false;
		}

		/* get next inner box */

		while (true) {

			$inner_box_id = $this->next_inner_box();

			if ($inner_box_id === false) {
				break;
			}

			$this -> sort_outer_boxes(); // smallest first				
			if ( count($this->inner_boxes) > 1 ) {
				arsort($this->outer_boxes); // biggest first				
			}

			$found_fitting_box = false;
			$arr_fits_inside = array();
			
			$menor_perda = 0;
			
			foreach ($this->outer_boxes as $outer_box_id => $outer_box) {

				// if (!$outer_box["packed"] && $this->fits_inside($inner_box_id, $outer_box_id)) {
				if ($this->fits_inside($inner_box_id, $outer_box_id)) {
					
					$this -> find_subboxes($inner_box_id, $outer_box_id);

					/* faz a verificação da menor perda de pacote */
					$volume_atual = $this->outer_boxes[$outer_box_id]["volume"] - $this->inner_boxes[$inner_box_id]["volume"];
					$perda_atual = 1-($volume_atual/$this->outer_boxes[$outer_box_id]["volume"]);
					
					if ($perda_atual > $menor_perda) {
						$menor_perda = $perda_atual;
						$pacote_menor = $outer_box_id;
						// produto empacotado
						$this -> inner_boxes[$inner_box_id]["packed"] = true;			
					}

					//$found_fitting_box = true;
					//break;
				}
			}
			
			// para cada um encontrado, separa o menor espaço sobrando
			$this->outer_boxes[$pacote_menor]["volume"] -= $this->inner_boxes[$inner_box_id]["volume"];
			$this->outer_boxes[$pacote_menor]["packed"] = true;
			// sum product weight
			$this -> outer_boxes[$pacote_menor]["weight"] += $this -> inner_boxes[$inner_box_id]["weight"];

			if (!$found_fitting_box) {
				// do it recursive later				
			}

		}
		/* we ran out of inner boxes but have outer boxes left */
		return true;

	}

	public function fits_volume() {

		$inner_volume = 0;
		$outer_volume = 0;

		foreach ($this -> inner_boxes as $k=>$inner) {

			$inner_box = ($inner["dimensions"][0] * $inner["dimensions"][1] * $inner["dimensions"][2]);
			$inner_volume +=$inner_box;
			// adiciona o volume
			$this->inner_boxes[$k]["volume"] = $inner_box;

		}
		
		foreach ($this -> outer_boxes as $k=>$outer) {
		
			$outer_box = ($outer["dimensions"][0] * $outer["dimensions"][1] * $outer["dimensions"][2]);
			$outer_volume += $outer_box;
			// adiciona o volume
			$this->outer_boxes[$k]["volume"] = $outer_box;

		}
		
		if ($inner_volume > $outer_volume) {
			# inner boxes have more volume than outer ones
			return false;
		} else {
			return true;
		}

	}

	private function find_subboxes($inner_box_id, $outer_box_id) {

		$inner_dimensions = $this->inner_boxes[$inner_box_id]["dimensions"];
		$outer_dimensions = $this->outer_boxes[$outer_box_id]["dimensions"];

		rsort($outer_dimensions);

		$pairs = array();

		foreach ($inner_dimensions as $inner_id => $inner_value) {

			foreach ($outer_dimensions as $outer_id => $outer_value) {

				if ($inner_value <= $outer_value) {

					$unset = $outer_id;

					$pairs[] = array(

						"inner" => $inner_value,
						"outer" => $outer_value,
						"diff" => $outer_value-$inner_value,

					);

					break;

				}

			}

			unset($outer_dimensions[$unset]);

		}

		do {

			$pairs = $this-> _diffsort($pairs);

			$this -> add_outer_box(false,$pairs[0]["diff"], $pairs[1]["outer"], $pairs[2]["outer"]);

			$pairs[0]["diff"] = 0;
			$pairs[0]["outer"] = $pairs[0]["inner"];


		} while($pairs[0]["diff"] > 0 || $pairs[1]["diff"] > 0 || $pairs[2]["diff"] > 0);

		return true;

	}

	private function fits_inside($inner_box_id, $outer_box_id) {

		if (
			$this->inner_boxes[$inner_box_id]["volume"] <= $this->outer_boxes[$outer_box_id]["volume"]
			/*$this->inner_boxes[$inner_box_id]["dimensions"][0] <= $this->outer_boxes[$outer_box_id]["dimensions"][0] &&
			$this->inner_boxes[$inner_box_id]["dimensions"][1] <= $this->outer_boxes[$outer_box_id]["dimensions"][1] &&
			$this->inner_boxes[$inner_box_id]["dimensions"][2] <= $this->outer_boxes[$outer_box_id]["dimensions"][2]*/

		) {
			// trocar este metodo de calculo
			/* 
				echo "volume: ".$outer_box_id.' - ';
				echo $this->outer_boxes[$outer_box_id]["volume"];
				echo "<hr>";
			*/
			// entra na caixa e já diminui do volume total
			#$this->outer_boxes[$outer_box_id]["volume"] -= $this->inner_boxes[$inner_box_id]["volume"] ;
			
			return true;

		} else {

			/* fits not */

			return false;

		}

	}

	private function sort_dimensions($l,$w,$h) {

		$dimensions = array($l,$w,$h);

		rsort($dimensions);

		return $dimensions;

	}

	private function sort_outer_boxes() {

		foreach ($this -> outer_boxes as $k => $v) {

			$this -> outer_boxes[$k]["longest_side"] = $v["dimensions"][0];

		}

		$this -> outer_boxes = $this -> _sksort($this -> outer_boxes, "longest_side", false, true);

		return true;

	}

	private function next_outer_box() {

		$biggest_size = 0;
		$biggest_id = false;

		foreach ($this -> outer_boxes as $id => $box) {

			if (!$box["packed"] && $box["dimensions"][0] > $biggest_size) {

				$biggest_size = $box["dimensions"][0];
				$biggest_id = $id;

			}

		}

		return $id;

	}

	private function next_inner_box() {

		$biggest_size = 0;
		$biggest_id = false;

		foreach ($this -> inner_boxes as $id => $box) {

			if (!$box["packed"] && $box["dimensions"][0] > $biggest_size) {

				$biggest_size = $box["dimensions"][0];
				$biggest_id = $id;

			}

		}

		return $biggest_id;

	}

	private function _sksort($array, $subkey, $sort_descending=false, $keep_keys_in_sub=false) {

		// slightly modified since stolen from http://www.php.net/manual/de/function.sort.php#93473
		foreach ($array as $key => &$value) {

			$sort = array();

			foreach ($value as $index => $val) {

				@$sort[$index] = $val[$subkey];

			}

			asort($sort);				

			$keys = array_keys($sort);

			$new_value = array();

			foreach ($keys as $index) {

				if($keep_keys_in_sub) {

					$new_value[$index] = $value[$index];

				} else {

					$new_value[] = $value[$index];

				}

			}

			if ($sort_descending) {

				$value = array_reverse($new_value, $keep_keys_in_sub);

			} else {

				$value = $new_value;

			}

		}

		return $array;

	}

	function _diffsort($array) {

		/* quick and dirty hack since _sksort() does strange things */

		$tmp_array = array();

		foreach ($array as $item) {

			$tmp_array[$item["diff"]][] = $item;

		}

		krsort($tmp_array);

		$array = array();

		foreach ($tmp_array as $a) {

			foreach ($a as $item) {

				$array[] = $item;

			}

		}

		return $array;

	}

	function get_inner_boxes(){
		return $this->inner_boxes;
	}
	
	function get_outer_boxes(){
		return $this->outer_boxes;
	}
	
	function set_inner_boxes($inner_boxes){
		$this->inner_boxes = $inner_boxes;
	}
	
	function set_outer_boxes($outer_boxes){
		$this->outer_boxes = $outer_boxes;
	}
	
	// fits box packages
	function get_fits_outer_boxes(){
		$vt = array();
		foreach ($this->outer_boxes as $key=>$box){
			// only packed boxes
			if ($box["packed"]) {
				$vt[$key] = $box;				
			}
		}
		return $vt;
	}
	
	// not fits products
	function get_not_fits_inner_boxes(){
		$prd = array();
		foreach ($this->inner_boxes as $key=>$box){
			// only packed boxes
			if (!$box["packed"]) {
				$prd[$key] = $box;				
			}
		}
		return $prd;
	}

	// fits products
	function get_fits_inner_boxes(){
		$prd = array();
		foreach ($this->inner_boxes as $key=>$box){
			// only packed boxes
			if ($box["packed"]) {
				$prd[$key] = $box;				
			}
		}
		return $prd;
	}
	
	
}
