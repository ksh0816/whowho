<?
	//SKT
	$mvno="(DESCRIPTION = (ADDRESS_LIST =(ADDRESS = (PROTOCOL = TCP)(HOST =61.41.9.40)(PORT = 1521))) (CONNECT_DATA = (SERVICE_NAME = orcl)))";
	$conn=oci_connect("mvno","mvno",$mvno,"KO16MSWIN949");  

	//LG
	$mvno_lg="(DESCRIPTION = (ADDRESS_LIST =(ADDRESS = (PROTOCOL = TCP)(HOST =61.41.9.35)(PORT = 1521))) (CONNECT_DATA = (SERVICE_NAME = orcl)))";
	$conn_lg=oci_connect("mvno","mvno_lg",$mvno_lg,"KO16MSWIN949"); 

?>