<?php

/** 
 * LibNotes.php
 *
 * Llibreria d'utilitats per a les notes.
 *
 * @author Josep Ciberta
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

/**
 * FUNCIONAMEMT DEL LLENÇOL DE NOTES
 * - Quan s'aprova una UF, la convocatòria passa a 0.
 * - Quan es convalida una UF, es posa un 5 a la convocatòria actual i la convocatòria passa a 0.
 */

require_once(ROOT.'/lib/LibArray.php');
require_once(ROOT.'/lib/LibDB.php');
require_once(ROOT.'/lib/LibForms.php');
require_once(ROOT.'/lib/LibProfessor.php');
require_once(ROOT.'/lib/LibAvaluacio.php');
require_once(ROOT.'/lib/LibCurs.php');

/**
 * ObteTaulaNotesJSON
 *
 * Recupera les notes d'un cicle i un nivell concret en format JSON.
 *
 * @param string $CicleId Identificador del cicle formatiu.
 * @param string $Nivell Nivell: 1r o 2n.
 * @return string Sentència SQL.
 */
function ObteTaulaNotesJSON($Connexio, $CicleId, $Nivell)
{
	$Notes = new Notes($conn, NULL);
	$SQL = $Notes->CreaSQL($CursId, $Nivell);

	//return $SQL;
	//print_r($SQL);
	$ResultSet = $Connexio->query($SQL);
	//return $ResultSet;
//print_r($ResultSet);
	return ResultSetAJSON($ResultSet);
} 
 
/**
 * EsNotaValida
 *
 * Comprova si un valor és una nota vàlida. Valors vàlids:
 *   - 1, 2, 3, 4, 5, 6, 7, 8, 9, 10.
 *   - NP (no presentat), A (apte), NA (no apte).
 *   - La cadena nul·la (per esborrar una nota).
 *
 * @param string $Valor Valor a comprovar.
 * @return boolean Cert si és una valor vàlid com a nota.
 */
function EsNotaValida($Valor)
{	
	$Valor = strtoupper($Valor);
	return ((is_numeric($Valor) && ($Valor>0) && ($Valor<=10)) ||
		($Valor == 'NP') ||
		($Valor == 'A') ||
		($Valor == 'NA') ||
		($Valor == ''));
}

/**
 * NotaANumero
 *
 * Transforma una nota al seu valor numèric. Valors numèrics:
 *   - 1, 2, 3, 4, 5, 6, 7, 8, 9, 10.
 *   - NP: -1, A: 100, NA: -100.
 *   - La cadena nul·la passa a ser NULL (per esborrar una nota).
 *
 * @param string $Valor Nota tal com s'entra a l'aplicació (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, NP, A, NA).
 * @return int Retorna el valor numèric o NULL.
 */
function NotaANumero($Valor)
{
	$Valor = strtoupper($Valor);
	if ($Valor == 'NP') 
		return -1;
	else if ($Valor == 'A') 
		return 100;
	else if ($Valor == 'NA') 
		return -100;
	else if ($Valor == '') 
		return 'NULL';
	else
		return $Valor;
}
 
/**
 * NumeroANota
 *
 * Transforma una nota numèrica al seu valor de text. Valors numèrics:
 *   - 1, 2, 3, 4, 5, 6, 7, 8, 9, 10.
 *   - NP: -1, A: 100, NA: -100.
 *   - NULL passa a ser la cadena nul·la.
 *
 * @param int $Valor Valor numèric o NULL.
 * @return string Retorna la nota tal com s'entra a l'aplicació (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, NP, A, NA).
 */
function NumeroANota($Valor)
{
	if ($Valor == -1) 
		return 'NP';
	else if ($Valor == 100) 
		return 'A';
	else if ($Valor == -100) 
		return 'NA';
	else
		return $Valor;
}

/**
 * Transforma una nota numèrica al seu valor de text sencer. Valors numèrics:
 *   - 1, 2, 3, 4, 5, 6, 7, 8, 9, 10.
 *   - NP: -1, A: 100, NA: -100.
 *   - NULL passa a ser la cadena nul·la.
 *
 * @param int $Valor Valor numèric o NULL.
 * @param boolean $bFemeni indica si el text ha de ser en femení.
 * @return string Retorna la nota tal com s'entra a l'aplicació (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, No presentat, Apte, No apte).
 */
function NumeroANotaText($Valor, bool $bFemeni = False)
{
	if ($Valor == -1) 
		return $bFemeni ? 'No presentada' : 'No presentat';
	else if ($Valor == 100) 
		return $bFemeni ? 'Apta' : 'Apte';
	else if ($Valor == -100) 
		return $bFemeni ? 'No apta' : 'No apte';
	else
		return $Valor;
}

/**
 * UltimaNota
 *
 * Donat un registre de notes, torna la nota de la última convocatòria.
 *
 * @param array $Registre Registre de notes corresponent a un alumne i una UF.
 * @return int Nota de la última convocatòria.
 */
function UltimaNota($Registre)
{
//print_r($Registre['nota5']);
	if ($Registre['nota5'] != '') 
		return $Registre['nota5'];
	else if ($Registre['nota4'] != '') 
		return $Registre['nota4'];
	else if ($Registre['nota3'] != '') 
		return $Registre['nota3'];
	else if ($Registre['nota2'] != '') 
		return $Registre['nota2'];
	else if ($Registre['nota1'] != '') 
		return $Registre['nota1'];
	else 
		return '';
}

/**
 * Classe per a les estadístiques d'un alumne.
 */
class EstadistiquesAlumne
{
	public $HoresTotals = 0;
	public $HoresFetes = 0;
	public $HoresAprovades = 0;
	public $NotaMitjana = 0;
	public $UFTotals = 0;
	public $UFFetes = 0;
	public $UFAprovades = 0;
	public $UFSuspeses = 0;
	public $EsRepetidor = false; // Si té la nota aprovada de la convocatòria anterior
}

/**
 * Classe que calcula les següents estadístiques d'una unitat formativa (d'un curs).
 * 	- Alumnes aprovats
 * 	- Alumnes aprovats altres anys
 * 	- Percentatge aprovats
 */
class EstadistiquesUF
{
	public $AlumnesConvocatoriaActual = 0;
	public $AlumnesAprovats = 0;
	public $AlumnesAprovatsConvocatoriaAnterior = 0;
	public $PercentatgeAprovats = 0;
	
	/**
	 * Calcula estadístiques d'una unitat formativa.
	 * @param object $Notes Registre de les notes.
	 * @param string $Nivell Nivell: 1r o 2n.
	 * @param int $IdUF Identificador de la UF.
	 * @return object Objecte amb les estadístiques.
	 */
	public static function Calcula($Notes, $Nivell, $IdUF) {
		$Retorn = new EstadistiquesUF();
		
		for($i = 0; $i < count($Notes->UF); $i++) {
			$row = $Notes->UF[$i][$IdUF];
		
		//foreach ($Notes->UF as $i => $NotesUF) {
			//$row = $NotesUF[$IdUF];
			
			if ($row["BaixaMatricula"] != 1 && !$row["baixa"] && $row["NivellMAT"] == $Nivell) {
				if ($row['Convocatoria'] > 0) {
					$Retorn->AlumnesConvocatoriaActual++;
					$Nota = $row['nota'.$row['Convocatoria']];
					if ($Nota >= 5) {
						$Retorn->AlumnesAprovats++;
					}
				}
				else {
					// Si la convocatòria està a 0, es suposa aprovat
					//if ($Nivell == 2 && )
						$Retorn->AlumnesAprovatsConvocatoriaAnterior++;
				}
			}
		}
		if ($Retorn->AlumnesConvocatoriaActual > 0)
			$Retorn->PercentatgeAprovats = number_format($Retorn->AlumnesAprovats/$Retorn->AlumnesConvocatoriaActual*100, 1);
		return $Retorn;
	}
}

/**
 * Classe per a les estadístiques d'un curs.
 */
class EstadistiquesCurs
{
	public $NumeroAlumnes = 0;
	public $NumeroRepetidors = 0;
	public $UFTotals = 0;
	public $UFFetes = 0;

	public $AlumnesTotAprovat = 0;
	public $AlumnesPendent1UF = 0;
	public $AlumnesPendent2UF = 0;
	public $AlumnesPendent3UF = 0;
	public $AlumnesPendent4UF = 0;
	public $AlumnesPendent5UF = 0;
	public $AlumnesPendentMesDe5UF = 0;
}

/**
 * Classe que encapsula les utilitats per al maneig de les notes.
 */
class Notes extends Form
{
	// Tipus d'exportació.
	const teULTIMA_NOTA = 1;
	const teULTIMA_CONVOCATORIA = 2;

	/**
	* Connexió a la base de dades.
	* @var object
	*/    
	public $Connexio;

	/**
	* Usuari autenticat.
	* @var object
	*/    
	public $Usuari;

	/**
	* Registre que conté les notes de 1r. Es carrega amb CarregaRegistre.
	* @var object
	*/    
	public $Registre1 = NULL;

	/**
	* Registre que conté les notes de 2n. Es carrega amb CarregaRegistre.
	* @var object
	*/    
	public $Registre2 = NULL;

	/**
	* Identificador del curs.
	* @var object
	*/    
	private $CursId = -1;

	/**
	* Indica si està activa l'administració avançada, amb més característiques:
	* 	- Augmenta 1 convocatòria per alumne (per entrar notes anteriors).
	* @var bool
	*/    
	public $Administracio = false;

	/**
	 * Constructor de l'objecte.
	 * @param objecte $conn Connexió a la base de dades.
	 */
	function __construct($con, $user) {
		parent::__construct($con, $user);
		$this->Registre1 = new stdClass();
		$this->Registre2 = new stdClass();
		$this->Administracio = false;
	}	
	
	/**
	 * Escriu el formulari corresponent a les notes d'un cicle i nivell.
	 * @param string $CicleId Identificador del cicle formatiu.
	 * @param string $Nivell Nivell: 1r o 2n.
	 * @param array $Notes Dades amb les notes.
	 * @param int $IdGraella Identificador de la graella de notes.
	 * @param object $Professor Objecte professor.
	 * @param object $Avaluacio Objecte avaluació.
	 * @return void.
	 */
	public function EscriuFormulari($CicleId, $Nivell, $Notes, $IdGraella, $Professor, $Avaluacio) {
//print_r($Notes);
		// Formulari amb les notes
		echo '<DIV id=notes'.$IdGraella.'>';
		echo '<FORM id=form'.$IdGraella.' method="post" action="">';
		echo '<input type=hidden id=CicleId value='.$CicleId.'>';
		echo '<input type=hidden id=Nivell value='.$Nivell.'>';
		echo '<TABLE id="TaulaNotes" border=0>';

		// Capçalera de la taula
		$aModuls = [];
		$aModulsNom = [];

		// CAI2 no existeix com a tal. Tots els crèdits estan posats a 1r
		if (!property_exists($Notes, 'UF')) return;
	
		for($j = 0; $j < count($Notes->UF[0]); $j++) {
			$row = $Notes->UF[0][$j];
			$aModulsId[$j] = $row["IdMP"];
			$aModuls[$j] = utf8_encode($row["CodiMP"]);
			$aModulsNom[$j] = utf8_encode($row["NomMP"]);
		}
		$aOcurrenciesModuls = Ocurrencies($aModuls);
//print_r($aOcurrenciesModuls);
//print_r($aModulsNom);

		// PEDAÇ. Cal arreglar
		$Curs = new Curs($this->Connexio, $this->Usuari);
		$Curs->CarregaRegistre($this->CursId);
		$NivellCurs = $Curs->ObteNivell();
		$IdCurs = ($NivellCurs == $Nivell) ? $row["IdCurs"] : $row["IdCurs"]+1;

		// Mòdul
		echo "<TR><TD></TD><TD></TD><TD></TD><TD></TD>";
		$index = 0;
		for($i = 0; $i < count($aOcurrenciesModuls); $i++) {
			$iOcurrencies = $aOcurrenciesModuls[$i][1];
//			$Link = 'NotesModul.php?CursId='.$row["IdCurs"].'&ModulId='.$aModulsId[$index].'NIVELL='.$Nivell;
//			$Link = 'NotesModul.php?CursId='.$row["IdCurs"].'&ModulId='.$aModulsId[$index];
			$Link = 'NotesModul.php?CursId='.$IdCurs.'&ModulId='.$aModulsId[$index];
			$MPId = $aModulsId[$index];
			if ($Professor->TeMP($MPId) || $Professor->EsAdmin() || $Professor->EsDireccio() || $Professor->EsCapEstudis())
				$TextModul = "<A href=$Link>".utf8_encode($aOcurrenciesModuls[$i][0])."</A>";
			else
				$TextModul = utf8_encode($aOcurrenciesModuls[$i][0]);
			echo '<TD width='.($iOcurrencies*25).' colspan='.$iOcurrencies.' data-toggle="tooltip" data-placement="top" title="'.$aModulsNom[$index].'">'.$TextModul.'</TD>';
			$index += $iOcurrencies;
		}
		echo "<TD></TD></TR>";
	
		// Unitat formativa
		echo "<TR><TD></TD><TD></TD><TD></TD><TD></TD>";
		for($j = 0; $j < count($Notes->UF[0]); $j++) {
			$row = $Notes->UF[0][$j];
			
			$UFId = $row["unitat_formativa_id"];
			if ($Professor->TeUF($UFId) || $Professor->EsAdmin() || $Professor->EsDireccio() || $Professor->EsCapEstudis())
				echo '<TD id="uf_'.$j.'" width=20 style="text-align:center" data-toggle="tooltip" data-placement="top" title="'.utf8_encode($row["NomUF"]).'"><a href="FPFitxa.php?accio=UnitatsFormatives&Id='.$UFId.'">'.utf8_encode($row["CodiUF"]).'</a></TD>';
			else
				echo '<TD id="uf_'.$j.'" width=20 style="text-align:center" data-toggle="tooltip" data-placement="top" title="'.utf8_encode($row["NomUF"]).'">'.utf8_encode($row["CodiUF"]).'</TD>';
		}
		echo "<TD style='text-align:center' colspan=2>Hores</TD>";
		if ($this->Usuari->es_admin || $this->Usuari->es_cap_estudis) {
			echo "<TD style='text-align:center;color:grey;'>UF</TD>";
			echo "<TD style='text-align:center;color:grey;'>Nota</TD>";
		}
		echo "</TR>";

		// Hores
		echo "<TR><TD></TD><TD></TD>";
		echo "<TD style='text-align:center'>Grup</TD>";
		echo "<TD style='text-align:center'>Tutoria</TD>";
		$TotalHores = 0;
		$aHores = []; // Array d'hores per posar-ho com a element ocult (format JSON) a l'HTML i poder-ho obtenir des de JavaScript.
		for($j = 0; $j < count($Notes->UF[0]); $j++) {
			$row = $Notes->UF[0][$j];
			$TotalHores += $row["Hores"];
			echo "<TD width=20 align=center>".$row["Hores"]."</TD>";
			array_push($aHores, $row["Hores"]);
		}
		echo "<TD style='text-align:center'>".$TotalHores."</TD>";
		echo "<TD style='text-align:center'>&percnt;</TD>";
		if ($this->Usuari->es_admin || $this->Usuari->es_cap_estudis) {
			echo "<TD style='text-align:center;color:grey;'>suspeses</TD>";
			echo "<TD style='text-align:center;color:grey;'>Mitjana</TD>";
		}
		echo "</TR>";

		for($i = 0; $i < count($Notes->Alumne); $i++) {
			$row = $Notes->Alumne[$i];
			if ($row["NivellMAT"] == $Nivell) {
				echo $this->CreaFilaNotes($IdGraella, $Nivell, $i, $Notes, $row, $Professor, $TotalHores, $Avaluacio);
			}
		}
		echo $this->CreaEstadistiquesUF($Notes, $Nivell);		
		echo "</TABLE>";
		echo "<input type=hidden name=TempNota value=''>";
		echo "<input type=hidden id='grd".$IdGraella."_ArrayHores' value='".ArrayIntAJSON($aHores)."'>";
		echo "<input type=hidden id='grd".$IdGraella."_TotalHores' value=".$TotalHores.">";
		echo "<input type=hidden id='grd".$IdGraella."_Nivell' value=".$Nivell.">";
		echo "</FORM>";
		echo "</DIV>";
	}

	/**
	 * Crea el botó per a la descàrrega en CSV.
	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @return string Codi HTML del botó.
	 */
	public function CreaBotoDescarregaCSV(string $CursId): string {
		$URL = "Descarrega.php?Accio=ExportaNotesCSV&CursId=$CursId";
		return $this->CreaBoto('btnDescarregaCSV', 'Descarrega en CSV', $URL);
 	}	
	
	/**
	 * Crea la fila de notes per a un alumne.
	 * @param string $IdGraella Nom de la graella.
	 * @param integer $Nivell Nivell.
	 * @param integer $i Fila.
	 * @param object $Notes Registre de les notes.
	 * @param object $row Registre que correspon a la nota.
	 * @param object $Professor Objecte de la classe Professor.
	 * @param integer $TotalHores Total d'hores del curs.
	 * @param object $Avaluacio Objecte de la classe Avaluacio.
	 * @return string Codi HTML de la cel·la.
	 */
	public function CreaFilaNotes(string $IdGraella, int $Nivell, int $i, $Notes, $row, $Professor, int $TotalHores, $Avaluacio): string {
		$Retorn = "";
		$bConvocatoriesAnteriors = True;
		$Color = ($row["BaixaMatricula"] == 1) ? ';color:lightgrey' : '';
		$AlumneId = $row["AlumneId"];
		$NomAlumne = utf8_encode(trim($row["Cognom1Alumne"]." ".$row["Cognom2Alumne"]).", ".$row["NomAlumne"]);

		if ($this->Usuari->es_admin || $this->Usuari->es_direccio || $this->Usuari->es_cap_estudis || $Professor->Tutor)
			$Retorn .= "<TD width=300 id='alumne_".$i."' style='text-align:left$Color'><a href='UsuariFitxa.php?Id=$AlumneId'>$NomAlumne</a></TD>";
		else
			$Retorn .= "<TD width=300 id='alumne_".$i."' style='text-align:left$Color'>$NomAlumne</TD>";

		if ($row["BaixaMatricula"] == 1)
			$Retorn .= "<TD></TD>";
		else
			$Retorn .= "<TD><A href='MatriculaAlumne.php?accio=MostraExpedient&MatriculaId=".$row["matricula_id"]."'><IMG src=img/grades-sm.svg></A></TD>";

		$Retorn .= "<TD style='text-align:center$Color'>".$row["Grup"]."</TD>";
		$Retorn .= "<TD style='text-align:center$Color'>".$row["GrupTutoria"]."</TD>";
		$Hores = 0;
		for($j = 0; $j < count($Notes->UF[$i]); $j++) {
			$row = $Notes->UF[$i][$j];
			if ($row['convocatoria'] != 0)
				$bConvocatoriesAnteriors = False;
			$Retorn .= $this->CreaCellaNota($IdGraella, $i, $j, $row, $Professor, $Hores, $Avaluacio);
		}

		$NotesAlumne = $Notes->Alumne[$i];
		$Id = 'grd'.$IdGraella.'_TotalHores_'.$i;
//		$Retorn .= '<TD id="'.$Id.'" style="text-align:center;color:grey">'.$Hores.'</TD>';
		$Retorn .= '<TD id="'.$Id.'" style="text-align:center;color:grey">'.$NotesAlumne['Estadistiques']->HoresAprovades.'</TD>';
		$Id = 'grd'.$IdGraella.'_TotalPercentatge_'.$i;
		$TotalPercentatge = $NotesAlumne['Estadistiques']->HoresAprovades/$NotesAlumne['Estadistiques']->HoresTotals*100;
		$Color = (($TotalPercentatge>=60 && $Nivell==1) ||($TotalPercentatge>=100 && $Nivell==2)) ? ';background-color:lightgreen' : '';
		$Retorn .= '<TD id="'.$Id.'" style="text-align:center'.$Color.'">'.number_format($TotalPercentatge, 2).'&percnt;</TD>';
		//if ($this->Usuari->es_admin || $this->Usuari->es_direccio || $this->Usuari->es_cap_estudis) {

		// Estadístiques alumne
//		$ea = EstadistiquesAlumne::Calcula($Notes->UF[$i]);

		if ($this->Usuari->es_admin || $this->Usuari->es_cap_estudis) {
//			$Retorn .= "<TD style='text-align:center;color:grey;'>".$this->NotaMitjana($Notes->UF[$i])."</TD>";style="color:grey;"
			$Retorn .= "<TD style='text-align:center;color:grey;'>".$NotesAlumne['Estadistiques']->UFSuspeses."</TD>";
			$Retorn .= "<TD style='text-align:center;color:grey;'>".$NotesAlumne['Estadistiques']->NotaMitjana."</TD>";
		}
		else
			$Retorn .= "<TD></TD><TD></TD>";
		if ($this->Usuari->es_admin && $this->Administracio) {
			$onClick = "AugmentaConvocatoriaFila($i, $IdGraella)";
			$Retorn .= "<TD><A href=# onclick='".$onClick."'>[PassaConv]</A></TD>";
		}
		else
			$Retorn .= "<TD></TD>";
		$Retorn .= "</TR>";

		$class = 'Grup'.$row["Grup"].' Tutoria'.$row["GrupTutoria"];
		//if ($Hores == $TotalHores)
		//	$class .= ' Aprovat100';
		//$style = ($Hores == $TotalHores) ? " style='display:none' " : "";
		if ($bConvocatoriesAnteriors)
			$class .= ' ConvocatoriesAnteriors';
		$style = ($bConvocatoriesAnteriors) ? " style='display:none' " : "";

		$Retorn = "<TR class='$class' $style name='Baixa".$row["BaixaMatricula"]."'>".$Retorn;
		
		return $Retorn;
	}
	
	/**
	 * Crea una cel·la de la taula de notes amb tota la seva casuística.
	 * @param string $IdGraella Nom de la graella.
	 * @param integer $i Fila.
	 * @param integer $j Columna.
	 * @param object $row Registre que correspon a la nota.
	 * @param object $Professor Objecte de la classe Professor.
	 * @param integer $Hores Hores que es sumen per saber el total.
	 * @param object $Avaluacio Objecte de la classe Avaluacio.
	 * @param string $Class Classe CSS per a la cel·la.
	 * @return string Codi HTML de la cel·la.
	 */
	public function CreaCellaNota(string $IdGraella, int $i, int $j, $row, $Professor, int &$Hores, $Avaluacio, $Class = ''): string {
		$EstatAvaluacio = $Avaluacio->Estat();
		//$style = "text-align:center;text-transform:uppercase;border:1px solid #A9A9A9;margin:1px;";
		$style = '';
		$Baixa = (($row["BaixaUF"] == 1) || ($row["BaixaMatricula"] == 1));
		$Convalidat = ($row["Convalidat"] == True);

		$Deshabilitat = '';
		if ($Baixa)
			$Deshabilitat = ' disabled ';
		else if (!$Professor->TeUF($row["unitat_formativa_id"]) && !$Professor->EsAdmin() && !$Professor->EsDireccio() && !$Professor->EsCapEstudis())
			$Deshabilitat = ' disabled ';

		$Nota = '';
		$ToolTip = ''; // L'usarem per indicar la nota anterior quan s'ha recuperat
		if (!$Baixa) {
			if ($Convalidat) {
				$Nota = UltimaNota($row);
				$Deshabilitat = " disabled ";
				$style .= ";background-color:blue;color:white";
			}
			else if ($row["Convocatoria"] == 0) {
				$Nota = UltimaNota($row);
				$Deshabilitat = " disabled ";
				$style .= ";background-color:black;color:white";
			}
			else if ($row["Convocatoria"] < self::UltimaConvocatoriaNota($row) && self::UltimaConvocatoriaNota($row) != -999) {
				// Nota recuperada
				$Nota = UltimaNota($row);
				$Deshabilitat = " disabled ";
				$style .= ";background-color:lime";
				$ToolTip = 'data-toggle="tooltip" title="Nota anterior: '.$row["nota".$row["Convocatoria"]].'"';
			}
			else {
				$Nota = $row["nota".$row["Convocatoria"]];
				if ($row["Orientativa"] && !$Baixa) {
					$style .= ";background-color:yellow";
				}
			}
		}
		else
			// Sense nota
			$style .= ";background-color:grey";
		if ($Nota >= 5)
			$Hores += $row["Hores"];
		else if ($Nota!='' && $Nota>=0 && $Nota<5)
			$style .= ";color:red";
		
		// Si l'avaluació (el curs) està tancada, tot deshabilitat.
		$Deshabilitat = ($EstatAvaluacio == Avaluacio::Tancada) ? ' disabled ' : $Deshabilitat;

		// Si els butlletins de l'avaluació (el curs) són visibles pels alumnes, tot deshabilitat.
		$Deshabilitat = ($Avaluacio->ButlletiVisible()) ? ' disabled ' : $Deshabilitat;
		
		$ClassInput = 'nota';
		if ($row["FCT"] == 1)
			$ClassInput .= ' fct';
		
		// <INPUT>
		// name: conté id i convocatòria
		// id: conté les coordenades x, y. Inici a (0, 0).
		$ValorNota = NumeroANota($Nota);
		$Id = 'grd'.$IdGraella.'_'.$i.'_'.$j;
		return "<TD $Class width=2>"
			."<input class='$ClassInput' type=text ".$Deshabilitat." style='".$style."'".
			" name=txtNotaId_".$row["NotaId"]."_".$row["Convocatoria"].
			" id='".$Id."' value='".$ValorNota."' size=1 ".$ToolTip.
			" onfocus='EnEntrarCellaNota(this);' onBlur='EnSortirCellaNota(this);' onkeydown='NotaKeyDown(this, event);'></TD>";
	}

	/**
	 * Crea les estadístiques per a les UF.
	 * @param object $Notes Registre que conté les notes.
	 * @param string $Nivell Nivell: 1r o 2n.
	 * @return string Codi HTML de la cel·la.
	 */
	private function CreaEstadistiquesUF($Notes, $Nivell): string {
		$aEstadistiquesUF = $this->CalculaEstadistiquesUF($Notes, $Nivell);

		$Retorn = '<TR style="color:grey;">';
		$Retorn .= '<TD colspan=4 style="text-align:right;">Alumnes aprovats</TD>';
		for($i = 0; $i < count($aEstadistiquesUF); $i++) {
			$euf = $aEstadistiquesUF[$i];
			$Retorn .= '<TD style="text-align:center;">'.$euf->AlumnesAprovats.'</TD>';
			
		}
		$Retorn .= '</TR>';

		$Retorn .= '<TR style="color:grey;">';
		$Retorn .= '<TD colspan=4 style="text-align:right;">Alumnes aprovats convocatòries anteriors</TD>';
		for($i = 0; $i < count($aEstadistiquesUF); $i++) {
			$euf = $aEstadistiquesUF[$i];
			$Retorn .= '<TD style="text-align:center;">'.$euf->AlumnesAprovatsConvocatoriaAnterior.'</TD>';
			
		}
		$Retorn .= '</TR>';

		$Retorn .= '<TR style="color:grey;">';
		$Retorn .= '<TD colspan=4 style="text-align:right;">% aprovats convocatòria actual</TD>';
		for($i = 0; $i < count($aEstadistiquesUF); $i++) {
			$euf = $aEstadistiquesUF[$i];
			$Retorn .= '<TD style="text-align:center;">'.$euf->PercentatgeAprovats.'</TD>';
			
		}
		$Retorn .= '</TR>';

		return $Retorn;
	}

	/**
	 * Donat un registre de notes, torna la última convocatòria.
     * Si la convocatòria és 0, torna la que té l'ultima nota.
	 * @param array $Registre Registre de notes corresponent a un alumne i una UF.
	 * @return int Última convocatòria.
	 */
	public static function UltimaConvocatoria($Registre) {
		if ($Registre['convocatoria'] != 0) 
			return $Registre['convocatoria'];
		else if ($Registre['nota5'] != '') 
			return 5;
		else if ($Registre['nota4'] != '') 
			return 4;
		else if ($Registre['nota3'] != '') 
			return 3;
		else if ($Registre['nota2'] != '') 
			return 2;
		else if ($Registre['nota1'] != '') 
			return 1;

		// Cas per quan s'usen àlies a la SQL
		if ($Registre['Convocatoria'] != 0) 
			return $Registre['Convocatoria'];
		else if ($Registre['Nota5'] != '') 
			return 5;
		else if ($Registre['Nota4'] != '') 
			return 4;
		else if ($Registre['Nota3'] != '') 
			return 3;
		else if ($Registre['Nota2'] != '') 
			return 2;
		else if ($Registre['Nota1'] != '') 
			return 1;

		else 
			return -999;
	}
	
	/**
	 * Donat un registre de notes, torna la última convocatòria amb nota.
	 * @param array $Registre Registre de notes corresponent a un alumne i una UF.
	 * @return int Última convocatòria.
	 */
	public static function UltimaConvocatoriaNota($Registre) {
//print_r($Registre);
		if ($Registre['nota5'] != '') 
			return 5;
		else if ($Registre['nota4'] != '') 
			return 4;
		else if ($Registre['nota3'] != '') 
			return 3;
		else if ($Registre['nota2'] != '') 
			return 2;
		else if ($Registre['nota1'] != '') 
			return 1;
/*
		// Cas per quan s'usen àlies a la SQL
		if ($Registre['Nota5'] != '') 
			return 5;
		else if ($Registre['Nota4'] != '') 
			return 4;
		else if ($Registre['Nota3'] != '') 
			return 3;
		else if ($Registre['Nota2'] != '') 
			return 2;
		else if ($Registre['Nota1'] != '') 
			return 1;
*/
		else 
			return -999;
	}

	/**
	 * Donat un registre de notes, torna la darrera nota (última convocatòria avaluada).
	 * @param array $Registre Registre de notes corresponent a un alumne i una UF.
	 * @return int Última convocatòria.
	 */
	public static function UltimaNotaAvaluada($Registre) {
		if ($Registre['nota5'] != '') 
			return $Registre['nota5'];
		else if ($Registre['nota4'] != '') 
			return $Registre['nota4'];
		else if ($Registre['nota3'] != '') 
			return $Registre['nota3'];
		else if ($Registre['nota2'] != '') 
			return $Registre['nota2'];
		else if ($Registre['nota1'] != '') 
			return $Registre['nota1'];
		else 
			'';
	}
	
	public static function CreaMenuContextual($Usuari) {
		// Adaptat de http://jsfiddle.net/KyleMit/X9tgY/
		echo '<ul id="contextMenu" class="dropdown-menu dropdown-menu-sm" role="menu" style="display:none" >';
		echo '    <li><a class="dropdown-item" id="ddi_IntrodueixRecuperacio" href="#">Introdueix recuperació</a></li>';
		if ($Usuari->es_admin || $Usuari->es_direccio || $Usuari->es_cap_estudis) {
			echo '    <li><a class="dropdown-item" id="ddi_NotaAnterior" href="#">Marca com a nota anterior (convocatòria a 0)</a></li>';
			echo '    <li><a class="dropdown-item" id="ddi_Convalida" href="#">Convalida</a></li>';
		}
		echo '</ul>';


		echo '<script>';
		echo '$("#TaulaNotes input").contextMenu({';
		echo '    menuSelector: "#contextMenu",';
		echo '    menuSelected: function (invokedOn, selectedMenu) {';
//echo 'console.dir(selectedMenu);';
		echo '        if (selectedMenu[0].id == "ddi_IntrodueixRecuperacio")';
		echo '            IntrodueixRecuperacio(invokedOn);';
		echo '        else if (selectedMenu[0].id == "ddi_NotaAnterior")';
		echo '            MarcaComNotaAnterior(invokedOn);';
		echo '        else if (selectedMenu[0].id == "ddi_Convalida")';
		echo '            Convalida(invokedOn);';
		echo '    }';
		echo '});';
		echo '</script>';
	}

	// Missatge recordatori a l'avaluació extraordinària
	public static function CreaMissatgeInici() {
		// Adaptat de https://www.tutorialrepublic.com/faq/how-to-launch-bootstrap-modal-on-page-load.php
		echo "<div id='RecordatoriAvExt' class='modal fade'>";
		echo "  <div class='modal-dialog'>";
		echo "    <div class='modal-content'>";
		echo "      <div class='modal-header'>";
		echo "        <h4 class='modal-title'>Avaluació extraordinària</h4>";
		echo "        <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>";
		echo "      </div>";
		echo "      <div class='modal-body'>";
		echo "		  <p>Esteu a l'avaluació extraordinària.</p>";
		echo "		  <p>Recordeu que només heu d'entrar les notes dels alumnes que s'han presentat. La resta les heu de deixar en blanc.</p>";
		echo "		  <p>També heu de tornar a calcular la qualificació final del mòdul per a aquells alumnes que els hi ha variat la nota de les UF.</p>";
		echo "      </div>";
		echo '      <div class="modal-footer">';
		echo "        <button type='button' class='btn btn-primary' data-dismiss='modal'>D'acord</button>";
		echo '      </div>';
		echo "    </div>";
		echo "  </div>";
		echo "</div>";
	}
	
	/**
	 * Donat un 1r curs, retorna l'identificador del 2n curs per a aquell any i cicle.
	 * Si no el troba, retorna -1.
	 * @return int Identificador del 2n curs.
	 */
	private function ObteSegonCurs(int $CursId): int {
		$iRetorn = -1;
		
		$SQL = 'SELECT curs_id FROM CURS C '.
		' LEFT JOIN ANY_ACADEMIC AA ON (C.any_academic_id=AA.any_academic_id) '.
		' WHERE cicle_formatiu_id in ( '.
		' 	SELECT cicle_formatiu_id FROM CURS C '.
		' 	LEFT JOIN ANY_ACADEMIC AA ON (C.any_academic_id=AA.any_academic_id) '.
		' 	WHERE curs_id='.$CursId.
		' ) '.
		' AND any_inici in ( '.
		' 	SELECT any_inici FROM CURS C '.
		' 	LEFT JOIN ANY_ACADEMIC AA ON (C.any_academic_id=AA.any_academic_id) '.
		' 	WHERE curs_id='.$CursId.
		' ) '.
		' AND nivell=2 ';
//print_r($SQL.'<HR>');
		$ResultSet = $this->Connexio->query($SQL);
		if ($ResultSet->num_rows > 0) {
			$obj = $ResultSet->fetch_object();
			$iRetorn = $obj->curs_id; 
			if ($iRetorn == $CursId)
				$iRetorn = -1;
		}
		
		return $iRetorn;
	}	
	
	/**
	 * CreaSQL
	 *
	 * Crea la sentència SQL per recuperar les notes d'un curs i un nivell concret.
	 *
	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @param string $Nivell Nivell: 1r o 2n.
	 * @return string Sentència SQL.
	 */
	public function CreaSQL($CursId, $Nivell)
	{
		$iSegonCurs = $this->ObteSegonCurs($CursId);
		$sRetorn = ' SELECT M.alumne_id AS AlumneId, '.
			' U.nom AS NomAlumne, U.cognom1 AS Cognom1Alumne, U.cognom2 AS Cognom2Alumne, '.
			' UF.unitat_formativa_id AS unitat_formativa_id, UF.codi AS CodiUF, UF.nom AS NomUF, UF.hores AS Hores, UF.orientativa AS Orientativa, UF.nivell AS NivellUF, UF.es_fct AS FCT, '.
			' MP.modul_professional_id AS IdMP, MP.codi AS CodiMP, MP.nom AS NomMP, '.
			' N.notes_id AS NotaId, N.baixa AS BaixaUF, N.convocatoria AS Convocatoria, N.convalidat AS Convalidat, '.
			' M.matricula_id, M.grup AS Grup, M.grup_tutoria AS GrupTutoria, M.baixa AS BaixaMatricula, '.
			' C.curs_id AS IdCurs, C.nivell AS NivellMAT, '.
			' N.*, U.* '.
			' FROM NOTES N '.
			' LEFT JOIN MATRICULA M ON (M.matricula_id=N.matricula_id) '.
			' LEFT JOIN CURS C ON (C.curs_id=M.curs_id) '.
			' LEFT JOIN USUARI U ON (M.alumne_id=U.usuari_id) '.
			' LEFT JOIN UNITAT_FORMATIVA UF ON (UF.unitat_formativa_id=N.uf_id) '.
			' LEFT JOIN MODUL_PROFESSIONAL MP ON (MP.modul_professional_id=UF.modul_professional_id) '.
			' WHERE C.curs_id='.$CursId;
		if ($iSegonCurs>0)
			$sRetorn .= ' OR C.curs_id='.$iSegonCurs;
		$sRetorn .= ' ORDER BY C.nivell, U.cognom1, U.cognom2, U.nom, MP.codi, UF.codi ';	
			
		return $sRetorn;
	}
	
	/**
	 * Carrega el registre amb les notes dels curs i nivell.
	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @param string $Nivell Nivell: 1r o 2n.
	 * @param string $Avaluacio Avaluacio ordinària o extraordinària.
	 */				
	public function CarregaRegistre($CursId, $Nivell, $Avaluacio = 'Ordinària') {
		$this->CursId = $CursId;
		
		$SQL = $this->CreaSQL($CursId, $Nivell);
//print_r($SQL);	
		$ResultSet = $this->Connexio->query($SQL);
		if (!$ResultSet)
			die("<b>ERROR</b>. SQL: ".$SQL); 
		
//print_r($ResultSet);	

		// Creem 2 objectes per administrar les notes de 1r i de 2n respectivament
		$i = -1; 
		$j1 = 0;
		$j2 = 0;
		$AlumneId = -1;
		$row = $ResultSet->fetch_assoc();
		while($row) {
//print_r($row);
//print '<hr>';
			if ($row["NivellUF"] == 1) {
				if ($row["AlumneId"] != $AlumneId) {
					$AlumneId = $row["AlumneId"];
					$i++;
					$this->Registre1->Alumne[$i] = $row;
					$this->Registre2->Alumne[$i] = $row;
					$j1 = 0; 
					$j2 = 0; 
				}	
				$this->Registre1->UF[$i][$j1] = $row;
				$j1++;
			}
			else if ($row["NivellUF"] == 2) {
				if ($row["AlumneId"] != $AlumneId) {
					$AlumneId = $row["AlumneId"];
					$i++;
					$this->Registre1->Alumne[$i] = $row;
					$this->Registre2->Alumne[$i] = $row;
					$j1 = 0; 
					$j2 = 0; 
				}	
				$this->Registre2->UF[$i][$j2] = $row;
				$j2++;
			}
			$row = $ResultSet->fetch_assoc();
		}		
//print_r($this->Registre1->Alumne[0]);
//print('<hr>');
//print_r($this->Registre2);
//print('<hr>');
		$this->CalculaEstadistiquesAlumne($this->Registre1, $Avaluacio);
		$this->CalculaEstadistiquesAlumne($this->Registre2, $Avaluacio);
//print_r($this->Registre2);
//print('<hr>');
	}	
	
	/**
	 * Calcula les estadístiques d'un alumne (amb les notes que té entrades) i les afegeix al mateix registre de notes.
	 * NOTA: S'usen variables per referència!
	 * Les estadístiques són:
	 * 	- Hores totals
	 * 	- Hores fetes
	 * 	- Hores aprovades
	 * 	- Nota mitjana
	 * 	- UF aprovades
	 * 	- UF suspeses
	 * @param object $Notes Graella de notes.
	 * @param string $Avaluacio Avaluacio ordinària o extraordinària.
	 */
	private function CalculaEstadistiquesAlumne(&$Notes, $Avaluacio) {
		foreach ($Notes->Alumne as $i => &$NotesAlumne) {
			$ea = new EstadistiquesAlumne;
			$NotesAlumne['Estadistiques'] = $ea;
			$TotalNota = 0;
			$HoresTotals = 0;

//print_r($i);			
//print('<HR>');	
//print_r($Notes->UF[$i]);			
//echo count($Notes->UF[$i]);
//print('<HR>');			
//print_r($Notes->UF);			
//print('<HR>');			
//exit;
			if (property_exists($Notes, 'UF') && (array_key_exists($i, $Notes->UF))) {
				for($j = 0; $j < count($Notes->UF[$i]); $j++) {
					$row = $Notes->UF[$i][$j];
//if ($row['AlumneId']==1022)	{
//	print_r($row);			
//	print('<HR>');			
//}
//	exit;
					if ($Avaluacio == 'Ordinària') {
						$ea->UFTotals++;
						$ea->HoresTotals += $row['Hores'];
						$UltimaNota = UltimaNota($row);
						if ($UltimaNota != '') {
							$ea->UFFetes++;
							if (!$row['FCT']) {
								$ea->HoresFetes += $row['Hores'];
								$TotalNota += $UltimaNota*$row['Hores'];
							}
							if ($UltimaNota >= 5)
								$ea->HoresAprovades += $row['Hores'];
						}
						if ($row['Convocatoria'] > 0) {
							$Nota = $row['nota'.$row['Convocatoria']];
	//						if ($Nota > 0 && $Nota < 5)
							if ($Nota < 5 && $Nota != '')
								$ea->UFSuspeses++;
							else if ($Nota >= 5)
								$ea->UFAprovades++;
						}
						if ($row['Convocatoria'] == 0) 
							$ea->EsRepetidor = true;
	//					if ($TotalNota > 0 && $ea->HoresFetes != 0)
						if ($TotalNota > 0 && $ea->HoresFetes != 0 && !$row['FCT'])
							$ea->NotaMitjana = number_format($TotalNota / $ea->HoresFetes, 2);
					}
					else if ($Avaluacio == 'Extraordinària') {
						
						$ea->UFTotals++;
						$ea->HoresTotals += $row['Hores'];
						$ea->UFFetes++;
						$UltimaNota = Notes::UltimaNotaAvaluada($row);  
//if ($row['AlumneId']==1022)	echo "UltimaNota: $UltimaNota<BR>";

						if ($UltimaNota != '') {
							if ($UltimaNota < 5 && $UltimaNota != '')
								$ea->UFSuspeses++;
							else if ($UltimaNota >= 5)
								$ea->UFAprovades++;
							
							if (!$row['FCT']) {
								$ea->HoresFetes += $row['Hores'];
								$TotalNota += $UltimaNota*$row['Hores'];
							}
							if ($UltimaNota >= 5)
								$ea->HoresAprovades += $row['Hores'];
						}
						else
							$ea->UFSuspeses++;
						/*if ($row['Convocatoria'] > 0) {
							$Nota = $row['nota'.$row['Convocatoria']];
	//						if ($Nota > 0 && $Nota < 5)
							if ($Nota < 5 && $Nota != '')
								$ea->UFSuspeses++;
							else if ($Nota >= 5)
								$ea->UFAprovades++;
						}
						if ($row['Convocatoria'] == 0) 
							$ea->EsRepetidor = true;*/
						
	//					if ($TotalNota > 0 && $ea->HoresFetes != 0)
						if ($TotalNota > 0 && $ea->HoresFetes != 0 && !$row['FCT'])
							$ea->NotaMitjana = number_format($TotalNota / $ea->HoresFetes, 2);						
						
						//$ea->UFSuspeses = $ea->UFTotals - $ea->UFAprovades;
						//$ea->UFFetes = $ea->UFTotals;
					}
				}
//if ($row['AlumneId']==1022)	print_r($ea);
					
				//$row['HoresTotals'] = 0;
			}
		}
	}

	/**
	 * Calcula les estadístiques de les UFs de la graella de notes.
	 * @param object $Notes Graella de notes.
	 * @param string $Nivell Nivell: 1r o 2n.
	 * @return array Array amb les estadístiques de cada UF.
	 */
	private function CalculaEstadistiquesUF($Notes, $Nivell) {
		$Retorn = [];
		for($i = 0; $i < count($Notes->UF[0]); $i++) {
			$Retorn[$i] = EstadistiquesUF::Calcula($Notes, $Nivell, $i); 
		}
		return $Retorn;
	}
	
	/**
	 * Exporta les notes d'un curs en format CSV.
	 * https://stackoverflow.com/questions/16251625/how-to-create-and-download-a-csv-file-from-php-script
	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @param int $Tipus Tipus d'exportació: última nota, última convocatòria.
	 * @param string $filename Nom del fitxer.
	 * @param string $delimiter Separador.
	 */				
	public function ExportaCSV($CursId, int $Tipus=self::teULTIMA_NOTA, string $filename="export.csv", string $delimiter=";")
	{
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename="'. $filename .'";');

		// Clean output buffer
		ob_end_clean();

		$Curs = new Curs($this->Connexio, $this->Usuari);
		$Curs->CarregaRegistre($CursId);
		$Nivell = $Curs->ObteNivell();
		$Notes = $this->CarregaRegistre($CursId, $Nivell);
		$RegistreNotes = ($Nivell == 1) ? $this->Registre1 : $this->Registre2;
//print_r($RegistreNotes);
//exit;

		$handle = fopen('php://output', 'w');
		switch ($Nivell) {
			case 1:
				$this->ExportaCSVRegistre($this->Registre1, $handle, 1, $Tipus, $filename, $delimiter);
				fputcsv($handle, [], $delimiter);
				$this->ExportaCSVRegistre($this->Registre1, $handle, 2, $Tipus, $filename, $delimiter);
				break;
			case 2:
				$this->ExportaCSVRegistre($this->Registre2, $handle, 2, $Tipus, $filename, $delimiter);
				fputcsv($handle, [], $delimiter);
				$this->ExportaCSVRegistre($this->Registre1, $handle, 2, $Tipus, $filename, $delimiter);
				break;
		}
		fclose($handle);

		// Flush buffer
		ob_flush();

		// Use exit to get rid of unexpected output afterward
		exit();
	}	
	
	/**
	 * Exporta les notes d'un registre (corresponent a 1r o 2n).
	 * @param string $RegistreNotes Registre de notes.
	 * @param resource $handle Identificador del fitxer.
	 * @param string $Nivell Nivell: 1r o 2n.
	 * @param int $Tipus Tipus d'exportació: última nota, última convocatòria.
	 * @param string $filename Nom del fitxer.
	 * @param string $delimiter Separador.
	 */				
	public function ExportaCSVRegistre($RegistreNotes, $handle, $Nivell, int $Tipus=self::teULTIMA_NOTA, string $filename="export.csv", string $delimiter=";")
	{
		// Mòduls
		$aNotes = [];
		array_push($aNotes, '');
		for($j = 0; $j < count($RegistreNotes->UF[0]); $j++) {
			$row = $RegistreNotes->UF[0][$j];
			array_push($aNotes, utf8_encode($row["CodiMP"]));
		}
		fputcsv($handle, $aNotes, $delimiter);
//print_r($aNotes);
//print('<hr>');
//exit;

		// Unitats formatives
		$aNotes = [];
		array_push($aNotes, '');
		for($j = 0; $j < count($RegistreNotes->UF[0]); $j++) {
			$row = $RegistreNotes->UF[0][$j];
			array_push($aNotes, utf8_encode($row["CodiUF"]));
		}
		array_push($aNotes, 'HoresTotals');
		array_push($aNotes, 'HoresFetes');
		array_push($aNotes, 'HoresAprovades');
		array_push($aNotes, 'NotaMitjana');
		array_push($aNotes, 'UFAprovades');
		array_push($aNotes, 'UFSuspeses');
		fputcsv($handle, $aNotes, $delimiter);
		//print_r($aNotes);
		//print('<hr>');
		
		// Notes
		for($i = 0; $i < count($RegistreNotes->UF); $i++) {
			$RegistreAlumne = $RegistreNotes->UF[$i];
			if ($RegistreNotes->Alumne[$i]['NivellMAT'] == $Nivell) {
				$aNotes = [];
				$Nom = $RegistreNotes->Alumne[$i]['Cognom1Alumne'].' '.$RegistreNotes->Alumne[$i]['Cognom2Alumne'].' '.$RegistreNotes->Alumne[$i]['NomAlumne'];
				//$Nom = utf8_encode($Nom);
				array_push($aNotes, $Nom);
				for($j = 0; $j < count($RegistreAlumne); $j++) {
					$row = $RegistreAlumne[$j];
//print_r($row);
//exit;
					switch ($Tipus) {
						case Notes::teULTIMA_NOTA:
							$UltimaNota = UltimaNota($row);
							break;
						case Notes::teULTIMA_CONVOCATORIA:
							$UltimaNota = ($row["Convocatoria"] == 0) ? UltimaNota($row) : $row["nota".$row["Convocatoria"]];
							break;
					}
					array_push($aNotes, $UltimaNota);
				}
				array_push($aNotes, $RegistreNotes->Alumne[$i]['Estadistiques']->HoresTotals);
				array_push($aNotes, $RegistreNotes->Alumne[$i]['Estadistiques']->HoresFetes);
				array_push($aNotes, $RegistreNotes->Alumne[$i]['Estadistiques']->HoresAprovades);
				array_push($aNotes, str_replace('.', ',', $RegistreNotes->Alumne[$i]['Estadistiques']->NotaMitjana));
				array_push($aNotes, $RegistreNotes->Alumne[$i]['Estadistiques']->UFAprovades);
				array_push($aNotes, $RegistreNotes->Alumne[$i]['Estadistiques']->UFSuspeses);
				fputcsv($handle, $aNotes, $delimiter);
				//print_r($aNotes);
				//print('<hr>');
			}
		}

		// Estadístiques UF
		$aEstadistiquesUF = $this->CalculaEstadistiquesUF($RegistreNotes, $Nivell);
		$aNotes = [];
		array_push($aNotes, utf8_decode('Alumnes aprovats'));
		for($i = 0; $i < count($aEstadistiquesUF); $i++) {
			$euf = $aEstadistiquesUF[$i];
			array_push($aNotes, $euf->AlumnesAprovats);
		}
		fputcsv($handle, $aNotes, $delimiter);
		$aNotes = [];
		array_push($aNotes, utf8_decode('Alumnes aprovats convocatòries anteriors'));
		for($i = 0; $i < count($aEstadistiquesUF); $i++) {
			$euf = $aEstadistiquesUF[$i];
			array_push($aNotes, $euf->AlumnesAprovatsConvocatoriaAnterior);
		}
		fputcsv($handle, $aNotes, $delimiter);
		$aNotes = [];
		array_push($aNotes, utf8_decode('% aprovats convocatòria actual'));
		for($i = 0; $i < count($aEstadistiquesUF); $i++) {
			$euf = $aEstadistiquesUF[$i];
			array_push($aNotes, str_replace('.', ',', $euf->PercentatgeAprovats));
		}
		fputcsv($handle, $aNotes, $delimiter);
	}

	/**
	 * Calcula les estadístiques d'un curs.
//	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @param string $Nivell Nivell: 1r o 2n.
     * @return oject Objecte amb les estadístiques del curs.
	 */				
	private function CalculaEstadistiquesCurs($Nivell) {
		$Retorn = new EstadistiquesCurs();
		
		
		$Registre = ($Nivell == 1) ? $this->Registre1->Alumne : $this->Registre2->Alumne;
		
		
		for($i = 0; $i < count($Registre); $i++) {
			$row = $Registre[$i];
			
//print_r(utf8_encode($row["NomAlumne"].' '.$row["Cognom1Alumne"].' '.$row["Cognom2Alumne"]).' '.$row["NivellMAT"].'<br>');	
//print_r('NivellMAT: '.$row["NivellMAT"].'<br>');	
//echo "Nivell: $Nivell<br>";
//print_r($row["Estadistiques"]);	
//echo "<hr>";		
			
			if ($row["BaixaMatricula"] != 1 && $row["NivellMAT"] == $Nivell) {
				$Retorn->NumeroAlumnes++;
				$Retorn->UFTotals = $row["Estadistiques"]->UFTotals;
				if ($row["Estadistiques"]->EsRepetidor)
					$Retorn->NumeroRepetidors++;
				else
					$Retorn->UFFetes = $row["Estadistiques"]->UFFetes;
				
				/*$UFSuspeses = $row["Estadistiques"]->UFSuspeses;
				if ($Avaluació == 'Extraordinària') {
					// Cas especial a l'avaluació extraordinària
					$Retorn->UFFetes = $row["Estadistiques"]->UFTotals;
					$UFSuspeses = $row["Estadistiques"]->UFTotals - $row["Estadistiques"]->UFAprovades;
				}*/
				
				switch ($row["Estadistiques"]->UFSuspeses) {
					case 0: 
						$Retorn->AlumnesTotAprovat++; break;
					case 1: 
						$Retorn->AlumnesPendent1UF++; break;
					case 2: 
						$Retorn->AlumnesPendent2UF++; break;
					case 3: 
						$Retorn->AlumnesPendent3UF++; break;
					case 4: 
						$Retorn->AlumnesPendent4UF++; break;
					case 5: 
						$Retorn->AlumnesPendent5UF++; break;
					default: 
						$Retorn->AlumnesPendentMesDe5UF++; break;
				}
			}
		}
		return $Retorn;
	}
	
	/**
	 * Genera una taula amb les estadístiques d'un curs.
	 * @param object $objCurs Objecte del curs.
	 * @param string $Nivell Nivell: 1r o 2n.
     * @return string Taula HTML amb les estadístiques del curs.
	 */				
	public function GeneraEstadistiquesCurs($objCurs, $Nivell): string {
		$style = " style='text-align:center;' ";
		
		$Retorn = '<TABLE BORDER=1>';
//print_r($objCurs);		
//exit;
		$ec = $this->CalculaEstadistiquesCurs($Nivell);
		
		$Retorn .= "<TR><TD style='background-color:black;color:white;' COLSPAN=3>".utf8_encode($objCurs->NomCurs)."</TD></TR>";

		$Retorn .= "<TR><TD>Nombre d'alumnes</TD><TD $style>".$ec->NumeroAlumnes."</TD><TD $style>100%</TD></TR>";
		$Retorn .= "<TR><TD>Repetidors</TD><TD $style>".$ec->NumeroRepetidors."</TD><TD $style>".number_format($ec->NumeroRepetidors/$ec->NumeroAlumnes*100, 2)."%</TD></TR>";
		$Retorn .= "<TR><TD>Total UF's</TD><TD $style>".$ec->UFTotals."</TD><TD $style>100%</TD></TR>";
		$Retorn .= "<TR><TD>UF's avaluades</TD><TD $style>".$ec->UFFetes."</TD><TD $style>".number_format($ec->UFFetes/$ec->UFTotals*100, 2)."%</TD></TR>";
		//$Retorn .= "<TR><TD>&nbsp;</TD><TD></TD></TR>";

		$Retorn .= "<TR><TD></TD><TD $style>Alumnes</TD><TD $style>%</TD></TR>";
		$TotalUF = $ec->AlumnesTotAprovat+$ec->AlumnesPendent1UF+$ec->AlumnesPendent2UF+$ec->AlumnesPendent3UF+
			$ec->AlumnesPendent4UF+$ec->AlumnesPendent5UF+$ec->AlumnesPendentMesDe5UF;
		$Retorn .= "<TR><TD>Alumnes tot aprovat</TD><TD $style>".$ec->AlumnesTotAprovat."</TD><TD $style>".number_format($ec->AlumnesTotAprovat/$TotalUF*100, 2)."%</TD></TR>";
		$Retorn .= "<TR><TD>Alumnes pendent 1 UF</TD><TD $style>".$ec->AlumnesPendent1UF."</TD><TD $style>".number_format($ec->AlumnesPendent1UF/$TotalUF*100, 2)."%</TD></TR>";
		$Retorn .= "<TR><TD>Alumnes pendent 2 UF</TD><TD $style>".$ec->AlumnesPendent2UF."</TD><TD $style>".number_format($ec->AlumnesPendent2UF/$TotalUF*100, 2)."%</TD></TR>";
		$Retorn .= "<TR><TD>Alumnes pendent 3 UF</TD><TD $style>".$ec->AlumnesPendent3UF."</TD><TD $style>".number_format($ec->AlumnesPendent3UF/$TotalUF*100, 2)."%</TD></TR>";
		$Retorn .= "<TR><TD>Alumnes pendent 4 UF</TD><TD $style>".$ec->AlumnesPendent4UF."</TD><TD $style>".number_format($ec->AlumnesPendent4UF/$TotalUF*100, 2)."%</TD></TR>";
		$Retorn .= "<TR><TD>Alumnes pendent 5 UF</TD><TD $style>".$ec->AlumnesPendent5UF."</TD><TD $style>".number_format($ec->AlumnesPendent5UF/$TotalUF*100, 2)."%</TD></TR>";
		$Retorn .= "<TR><TD>Alumnes pendent més de 5 UF</TD><TD $style>".$ec->AlumnesPendentMesDe5UF."</TD><TD $style>".number_format($ec->AlumnesPendentMesDe5UF/$TotalUF*100, 2)."%</TD></TR>";
		$Retorn .= "<TR><TD></TD><TD $style>".$TotalUF."</TD><TD></TD></TR>";
		
		$Retorn .= '</TABLE>';
		return $Retorn;
	}
	
	/**
	 * Genera un pastís amb les estadístiques d'un curs.
	 * @param object $objCurs Objecte del curs.
	 * @param string $Nivell Nivell: 1r o 2n.
     * @return string Codi HTML amb el pastís.
	 */				
	public function GeneraPastisEstadistiquesCurs($objCurs, $Nivell): string {
		$ec = $this->CalculaEstadistiquesCurs($Nivell);
		
		$Id = $objCurs->curs_id;
		$NomCurs = utf8_encode($objCurs->NomCurs);
		$NomCurs = str_replace("'", "&quot;", $NomCurs);

		$Retorn = "<div id='canvas-holder$Id' style='width:100%'>";
		$Retorn .= "    <canvas id='myChart$Id'></canvas>";
		$Retorn .= "</div>";

		$Retorn .= "<script>";
		$Retorn .= "var ctx$Id = document.getElementById('myChart$Id').getContext('2d');";
		$Retorn .= "var myChart$Id = new Chart(ctx$Id, {";
		$Retorn .= "    type: 'pie',";
		$Retorn .= "    data: {";
		$Retorn .= "        labels: ['Alumnes tot aprovat', 'Alumnes pendent 1 UF', 'Alumnes pendent 2 UF', 'Alumnes pendent 3 UF', 'Alumnes pendent 4 UF', 'Alumnes pendent 5 UF', 'Alumnes pendent més de 5 UF'],";
		$Retorn .= "        datasets: [{";
		$Data = '['.$ec->AlumnesTotAprovat.','.$ec->AlumnesPendent1UF.','.$ec->AlumnesPendent2UF.','.$ec->AlumnesPendent3UF.','.
			$ec->AlumnesPendent4UF.','.$ec->AlumnesPendent5UF.','.$ec->AlumnesPendentMesDe5UF.']';
		$Retorn .= "            data: $Data,";
		$Retorn .= "            backgroundColor: ['navy', 'blue', 'green', 'lime', 'yellow', 'orange', 'red'],";
		$Retorn .= "            borderWidth: 2";
		$Retorn .= "        }]";
		$Retorn .= "    },";
		$Retorn .= "	options: {";
		$Retorn .= "		legend: {position: 'right'},";
		$Retorn .= "		title: {display: true, text: '$NomCurs'}";
		$Retorn .= "	}";
		$Retorn .= "});";
		$Retorn .= "</script>";
		
		return $Retorn;
	}
}

/**
 * Classe que encapsula les utilitats per al maneig de les notes del mòdul.
 */
class NotesModul extends Notes 
{
	/**
	* Registre carregat amb CarregaRegistre.
	* @var object
	*/    
	private $Registre = NULL;

	/**
	* Registre carregat amb CarregaRegistreMitjana.
	* Conté les mitjanes del mòdul per a cada alumne.
	* És un array associatiu amb els següents valors:
	*  - Clau: Id de la matrícula
	*  - Valor: Registre de la taula NOTES_MP
	* @var array
	*/    
	private $RegistreMitjanes = NULL;

	/**
	* Indica si la fila és l'alterna o no de cara a pintar-les de diferents colors.
	* @var boolean
	*/    
	private $FilaAlterna = False;

	/**
	 * Constructor de l'objecte.
	 * @param objecte $conn Connexió a la base de dades.
	 */
	function __construct($con, $user) {
		parent::__construct($con, $user);
		$this->Registre = new stdClass();
		$this->RegistreMitjanes = [];
	}

	/**
	 * Crea la sentència SQL per recuperar les notes d'un curs i un mòdul concret.
	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @param string $ModulId Identificador del mòdul.
	 * @return string Sentència SQL.
	 */
	public function CreaSQL($CursId, $ModulId)
	{
		$sRetorn = ' SELECT M.alumne_id AS AlumneId, '.
			' U.nom AS NomAlumne, U.cognom1 AS Cognom1Alumne, U.cognom2 AS Cognom2Alumne, '.
			' UF.unitat_formativa_id AS unitat_formativa_id, UF.codi AS CodiUF, UF.nom AS NomUF, UF.hores AS Hores, UF.orientativa AS Orientativa, UF.nivell AS NivellUF, UF.es_fct AS FCT,'.
			' MP.modul_professional_id AS IdMP, MP.codi AS CodiMP, MP.nom AS NomMP, MP.es_fct AS FCTMP, '.
			' N.notes_id AS NotaId, N.baixa AS BaixaUF, N.convocatoria AS Convocatoria, N.convalidat AS Convalidat, '.
			' M.matricula_id, M.grup AS Grup, M.grup_tutoria AS GrupTutoria, M.baixa AS BaixaMatricula, '.
			' C.curs_id AS IdCurs, C.nivell AS NivellMAT, '.
			' N.*, U.* '.
			' FROM NOTES N '.
			' LEFT JOIN MATRICULA M ON (M.matricula_id=N.matricula_id) '.
			' LEFT JOIN CURS C ON (C.curs_id=M.curs_id) '.
			' LEFT JOIN USUARI U ON (M.alumne_id=U.usuari_id) '.
			' LEFT JOIN UNITAT_FORMATIVA UF ON (UF.unitat_formativa_id=N.uf_id) '.
			' LEFT JOIN MODUL_PROFESSIONAL MP ON (MP.modul_professional_id=UF.modul_professional_id) '.
			' WHERE C.curs_id='.$CursId.' AND MP.modul_professional_id='.$ModulId;
		$sRetorn .= ' ORDER BY C.nivell, U.cognom1, U.cognom2, U.nom, MP.codi, UF.codi ';	
		return $sRetorn;
	}
	
	/**
	 * Crea la sentència SQL per recuperar les notes mitjanes d'un curs i un mòdul concret.
	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @param string $ModulId Identificador del mòdul.
	 * @return string Sentència SQL.
	 */
	public function CreaSQLMitjanes($CursId, $ModulId)
	{
		$sRetorn = ' SELECT NMP.notes_mp_id, NMP.matricula_id, NMP.modul_professional_id, NMP.nota '.
			' FROM NOTES_MP NMP '.
			' LEFT JOIN MATRICULA M ON (M.matricula_id=NMP.matricula_id) '.
			' WHERE M.curs_id='.$CursId.' AND NMP.modul_professional_id='.$ModulId;
		return $sRetorn;
	}

	/**
	 * Carrega el registre.
	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @param string $ModulId Identificador del mòdul.
	 */				
	public function CarregaRegistre($CursId, $ModulId) {
		$SQL = $this->CreaSQL($CursId, $ModulId);
		$ResultSet = $this->Connexio->query($SQL);
		if ($ResultSet->num_rows > 0) {
			$i = -1; 
			$j = 0;
			$AlumneId = -1;
			$row = $ResultSet->fetch_assoc();
			while($row) {
				if ($row["AlumneId"] != $AlumneId) {
					$AlumneId = $row["AlumneId"];
					$i++;
					$this->Registre->Alumne[$i] = $row;
					$j = 0; 
				}	
				$this->Registre->UF[$i][$j] = $row;
				$j++;
				$row = $ResultSet->fetch_assoc();
			}
		}
		//print_r($this->Registre);
	}
	
	/**
	 * Carrega el registre de mitjanes.
	 * @param string $CursId Identificador del curs del cicle formatiu.
	 * @param string $ModulId Identificador del mòdul.
	 */				
	public function CarregaRegistreMitjanes($CursId, $ModulId) {
		$SQL = $this->CreaSQLMitjanes($CursId, $ModulId);
		$ResultSet = $this->Connexio->query($SQL);

		if ($ResultSet->num_rows > 0) {
			$row = $ResultSet->fetch_assoc();
			while($row) {
				$this->RegistreMitjanes[$row["matricula_id"]] = $row;
				$row = $ResultSet->fetch_assoc();
			}
		}
//print_r($this->RegistreMitjanes); print('<HR>');
	}
	
	/**
	 * Escriu el formulari corresponent a les notes d'un cicle i nivell.
	 * @param string $CicleId Identificador del cicle formatiu.
	 * @param string $Nivell Nivell: 1r o 2n.
	 * @param array $Notes Dades amb les notes.
	 * @param int $IdGraella Identificador de la graella de notes.
	 * @param object $Professor Objecte professor.
	 * @param object $Avaluacio Objecte avaluació.
	 * @return void.
	 */
	public function EscriuFormulari($CicleId, $Nivell, $Notes, $IdGraella, $Professor, $Avaluacio) {
		$Notes = $this->Registre;
		$Nivell = 0;

		// Formulari amb les notes
		echo '<DIV class="saga" id=notes'.$IdGraella.'>';
		echo '<FORM id=form'.$IdGraella.' method="post" action="">';
		
echo '<div style="padding-left: 20px; padding-right: 5px; background-color: rgb(141, 164, 160); overflow: auto; height: 641px;" id="content">';

		echo '<input type=hidden id=CicleId value='.$CicleId.'>';
		echo '<input type=hidden id=Nivell value='.$Nivell.'>';
		echo '<TABLE id="TaulaNotes" border=0 style="border-collapse: separate">';
		echo '<TBODY>';

		// Capçalera de la taula
		$aModuls = [];
		$aModulsNom = [];

		// CAI2 no existeix com a tal. Tots els crèdits estan posats a 1r
		if (!property_exists($Notes, 'UF')) return;
	
		for($j = 0; $j < count($Notes->UF[0]); $j++) {
			$row = $Notes->UF[0][$j];
			$aModulsId[$j] = $row["IdMP"];
			$aModuls[$j] = utf8_encode($row["CodiMP"]);
			$aModulsNom[$j] = utf8_encode($row["NomMP"]);
		}
		$aOcurrenciesModuls = Ocurrencies($aModuls);
//print_r($aOcurrenciesModuls);
//print_r($aOcurrenciesModuls[0][1]);
//print_r($aModulsNom);

		echo '<input type=hidden id=ModulId value='.$aModulsId[0].'>';

//		$iNumeroUF = $aOcurrenciesModuls[0][1];

		// Mòdul
		echo "<TR><TD></TD>";
		$index = 0;
		for($i = 0; $i < count($aOcurrenciesModuls); $i++) {
			$iOcurrencies = $aOcurrenciesModuls[$i][1];
			//$TextModul = utf8_encode($aOcurrenciesModuls[$i][0]);
			$TextModul = 'Qualificació de les unitats formatives del mòdul professional';
			$TextModul .= '<br>'.utf8_encode($aOcurrenciesModuls[$i][0]);
			echo '<TH class="contingut" width='.($iOcurrencies*25).' colspan='.($iOcurrencies*2).' style="text-align:center" data-toggle="tooltip" data-placement="top" title="'.$aModulsNom[$index].'">'.$TextModul.'</TH>';
			$index += $iOcurrencies;
		}
		echo '<TH class="contingut" colspan=2 rowspan=2 style="text-align:center">Qualificació final del mòdul</TH></TR>';
	
		// Unitat formativa
		echo "<TR><TD></TD>";
		for($j = 0; $j < count($Notes->UF[0]); $j++) {
			$row = $Notes->UF[0][$j];
			echo '<TH class="contingut" width=20 colspan=2 style="text-align:center" data-toggle="tooltip" data-placement="top" title="'.utf8_encode($row["NomUF"]).'">'.utf8_encode($row["CodiUF"]).'</TH>';
		}

		// Hores
		echo '<TR><TH class="contingut" style="text-align:center">Alumnat</TD>';
		$TotalHores = 0;
		$aHores = []; // Array d'hores per posar-ho com a element ocult (format JSON) a l'HTML i poder-ho obtenir des de JavaScript.
		for($j = 0; $j < count($Notes->UF[0]); $j++) {
			echo '<TH class="contingut" width=20 align=center>Hores</TH>';
			echo '<TH class="contingut" width=20 align=center>Qualif.</TH>';

			$row = $Notes->UF[0][$j];
			$TotalHores += $row["Hores"];
			array_push($aHores, $row["Hores"]);
		}
		echo '<TH class="contingut" width=20 align=center>Hores</TH>';
		echo '<TH class="contingut" width=20 align=center>Qualif.</TH>';

		for($i = 0; $i < count($Notes->Alumne); $i++) {
			$row = $Notes->Alumne[$i];
			echo $this->CreaFilaNotes($IdGraella, $Nivell, $i, $Notes, $row, $Professor, $TotalHores, $Avaluacio);
		}
		echo '</TBODY>';
		echo "</TABLE>";
		echo "<input type=hidden name=TempNota value=''>";
		echo "<input type=hidden name=TempNotaModul value=''>";
		echo "<input type=hidden id='grd".$IdGraella."_ArrayHores' value='".ArrayIntAJSON($aHores)."'>";
		echo "<input type=hidden id='grd".$IdGraella."_TotalHores' value=".$TotalHores.">";
		echo "<input type=hidden id='grd".$IdGraella."_Nivell' value=".$Nivell.">";
		echo "<input type=hidden id='TotalX' value=".count($Notes->UF[0]).">";
		echo "<input type=hidden id='TotalY' value=".(count($Notes->Alumne)-1).">";

	echo '</div>';
	
		echo "</FORM>";
		echo "</DIV>";
	}
	
	/**
	 * Crea la fila de notes per a un alumne.
	 * @param string $IdGraella Nom de la graella.
	 * @param integer $Nivell Nivell.
	 * @param integer $i Fila.
	 * @param object $Notes Registre de les notes.
	 * @param object $row Registre que correspon a la nota.
	 * @param object $Professor Objecte de la classe Professor.
	 * @param integer $TotalHores Total d'hores del curs.
	 * @param object $Avaluacio Objecte avaluació.
	 * @return string Codi HTML de la cel·la.
	 */
	public function CreaFilaNotes(string $IdGraella, int $Nivell, int $i, $Notes, $row, $Professor, int $TotalHores, $Avaluacio): string {
		$Llista = $this->FilaAlterna ? 2 : 1;
		$Class = 'class="llistat'.$Llista.'"';
		
		$Retorn = "";
		$bConvocatoriesAnteriors = True;
//		if ($row["BaixaMatricula"] == 1)
//			return $Retorn;
		$style = ($row["BaixaMatricula"] == 1) ? " style='display:none' " : "";
		
		$NomAlumne = utf8_encode(trim($row["Cognom1Alumne"]." ".$row["Cognom2Alumne"]).", ".$row["NomAlumne"]);
		$Retorn .= "<TD $Class id='alumne_".$i."' style='text-align:left'>$NomAlumne</TD>";

		//$Retorn .= "<TD></TD>";

		$Hores = 0;
		for($j = 0; $j < count($Notes->UF[$i]); $j++) {
			$row = $Notes->UF[$i][$j];
			if ($row['convocatoria'] != 0)
				$bConvocatoriesAnteriors = False;
			$Retorn .= '<td '.$Class.' style="text-align:center">'.$row["Hores"].'</td>';			
			$Retorn .= $this->CreaCellaNota($IdGraella, $i, $j, $row, $Professor, $Hores, $Avaluacio, $Class);
		}
//print_r($Hores.'-');
		// Nota mòdul
		$EstilNotaModul = ($bConvocatoriesAnteriors) ? "background-color:black;color:white;" : "";
		$Retorn .= '<td '.$Class.' style="text-align:center">'.$TotalHores.'</td>';			
		$Retorn .= $this->CreaCellaNotaModul($IdGraella, $i, $j, $row, $Professor, $Hores, $Avaluacio, '', $EstilNotaModul);
		
		$Retorn .= "<TD></TD></TR>";

		$class = ($row["BaixaMatricula"] != 1) ? 'Grup'.$row["Grup"].' Tutoria'.$row["GrupTutoria"] : '';
		//$class = 'Grup'.$row["Grup"].' Tutoria'.$row["GrupTutoria"];

//print_r($Hores.'-');
//print_r($TotalHores.' ');

		if ($bConvocatoriesAnteriors) {
			$class .= ' ConvocatoriesAnteriors';
			$style = " style='display:none'";
		}

		$Retorn = "<TR class='$class' $style name='Baixa".$row["BaixaMatricula"]."'>".$Retorn;
		
		if ($row["BaixaMatricula"] != 1)
			$this->FilaAlterna = !$this->FilaAlterna;
		
		return $Retorn;
	}	
	
	/**
	 * Crea una cel·la per a la nota del mòdul.
	 * @param string $IdGraella Nom de la graella.
	 * @param integer $i Fila.
	 * @param integer $j Columna. És necessària per al moviment de les fletxes, etc.
	 * @param object $row Registre que correspon a la nota.
	 * @param object $Professor Objecte de la classe Professor.
	 * @param integer $Hores Hores que es sumen per saber el total.
	 * @param object $Avaluacio Objecte avaluació.
	 * @param string $Class Classe CSS per a la cel·la.
	 * @param string $style Estil CSS per a la cel·la.
	 * @return string Codi HTML de la cel·la.
	 */
	public function CreaCellaNotaModul(string $IdGraella, int $i, int $j, $row, $Professor, int &$Hores, $Avaluacio, $Class = '', $style = ''): string {
		$EstatAvaluacio = $Avaluacio->Estat();
		$MatriculaId = $row["matricula_id"];
		$Llista = $this->FilaAlterna ? 2 : 1;
		$Class = 'class="llistat'.$Llista.'"';
		
		$NotaId = 0;
		$Nota = '';
		if (array_key_exists($MatriculaId, $this->RegistreMitjanes)) {
			$NotaId = $this->RegistreMitjanes[$MatriculaId]['notes_mp_id'];
			$Nota = $this->RegistreMitjanes[$MatriculaId]['nota'];
		}
		
		$style .= "text-align:center;text-transform:uppercase;";
		$Baixa = (($row["BaixaUF"] == 1) || ($row["BaixaMatricula"] == 1));
		$Convalidat = ($row["Convalidat"] == True);

		$Deshabilitat = '';
/*		if ($Baixa)
			$Deshabilitat = ' disabled ';
		else if (!$Professor->TeUF($row["unitat_formativa_id"]) && !$Professor->EsAdmin() && !$Professor->EsDireccio() && !$Professor->EsCapEstudis())
			$Deshabilitat = ' disabled ';

		$Nota = '';
		$ToolTip = ''; // L'usarem per indicar la nota anterior quan s'ha recuperat
		if (!$Baixa) {
			if ($Convalidat) {
				//$Nota = UltimaNota($row);
				$Deshabilitat = " disabled ";
				$style .= ";background-color:blue;color:white";
			}
			else if ($row["Convocatoria"] == 0) {
				//$Nota = UltimaNota($row);
				$Deshabilitat = " disabled ";
				$style .= ";background-color:black;color:white";
			}
			else if ($row["Convocatoria"] < self::UltimaConvocatoriaNota($row) && self::UltimaConvocatoriaNota($row) != -999) {
				// Nota recuperada
				//$Nota = UltimaNota($row);
				$Deshabilitat = " disabled ";
				$style .= ";background-color:lime";
			}
			else {
				//$Nota = $row["nota".$row["Convocatoria"]];
				if ($row["Orientativa"] && !$Baixa) {
					$style .= ";background-color:yellow";
				}
			}
		}
		else
			// Sense nota
			$style .= ";background-color:grey";
*/		
		//if ($Nota >= 5)
		//	$Hores += $row["Hores"];
		//else 
		if ($Nota!='' && $Nota>=0 && $Nota<5)
			$style .= "color:red;";

		// Si l'avaluació (el curs) està tancada, tot deshabilitat.
		$Deshabilitat = ($EstatAvaluacio == Avaluacio::Tancada) ? ' disabled ' : $Deshabilitat;

		// Si els butlletins de l'avaluació (el curs) són visibles pels alumnes, tot deshabilitat.
		$Deshabilitat = ($Avaluacio->ButlletiVisible()) ? ' disabled ' : $Deshabilitat;

		// Si estan totes les UF aprovades de les convocatòries anteriors.
//print_r('$style: '.$style.'<BR>');		
//print_r('$Deshabilitat:'.strpos($style, 'background-color:black').'<BR>');
		// Comportament estrany si començava exactament així.
		if (strpos('*'.$style, 'background-color:black') > 0) {
			$Deshabilitat = ' disabled ';
		}

		$ClassInput = 'nota';
		if ($row["FCTMP"] == 1)
			$ClassInput .= ' fct';
		
		// <INPUT>
		// name: conté id i matrícula
		// id: conté les coordenades x, y. Inici a (0, 0).
		$ValorNota = NumeroANota($Nota);
		$Id = 'grd'.$IdGraella.'_'.$i.'_'.$j;
		return "<TD $Class width=2>".
			"<input class='$ClassInput' type=text ".$Deshabilitat." style='".$style."'".
			" name=txtNotaModulId_".$NotaId."_".$MatriculaId.
			" id='".$Id."' value='".$ValorNota."' size=1 ".
			" onfocus='EnEntrarCellaNotaModul(this);' onBlur='EnSortirCellaNotaModul(this);' onkeydown='NotaKeyDown(this, event);'></TD>";
	}
}

?>