<?php

include('repair.php');

$sql = "select 
CONVERT(INT, 0),
b.SNA_ID as sna_id,
'Gaji Anggota' as gaji ,
A.BANK_ID as \"Nama Bank\",
b.sna_no_akaun as \"No Akaun\",
'' as lm,
'<a href=\"index.php?page=page_wrapper&tj_id={GET|tj_id}&pg_id={GET|pg_id}&stt={GET|stt}&Q={GET|Q}&stf_id={GET|stf_id}&menuID=302293\" onclick=\"window.open('''+'img/dokumen/gaji/'+b.SNA_LAMPIRAN+''',''mywindow'',''width=800'',''height=200'')\">'+CASE b.SNA_LAMPIRAN WHEN '' THEN 'Tiada' ELSE b.SNA_LAMPIRAN END+'</a>' as lampiranakaun
from lkp_bank a, hr_staf_no_akaun b
where a.bank_id = B.SNA_BANK_ID
and b.sna_stf_id ='{GET|stf_id}'";

$sql = RemoveSQLInsideBlockComment($sql);
$sql = RepairSQL($sql);
$sql = RemoveCurlyBraces($sql);
//$sql = htmlspecialchars($sql);

echo SqlFormatter::format($sql);

?>