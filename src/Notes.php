﻿<?php

/** 
 * Notes.php
 *
 * Mostra les notes d'un cicle i un nivell.
 */

require_once('Config.php');
require_once('lib/LibHTML.php');
require_once('lib/LibNotes.php');

$conn = new mysqli($CFG->Host, $CFG->Usuari, $CFG->Password, $CFG->BaseDades);
if ($conn->connect_error) {
  die("ERROR: Unable to connect: " . $conn->connect_error);
} 

CreaIniciHTML('Notes cicle/nivell');
echo '<script language="javascript" src="js/jquery-3.3.1.min.js" type="text/javascript"></script>';
echo '<script language="javascript" src="js/Notes.js" type="text/javascript"></script>';
echo '<script language="javascript" type="text/javascript">let timerId = setInterval(ActualitzaTaulaNotes, 5000);</script>';

echo "<P><font color=blue>S'ha de sortir de la cel·la per que quedi desada.</font></P>";

$CicleId = $_GET['CicleId'];
$Nivell = $_GET['Nivell'];

$SQL = CreaSQLNotes($CicleId, $Nivell);
//print_r($SQL);

$ResultSet = $conn->query($SQL);

if ($ResultSet->num_rows > 0) {
//print_r($ResultSet);	

	// Creem un objecte per administrar les notes
	$Notes = new stdClass();
	$i = -1; 
	$j = 0;
	$AlumneId = -1;
	$row = $ResultSet->fetch_assoc();
	while($row) {
//print_r($row);
		if ($row["AlumneId"] != $AlumneId) {
			$AlumneId = $row["AlumneId"];
			$i++;
			$Notes->Alumne[$i] = $row;
			$j = 0; 
		}	
		$Notes->UF[$i][$j] = $row;
		$j++;
		$row = $ResultSet->fetch_assoc();
	}
//print_r($Notes);



	echo '<FORM id=form method="post" action="">';
	echo '<input type=hidden id=CicleId value='.$CicleId.'>';
	echo '<input type=hidden id=Nivell value='.$Nivell.'>';
	echo '<TABLE border=0 width="100%">';

	// Capçalera de la taula
	echo "<TR><TD width=200></TD>";
	for($j = 0; $j < count($Notes->UF[0]); $j++) {
		$row = $Notes->UF[0][$j];
		echo "<TD width=25>".utf8_encode($row["CodiMP"])."</TD>";
	}
	echo "<TD></TD></TR>";
	echo "<TR><TD width=200></TD>";
	for($j = 0; $j < count($Notes->UF[0]); $j++) {
		$row = $Notes->UF[0][$j];
		echo "<TD width=25>".utf8_encode($row["CodiUF"])."</TD>";
	}
	echo "<TD></TD></TR>";

	for($i = 0; $i < count($Notes->Alumne); $i++) {
		echo "<TR>";
		$row = $Notes->Alumne[$i];
		echo "<TD width=200>".utf8_encode($row["NomAlumne"]." ".$row["Cognom1Alumne"]." ".$row["Cognom2Alumne"])."</TD>";
		for($j = 0; $j < count($Notes->UF[$i]); $j++) {
			$row = $Notes->UF[$i][$j];
			$ValorNota = NumeroANota($row["nota".$row["Convocatoria"]]);
			echo "<TD width=2><input type=text name=txtNotaId_".$row["NotaId"]."_".$row["Convocatoria"]." value='".$ValorNota."' size=1 onBlur='ActualitzaNota(this);'></TD>";
		}
		echo "<TD></TD></TR>";
	}
	echo "</TABLE>";
	echo "</FORM>";
	
	
//echo '<form> <label for="ccnum">CC Number</label><br> <input size="16" name="ccnum" id="ccnum">
//<br> <label for="ccv">CCV</label> <input id="ccv" name="ccv" size="4"> </form>';
	
	

}

/*
echo "<script>function test() {var s='input[name=txtNotaId_1_1]'; $(s).val('XXX'); var s='input[id=txt]'; $(s).val('XXX');}</script>";
echo '<form id=form2 method="post" action="">';
echo '<input maxlength=6 size=6 id=txtNotaId_100_100 value=#EEEEEE type=text onBlur="ActualitzaNota(this);">';
echo '</form>';
echo '<button onclick="test()">Test</button>';*/

echo "<DIV id=debug></DIV>";
echo "<DIV id=debug2></DIV>";

$ResultSet->close();

$conn->close(); 
 
 ?>