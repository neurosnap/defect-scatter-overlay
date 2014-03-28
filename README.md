Nysus' Defect Collection Scatter Overlay
================

The purpose of this utility is to apply defects as points (in x, y coordinates)
on a part, which are to be entered by operators manufacturing the part.

Our defect collection system tracks defects by type (e.g. dirt, scratch, paint run) and 
the location (x, y) the defect occured on a 2D representation (image jpg) of the part being
manufactured.

Once the defects have been logged as (x, y) coordinates on an image, we render the defects
on the image using scatterImg(), which are color coded by defect type, across a wide variety of filters 
(e.g. time, defect type, part number, shift, etc.).  We also allow the ability to filter by section.

Sections are created, parsed, and overlayed by scatterGrid().  Sections are defined similar to how a 
table of data is defined: columns and rows.  Once the number of columns and rows are specified 
as well as the image resolution, scatterGrid() does the rest of the leg-work, 
separating data by section as well as filtering data by section.

#### Demo -- ./demo.php

Quick and dirty demo with some faked data, realistically the data will come from a database.
Simply pull the repository down into a webserver, and point to the demo page to see the magic.

#### [Live Demo](http://nysus.net/erb/defect-scatter-overlay/demo.php)

Pre-Reqs
=======

 *  PHP
 *  GD Library

Credits
=======

Created by Eric Bower