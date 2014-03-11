<?php

// Simple treemap

//--------------------------------------------------------------------------------------------------

// Helper functions

//--------------------------------------------------------------------------------------------------
// Word wrapping
// http://www.xtremevbtalk.com/showthread.php?t=289709
function findStrWidth($str, $width, $low, $hi)
{
	$txtWidth = strlen($str);
	
	if (($txtWidth < $width) || ($hi == 1))
	{
		// string fits, or is one character long
		return $hi;
	}
	else
	{
		if ($hi - $low <= 1)
		{
			// we have at last character
			$txtWidth = $low;
			return $low;
		}
		else
		{
			$mid = $low + floor(($hi - $low)/2.0);
			
			$txtWidth = strlen(substr($str, 0, $mid));
			if ($txtWidth < $width)
			{
				// too short
				$low = $mid;
				return findStrWidth($str, $width, $low, $hi);
			}
			else
			{
				// too long
				$hi = $mid;
				return findStrWidth($str, $width, $low, $hi);
			}
		}
	}
}

//--------------------------------------------------------------------------------------------------
// http://www.herethere.net/~samson/php/color_gradient/
// Return the interpolated value between pBegin and pEnd
function interpolate($pBegin, $pEnd, $pStep, $pMax) 
{
	if ($pBegin < $pEnd) 
	{
  		return (($pEnd - $pBegin) * ($pStep / $pMax)) + $pBegin;
	} 
	else 
	{
  		return (($pBegin - $pEnd) * (1 - ($pStep / $pMax))) + $pEnd;
	}
}


//--------------------------------------------------------------------------------------------------
/**
 * @brief Encapsulate a rectangle
 *
 */class Rectangle
{
	var $x;
	var $y;
	var $w;
	var $h;
	

	function Rectangle($x=0, $y=0, $w=0, $h=0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->w = $w;
		$this->h = $h;
	}
	
	function Dump()
	{
		echo $this->x . ' ' . $this->y . ' ' . $this->w . ' ' . $this->h . "\n";
	}
	
	
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Encapsulate a cell in the Treemap
 *
 */
class Item
{
	var $bounds;			 // rectangle cell occupies (computed by treemap layout)
	var $size;				 // quantity cell corresponds to
	var $id;				 // id, typically an external id so we can make a link
	var $children = array(); // children of this node, if we are doing > 1 level
	var $label;				 // label for cell
	var $isLeaf;			 // flag for whether cell is a leaf

	/**
	* @brief Constructor
	*
	* @param n Number of items in this cell
	* @param label Label for this cell
	* @param ext External identifier for this cell (used to make a link)
	* @param leaf True if this cell has no children
	*
	*/
	function Item($n = 0, $label = '', $ext = 0, $leaf = false)
	{		
		$this->bounds 	= new Rectangle();
		$this->size 	= $n;
		$this->label 	= $label;
		$this->isLeaf 	= $leaf;
		$this->id		= $ext;
	}
	
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Compute weight of list of items to be placed
 *
 * This is the sum of the quantity represented by each item in the list.
 * @param l Array of items being placed
 *
 * @return Weight of items
 */
function w($l)
{
	$sum = 0.0;
	foreach ($l as $item)
	{
		$sum += $item->size;
	}
	return $sum;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Split layout
 *
 * Implements Björn Engdahl's Split Layout algorithm for treemaps,
 * see http://www.nada.kth.se/utbildning/grukth/exjobb/rapportlistor/2005/rapporter05/engdahl_bjorn_05033.pdf
 *
 * This is a recursive function that lays out the treemap. It tries to satisfy the twin goals of 
 * a good aspect ratio for the rectangles, and minimal changes to the order of the items in the treemap.
 *
 * @param items Array of items to place
 * @param r Current rectangle
 *
 */
function splitLayout($items, &$r)
{
	global $cr;
	
	if (count($items) == 0)
	{
		return;
	}
	
	if (count($items) == 1)
	{
		// Store rectangle dimensions
		$cr[$items[0]->id] = $r;
		
		$items[0]->bounds = $r;
		
		
		// Handle children (if any)		
		if (isset($items[0]->children))
		{
			splitLayout($items[0]->children, $r);
		}
		else
		{
			return;
		}
		
		return;
		
	}
	
	// Split list of items into two roughly equal lists
	$l1 = array();
	$l2 = array();
	
	$halfSize = w($items) / 2.0;
	$w1 		= 0.0;
	$tmp 		= 0.0;
	
	while (count($items) > 0)
	{
		$item = $items[0];
		
		$tmp = $w1 + $item->size;
		
		// Has it gotten worse by picking another item?
		if (abs($halfSize - $tmp) > abs($halfSize - $w1))
		{
			break;
		}
		
		// It was good to pick another
		array_push($l1, array_shift($items));
		$w1 = $tmp;
	}
	
	// The rest of the items go into l2
	foreach ($items as $item)
	{
		array_push($l2, $item);
	}
	
	$wl1 = w($l1);
	$wl2 = w($l2);
	
	
	// Which way do we split current rectangle it?	
	if ($r->w > $r->h)
	{
		// vertically
		$r1 = new Rectangle(
			$r->x, 
			$r->y,
			$r->w * $wl1/($wl1 + $wl2),
			$r->h);
	
		$r2 = new Rectangle(
			$r->x + $r1->w, 
			$r->y,
			$r->w - $r1->w,
			$r->h);
	}
	else
	{
		// horizontally
		$r1 = new Rectangle(
			$r->x, 
			$r->y,
			$r->w,
			$r->h * $wl1/($wl1 + $wl2));
	
		$r2 = new Rectangle(
			$r->x, 
			$r->y + $r1->h,
			$r->w,
			$r->h - $r1->h);	
	}
		
	// Continue recursively
	splitLayout($l1, $r1);
	splitLayout($l2, $r2);
}

?>