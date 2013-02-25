# boxing.class.php

this some kind of box packing algorithm,
check if n boxes fit in n bigger boxes
i use this for checking if items will fit a packaging box
if this algorithm says true: they do definitely fit
if this algorithm says false: they most probably will not fit, but this is a program, not a cop.

## disclaimer

this is not an accurate solution for knapsack or any of those fancy np-complete combinatory problems
i am not a mathematican, i have no idea what i am talking about, and also the german wikipedia sucks on this topic
quick and dirty, works for me, public domain, donations very welcome

## author

sebastian vollnhals <sebastian at vollnhals dot info>

usage:

````php
$b = new boxing();

$b->add_outer_box(40,30,30); // our quantum box; l, w, h

$b->add_inner_box(20,30,40); // schroedingers cat; l, w, h
$b->add_inner_box(10,5,5); // the poison; l, w, h
$b->add_inner_box(5,5,10); // some katzenstreu; l, w, h

if ($b->fits()) {

	// schroedingers cat and schroedingers stuff do fit in the box

}
````
