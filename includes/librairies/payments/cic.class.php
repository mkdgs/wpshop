<?php

/***************************************************************************************
* Warning !! CMCIC_Config contains the key, you have to protect this file with all     *   
* the mechanism available in your development environment.                             *
* You may for instance put this file in another directory and/or change its name       *
***************************************************************************************/

define ("CMCIC_CLE", "011E8E48DA84C9091AD3E05F52EA00352EE8309B");
define ("CMCIC_TPE", "6595875");
define ("CMCIC_VERSION", "3.0");
define ("CMCIC_SERVEUR", "https://ssl.paiement.cic-banques.fr/test/");
define ("CMCIC_CODESOCIETE", "mvincentja");
define ("CMCIC_URLOK", "http://www.urlok.com");
define ("CMCIC_URLKO", "http://www.urlnotok.com");


define("CMCIC_CTLHMAC","V1.04.sha1.php--[CtlHmac%s%s]-%s");
define("CMCIC_CTLHMACSTR", "CtlHmac%s%s");
define("CMCIC_CGI2_RECEIPT","version=2\ncdr=%s");
define("CMCIC_CGI2_MACOK","0");
define("CMCIC_CGI2_MACNOTOK","1\n");
define("CMCIC_CGI2_FIELDS", "%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*");
define("CMCIC_CGI1_FIELDS", "%s*%s*%s%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s");
define("CMCIC_URLPAIEMENT", "paiement.cgi");

class CMCIC_Tpe {


	public $sVersion;	// Version du TPE - TPE Version (Ex : 3.0)
	public $sNumero;	// Numero du TPE - TPE Number (Ex : 1234567)
	public $sCodeSociete;	// Code Societe - Company code (Ex : companyname)
	public $sLangue;	// Langue - Language (Ex : FR, DE, EN, ..)
	public $sUrlOK;		// Url de retour OK - Return URL OK
	public $sUrlKO;		// Url de retour KO - Return URL KO
	public $sUrlPaiement;	// Url du serveur de paiement - Payment Server URL (Ex : https://paiement.creditmutuel.fr/paiement.cgi)

	private $_sCle;		// La cl� - The Key
	

	// Constructeur / Constructor
	
	function __construct($sLangue = "FR") {

		// contr�le de l'existence des constantes de param�trages.
		$aRequiredConstants = array('CMCIC_CLE', 'CMCIC_VERSION', 'CMCIC_TPE', 'CMCIC_CODESOCIETE');
		$this->_checkTpeParams($aRequiredConstants);

		$this->sVersion = CMCIC_VERSION;
		$this->_sCle = CMCIC_CLE;
		$this->sNumero = CMCIC_TPE;
		$this->sUrlPaiement = CMCIC_SERVEUR . CMCIC_URLPAIEMENT;

		$this->sCodeSociete = CMCIC_CODESOCIETE;
		$this->sLangue = $sLangue;

		$this->sUrlOK = CMCIC_URLOK;
		$this->sUrlKO = CMCIC_URLKO;

	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : getCle
	//
	// Renvoie la cl� du TPE / return the TPE Key
	//
	// ----------------------------------------------------------------------------

	public function getCle() {

		return $this->_sCle;
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : _checkTpeParams
	//
	// Contr�le l'existence des constantes d'initialisation du TPE
	// Check for the initialising constants of the TPE
	//
	// ----------------------------------------------------------------------------

	private function _checkTpeParams($aConstants) {

		for ($i = 0; $i < count($aConstants); $i++)
			if (!defined($aConstants[$i]))
				die ("Erreur param�tre " . $aConstants[$i] . " ind�fini");
	}

}


/*****************************************************************************
*
* Classe / Class : CMCIC_Hmac
*
*****************************************************************************/

class CMCIC_Hmac {

	private $_sUsableKey;	// La cl� du TPE en format op�rationnel / The usable TPE key

	// ----------------------------------------------------------------------------
	//
	// Constructeur / Constructor
	//
	// ----------------------------------------------------------------------------

	function __construct($oTpe) {
		
		$this->_sUsableKey = $this->_getUsableKey($oTpe);
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : _getUsableKey
	//
	// Renvoie la cl� dans un format utilisable par la certification hmac
	// Return the key to be used in the hmac function
	//
	// ----------------------------------------------------------------------------

	private function _getUsableKey($oTpe){

		$hexStrKey  = substr($oTpe->getCle(), 0, 38);
		$hexFinal   = "" . substr($oTpe->getCle(), 38, 2) . "00";
    
		$cca0=ord($hexFinal); 

		if ($cca0>70 && $cca0<97) 
			$hexStrKey .= chr($cca0-23) . substr($hexFinal, 1, 1);
		else { 
			if (substr($hexFinal, 1, 1)=="M") 
				$hexStrKey .= substr($hexFinal, 0, 1) . "0"; 
			else 
				$hexStrKey .= substr($hexFinal, 0, 2);
		}


		return pack("H*", $hexStrKey);
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : computeHmac
	//
	// Renvoie le sceau HMAC d'une chaine de donn�es
	// Return the HMAC for a data string
	//
	// ----------------------------------------------------------------------------

	public function computeHmac($sData) {

		return strtolower(hash_hmac("sha1", $sData, $this->_sUsableKey));

		// If you don't have PHP 5 >= 5.1.2 and PECL hash >= 1.1 
		// you may use the hmac_sha1 function defined below
		//return strtolower($this->hmac_sha1($this->_sUsableKey, $sData));
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : hmac_sha1
	//
	// RFC 2104 HMAC implementation for PHP >= 4.3.0 - Creates a SHA1 HMAC.
	// Eliminates the need to install mhash to compute a HMAC
	// Adjusted from the md5 version by Lance Rushing .
	//
	// Impl�mentation RFC 2104 HMAC pour PHP >= 4.3.0 - Cr�ation d'un SHA1 HMAC.
	// Elimine l'installation de mhash pour le calcul d'un HMAC
	// Adapt�e de la version MD5 de Lance Rushing.
	//
	// ----------------------------------------------------------------------------

	public function hmac_sha1 ($key, $data) {
		
		$length = 64; // block length for SHA1
		if (strlen($key) > $length) { $key = pack("H*",sha1($key)); }
		$key  = str_pad($key, $length, chr(0x00));
		$ipad = str_pad('', $length, chr(0x36));
		$opad = str_pad('', $length, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return sha1($k_opad  . pack("H*",sha1($k_ipad . $data)));
	}	

}

// ----------------------------------------------------------------------------
// function getMethode 
//
// IN: 
// OUT: Donn�es soumises par GET ou POST / Data sent by GET or POST
// description: Renvoie le tableau des donn�es / Send back the data array
// ----------------------------------------------------------------------------

function getMethode()
{
    if ($_SERVER["REQUEST_METHOD"] == "GET")  
        return $_GET; 

    if ($_SERVER["REQUEST_METHOD"] == "POST")
	return $_POST;

    die ('Invalid REQUEST_METHOD (not GET, not POST).');
}

// ----------------------------------------------------------------------------
// function HtmlEncode
//
// IN:  chaine a encoder / String to encode
// OUT: Chaine encod�e / Encoded string
//
// Description: Encode special characters under HTML format
//                           ********************
//              Encodage des caract�res sp�ciaux au format HTML
// ----------------------------------------------------------------------------
function HtmlEncode ($data)
{
    $SAFE_OUT_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890._-";
    $encoded_data = "";
    $result = "";
    for ($i=0; $i<strlen($data); $i++)
    {
        if (strchr($SAFE_OUT_CHARS, $data{$i})) {
            $result .= $data{$i};
        }
        else if (($var = bin2hex(substr($data,$i,1))) <= "7F"){
            $result .= "&#x" . $var . ";";
        }
        else
            $result .= $data{$i};
            
    }
    return $result;
}

class wpshop_CIC {

	public function __construct() { 
		global $wpshop;
		
		if(!empty($_GET['paymentListener']) && $_GET['paymentListener']=='cic') {
			header("Pragma: no-cache");
			header("Content-type: text/plain");
			self::display_response();
			exit;
		}
	}

	function display_response() {

		// Begin Main : Retrieve Variables posted by CMCIC Payment Server 
		$CMCIC_bruteVars = getMethode();

		// TPE init variables
		$oTpe = new CMCIC_Tpe();
		$oHmac = new CMCIC_Hmac($oTpe);

		// Message Authentication
		$cgi2_fields = sprintf(CMCIC_CGI2_FIELDS, $oTpe->sNumero,
							  $CMCIC_bruteVars["date"],
								  $CMCIC_bruteVars['montant'],
								  $CMCIC_bruteVars['reference'],
								  $CMCIC_bruteVars['texte-libre'],
								  $oTpe->sVersion,
								  $CMCIC_bruteVars['code-retour'],
							  $CMCIC_bruteVars['cvx'],
							  $CMCIC_bruteVars['vld'],
							  $CMCIC_bruteVars['brand'],
							  $CMCIC_bruteVars['status3ds'],
							  $CMCIC_bruteVars['numauto'],
							  $CMCIC_bruteVars['motifrefus'],
							  $CMCIC_bruteVars['originecb'],
							  $CMCIC_bruteVars['bincb'],
							  $CMCIC_bruteVars['hpancb'],
							  $CMCIC_bruteVars['ipclient'],
							  $CMCIC_bruteVars['originetr'],
							  $CMCIC_bruteVars['veres'],
							  $CMCIC_bruteVars['pares']
							);
							
		if ($oHmac->computeHmac($cgi2_fields) == strtolower($CMCIC_bruteVars['MAC']))
			{
			switch($CMCIC_bruteVars['code-retour']) {
				case "Annulation" :
					// Attention : an autorization may still be delivered for this payment
					wpshop_payment::setOrderPaymentStatus($CMCIC_bruteVars['reference'], 'denied');
				break;

				case "payetest": // test
					wpshop_payment::setOrderPaymentStatus($CMCIC_bruteVars['reference'], 'completed');
					wpshop_payment::the_order_payment_is_completed($CMCIC_bruteVars['reference']);
				break;

				case "paiement": // prod
					wpshop_payment::setOrderPaymentStatus($CMCIC_bruteVars['reference'], 'completed');
					wpshop_payment::the_order_payment_is_completed($CMCIC_bruteVars['reference']);
				break;


				/*** ONLY FOR MULTIPART PAYMENT ***/
				case "paiement_pf2":
				case "paiement_pf3":
				case "paiement_pf4":
					// Payment has been accepted on the productive server for the part #N
					// return code is like paiement_pf[#N]
					// put your code here (email sending / Database update)
					// You have the amount of the payment part in $CMCIC_bruteVars['montantech']
					break;

				case "Annulation_pf2":
				case "Annulation_pf3":
				case "Annulation_pf4":
					// Payment has been refused on the productive server for the part #N
					// return code is like Annulation_pf[#N]
					// put your code here (email sending / Database update)
					// You have the amount of the payment part in $CMCIC_bruteVars['montantech']
				break;
					
			}

			$receipt = CMCIC_CGI2_MACOK;

		}
		else
		{
			// your code if the HMAC doesn't match
			$receipt = CMCIC_CGI2_MACNOTOK.$cgi2_fields;
		}

		// Send receipt to CMCIC server
		printf (CMCIC_CGI2_RECEIPT, $receipt);
	}
	
	function display_form($oid) {
	
		$order = get_post_meta($oid, '_order_postmeta', true);
		$currency_code = wpshop_tools::wpshop_get_currency($code=true);
		
		if(!empty($order) && !empty($currency_code)) {
		
			$sOptions = "";
			// ----------------------------------------------------------------------------
			//  CheckOut Stub setting fictious Merchant and Order datas.
			//  That's your job to set actual order fields. Here is a stub.
			// -----------------------------------------------------------------------------
			$sReference = $oid; // Reference: unique, alphaNum (A-Z a-z 0-9), 12 characters max
			$sMontant = number_format($order['order_grand_total'],2,'.',''); // Amount : format  "xxxxx.yy" (no spaces)
			$sDevise  = $currency_code; // Currency : ISO 4217 compliant
			$sTexteLibre = ""; // free texte : a bigger reference, session context for the return on the merchant website
			$sDate = date("d/m/Y:H:i:s"); // transaction date : format d/m/y:h:m:s
			$sLangue = "FR"; // Language of the company code
			$sEmail = "dev@eoxia.com"; // customer email
			///////////////////////////////////////////////////////////////////////////////////////////
			$sNbrEch = ""; //$sNbrEch = "4"; // between 2 and 4
			$sDateEcheance1 = ""; // date echeance 1 - format dd/mm/yyyy //$sDateEcheance1 = date("d/m/Y");
			$sMontantEcheance1 = ""; // montant �ch�ance 1 - format  "xxxxx.yy" (no spaces) //$sMontantEcheance1 = "0.26" . $sDevise;
			$sDateEcheance2 = ""; // date echeance 2 - format dd/mm/yyyy
			$sMontantEcheance2 = ""; // montant �ch�ance 2 - format  "xxxxx.yy" (no spaces) //$sMontantEcheance2 = "0.25" . $sDevise;
			$sDateEcheance3 = ""; // date echeance 3 - format dd/mm/yyyy
			$sMontantEcheance3 = ""; // montant �ch�ance 3 - format  "xxxxx.yy" (no spaces) //$sMontantEcheance3 = "0.25" . $sDevise;
			$sDateEcheance4 = ""; // date echeance 4 - format dd/mm/yyyy
			$sMontantEcheance4 = ""; // montant �ch�ance 4 - format  "xxxxx.yy" (no spaces) //$sMontantEcheance4 = "0.25" . $sDevise;

			// ----------------------------------------------------------------------------

			$oTpe = new CMCIC_Tpe($sLangue);     		
			$oHmac = new CMCIC_Hmac($oTpe);      	        

			// Control String for support
			$CtlHmac = sprintf(CMCIC_CTLHMAC, $oTpe->sVersion, $oTpe->sNumero, $oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $oTpe->sVersion, $oTpe->sNumero)));

			// Data to certify
			$PHP1_FIELDS = sprintf(CMCIC_CGI1_FIELDS,     $oTpe->sNumero,
															$sDate,
															$sMontant,
															$sDevise,
															$sReference,
															$sTexteLibre,
															$oTpe->sVersion,
															$oTpe->sLangue,
															$oTpe->sCodeSociete, 
															$sEmail,
															$sNbrEch,
															$sDateEcheance1,
															$sMontantEcheance1,
															$sDateEcheance2,
															$sMontantEcheance2,
															$sDateEcheance3,
															$sMontantEcheance3,
															$sDateEcheance4,
															$sMontantEcheance4,
															$sOptions);

			// MAC computation
			$sMAC = $oHmac->computeHmac($PHP1_FIELDS);
		?>
		<script type="text/javascript">jQuery(document).ready(function(){ jQuery('#PaymentRequest_cic').submit(); });</script>
		<div class="paypalPaymentLoading"><span>Redirection vers le site CIC en cours...</span></div>
		<form action="<?php echo $oTpe->sUrlPaiement;?>" method="post" id="PaymentRequest_cic">
			<input type="hidden" name="version"             id="version"        value="<?php echo $oTpe->sVersion;?>" />
			<input type="hidden" name="TPE"                 id="TPE"            value="<?php echo $oTpe->sNumero;?>" />
			<input type="hidden" name="date"                id="date"           value="<?php echo $sDate;?>" />
			<input type="hidden" name="montant"             id="montant"        value="<?php echo $sMontant . $sDevise;?>" />
			<input type="hidden" name="reference"           id="reference"      value="<?php echo $sReference;?>" />
			<input type="hidden" name="MAC"                 id="MAC"            value="<?php echo $sMAC;?>" />
			<input type="hidden" name="url_retour"          id="url_retour"     value="<?php echo $oTpe->sUrlKO;?>" />
			<input type="hidden" name="url_retour_ok"       id="url_retour_ok"  value="<?php echo $oTpe->sUrlOK;?>" />
			<input type="hidden" name="url_retour_err"      id="url_retour_err" value="<?php echo $oTpe->sUrlKO;?>" />
			<input type="hidden" name="lgue"                id="lgue"           value="<?php echo $oTpe->sLangue;?>" />
			<input type="hidden" name="societe"             id="societe"        value="<?php echo $oTpe->sCodeSociete;?>" />
			<input type="hidden" name="texte-libre"         id="texte-libre"    value="<?php echo HtmlEncode($sTexteLibre);?>" />
			<input type="hidden" name="mail"                id="mail"           value="<?php echo $sEmail;?>" />
			<!-- Uniquement pour le Paiement fractionn� -->
			<input type="hidden" name="nbrech"              id="nbrech"         value="<?php echo $sNbrEch;?>" />
			<input type="hidden" name="dateech1"            id="dateech1"       value="<?php echo $sDateEcheance1;?>" />
			<input type="hidden" name="montantech1"         id="montantech1"    value="<?php echo $sMontantEcheance1;?>" />
			<input type="hidden" name="dateech2"            id="dateech2"       value="<?php echo $sDateEcheance2;?>" />
			<input type="hidden" name="montantech2"         id="montantech2"    value="<?php echo $sMontantEcheance2;?>" />
			<input type="hidden" name="dateech3"            id="dateech3"       value="<?php echo $sDateEcheance3;?>" />
			<input type="hidden" name="montantech3"         id="montantech3"    value="<?php echo $sMontantEcheance3;?>" />
			<input type="hidden" name="dateech4"            id="dateech4"       value="<?php echo $sDateEcheance4;?>" />
			<input type="hidden" name="montantech4"         id="montantech4"    value="<?php echo $sMontantEcheance4;?>" />
			<!-- -->
			<noscript><input type="submit" name="bouton"              id="bouton"         value="Connexion / Connection" /></noscript>
		</form>
<?php
		}
	}
}
?>