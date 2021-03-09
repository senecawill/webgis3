<?php


function getLayerList()
	{

		/*
			//Check Seneca Session Stuff Here
			
			if (session_id() == '' || !isset($_SESSION)) {
					// session isn't started
					session_start();
			}	
	
			if (isset($_SESSION['userid_seneca'])) {
				$dbconn = pg_connect("host=prodpostgis.c2uuqvceakbj.us-east-1.rds.amazonaws.com dbname=seneca_security user=webgis_admin password=Gr00vy123") or die('Could not connect: ' . pg_last_error());
					$query = "select * from security_schema.editor_config ec, security_schema.editor_config_users br where ec.editor_config_id = br.editor_config_id and br.user_id = " . $_SESSION['userid_seneca'] . ";";
		
					$result = pg_query($query);
					$arr = pg_fetch_all($result);
					header("Content-Type: application/json");
					echo json_encode($arr);
			}
		}
		*/
	
	
		$mapBookParts = array();
		// map sources
		$mapBookParts[0] = '';
		// catalog
		$mapBookParts[1] = '';
		
		
		$mapSourcePartOne = '<map-source name="{LAYER_NAME}_wfs" type="wfs" >
			<url>{SERVER_URL}/wfs</url>
			<param name="typename" value="{SERVER_NAME}:{LAYER_NAME}"/>
			<param name="cross-origin" value="anonymous"/>
			<config name="geometry-name" value="{GEOMETRY_NAME}"/>
			<properties>
			';
		
		//properties go here
		$columnsPropertiesPart = '<property name="{COLUMN_NAME}" label="{COLUMN_CAPTION}" />
		';
		
		$mapSourcePartTwo = '        </properties>        
			<layer name="{LAYER_NAME}_wfs" status="off" >    
				<template name="identify"><![CDATA[
					<div>
					<div class="feature-class">{LAYER_CAPTION}</div>
					';
					
		//identify
		$columnsIdentifyPart = ' <B>{COLUMN_CAPTION}: </B> {{properties.{COLUMN_NAME}}}<BR>
		';	
		
		$mapSourcePartThree = '                </div>
				]]></template>
			</layer>
		</map-source>
		<map-source name="{LAYER_NAME}_wms" type="wms"  maxresolution="{MAX_RESOLUTION}">
			<url>{SERVER_URL}/wms</url>
			<param name="cross-origin" value="anonymous"/>
			<layer name="{LAYER_NAME}" status="off" query-as="{LAYER_NAME}_wfs/{LAYER_NAME}_wfs">
				<param name="FORMAT"      value="image/png"/>
				<param name="TRANSPARENT" value="TRUE"/>
			</layer>
		</map-source>';
		
		$catalogPartOne = '<group title="Editable Layers " expand="true">
		';
		$catalogPartTwo = '	<layer title="{LAYER_CAPTION}" src="{LAYER_NAME}_wms/{LAYER_NAME}" draw-edit="true" draw-modify="true" draw-polygon="true" draw-remove="true"/>
		';
		$catalogPartThree = '</group>
		';
		
		//get variables from INI file
		$config = parse_ini_file('../config/database.ini');
		$host      = $config['host'];
		$port      = $config['port'];
		$dbName    = $config['dbname'];
		$username  = $config['username'];
		$password  = $config['password'];
		
		//generate connect string
		$connectString = sprintf('host=%s port=%s dbname=%s user=%s password=%s', $host, $port, $dbName, $username, $password);
		$connection  = pg_connect($connectString);
		
		// TODO check if connection was successful
		
		//TODO modify to put user's email into sql string
		$sql = "select * from security_schema_3.user_layers_view where email='will@senecatechnologies.com';";
		
		$result = pg_query($connection, $sql);
		if (!$result) { die("Error in SQL query: " . pg_last_error()); }
	
		$layerTagsArray = ['{SERVER_NAME}', '{SERVER_TYPE}', '{SERVER_URL}', '{NAMESPACE_URI}', '{LAYER_NAME}', '{LAYER_TYPE}', '{LAYER_CAPTION}', '{GEOMETRY_NAME}', 
			'{SRS}', '{WMS_FORMAT}', '{MAX_RESOLUTION}'];
		
	
		//loop through layer list
		while ($row = pg_fetch_array($result)) {

			$layerValuesArray = [$row['server_name'], $row['server_type'], $row['server_url'], $row['namespace_uri'], $row['layer_name'], $row['layer_type'], $row['layer_caption'], $row['geometry_name'], $row['srs'], $row['wms_format'], $row['max_resolution']];
			
			//query for field details
			$columnsSql = "select * from security_schema_3.columns where layer_id = " . $row['layer_id'] . ' order by sort_order';

			//construct array
			$columnTagsArray = ['{COLUMN_NAME}', '{COLUMN_CAPTION}'];

			
			//generate map sources
			$mapBookParts[0] .= str_replace($layerTagsArray, $layerValuesArray , $mapSourcePartOne);
			
			//properties for editing
			$columnsResult = pg_query($connection, $columnsSql);
			$columnsProperties = '';
			$columnsIdentify = '';
			while ($columnsRow = pg_fetch_array($columnsResult)) {	
				// generate field properties and identify
				$columnValuesArray = [$columnsRow['column_name'], $columnsRow['column_caption']];
				$columnsProperties .= str_replace($columnTagsArray, $columnValuesArray , $columnsPropertiesPart);
				$columnsIdentify .= str_replace($columnTagsArray, $columnValuesArray , $columnsIdentifyPart);

			}
			$mapBookParts[0] .= $columnsProperties;
			$mapBookParts[0] .= str_replace($layerTagsArray, $layerValuesArray , $mapSourcePartTwo);
			$mapBookParts[0] .= $columnsIdentify;
			$mapBookParts[0] .= str_replace($layerTagsArray, $layerValuesArray , $mapSourcePartThree);
			
			//generate catalog
			$mapBookParts[1] .= str_replace($layerTagsArray, $layerValuesArray , $catalogPartTwo);
		}
		
		if (pg_num_rows($result) > 0){
			$mapBookParts[1] = $catalogPartOne . $mapBookParts[1] . $catalogPartThree;
		}
		
		
		pg_free_result($result);
	
		return $mapBookParts;
	}

?>
