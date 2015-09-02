<?php

/*define('DBMS_NAME','sqlsrv');
define('DB_CONNECTION','epayhr\mssqlepayhr');
define('DB_DATABASE','epayhr_usr');
define('DB_USERNAME','epayhr');
define('DB_PASSWORD','epayhr');
define('DB_OTHERS','epayhr_corrad');
include('db.php');*/

include('repair.php');

$task = $_POST['task'];

// connection details
$serverName = "epayhr\mssqlepayhr";
$connectionInfo = array( "Database"=>"epayhr_usr", "UID"=>"epayhr", "PWD"=>"epayhr");

if ($task == 'submit') {

	$searchComponent = $_POST['searchComponent'] == "true" ? true : false;
	$searchComponentItem = $_POST['searchComponentItem'] == "true" ? true : false;
	$searchBL = $_POST['searchBL'] == "true" ? true : false;

	$pageID = null;
	$menuID = $_POST['menuID'];
	$componentID = array();

	// dapatkan page ID
	// ====================================================================================================
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	$sql = "select PAGEID from dbo.FLC_PAGE where MENUID = $menuID";
	$stmt = sqlsrv_query($conn, $sql);

	while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
		$pageID = $row['PAGEID'];
	}


	// query component
	// ====================================================================================================
	//if ($searchComponent) {
	if (true) {	 // always true sebab component item depend pada benda ni
		$component = array();
		$sql = "select COMPONENTID, PAGEID, COMPONENTTYPEQUERY
		from dbo.FLC_PAGE_COMPONENT
		where PAGEID = $pageID";

		$stmt = sqlsrv_query($conn, $sql);

		$index = 0;
		while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$componentID[] = $row['COMPONENTID'];
			$component[$index]['COMPONENTID'] = $row['COMPONENTID'];
			$component[$index]['COMPONENTTYPEQUERY'] = $row['COMPONENTTYPEQUERY'];

			// escape html chars
			$component[$index]['COMPONENTTYPEQUERY'] = htmlspecialchars($component[$index]['COMPONENTTYPEQUERY']);
			
			// replace
			$component[$index]['MODCOMPONENTTYPEQUERY'] = RepairSQL($row['COMPONENTTYPEQUERY']);

			// escape html chars
			$component[$index]['MODCOMPONENTTYPEQUERY'] = htmlspecialchars($component[$index]['MODCOMPONENTTYPEQUERY']);

			// check
			$checkingSQL = "set noexec on; " . RemoveCurlyBraces($component[$index]['MODCOMPONENTTYPEQUERY']);
			$checkingSQL = htmlspecialchars_decode($checkingSQL);
			$checkingStmt = sqlsrv_query($conn, $checkingSQL);
			$component[$index]['SUCCESSCOMPONENTTYPEQUERY'] = ($checkingStmt === false) ? false : true;
			if (!$checkingStmt) {
				$component[$index]['ERRORCOMPONENTTYPEQUERY'] = sqlsrv_errors();
				$component[$index]['ERRORCOMPONENTTYPEQUERY'] = $component[$index]['ERRORCOMPONENTTYPEQUERY'][0];
			}

			// raw
			$component[$index]['RAWMODCOMPONENTTYPEQUERY'] = RemoveCurlyBraces($component[$index]['MODCOMPONENTTYPEQUERY']);

			// html highlight
			$component[$index]['MODCOMPONENTTYPEQUERY'] = Highlight($component[$index]['MODCOMPONENTTYPEQUERY']);

			$index++;
		}
		sqlsrv_close($conn);
	}

	// query dalam component item
	// ====================================================================================================
	// - ITEMDEFAULTVALUE
	// - ITEMLOOKUP
	if ($searchComponentItem) {
		$conn = sqlsrv_connect($serverName, $connectionInfo);
		$componentList = '';
		for ($c = 0; $c < count($componentID); $c++) {
			$componentList .= $componentID[$c] . ',';
		}
		$componentList = rtrim($componentList, ',');
		$item = array();

		$sql = "select COMPONENTID, ITEMDEFAULTVALUE, ITEMDEFAULTVALUEQUERY, ITEMLOOKUP, ITEMID
		from dbo.FLC_PAGE_COMPONENT_ITEMS
		where COMPONENTID in ($componentList)";

		$stmt = sqlsrv_query($conn, $sql);

		$index = 0;
		while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

			$item[$index]['ITEMID'] = $row['ITEMID'];

			$item[$index]['ITEMDEFAULTVALUE'] = $row['ITEMDEFAULTVALUE'];
			$item[$index]['ITEMLOOKUP'] = $row['ITEMLOOKUP'];
			$item[$index]['ITEMDEFAULTVALUEQUERY'] = $row['ITEMDEFAULTVALUEQUERY'];

			// destination copy from source
			if ($row['ITEMDEFAULTVALUE'] != null || $row['ITEMDEFAULTVALUE'] != '') {
				$item[$index]['MODITEMDEFAULTVALUE'] = RepairSQL($row['ITEMDEFAULTVALUE']);
			}

			if ($row['ITEMLOOKUP'] != null || $row['ITEMLOOKUP'] != '') {
				$item[$index]['MODITEMLOOKUP'] = RepairSQL($row['ITEMLOOKUP']);
			}

			if ($row['ITEMDEFAULTVALUEQUERY'] != null || $row['ITEMDEFAULTVALUEQUERY'] != '') {
				$item[$index]['MODITEMDEFAULTVALUEQUERY'] = RepairSQL($row['ITEMDEFAULTVALUEQUERY']);
			}

			// syntax checking
			$checkingSQL = "set noexec on; " . RemoveCurlyBraces($item[$index]['MODITEMDEFAULTVALUE']);
			$checkingStmt = sqlsrv_query($conn, $checkingSQL);
			$item[$index]['SUCCESSITEMDEFAULTVALUE'] = ($checkingStmt === false) ? false : true;
			if (!$checkingStmt) {
				$item[$index]['ERRORITEMDEFAULTVALUE'] = sqlsrv_errors();
				$item[$index]['ERRORITEMDEFAULTVALUE'] = $item[$index]['ERRORITEMDEFAULTVALUE'][0];
			}

			$checkingSQL = "set noexec on; " . RemoveCurlyBraces($item[$index]['MODITEMLOOKUP']);
			$checkingStmt = sqlsrv_query($conn, $checkingSQL);
			$item[$index]['SUCCESSITEMLOOKUP'] = ($checkingStmt === false) ? false : true;
			if (!$checkingStmt) {
				$item[$index]['ERRORITEMLOOKUP'] = sqlsrv_errors();
				$item[$index]['ERRORITEMLOOKUP'] = $item[$index]['ERRORITEMLOOKUP'][0];
			}

			$checkingSQL = "set noexec on; " . RemoveCurlyBraces($item[$index]['MODITEMDEFAULTVALUEQUERY']);
			$checkingStmt = sqlsrv_query($conn, $checkingSQL);
			$item[$index]['SUCCESSITEMDEFAULTVALUEQUERY'] = ($checkingStmt === false) ? false : true;
			if (!$checkingStmt) {
				$item[$index]['ERRORITEMDEFAULTVALUEQUERY'] = sqlsrv_errors();
				$item[$index]['ERRORITEMDEFAULTVALUEQUERY'] = $item[$index]['ERRORITEMDEFAULTVALUEQUERY'][0];
			}

			// raw
			if ($item[$index]['MODITEMDEFAULTVALUE']) { $item[$index]['RAWMODQUERY'] =  RemoveCurlyBraces($item[$index]['MODITEMDEFAULTVALUE']); }
			elseif ($item[$index]['MODITEMLOOKUP']) { $item[$index]['RAWMODQUERY'] =  RemoveCurlyBraces($item[$index]['MODITEMLOOKUP']); }
			elseif ($item[$index]['MODITEMDEFAULTVALUEQUERY']) { $item[$index]['RAWMODQUERY'] =  RemoveCurlyBraces($item[$index]['MODITEMDEFAULTVALUEQUERY']); }

			// html highlight
			$item[$index]['MODITEMDEFAULTVALUE'] = Highlight($item[$index]['MODITEMDEFAULTVALUE']);
			$item[$index]['MODITEMLOOKUP'] = Highlight($item[$index]['MODITEMLOOKUP']);
			$item[$index]['MODITEMDEFAULTVALUEQUERY'] = Highlight($item[$index]['MODITEMDEFAULTVALUEQUERY']);

			$index++;
		}
		sqlsrv_close($conn);
	}


	// query dalam BL
	// ====================================================================================================
	if ($searchBL) {
		$conn = sqlsrv_connect($serverName, $connectionInfo);

		$sql = "select a.TRIGGER_ID, a.TRIGGER_TYPE, a.TRIGGER_EVENT, a.TRIGGER_BL, a.TRIGGER_ITEM_ID, a.TRIGGER_ITEM_TYPE, b.COMPONENTNAME from FLC_TRIGGER a, FLC_PAGE_COMPONENT b where a.TRIGGER_ITEM_ID = b.COMPONENTID and a.TRIGGER_ITEM_TYPE = 'component' and b.COMPONENTID in (select COMPONENTID from FLC_PAGE_COMPONENT where PAGEID = $pageID) and TRIGGER_TYPE = 'PHP'
				union all
				select a.TRIGGER_ID, a.TRIGGER_TYPE, a.TRIGGER_EVENT, a.TRIGGER_BL, a.TRIGGER_ITEM_ID, a.TRIGGER_ITEM_TYPE, b.ITEMNAME from FLC_TRIGGER a, FLC_PAGE_COMPONENT_ITEMS b where a.TRIGGER_ITEM_ID = b.ITEMID and a.TRIGGER_ITEM_TYPE = 'item' and b.COMPONENTID in (select COMPONENTID from FLC_PAGE_COMPONENT where PAGEID = $pageID) and TRIGGER_TYPE = 'PHP'
				union all
				select a.TRIGGER_ID, a.TRIGGER_TYPE, a.TRIGGER_EVENT, a.TRIGGER_BL, a.TRIGGER_ITEM_ID, a.TRIGGER_ITEM_TYPE, b.CONTROLNAME from FLC_TRIGGER a, FLC_PAGE_CONTROL b where a.TRIGGER_ITEM_ID = b.CONTROLID and a.TRIGGER_ITEM_TYPE = 'control' and (b.COMPONENTID in (select COMPONENTID from FLC_PAGE_COMPONENT where PAGEID = $pageID) or b.PAGEID = $pageID) and TRIGGER_TYPE = 'PHP'
				union all
				select a.TRIGGER_ID, a.TRIGGER_TYPE, a.TRIGGER_EVENT, a.TRIGGER_BL, a.TRIGGER_ITEM_ID, a.TRIGGER_ITEM_TYPE, b.PAGENAME from FLC_TRIGGER a, FLC_PAGE b where a.TRIGGER_ITEM_ID = b.PAGEID and a.TRIGGER_ITEM_TYPE = 'page' and b.PAGEID = $pageID and TRIGGER_TYPE = 'PHP' order by TRIGGER_TYPE, TRIGGER_EVENT";

		$stmt = sqlsrv_query($conn, $sql);

		$relatedBL = array();

		while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
			$relatedBL[] = $row['TRIGGER_BL'];
		}

		$relatedBL = array_unique($relatedBL);
		$businessLogic = array();

		for ($n = 0; $n < count($relatedBL); $n++) {
			$sql = "select BLID, BLNAME, BLDETAIL from dbo.FLC_BL where BLNAME = '". $relatedBL[$n] ."'";
			$stmt = sqlsrv_query($conn, $sql);

			while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
				$bl = $row['BLDETAIL'];

				$select = ExtractQueryFromBL($bl, 'select');
				$insert = ExtractQueryFromBL($bl, 'insert');
				$update = ExtractQueryFromBL($bl, 'update');
				$delete = ExtractQueryFromBL($bl, 'delete');

				$queries = array_merge($select, $insert, $update, $delete);

				for ($q = 0; $q < count($queries); $q++) {
					$originalQuery = $queries[$q];
					
					// replace
					$modQuery = RepairSQL($originalQuery);

					// check
					$checkingSQL = "set noexec on; " . RemoveCurlyBraces($modQuery);
					$checkingStmt = sqlsrv_query($conn, $checkingSQL);
					$success = ($checkingStmt === false) ? false : true;

					if (!$checkingStmt) {
						$error = sqlsrv_errors();
						$error = $error[0];
					}

					// raw
					$rawModQuery = RemoveCurlyBraces($modQuery);

					// html highlight
					$modQuery = Highlight($modQuery);

					$businessLogic[] = array(
						'BLID' => $row['BLID'],
						'BLNAME' => $row['BLNAME'],
						'QUERY' => $originalQuery,
						'RAWMODQUERY' => $rawModQuery,
						'MODQUERY' => $modQuery,
						'SUCCESS' => $success,
						'ERRORMESSAGE' => $error
					);
				}
			}
		}

		sqlsrv_close($conn);
	}

	// return
	// ====================================================================================================
	echo json_encode(array(
		'searchComponent' => $searchComponent,
		'searchComponentItem' => $searchComponentItem,
		'searchBL' => $searchBL,
		'pageid' => $pageID,
		'component' => $component,
		'item' => $item,
		'bl' => $businessLogic
	));
}

elseif ($task == 'replaceComponentItem') {
	$result = array(
			'success' => false,
			'error' => null
	);

	$columnName = $_POST['columnType'];
	$itemId = $_POST['itemId'];

	$originalQuery = $_POST['query'];
	$query = str_replace("'", "''", $originalQuery);

	$conn = sqlsrv_connect($serverName, $connectionInfo);

	// cek adakah nilai sama (query yg nak diupdate sama dengan query yang dihantar)
	$getCurrentQuery = "select ". $columnName ." from FLC_PAGE_COMPONENT_ITEMS where ITEMID = " . $itemId;
	$stmt = sqlsrv_query($conn, $getCurrentQuery);

	while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
		$currentQuery = $row[$columnName];
	}

	// jika already matched
	if ($originalQuery == $currentQuery) {
		$result['success'] = false;
		$result['error'] = 'Query already matched.';
		$result['matched'] = true;
	}
	// jika tidak match
	else {
		$updateQuery = "update FLC_PAGE_COMPONENT_ITEMS set " . $columnName . " = '". $query ."' WHERE ITEMID = " . $itemId;
		$stmt = sqlsrv_query($conn, $updateQuery);

		if ($stmt) {
			$result['success'] = true;
			$result['query'] = $originalQuery;

		} elseif (!$stmt) {
			$result['success'] = false;
			$result['error'] = sqlsrv_errors();
		}
	}

	sqlsrv_close($conn);

	echo json_encode($result);
}

?>