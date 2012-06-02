<?php
    
/**
 * Sagepay Form Integration Class (PHP5)
 * @author Gavin Kimpson <gkimpson@gmail.com>
 * @copyright Copyright (c) 2009, Gavin Kimpson
 * 
 * 
 * Version History
 * v1.0 - 11/2009 initial release
 * v1.1 - 10/2011 removed some irrevelant code
 */

class SagepayForm {
    public $VPSProtocol             = 2.23;
    public $TxType                  = 'PAYMENT';
    public $Vendor                  = 'testvendor';         // sagepay vendorname (defaults to this)
    public $EncryptionPassword      = 'testvendor';         // sagepay password (defaults to this)
    public $YourSiteFQDN            = APP_WEBROOT;          // enter the full URL (not relative)
    public $VendorTxCode;           // This should be your own reference code to the transaction. Your site should provide a completely unique VendorTxCode for each transaction.
    public $VendorPrefix            = 'ABC';
    public $ReferrerID;
    public $Amount;
    public $Currency                = 'GBP';
    public $Description             = 'Your website Online';
    public $SuccessURL              = 'order-success.php';
    public $FailureURL              = 'order-failed.php';
    public $VendorEmail;
    public $SendEmail               = 1;          
    public $EmailMessage            = 'Thank you so very much for your order.';
    public $Basket;
    public $AllowGiftAid            = 0;
    public $ApplyAVSCV2             = 0;
    public $Apply3DSecure           = 0;
    
    public $CustomerName;
    public $CustomerEmail;
    public $BillingSurname;
    public $BillingFirstnames;
    public $BillingAddress1;
    public $BillingAddress2;
    public $BillingCity;
    public $BillingPostCode;
    public $BillingState;
    public $BillingCountry          = 'GB'; // default to GB (United Kingdom) ISO 3166 standard
    public $BillingPhone;
    public $isDeliverySame          = 'Y';    
    public $DeliverySurname;
    public $DeliveryFirstnames;
    public $DeliveryAddress1;
    public $DeliveryAddress2;
    public $DeliveryCity;
    public $DeliveryPostCode;
    public $DeliveryState;
    public $DeliveryCountry         = 'GB'; // default to GB (United Kingdom) ISO 3166 standard
    public $DeliveryPhone;
    
    public $GeneratedString;
    public $Crypt;
    public $StatusDetailErrors      = array('OK', 'NOTAUTHED', 'REJECTED', 'ERROR', 'ABORT', 'INVALID', 'MALFORMED');
    public $StatusDetail;
    public $cart;                               // wfc cart instance
    public $paymentGateway;
    public $shippingCharge = "0.00";            // shipping charge (default to FREE eg. 0.00)
     
    public $Tax = APP_VAT;
    public $TaxDecimal = APP_VAT_DECIMAL;    
    public $TaxAmount = 0.00;
    public $ApplyTax = true;
    public $instructions;
    
        /**
         * constructor
         * @param obj $cart (wfcCart object)
         * @param string $vendorname
         * @param string $password
         * @param string $instructions
         * @param string $successURL (optional)
         * @param string $failedURL (optional)
         * @return void
         */
        function __construct($cart, $vendorname, $password, $instructions = '', $successURL = 'order-success.php', $failedURL = 'order-failed.php') {
            $this->cart = $cart;
            $this->setPaymentGateway();
            $this->Vendor = $vendorname;
            $this->EncryptionPassword = $password;
            $this->instructions = $instructions;
            $this->generateVendorTxCode();
            $this->setSuccessURL($successURL);
            $this->setFailureURL($failedURL);
            
            $this->Amount = $cart->final_total;      
        } // end function


        /**
         * payment gateway
         * @param string $gateway ('LIVE', 'TEST', 'SIMULATOR') defaults to SIMULATOR
         * @return void
         */
        function setPaymentGateway($gateway = 'SIMULATOR') {
            if ($gateway === 'LIVE') {
                $this->paymentGateway = "https://live.sagepay.com/gateway/service/vspform-register.vsp";
            } elseif ($gateway === 'TEST') {
                $this->paymentGateway = "https://test.sagepay.com/gateway/service/vspform-register.vsp";
            } elseif ($gateway === 'SIMULATOR') {
                $this->paymentGateway = "https://test.sagepay.com/Simulator/VSPFormGateway.asp";
            }
        } // end function
        

        /**
         * set description
         * Free text description of goods or services being purchased
         * @param string $string
         * @return void
         */
        function setDescription($description) {
            $this->Description = $description;
        } // end function

    
        /**
         * set vendor email address
         * @param string $email
         * @return void
         */    
        function setVendorEmail($email) {
            $this->VendorEmail = $email;
        } // end function
        
        
        /**
         * set referrerID
         * @param string $referrerID
         * @return void
         */
        function setReferrerID($referrerID) {
            $this->ReferrerID = $referrerID;
        } // end function

        
        /**
         * set full url path
         * @param string $url
         * @return void
         */
        function setYourSiteFQDN($url) {
            $this->YourSiteFQDN = $url;
        } // end function


        /**
         * set description
         * 0 = Do not send either customer or vendor e-mails
         * 1 = Send customer and vendor e-mails if addresses are provided (DEFAULT)
         * 2 = Send vendor e-mail but NOT the customer e-mail
         * @param int $flag 
         * @return void
         */
        function setSendEmail($flag) {
            $this->SendEmail = $flag;
        } // end function        

        
        /**
         * set email message
         * @param string $message
         * @return void
         */
        function setEmailMessage($message) {
            $this->EmailMessage = $message;
        } // end function

        
        /**
         * set Allow Gift Aid Box
         * 0 = No Gift Aid Box displayed (default)
         * 1 = Display Gift Aid Box on payment screen. 
         * @param int $flag
         * @return void
         */
         function setAllowGiftAidBox($flag) {
            $this->AllowGiftAid = $flag;
         } // end function
         
         
         /**
          * set Apply AVSCV2
          * 0 = If AVS/CV2 enabled then check them. If rules apply, use rules. (default)
          * 1 = Force AVS/CV2 checks even if not enabled for the account. If rules apply, use rules.
          * 2 = Force NO AVS/CV2 checks even if enabled on account.
          * 3 = Force AVS/CV2 checks even if not enabled for the account but DON’T apply any rules.
          * @param int $flag
          * @return void
          */
         function setApplyAVSCV2($flag) {
            $this->ApplyAVSCV2 = $flag;
         } // end function

        
        /**
         * set Apply 3DSecure
         * 0 = If 3D-Secure checks are possible and rules allow, perform the checks and apply the authorisation rules. (default)
         * 1 = Force 3D-Secure checks for this transaction if possible and apply rules for authorisation.
         * 2 = Do not perform 3D-Secure checks for this transaction and always authorise.
         * 3 = Force 3D-Secure checks for this transaction if possible but ALWAYS obtain an auth code, irrespective of rule base.
         * @param int $flag
         * @return void
         */
        function setApply3DSecure($flag) {
            $this->Apply3DSecure = $flag;
        } // end function
        

        /**
         * set successURL
         * @param string $url
         * @return void
         */
        function setSuccessURL($url) {
            $url = $this->YourSiteFQDN . $url;
            $this->SuccessURL = $url . '?vendorTxCode=' . $this->VendorTxCode . '&instructions=' . $this->instructions;
        } // end function
                       

        /**
         * set FailureURL
         * @param string $url
         * @return void
         */
        function setFailureURL($url) {
            $url = $this->YourSiteFQDN . $url;
            $this->FailureURL = $url;
        } // end function

        
        /**
         * set Currency (ISO4217 standard)
         * @param string $code
         * @return void
         */
        function setCurrency($code) {
            $this->setCurrency($code);
        } // end function
        
        
        /**
         * set Customer details (eg Customer & Billing details)
         * @param array $array
         * @return void
         */
        function setCustomerDetails($array) {
            $this->CustomerName         = $array['name'];
            $this->CustomerEmail        = $array['email'];
            $this->BillingSurname       = $array['surname'];
            $this->BillingFirstnames    = $array['firstnames'];
            $this->BillingAddress1      = $array['address1'];
            $this->BillingAddress2      = $array['address2'];
            $this->BillingCity          = $array['city'];
            $this->BillingPostCode      = $array['postcode'];
            $this->BillingState         = $array['state'];
            $this->BillingCountry       = ($array['country']) ? $array['country'] : $this->BillingCountry;
            $this->BillingPhone         = $array['telephone'];
        } // end function
        
        
        /**
         * set Delivery details
         * @param array $array
         * @return void
         */
        function setDeliveryDetails($array) {
            $this->DeliverySurname      = $array['surname'];
            $this->DeliveryFirstnames   = $array['firstnames'];
            $this->DeliveryAddress1     = $array['address1'];
            $this->DeliveryAddress2     = $array['address2'];
            $this->DeliveryCity         = $array['city'];
            $this->DeliveryPostCode     = $array['postcode'];
            $this->DeliveryState        = $array['state'];
            $this->DeliveryCountry      = ($array['country']) ? $array['country'] : $this->DeliveryCountry;
            $this->DeliveryPhone        = $array['telephone'];              
        } // end function
        
        
        /**
         * set if delivery address same as customer address 
         * @param array $array
         * @return void
         */
        function setIsDeliverySame($array) {
            $this->isDeliverySame = $array['is_delivery_same'];
        } // end function
        

        /**
         * set shipping charge
         * @param float $fee (optional)
         * @return void
         */
         function setShippingCharge($fee = '0.00') {
            $this->shippingCharge = $fee;
         } // end function
         
                 
        /**
         * get encrypted Crypt string & decrypt to get values (after transaction made)
         * @return array $array 
         */
        function transactionResult() {
            $string = base64_decode(str_replace(' ', '+', $_GET['crypt']));
            $output = $this->simpleXor($string, $this->EncryptionPassword);
            
            $array = $this->getToken($output);
            return $array;
        }   // end function
        
        
        /**
         * @param string $string
         * @param string $password
         * @return string $output
         */
        function simpleXor($string, $password) {
    		$data = array();
    
    		for ($i = 0; $i < strlen(utf8_decode($password)); $i++) {
    			$data[$i] = ord(substr($password, $i, 1));
    		}
    
    		$output = '';
    
    		for ($i = 0; $i < strlen(utf8_decode($string)); $i++) {
        		$output .= chr(ord(substr($string, $i, 1)) ^ ($data[$i % strlen(utf8_decode($password))]));
    		}
    
    		return $output;	            
        } // end function
    

        /** 
         * Base 64 Encoding function
         * PHP does it natively but just for consistency and ease of maintenance, let's declare our own function 
         * @param string $plain
         * @return string $output
         */      
        function base64Encode($plain) {
          // Initialise output variable
          $output = "";
          
          // Do encoding
          $output = base64_encode($plain);
          
          // Return the result
          return $output;
        } // end function
        
        
        /**
         * Base 64 decoding function
         * PHP does it natively but just for consistency and ease of maintenance, let's declare our own function
         * @param string $scrambled
         * @return string $output
         */
        function base64Decode($scrambled) {
          // Initialise output variable
          $output = "";
          
          // Fix plus to space conversion issue
          $scrambled = str_replace(" ","+",$scrambled);
          
          // Do encoding
          $output = base64_decode($scrambled);
          
          // Return the result
          return $output;
        } // end function
        
        
        /**
         * @param string $string
         * @return string $output
         */
    	private function getToken($string) {
      		$tokens = array(
       			'Status',
        		'StatusDetail',
        		'VendorTxCode',
       			'VPSTxId',
        		'TxAuthNo',
        		'Amount',
       			'AVSCV2', 
        		'AddressResult', 
        		'PostCodeResult', 
        		'CV2Result', 
        		'GiftAid', 
        		'3DSecureStatus', 
        		'CAVV',
    			'AddressStatus',
    			'CardType',
    			'Last4Digits',
    			'PayerStatus',
    			'CardType'
    		);		
    		
      		$output = array();
    		$data = array();
      
      		for ($i = count($tokens) - 1; $i >= 0; $i--){
        		$start = strpos($string, $tokens[$i]);
        		
    			if ($start){
         			$data[$i]['start'] = $start;
         			$data[$i]['token'] = $tokens[$i];
    			}
    		}
      
    		sort($data);
    		
    		for ($i = 0; $i < count($data); $i++){
    			$start = $data[$i]['start'] + strlen($data[$i]['token']) + 1;
    
    			if ($i == (count($data) - 1)) {
    				$output[$data[$i]['token']] = substr($string, $start);
    			} else {
    				$length = $data[$i+1]['start'] - $data[$i]['start'] - strlen($data[$i]['token']) - 2;
    				
    				$output[$data[$i]['token']] = substr($string, $start, $length);
    			}      
    
    		}
      
    		return $output;	
        } // end function


        /**
         * Filters unwanted characters out of an input string.  Useful for tidying up FORM field inputs.
         * @param string $strRawText
         * @param string $strType
         * @return string $cleanInput
         */
        function cleanInput($strRawText, $strType) {        
        	if ($strType=="Number") {
        		$strClean="0123456789.";
        		$bolHighOrder=false;
        	}
        	else if ($strType=="VendorTxCode") {
        		$strClean="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
        		$bolHighOrder=false;
        	}
        	else {
          		$strClean=" ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.,'/{}@():?-_&£$=%~<>*+\"";
        		$bolHighOrder=true;
        	}
        	
        	$strCleanedText="";
        	$iCharPos = 0;
        		
        	do
        		{
            		// Only include valid characters
        			$chrThisChar=substr($strRawText,$iCharPos,1);
        			
        			if (strspn($chrThisChar,$strClean,0,strlen($strClean))>0) { 
        				$strCleanedText=$strCleanedText . $chrThisChar;
        			}
        			else if ($bolHighOrder==true) {
        				// Fix to allow accented characters and most high order bit chars which are harmless 
        				if (bin2hex($chrThisChar)>=191) {
        					$strCleanedText=$strCleanedText . $chrThisChar;
        				}
        			}
        			
        		$iCharPos=$iCharPos+1;
        		}
        	while ($iCharPos<strlen($strRawText));
        		
          	$cleanInput = ltrim($strCleanedText);
        	return $cleanInput;        	
        } // end function


        /**
         * function to redirect browser to a specific page
         * @param string $url
         * @return void
         */
        function redirect($url) {
           if (!headers_sent())
               header('Location: '.$url);
           else {
               echo '<script type="text/javascript">';
               echo 'window.location.href="'.$url.'";';
               echo '</script>';
               echo '<noscript>';
               echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
               echo '</noscript>';
           }
        } // end function  


        /**
         * function to check validity of email address entered in form fields
         * @param string $email
         * @return bool $result
         */
        function is_valid_email($email) {
            $result = TRUE;
            if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email))
                $result = FALSE; 
            return $result;
        } // end function


        /**
         * generate the basket string that will be appended to the generateStringData()
         * 
            Number of lines of detail in the basket field: (eg delivery is 1 individual line) 
            Item 1 Description: 
            Quantity of item 1:  
            Unit cost item 1 without tax:  
            Tax applied to item 1:  
            Cost of Item 1 including tax:  
            Total cost of item 1 (Quantity x cost including tax):  
            Item 2 Description:  
            Quantity of item 2:  
            ....  
            Cost of Item n including tax:  
            Total cost of item n
            
            4:Pioneer NSDV99 DVD-Surround Sound System:1:424.68:74.32:499.00: 499.00:Donnie Darko Director’s 
            Cut:3:11.91:2.08:13.99:41.97: Finding Nemo:2:11.05:1.94:12.99:25.98: Delivery:---:---:---:---:4.99
            
         * If you wish to leave a field empty, you must still include the colon. e.g.
         * DVD Player:1:199.99:::199.99
         * @return void
         */
        private function generateBasketString() {
            $cart = $this->cart;
            if (!is_object($cart))
                return false;
            // eg //3:Xerox Phaser 999:5:85.00::425:Xerox Phaser 991:15:15.00::225:Delivery:---:---:---:---:0.00    
            $additional_lines = ($this->TaxAmount) ? 2 : 1;
            
            $no_of_lines = $cart->itemcount + $additional_lines;    // add one extra line for the delivery charges 
            $line = "{$no_of_lines}:";
 
            // loop through each line
            // product-title : qty : unit cost minus tax : tax applied to item : unit cost inc tax
            foreach ($cart->get_contents() as $v) {
                if (is_array($v['options']))
                    $options = implode(', ', $v['options']);
                    
                $v['price'] = number_format($v['price'], 2);        // force 18.9 to become 18.90 in the basket
                $v['subtotal'] = number_format($v['subtotal'], 2);

                if ($options)                    
                    $line .= "{$v['info']} [$options]:{$v['qty']}:{$v['price']}:::{$v['subtotal']}:";
                else
                    $line .= "{$v['info']}:{$v['qty']}:{$v['price']}:::{$v['subtotal']}:";
            }
            
            if ($this->TaxAmount)
                $line .= "VAT :---:---:---:---:" . number_format($this->TaxAmount, 2) . ":";
                    
            // add delivery line including the shipping charge
            $shipping_type = $cart->shipping_type;
            $line .= "Delivery ({$shipping_type}):---:---:---:---:" . number_format($this->shippingCharge, 2);

            // save basket string
            $this->Basket = $line;
        } // end function

        
        /**
         * generate the VendorTxCode        
         * @return void
         */                         
        private function generateVendorTxCode() {
            $code = uniqid(mt_rand(), true);            
            $code = substr($code, 0, 8);
            $code = $this->VendorPrefix . '-' . $code;
            
            // check this doesn't exist - if so redo function to generate a new code
            $objOrder = & new Orders;            
            if ($objOrder->checkUniqueVendorTxCode($code))
                $this->generateVendorTxCode();
            
            $this->VendorTxCode = $code;
        } // end function
        
               
        /**
         * generate the string required for creating the 'crypt' string
         * @return void
         */
        private function generateStringData() {                    
            $string = "&VendorTxCode=" .            $this->VendorTxCode;
                        
            if ($this->ReferrerID)
                $string .= "&ReferrerID=" .         $this->ReferrerID ;
            
            $string .= "&Amount=" .                 number_format($this->Amount, 2);
            $string .= "&Currency=" .               $this->Currency;
            $string .= "&Description=" .            $this->Description;
            $string .= "&SuccessURL=" .             $this->SuccessURL;
            $string .= "&FailureURL=" .             $this->FailureURL;
            $string .= "&CustomerName=" .           $this->BillingFirstnames . " " . $this->BillingSurname;
            $string .= "&SendEmail=" .              $this->SendEmail;
            $string .= "&CustomerEmail=" .          $this->CustomerEmail;
            $string .= "&VendorEmail=" .            $this->VendorEmail;
            $string .= "&eMailMessage=" .           $this->EmailMessage;
            $string .= "&BillingFirstnames=" .      $this->BillingFirstnames;
            $string .= "&BillingSurname=" .         $this->BillingSurname;
            $string .= "&BillingAddress1=" .        $this->BillingAddress1;
            if ($this->BillingAddress2)
                $string .= "&BillingAddress2=" .    $this->BillingAddress2;
            $string .= "&BillingCity=" .            $this->BillingCity;
            $string .= "&BillingPostCode=" .        $this->BillingPostCode;
            $string .= "&BillingCountry=" .         $this->BillingCountry;
            if ($this->BillingState)
                $string .= "&BillingState=" .       $this->BillingState;
            if ($this->BillingPhone)
                $string .= "&BillingPhone=" .       $this->BillingPhone;
            $string .= "&DeliveryFirstnames=" .     $this->DeliveryFirstnames;
            $string .= "&DeliverySurname=" .        $this->DeliverySurname;
            $string .= "&DeliveryAddress1=" .       $this->DeliveryAddress1;
            $string .= "&DeliveryAddress2=" .       $this->DeliveryAddress2;
            $string .= "&DeliveryCity=" .           $this->DeliveryCity;
            $string .= "&DeliveryPostcode=" .       $this->DeliveryPostCode;
            $string .= "&DeliveryCountry=" .        $this->DeliveryCountry;
            if ($this->DeliveryState)
                $string .= "&DeliveryState=" .      $this->DeliveryState;
            if ($this->DeliveryPhone)
                $string .= "&DeliveryPhone=" .      $this->DeliveryPhone;
            $string .= "&Basket=" .                 $this->Basket;
            $string .= "&AllowGiftAid=" .           $this->AllowGiftAid;
            if ($this->TxType == 'AUTHENTICATE')
                $string .= "&ApplyAVSCV2=" .        $this->ApplyAVSCV2;
            $string .= "&Apply3DSecure=" .          $this->Apply3DSecure;
            
            $this->GeneratedString = $string;
        } // end function   


        /**
         * generate entire string (include sagepay options, basket, customer billing & delivery)
         * @return void 
         */
        private function generateFullString() {
            $this->generateBasketString();
            $this->generateStringData();
        } // end function
            
        
        /**
         * generate crypt string to be sent to sagepay
         * @return void
         */
        private function generateCrypt() {
            $crypt = $this->base64Encode($this->SimpleXor($this->GeneratedString, $this->EncryptionPassword));
            $this->Crypt = $crypt;
        } // end function    
             
        
        /**
         * set sagepay errors
         * @link http://www.sagepay.com/errorindex.asp
         *    
         * @param mixed $error
         * @return void
         */
         function setStatusDetailError($error) {
            $this->StatusDetail = $error;
         } // end function
         
         
         /**
          * get sagepay errors
          * @return array $arrStatus
          */
         function getStatusDetailError() {            
        		if (preg_match("/NOTAUTHED/i", $this->StatusDetail) || preg_match("/REJECTED/i", $this->StatusDetail)) {
        			$arrStatus['title'] = "The VSP was unable to authorise your payment";
        			$arrStatus['text'] = "The acquiring bank would not authorise your selected method of payment.  You will not be charged for this transaction.";
        			$arrStatus['context'] = "Rejected";       		  
        		} elseif (preg_match("/MALFORMED/i", $this->StatusDetail) || preg_match("/INVALID/i", $this->StatusDetail)) {
        			$arrStatus['title'] = "Transaction Registration POST is poorly formatted";
        			$arrStatus['text'] = "Transaction Registration POST is poorly formatted. Please contact the website administrator. You will not be charged for this transaction.";
        			$arrStatus['context'] = "Failed";        		  
        		} elseif (preg_match("/ABORT/i", $this->StatusDetail)) {
        			$arrStatus['title'] = "You chose to cancel your online payment";
        			$arrStatus['text'] = "Any credit/debit card details you entered have not been sent to the bank. You will not be charged for this transaction.";
        			$arrStatus['context'] = "Aborted";        		  
                } else {
        			$arrStatus['title'] = "An error has occurred at Sagepay";
        			$arrStatus['text'] = "Because an error occurred in the payment process, you will not be charged for this transaction, even if an authorisation was given by the bank.";
        			$arrStatus['context'] = "Failed";                     
                }
            return $arrStatus;        
         } // end function


        /**
         * once all details required rec'd action everything else & complete
         * @return void 
         */
        function action() {
            $this->generateFullString();
            $this->generateCrypt();
        } // end function


        /**
         * set tax amount
         * @param float $tax
         */         
        function setTaxAmount($tax) {
            $this->TaxAmount = $tax;
        }
                  

        /**
         * set Apply Tax
         * true = VAT Excluded (and not displayed)
         * false = VAT Included (and is displayed)                  
         * @param bool $flag
         */
         function setApplyTax($flag) {
            $this->ApplyTax = $flag;
         }
                           
        
} // end class

?>