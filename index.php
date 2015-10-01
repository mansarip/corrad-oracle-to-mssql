<!DOCTYPE html>
<html>
<head>
	<title>Oracle To SQL Server Script Converter</title>
	<style type="text/css">
		#overlay{
			display: none;
			position: fixed;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			background-color: black;
			opacity: .5;
		}
		#raw{
			display: none;
			background-color: white;
			position: fixed;
			top: 50px;
			bottom: 50px;
			left: 50px;
			right: 50px;
			border:1px solid #333;
			box-shadow: 2px 2px 5px #333;
		}
		#raw textarea{
			position: absolute;
			bottom: 0;
			top: 21px;
			resize: none;
			width: 99%;
			font-family: monospace;
			font-size: 11pt;
			white-space: nowrap;
		}
		#raw textarea:disabled{
			color: #BCBCBC;
		}
		#raw span{
			position: absolute;
			top: 0;
			right: 0;
			color: blue;
			cursor: pointer;
			
		}
		#loadingRawSaving{
			display: none;
			margin-right: 130px;	
		}
		span.blue{
			color:blue;
			font-weight: bold;
		}
		#displayResult{
			width: 100%;
			border-collapse: collapse;
		}
		th{
			background-color: #D0C489;
		}
		th.title{
			background-color: #7FB8F1;
		}
		body{
			font-family: sans-serif;
			font-size: 10pt;
		}
		tr.title{
			border-top: 1px dashed #333;
		}
		/*tr:not(.title):hover{
			background-color: lightblue;
		}*/
		span.ok{
			background-color: green;
		    color: #FFF;
		    font-size: 10px;
		    padding: 2px 7px;
		    float: right;
		    margin-right: 3px;
		    border-radius: 5px;
		}
		span.fail{
			background-color: #F00;
		    color: #FFF;
		    font-size: 10px;
		    padding: 2px 7px;
		    float: right;
		    margin-right: 3px;
		    border-radius: 5px;
		}
		span.match{
			background-color: blue;
		    color: #FFF;
		    font-size: 10px;
		    padding: 2px 7px;
		    float: right;
		    margin-right: 3px;
		    border-radius: 5px;
		}
		span.hl{
			color: #C400FF;
		}
		.whiteOverlay{
			position: absolute;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			background-color: #FFF;
			opacity: 0.8;
		}
		.whiteOverlay img{
			position: absolute;
			left: 50%;
			top: 50%;
			margin-left: -10px;
			margin-top: -10px;
		}
	</style>
	<script type="text/javascript" src="jquery.js"></script>
	<script type="text/javascript">

	var decodeEntities = (function() {
		// this prevents any overhead from creating the object each time
		var element = document.createElement('div');

		function decodeHTMLEntities (str) {
			if(str && typeof str === 'string') {
				// strip script/html tags
				str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
				str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
				element.innerHTML = str;
				str = element.textContent;
				element.textContent = '';
			}

			return str;
		}

		return decodeHTMLEntities;
	})();

	function Reset() {
		$('#displayResult').hide();
		$('#componentPart').hide().html('<tr><th colspan="2" class="title">Component</th></tr>');
		$('#componentItemPart').hide().html('<tr><th colspan="2" class="title">Component Item</th></tr>');
		$('#blPart').hide().html('<tr><th colspan="2" class="title">Business Logic</th></tr>');
	}

	function ViewRawOracle(type, index, elem) {
		$('#savelink').hide();
		$('#overlay').show();
		$('#raw').show();
		if (type === 'component') {
			$('#raw textarea').val(decodeEntities(data.component[index].COMPONENTTYPEQUERY));

		} else if (type === 'componentItem') {
			var columnType = $(elem).closest('td').find('span.componentItemColumnType').text();
			$('#raw textarea').val(decodeEntities(data.item[index][columnType]));

		} else if (type === 'bl') {
			$('#raw textarea').val(decodeEntities(data.bl[index].RAWMODQUERY));
		}
	}

	function ViewRaw(type, index, elem) {
		var columnType = $(elem).closest('tr').find('span.componentItemColumnType').text();

		$('#savelink').show();
		$('#overlay').show();
		$('#raw').show();
		$('#savelink').attr('onclick', 'Save(\''+ type +'\', '+ index +', \''+ columnType +'\')');

		if (type === 'component') {
			$('#raw textarea').val(decodeEntities(data.component[index].RAWMODCOMPONENTTYPEQUERY));

		} else if (type === 'componentItem') {
			$('#raw textarea').val(decodeEntities(data.item[index].RAWMODQUERY));

		} else if (type === 'bl') {
			$('#raw textarea').val(decodeEntities(data.bl[index].RAWMODQUERY));
		}
	}

	function Close() {
		$('#overlay').hide();
		$('#raw').hide();
		$('#raw textarea').val('');
	}

	function Save(type, index, columnType) {
		var textarea = $('#raw textarea');
		var loadingLabel = $('#loadingRawSaving');

		textarea.prop('disabled', true);
		loadingLabel.show();

		if (type === 'componentItem') {
			$.ajax({
				url:'process.php',
				type:'post',
				data:{
					task: 'saveComponentItem',
					query: textarea.val(),
					type: type,
					id: data.item[index].ITEMID,
					columnType: columnType
				}
			})
			.done(function(data){
				console.log(data);
			});
		}
		else if (type === 'component') {
			$.ajax({
				url:'process.php',
				type:'post',
				data:{
					task: 'saveComponent',
					query: textarea.val(),
					type: type,
					id: data.component[index].COMPONENTID
				},
				dataType: 'json'
			})
			.done(function(data){
				if (!data.error) {
					_SaveSuccessAction(loadingLabel, textarea);
				}
			});
		}
	}

	function _SaveSuccessAction(loadingLabel, textarea) {
		textarea.prop('disabled', false);
		loadingLabel.css('color','green').text('Saved!').fadeOut(2000, function(){
			loadingLabel.css('color','').text('Loading...');
		});
	}

	function Submit() {
		Reset();
		//return false;
		var menuID = $('#menuID').val();

		if (menuID === '' || isNaN(Number(menuID))) {
			alert('Invalid menu ID');
			return false;
		}

		$('#preloader1').show();

		var data = {
			task:'submit',
			menuID:menuID,
			searchComponent: $('#chkboxSearchComponent').prop('checked'),
			searchComponentItem: $('#chkboxSearchComponentItem').prop('checked'),
			searchBL: $('#chkboxSearchBL').prop('checked')
		};

		$.ajax({
			url:'process.php',
			data:data,
			type:'post',
			dataType:'json'
		})
		.done(function(data){
			window.data = data;
			console.log(data);
			//return false;

			$('#preloader1').hide();

			$('#displayResult').show();

			// append component list
			if (data.searchComponent) {
				var componentPart = $('#componentPart');
				var html = '';

				for (var i = 0; i < data.component.length; i++) {
					if (data.component[i]['COMPONENTTYPEQUERY'] !== null && data.component[i]['COMPONENTTYPEQUERY'] !== '') {
						var tag = '';
						var match = '';

						// jika ada error
						var errormessage = '';
						var error = null;
						if (data.component[i]['ERRORCOMPONENTTYPEQUERY'] !== undefined) {
							error = data.component[i]['ERRORCOMPONENTTYPEQUERY'];
							errormessage = '<span style="color:red">'+ error[0] + ' : ' + error[2] +'</span>';
						}

						// check if match
						if (data.component[i]['COMPONENTTYPEQUERY'] === data.component[i]['MODCOMPONENTTYPEQUERY']) {
							match = '<span class="match">MATCH</span>';
						}

						tag = data.component[i]['SUCCESSCOMPONENTTYPEQUERY'] ? '<span class="ok">OK</span> ' : '<span class="fail">FAILED</span> ';

						html += '<tr class="title" data-index="'+i+'"><td colspan="2">';
						html += '<input style="float:right; margin-right:3px" type="button" onclick="ReplaceComponent('+ i +', this)" value="Commit Replace"/> <input style="float:right; margin-right:3px" type="button" value="Ignore"/> <input style="float:right; margin-right:3px" type="button" onclick="ViewRaw(\'component\','+i+', this)" value="View Raw"/>';
						html += '<b>[ID : '+ data.component[i].COMPONENTID +'] </b> <input type="button" onclick="ViewRawOracle(\'component\','+i+')" value="View Raw"/>'+ match + tag +' <br/>'+ errormessage +'</td></tr>';

						html += '<tr>';
						html += '<td>'+ data.component[i]['COMPONENTTYPEQUERY'] +'</td>';
						html += '<td>'+ data.component[i]['MODCOMPONENTTYPEQUERY'] +'</td>';

						html += '</tr>';
						html += '<tr><td colspan="2" style="border:none; height:10px;"></td></tr>';
					}
				}

				componentPart.append(html);
			}

			// append item list
			if (data.searchComponentItem) {
				var componentItemPart = $('#componentItemPart');
				var html = '';
				
				for (var i=0; i < data.item.length; i++) {

					for (var key in data.item[i]) {
						
						if (key === 'ITEMDEFAULTVALUE' || key === 'ITEMDEFAULTVALUEQUERY' || key === 'ITEMLOOKUP') {

							// filter : jika null atau empty string, ignore	
							if (data.item[i][key] !== null && data.item[i][key] !== '') {

								// filter : jika ada satu character sahaja, ignore
								if (data.item[i][key].length > 1) {

									// filter : jika start dari curly bracket, ignore
									if (data.item[i][key].substr(0, 1) !== '{') {
										var tag = '';
										var mod = '';
										var match = false;

										if (key === 'ITEMDEFAULTVALUE') {
											tag = data.item[i]['SUCCESSITEMDEFAULTVALUE'] ? '<span class="ok">OK</span> ' : '<span class="fail">FAILED</span> ';
											mod = data.item[i]['MODITEMDEFAULTVALUE'];
											if (data.item[i]['ITEMDEFAULTVALUE'] === mod) match = true;

										} else if (key === 'ITEMDEFAULTVALUEQUERY') {
											tag = data.item[i]['SUCCESSITEMDEFAULTVALUEQUERY'] ? '<span class="ok">OK</span> ' : '<span class="fail">FAILED</span> ';
											mod = data.item[i]['MODITEMDEFAULTVALUEQUERY'];
											if (data.item[i]['ITEMDEFAULTVALUEQUERY'] === mod) match = true;

										} else if (key === 'ITEMLOOKUP') {
											tag = data.item[i]['SUCCESSITEMLOOKUP'] ? '<span class="ok">OK</span> ' : '<span class="fail">FAILED</span> ';
											mod = data.item[i]['MODITEMLOOKUP'];
											if (data.item[i]['ITEMLOOKUP'] === mod) match = true;
										}

										// jika ada error
										var errormessage = '';
										var error = null;
										if (data.item[i]['ERROR' + key] !== undefined) {
											error = data.item[i]['ERROR' + key];
											errormessage = '<span style="color:red">'+ error[0] + ' : ' + error[2] +'</span>';
										}

										// jika match
										match = (match) ? '<span class="match">MATCH</span>' : '';

										html += '<tr class="title" data-index="'+i+'"><td colspan="2">';
										html += '<input style="float:right; margin-right:3px" type="button" onclick="ReplaceComponentItem('+ i +', this)" value="Commit Replace"/> <input style="float:right; margin-right:3px" type="button" value="Ignore"/>  <input style="float:right; margin-right:3px" type="button" onclick="ViewRaw(\'componentItem\','+i+', this)" value="View Raw"/>';
										html += '<b>[ID : <span class="itemid">'+ data.item[i].ITEMID +'</span>] [<span class="componentItemColumnType">'+ key +'</span>]</b> <input type="button" onclick="ViewRawOracle(\'componentItem\','+i+',this)" value="View Raw"/>'+ match + tag +' <br/>'+ errormessage +'</td></tr>';

										html += '<tr>';
										html += '<td>'+ data.item[i][key] +'</td>';
										html += '<td>'+ mod +'</td>';

										html += '</tr>';
										html += '<tr><td colspan="2" style="border:none; height:10px;"></td></tr>';
									}

								}

							}
							
						}
					}

				}

				componentItemPart.append(html);
			}

			// append bl list
			if (data.searchBL) {
				var blPart = $('#blPart');
				var html = '';

				for (var i = 0; i < data.bl.length; i++) {
					if (data.bl[i]['QUERY'] !== null && data.bl[i]['QUERY'] !== '') {
						var tag = '';
						var mod = '';

						// jika ada error
						var errormessage = '';
						var error = null;
						if (data.bl[i]['ERRORMESSAGE'] !== undefined && data.bl[i]['ERRORMESSAGE'] !== null) {
							error = data.bl[i]['ERRORMESSAGE'];
							errormessage = '<span style="color:red">'+ error[0] + ' : ' + error[2] +'</span>';
						}

						tag = data.bl[i]['SUCCESS'] ? '<span class="ok">OK</span> ' : '<span class="fail">FAILED</span> ';

						html += '<tr class="title" data-index="'+i+'"><td colspan="2">';
						html += '<input style="float:right; margin-right:3px" type="button" value="Commit Replace"/> <input style="float:right; margin-right:3px" type="button" value="Ignore"/> <input style="float:right; margin-right:3px" type="button" onclick="ViewRaw(\'bl\','+i+', this)" value="View Raw"/>';
						html += '<b>[ID : '+ data.bl[i].BLID +'] ['+ data.bl[i].BLNAME +']</b> <input type="button" onclick="ViewRawOracle(\'bl\','+i+')" value="View Raw"/>'+ tag +' <br/>'+ errormessage +'</td></tr>';

						html += '<tr>';
						html += '<td>'+ data.bl[i]['QUERY'] +'</td>';
						html += '<td>'+ data.bl[i]['MODQUERY'] +'</td>';

						html += '</tr>';
						html += '<tr><td colspan="2" style="border:none; height:10px;"></td></tr>';
					}
				}

				blPart.append(html);
			}

			if (data.searchComponent) componentPart.show();
			if (data.searchComponentItem) componentItemPart.show();
			if (data.searchBL) blPart.show();
		});
	}

	function ReplaceComponent(index, elem) {
		_ReplaceLoading(elem);
		_DisableAllButtons(elem);

		var buttonTr = $(elem).closest('tr');
		var componentId = data.component[index].COMPONENTID;
		var query = data.component[index].RAWMODCOMPONENTTYPEQUERY;

		$.ajax({
			url:'process.php',
			data:{
				task:'replaceComponent',
				componentId:componentId,
				query:query
			},
			type:'post',
			dataType:'json'
		})
		.done(function(data){
			//console.log(data);
			_CommitReplaceNotification(data, buttonTr, elem);
		});
	}

	function ReplaceComponentItem(index, elem) {
		_ReplaceLoading(elem);
		_DisableAllButtons(elem);

		var buttonTr = $(elem).closest('tr');
		var td = $(elem).closest('td');
		var columnType = td.find('span.componentItemColumnType').text();

		$.ajax({
			url:'process.php',
			data:{
				task: 'replaceComponentItem',
				itemId: data.item[index].ITEMID,
				columnType: columnType,
				query: data.item[index].RAWMODQUERY
			},
			type:'post',
			dataType:'json'
		})
		.done(function(data){
			_CommitReplaceNotification(data, buttonTr, elem);
		});
	}

	function _CommitReplaceNotification(data, buttonTr, elem) {
		// jika failed
		if (!data.success) {
			if (data.matched) {
				alert(data.error);
			} else {
				alert("Replace Failed!\n\n" + data.error[0][0] + ' : ' + data.error[0][2]);
			}
		}
		// jika berjaya
		else {
			buttonTr.next('tr').find('td').each(function(){
				$(this).html(data.query).text();
			});

			$(elem).next().next().after('<span class="match">MATCH</span>');
			alert("Successfully Replaced!");
		}

		// reset
		_ResetReplace(elem);
	}

	function _ReplaceLoading(elem) {
		var selfTr = $(elem).closest('tr');
		var contentTr = selfTr.next();

		contentTr.find('td').each(function(){
			$(this).css('position', 'relative');
			$(this).append('<div class="whiteOverlay"><img src="preloader.gif"/></div>')
		});
	}

	function _DisableAllButtons(elem) {
		var selfTr = $(elem).closest('tr');
		selfTr.find('input[type="button"]').prop('disabled',true);
	}

	function _ResetReplace(elem) {
		var td = $(elem).closest('td');
		td.find('input[type="button"]').prop('disabled', false);

		var tr = $(elem).closest('tr');
		var contentTr = tr.next('tr');
		contentTr.find('.whiteOverlay').remove();
	}

	</script>
</head>
<body>
	<h1>Oracle To SQL Server Script Converter</h1>
	<p><i>Untuk kegunaan EHR Perkeso (MPS) sahaja</i></p>

	<p>
		<input type="checkbox" checked="checked" id="chkboxSearchComponent"/><label for="chkboxSearchComponent">Component</label> 
		<input type="checkbox" checked="checked" id="chkboxSearchComponentItem"/><label for="chkboxSearchComponentItem">Component Item</label> 
		<input type="checkbox" checked="checked" id="chkboxSearchBL"/><label for="chkboxSearchBL">BL</label>
	</p>
	<p>Menu ID : <input type="text" id="menuID" value="302293"/> <input type="button" value="Submit" onclick="Submit()"/></p>
	
	<img id="preloader1" style="display:none" src="preloader.gif"/>

	<table id="displayResult" border="0" cellpadding="4" style="display:none; table-layout:fixed">
		<tr>
			<th style="width:40%">Oracle</th>
			<th style="width:40%">SQL Server</th>
		</tr>
		<tbody id="componentPart" style="display:none">
			<tr>
				<th colspan="2" class="title">Component</th>
			</tr>
		</tbody>
		<tbody id="componentItemPart" style="display:none">
			<tr>
				<th colspan="2" class="title">Component Item</th>
			</tr>
		</tbody>
		<tbody id="blPart" style="display:none">
			<tr>
				<th colspan="2" class="title">Business Logic</th>
			</tr>
		</tbody>
	</table>

	<div id="overlay"></div>
	<div id="raw">
		<span id="savelink" style="margin-right:70px; text-align:right" onclick="Save()">SAVE</span>
		<span onclick="Close()">CLOSE</span>
		<textarea></textarea>
		<span id="loadingRawSaving">Loading...</span>
	</div>
</body>
</html>