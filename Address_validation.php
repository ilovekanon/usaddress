<?php
require_once('ups_config.php');

class Address_validation {
	protected $_url;
	protected $_returnType = 'json';
	protected $_consignee;
	protected $_address1;
	protected $_address2;
	protected $_city;
	protected $_state;
	protected $_zipCode;
	protected $_country;
	
	public function __construct(){
		if(strtolower(MODE) == 'dev'){
			$this->_url = "https://wwwcie.ups.com/ups.app/xml/XAV"; 
		}else{
			$this->_url = "https://onlinetools.ups.com/ups.app/xml/XAV"	;
		}
	}

	public function setAddress($addressArray){
		if(!is_array($addressArray)){
			throw new Exception("Address Info should be an array!");
		}
		
		if(!isset($addressArray['address1']) && $addressArray['address1'] == ''){
			throw new Exception("Address1 is required, array key is address1");
		}
		if(!isset($addressArray['city']) && $addressArray['city'] == ''){
			throw new Exception("City is required, array key is city");
		}
		if(!isset($addressArray['state']) && $addressArray['state'] == ''){
			throw new Exception("State is required, array key is state");
		}
		if(!isset($addressArray['zipcode']) && $addressArray['zipcode'] == ''){
			throw new Exception("Zip Code is required, array key is zipcode");
		}
		if(!isset($addressArray['country']) && $addressArray['country'] != 'US'){
			throw new Exception("Country is required and county should be US only, array key is country");
		}
		
		if(isset($addressArray['consignee'])) $this->_consignee = $addressArray['consignee'];
		$this->_address1 = $addressArray['address1'];
		if(isset($addressArray['address2'])) $this->_address2 = $addressArray['address2'];
		$this->_city = $addressArray['city'];
		$this->_state = $addressArray['state'];
		$this->_zipcode = $addressArray['zipcode'];
		$this->_country = $addressArray['country'];
	}

	public function setReturnType($type){
		$this->_returnType = $type;
	}

	public function getResponse(){
		$response = $this->request();

		$responseArray = new SimpleXMLElement($response);
		$data = array(); //return data container

		//print_r($responseArray);

		if($responseArray->Response->ResponseStatusDescription == 'Failure'){
			// error
			$data['error']['errorSeverity'] = $responseArray->Response->Error->ErrorSeverity;
			$data['error']['errorCode'] = $responseArray->Response->Error->ErrorCode;
			$data['error']['errorDescription'] = $responseArray->Response->Error->ErrorDescription;

			return $this->getReturnByType($data);
		}
	
		$addressSize = sizeof($responseArray->AddressKeyFormat);
		if($addressSize <1){
			$data['error']['errorCode'] = "0000";
			$data['error']['errorDescription'] = "Can not find the address";
		}elseif($addressSize > 1){
			$arrSize = sizeof($responseArray->AddressKeyFormat);

			for($i=0; $i<$arrSize; $i++){
				$temp = $responseArray->AddressKeyFormat[$i];
				
				$data[$i]['code'] = $temp->AddressClassification->Code;
				$data[$i]['description'] = $temp->AddressClassification->Description;
				
				if(is_array($temp->AddressLine)){
					$data[$i]['address'] = implode(",",$temp->AddressLine);
				}else{
					$data[$i]['address'] = $temp->AddressLine;
				}
				$data[$i]['region'] = $temp->Region;
				$data[$i]['city'] = $temp->PoliticalDivision2;
				$data[$i]['state'] = $temp->PoliticalDivision1;
				$data[$i]['zipcode'] = $temp->PostcodePrimaryLow;
				$data[$i]['country'] = $temp->CountryCode;
			}
		}else{
			$temp = $responseArray->AddressKeyFormat;

			$data[0]['code'] = $temp->AddressClassification->Code;
			$data[0]['description'] = $temp->AddressClassification->Description;

			if(is_array($temp->AddressLine)){
				$data[0]['address'] = implode(",",$temp->AddressLine);
			}else{
				$data[0]['address'] = $temp->AddressLine;
			}
			$data[0]['region'] = $temp->Region;
			$data[0]['city'] = $temp->PoliticalDivision2;
			$data[0]['state'] = $temp->PoliticalDivision1;
			$data[0]['zipcode'] = $temp->PostcodePrimaryLow;
			$data[0]['country'] = $temp->CountryCode;
		}

		return $this->getReturnByType($data);
	}
	
	protected function getReturnByType($data){
		if(strtolower($this->_returnType) == 'json'){
			return json_encode($data);	
		}else{
			return $data;
		}
	}

	protected function request(){
		$xml = $this->generateXML();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if($status != '200'){
			throw new Exception("CURL Faild, status code is : ".$status);
		}

		curl_close($ch);

		return $response;
	}

	protected function generateXML(){
		$xml  = "<?xml version='1.0' ?>
		<AccessRequest xml:lang='en-US'>
			<AccessLicenseNumber>".ACCESS_LICENSE_NUMBER."</AccessLicenseNumber>
			<UserId>".USER_ID."</UserId>
			<Password>".USER_PASSWORD."</Password>
		</AccessRequest>
		<?xml version='1.0' ?>
		<AddressValidationRequest xml:lang='en-US'>
			<Request>
				<TransactionReference>
					<CustomerContext /><XpciVersion>1.0001</XpciVersion>
				</TransactionReference> 
				<RequestAction>XAV</RequestAction>
				<RequestOption>3</RequestOption>
			</Request>
			<MaximumListSize>3</MaximumListSize>
			<AddressKeyFormat>";
			if($this->_consignee) $xml .= "	<ConsigneeName>UPS Capital</ConsigneeName>";
			$xml .= "<AddressLine>".$this->_address1."</AddressLine>";
			if($this->_address2) $xml .= "<AddressLine>".$this->_address2."</AddressLine>";
			$xml .= "
				<PoliticalDivision2>".$this->_city."</PoliticalDivision2>
				<PoliticalDivision1>".$this->_state."</PoliticalDivision1>
				<PostcodePrimaryLow>".$this->_zipcode."</PostcodePrimaryLow>
				<CountryCode>".$this->_country."</CountryCode>
			</AddressKeyFormat>
		</AddressValidationRequest>
		";
		return $xml;
	}
}//class
?>