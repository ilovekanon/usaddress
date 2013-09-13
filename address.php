<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<title></title>
<style type='text/css'>
#ttable {
	border-collapse:collapse;
	width: 400px;
	margin-top:15px;
	font:12px arial;
}
#ttable th ,#ttable td {
	border:1px solid #cdcdcd;
}
#ttable th {
	width:150px;
	text-align:right;
	padding-right:10px;
	height:27px;
}
#ttable td {
	text-align:left;
	padding-left:5px;
}
#ttable td input[type=text] {
	border:1px solid gray;
}
</style>
</head>

 <body>
<?php
require_once('Address_validation.php');

try{
	$av = new Address_validation();
	if(!$_GET['returntype']){
		$returntype = 'Array';
	}else{
		$returntype = $_GET['returntype'];
	}
	
	$av->setReturnType($returntype); // or json (Default)

	// $address['address1'],$address['city'],$address['state'],$address['zipcode'],$address['country'] is required
	// $address['country'] = 'US' Only
	$address = array();
	$address['address1'] = $_GET['address1'];
	if($_GET['address2']) $address['address2'] = $_GET['address2'];
	$address['city'] = $_GET['city'];
	$address['state'] = $_GET['state'];
	$address['zipcode'] = $_GET['zipcode'];
	if(isset($_GET['country']) && $_GET['country']){
		$address['country'] = $_GET['country'];
	}else{
		$address['country'] = 'US';
	}
	
	$av->setAddress($address);
	$data = $av->getResponse();
	
//	echo "<pre>";
//	print_r($data);
//	echo "</pre>";
	
	if(is_array($data) && isset($data['error'])){
		echo "[Error Code : ".$data['error']['errorCode']."] : ".$data['error']['errorDescription'];
	}
}catch(Exception $e){
	echo $e->getMessage();
}

if(strtolower($returntype) == 'json'){
	echo $data;
	exit;
}

$dataSize = sizeof($data);
if($dataSize != 1){
?>
<h3> Couldn't find exactly matched address </h3>
<?php
}
if(!isset($data['error'])){
for ($i=0; $i<$dataSize; $i++){
?>
<table border='1' cellspacing='0' cellpadding='0' id='ttable'>
<tr>
	<th> Residential or <br />Commercial : </th>
	<td><?php echo $data[$i]['description']; ?></td>
</tr><tr>
	<th> Address : </th>
	<td><?php echo $data[$i]['address']; ?></td>
</tr><tr>
	<th> City : </th>
	<td><?php echo $data[$i]['city']; ?></td>
</tr><tr>
	<th> State : </th>
	<td><?php echo $data[$i]['state']; ?></td>
</tr><tr>
	<th> Zip Code : </th>
	<td><?php echo $data[$i]['zipcode']; ?></td>
</tr><tr>
	<th> Country : </th>
	<td><?php echo $data[$i]['country']; ?></td>
</tr>
</table>
<?php
}}
?>
<br /><br /><input type='button' value='Back' onclick="location.href='index.html'"; />
 </body>
</html>
