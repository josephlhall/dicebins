<?php

/*

Author
======

Joseph Lorenzo Hall
University of California at Berkeley
School of Information, NSF ACCURATE Center
http://josephhall.org/
joehall@berkeley.edu

Purpose
=======

This PHP script takes a number of dice ($numdice) and number of
precincts ($numprec) (and optionally a flag that specifies output that
can be pasted into a spreadsheet ($csv)) and calculates uniform bins
to increase the effectiveness of dice rolls using 10-sided dice.

License
=======

(BSD License: http://www.opensource.org/licenses/bsd-license.php )

Copyright (c) 2008, Joseph Lorenzo Hall

All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

* Redistributions of source code must retain the above copyright
  notice, this list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright
  notice, this list of conditions and the following disclaimer in the
  documentation and/or other materials provided with the distribution.

* Neither the name of Joseph Lorenzo Hall nor the names of its
  contributors may be used to endorse or promote products derived from
  this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Release Notes
=============

2008-02-17: version 1.2.

   * Added a ton of error validation code.  Now, we:
      * Check to make sure that $numdice and $numprec are positive
        integers.
      * Check to make sure $csv is a 1 or 0 if it is set.
      * Make sure that the $skipall flag is set upon each error (which
        will skip various math components), not just upon the interval
        < 1 error.
      * Similarly, now there is a separate $err_int flag for the
        interval error condition.
      * 1) avoid the range, modulus and interval math and 2)
        associated metadata display if we have an error (or we can get
        weirdness like a divide by zero condition, 10 raised to a
        (char), etc.)
      * Only print interval error notice if the other error conditions
        don't exist (because validation errors take precedence over
        user math errors).
      * Print the various error notices for the user.
      * Add validDigits() and IsEmpty() functions to 1) check to make
        sure a returned string is a positive integer and 2) make sure
        the returned string isn't empty.

2008-02-15: version 1.1.

   * Per David Wagner suggestion: Changed display of numbers to
     include leading zeros to reflect die rolls that start with zeros.
   * Moved "source" href tag from an <a name=""> to inside the heading
     tag for that section.
   * Also changed display so that when the interval is zero, one die
     is displayed instead of a range that looks like "0-0", "1-1",
     etc. (these now are "0", "1", etc.

2008-02-14: Release initial version 1.0.

*/


//version number goes here
$version = "1.2";


//jump out of PHP to print HTML header, title and intro
?>
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head>
<title>Dice Binning Calculator</title>
<link rel="stylesheet" type="text/css" href="../joe.css" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
<body>

<h1>Dice Binning Calculator for Post-Election Audits</h1>

<small><a href="http://josephhall.org">Joseph Lorenzo Hall</a>
(joehall@berkeley.edu), UC Berkeley School of Information</small>

<table width="60%"><tr><td>

<p><small>To increase the transparency of the 1% manual tally process, a few
California counties have begun to use 10-sided dice to produce
<em>publicly-verifiable</em> random numbers
(See <a href="http://www.cs.berkeley.edu/~daw/papers/dice-wote06.pdf">Cordero,
Wagner and Dill 2006</a>).  Unfortunately, using 10-sided dice to 1)
select from only a few precincts or 2) to select from many precincts
can require a lot of re-rolling of the dice.  To increase the
efficiency of the process, Cordero et al. suggest "binning" the dice
rolls so that each precinct has a <em>range</em> of corresponding
values, of <em>equal width</em>, that allow a higher percentage of
dice rolls to "hit".  This calculator implements this idea.  It can
also output the binning data in a form that is easily pasteable into a
spreadsheet.  Please <a href="#source">click here for source code and
licensing information</a>.</small></p>

</td></tr></table>


<?php
//jump back into php

//grab certain values from the PHP GET object
$numdice = $_GET["numdice"];   //number of dice
$numprec = $_GET["numprec"];   //number of precincts
$csv     = $_GET["csv"];       //flag for pasteable output

/* if nothing is passed, set default number of dice and precincts.  If
   things are passed validate them. */
if (!isset($numdice) && !isset($numprec)) {
   $numdice = 2;               //default number of dice
   $numprec = 13;	       //default number of precincts
} else {

   /* Here is a bunch of validation logic to check vars to make sure
      they're positive integers and in the proper ranges.
      validDigits() makes sure a returned string is a positive integer
      (not a char or float). */

   //validate $numdice
   if (validDigits($numdice)) {
      $err_numdice = 0;   //is valid, no error
   } else {
      $err_numdice = 1;   //is not valid, we have an error
      $skipall     = 1;   //need to skip things because of error
   }

   //validate $numprec
   if (validDigits($numprec)) {
      $err_numprec = 0;   //is valid, no error
   } else {
      $err_numprec = 1;   //is not valid, we have an error
      $skipall     = 1;   //need to skip things because of error
   }

   //validate $csv
   if (isset($csv)) {     //check if it is set and a valid digit
      if (validDigits($csv)) {
         if ($csv == "1" || $csv == "0") {
            $err_csv = 0; //is valid "1" or "0" digit
         } else {
            $err_csv = 1; //is some other digit, we have an error
            $skipall = 1; //need to skip things because of error
         }
      } else {
         $err_csv = 1;    //is not valid, we have an error
         $skipall = 1;    //need to skip things because of error
      }
   } else {
      $csv  = 0;          //if not set, default is no pasteable results
   }

}

//calculate a few things we'll need later, only if no errors
if (!isset($skipall)) {
   $range = pow(10,$numdice);                 //range is 10**$numdice
   $intmod   = $range % $numprec;             //numbers left over
   $intmodp  = ($intmod / $range) * 100;      //left overs in %
   $interval = $range / $numprec;             //numbers per bin
   $intervalr= ($range - $intmod) / $numprec; //rounded interval
}

//if the interval < 1, then tell user to use more dice
if ($interval < 1) {
   $skipall = 1;	       //set flag to skip bin calc
   $err_int = 1;               //set interval error flag
} else {
   $skipall = 0;               //set flag not to skip bin calc
   $err_int = 0;               //set interval error flag negative
}

//jump out of PHP again
?>

<hr/>
<h1>Settings</h1>

<!-- Begin form using this script as action -->
<form action="dicebins.php" method="GET">
 <p>
 Number of dice: 
<?php
//back in php, fill field if $numdice is set (which it always is)
if (isset($numdice)) {
   print " <input type=\"text\" name=\"numdice\" size=\"1\" value=\"$numdice\">";
} else {
   print " <input type=\"text\" name=\"numdice\" size=\"1\">";
}
?>
 <br/>
 Number of precincts: 
<?php
//back in php, fill field if $numprec is set (which it always is)
if (isset($numprec)) {
   print " <input type=\"text\" name=\"numprec\" size=\"4\" value=\"$numprec\">";
} else {
   print " <input type=\"text\" name=\"numprec\" size=\"4\">";
}
?>
 <br/>
 <small>
   <small>(By default, it starts with 2 dice and 13 precincts.)</small>
 </small>
 </p>
 <input type="submit" value="Calculate">
</form>
<!-- End form using this script as action -->

<hr/>
<h1 id="results">Results</h1>

<?php

//back in php, deal with plural/not-plural semantics for label
if ($numdice == 1) {
   $worddice = "die";
} else{
   $worddice = "dice";
}

//set a few comments to be displayed later
$comm1 = "<small><small>(This is the quantity of random numbers $numdice $worddice can produce.)</small></small>";
$comm2 = "<small><small>(This is the number of random numbers per bin.)</small></small>";
$comm3 = "<small><small>(This is the number of random numbers that will require a re-roll.)</small></small>";

/* if 1) user doesn't want pasteable results and 2) as long as we have
   no errors, print some metadata about the calculation. */
if ($csv == 0 && $skipall == 0) {
   print "<ul>\n";
   print "<li>Range is $range. $comm1</li>\n";
   print "<li>Rounded interval is $intervalr. $comm2</li>\n";
   print "<li>Interval modulus is $intmod ($intmodp% of rolls). $comm3</li>\n";
   print "</ul>\n";
}

/* if user doesn't want pasteable results and doesn't need to use more
   dice (in which case no results are displayed), display link that
   will produce pasteable results. */
if ($csv == 0 && $skipall == 0) {
	print "<p><small><a href=\"dicebins.php?numdice=$numdice&numprec=$numprec&csv=1#results\">Paste these bins into a spreadsheet</a></small></p>\n";
}

/* if the user *does* want pasteable results and if there are no
   errors, then display the instructions for copying and pasting the
   pasteable results into a spreadsheet. */
if ($csv == 1 && $skipall == 0) {

//jump out of PHP
?>
<table width="60%"><tr><td>
<small>

<p>Here are the steps necessary to paste the bins below into a column
of a spreadsheet (We need to insert a new column, format that column
and then copy and paste the data below.):</p>

<ol>

  <li>Open the spreadsheet.</li>

  <li>In your spreadsheet, click on the column header for the column
  to the right of where you want the new column.</li>

  <li>In the spreadsheet menu, select "Insert" -> "Columns". This will
  insert a new column.</li>

  <li>With the new column selected, choose "Format" -> "Cells...".  In
  the number tab select "Text" and click "OK". (We're text-formatting
  this column because some spreadsheet programs interpret numers like
  "7-13" as dates.)</li>

  <li>With your mouse, select all the bins below on this page.  Copy
  the selection (Ctrl-C on Windows PCs or Cmd-C on Macs).</li>

  <li>In the spreadsheet again, click on the first cell of the column
  you just created.  Paste the selection (Ctrl-V on Windows PCs or
  Cmd-V on Macs).</li>

</ol>

</small>
</td></tr></table>

<?php

//jump back into PHP, close if statement
}

//if there are no errors, do calculations
if ($skipall == 0) {

   //loop to display ranges for each precinct to pick
   $pick = 0; //start at zero, first precinct will be 1
   for ($i = 0; $i <= $numprec-1 ; $i++) {
      $range1 = $i * $intervalr;        //first number in range
      $range2 = $range1+($intervalr-1); //second number in range
      $pick++;                          //increment precinct number

      //need to convert to string and add leading zeros
      $range1 = (string) $range1;  //cast as string
      $range2 = (string) $range2;  //cast as string
      $r1l    = strlen($range1);   //find string length
      $r2l    = strlen($range2);   //find string length
      $r1d    = $numdice - $r1l;   //number of leading zeros to add
      $r2d    = $numdice - $r2l;   //number of leading zeros to add
      if ($r1d != 0) {             //add zeros if needed
      	 //loop to add zeros to string
         for ($j = 0; $j <= $r1d-1; $j++) {
	    $range1 = "0".$range1; //add zero to string
	 }
      }
      if ($r2d != 0) {             //add zeros if needed
      	 //loop to add zeros to string
         for ($j = 0; $j <= $r2d-1; $j++) {
	    $range2 = "0".$range2; //add zero to string
	 }
      }

      //print semantic stuff. if pasteable flag is set, print less words
      if ($csv == 0) {
      	 //if the interval is only 1, don't print a range
         if ($intervalr == 1) {
            print "Roll $range1, pick precinct $pick<br/>\n";
         } else {
            print "Roll $range1-$range2, pick precinct $pick<br/>\n";
         }
      } else {
      	 //if the interval is only 1, don't print a range
         if ($intervalr == 1) {
       	    print "$range1<br/>\n";
	 } else {
       	    print "$range1-$range2<br/>\n";
	 }
      }
   }

   /* If the interval isn't round (has modulus of zero), there will be
      some extra numbers that won't correspond to precincts and where
      the user will need to reroll. */
   $maxmin = $range-$intmod;            //beginning of reroll range
   $maxmax = $range-1;                  //end of reroll range
   //print differently if no rerolls are needed
   if ($intmod != 0) {
       //of course, need this to look different if pasteable
       if ($csv == 0) {
          print "Roll $maxmin-$maxmax, reroll dice<br/>\n";
       } else {
          print "$maxmin-$maxmax -> reroll<br/>\n";
       }
   } else {
       print "No rerolls needed<br/>\n";
   }
}

/* print an error notice if they need to use more dice... however
   don't print if we have a validation error so that they take a
   higher precedence. */
if ($err_int && !$err_numdice && !$err_numprec && !$err_csv) {
print "<pre>

       !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	       You need to increase the number of dice.
       The range must be greater than the number of precincts.
       !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

</pre>";
}

//print an error notice if numdice didn't validate
if ($err_numdice) {
print "<pre>

       !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	     The number of dice you provided is invalid.
		    It must be a positive integer.
       !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

</pre>";
}

//print an error notice if numprec didn't validate
if ($err_numprec) {
print "<pre>

       !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	   The number of precincts you provided is invalid.
		    It must be a positive integer.
       !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

</pre>";
}

//print an error notice if csv doesn't pass validation
if ($err_csv) {
print "<pre>

       !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	  The value for the pasteable flag (\"csv\") you've
	      provided is invalid. It must be a 1 or 0.
       !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

</pre>";
}

//set up stuff to get timestamp on this here file
$f = "dicebins.php";   //file location
$attribs = stat("$f"); //get file attributes
//parse modification date attribute using date() function
$ftime = date("Y-m-d H:i:s T",$attribs[10]);

/* if pasteable flag is not set or if we have a csv value validation
   error, print source and licensing info. */
if ($csv == 0 || $err_csv) {
?>
<hr/>

<h2 id="source">Source Code, Licensing, Version, Etc.</h2>

<table width="60%"><tr><td>

<p><small>The source code to this script is <a href="dicebins.php.txt">available here</a>, licensed under the <a href="http://www.opensource.org/licenses/bsd-license.php">BSD license</a> (see source for license text).</small></p>

<p><small>This is version <?php print "$version"; ?> of <code>dicebins.php</code> (as of <?php print $ftime; ?>).</small></p>

</td></tr></table>

<?php
}

//print HTML footer
print "</body>\n</html>\n";


/* ******************************** */
/* FUNCTIONS BELOW, NO MORE DISPLAY */
/* ******************************** */

//function to check for a valid positive integer
function validDigits($var)
{
   //it's not valid if it is empty
   if(IsEmpty($var)) return false;

   //if it is numeric and not a float, then it's an integer
   if(is_numeric($var) && !preg_match('/[^\d]+/',$var)) return true;

   return false; //must not be digits
} 

//function to check if var is empty or not
function IsEmpty($var)
{
   if(!isset($var)) return true; //if not set, it's empty
   $type=gettype($var);          //get the type in a string

   //check against empty versions of each type
   if($type == "string" && trim($var) == '') return true;
   if($type == "array" && $var == array()) return true;
   if($type == "object" && $var == (object)0) return true;
   if($type == "NULL") return true;
   return false; //must not be empty
} 

?>
