<?php
# Copyright 2006, 2007, 2008, 2016 Ohio Supercomputer Center
# Copyright 2009, 2010, 2011, 2014 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';
require_once 'page-layout.php';
require_once 'metrics.php';
require_once 'site-specific.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['node']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['node']) )
  { 
    $title = "Jobs using node ".$_POST['node']." on ".$_POST['system'];
    $verb = title_verb($_POST['datelogic']);
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
      {
	$title .= " ".$verb." on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " ".$verb." between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	$title .= " ".$verb." after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	$title .= " ".$verb." before ".$_POST['end_date'];
      }
  }
else
  {
    $title = "Jobs on node";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['node']) )
  {
    $db = db_connect();
    $sql = "SELECT jobid";
    foreach ($keys as $key)
      {
	if ( isset($_POST[$key]) && $key!='jobid' && $key!='node' && 
	     $key!='start_date' && $key!='end_date' && $key!='datelogic' )
	  {
	    $sql .= ",".$key;
	  }
      }
    $sql .= " FROM Jobs WHERE hostlist REGEXP '".$_POST['node']."' AND ( ".sysselect($_POST['system'])." ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." ) ORDER BY start_ts;";
#    echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    echo "<TABLE border=\"1\">\n";
    $ncols=1;
    $col[0]="jobid";
    echo "<TR><TH>jobid</TH>";
    foreach ($keys as $key)
      {
	if ( $key!='node' && $key!='start_date' && $key!='end_date' && $key!='datelogic' )
	  {
	    echo "<TH>".$key."</TH>";
	    $col[$ncols]=$key;
	    $ncols++;
	  }
      }
    echo "</TR>\n";
    
    while ($result->fetchInto($row))
      {
	echo "<TR>";
	$rkeys=array_keys($row);
	foreach ($rkeys as $key)
	  {
	    $data[$key]=array_shift($row);
	    if ( $col[$key]=="submit_ts" || $col[$key]=="eligible_ts" || $col[$key]=="start_ts" || $col[$key]=="end_ts")
	      {
		echo "<TD><PRE>".date("Y-m-d H:i:s",$data[$key])."</PRE></TD>\n";
	      }
	    else if ($col[$key] == "jobid")
	      {
		$jobid_nodot = ereg_replace('\..*', '', $data[$key]);
		echo "<TD><PRE><A HREF=\"",
		  "jobinfo.php?jobid=$jobid_nodot",
		  "&system=$_POST[system]&all=1\"\>",
		  htmlspecialchars($jobid_nodot), "</A></PRE></TD>";
	      }
	    else
	      {
		echo "<TD><PRE>".htmlspecialchars($data[$key])."</PRE></TD>";
	      }
	  }
	echo "</TR>\n";
      }
    echo "</TABLE>\n";
  
    db_disconnect($db);
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("jobs-by-node.php");

    text_field("Node RegExp","node",16);
    system_chooser();
    date_fields();

    $props=array("username","groupname","account","jobname","nproc","mppe","mppssp",
		 "nodes","feature","gattr","gres","queue","qos",
		 "submit_ts","eligible_ts","start_ts","end_ts",
		 "cput_req","cput_req_sec","cput","cput_sec",
		 "walltime_req","walltime_req_sec","walltime","walltime_sec",
		 "mem_req","mem_kb","vmem_req","vmem_kb",
		 "energy","software","submithost","hostlist",
		 "exit_status","script","sw_app");
    checkboxes_from_array("Properties",$props);

    end_form();
  }

page_footer();
?>
