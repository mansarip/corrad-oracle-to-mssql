<?php

$sql = "/*select 
to_number('0') as id1,
to_number('') as c_id,
to_number('') as pg_id,
'Gaji Anggota' as gji,
'Gaji Anggota' as caruman,
to_number('0') as peratus,
to_number('0')  as nilai,
b.sna_no_akaun as akaun,
A.NAMA_BANK as bank,
'0' as lm,
'0' as lampiran,
to_number('') as stt
from lkp_bank a, hr_staf_no_akaun b
where a.bank_id = B.SNA_BANK_ID
and b.sna_stf_id ='{GET|stf_id}'
union*/
select 
to_number('1') as id1,
a.c_id as c_id ,
b.pg_id as pg_id,
'' as gji,
to_char(a.C_JNS_CARUMAN_ID) as caruman,
a.C_PERATUS as peratus,
a.C_NILAI as nilai,
a.C_NO_AKAUN as akaun,
a.C_BANK as bank,
'' as lm,
'<a href=\"index.php?page=page_wrapper&menuID=302293&tj_id={GET|tj_id}&pg_id={GET|pg_id}&stt={GET|stt}&Q={GET|Q}&stf_id={GET|stf_id}\" onclick=\"window.open('''||'img/dokumen/gaji/'||a.C_LAMPIRAN||''',''mywindow'',''width=800'',''height=200'')\">'||decode(a.C_LAMPIRAN,'','Tiada',a.C_LAMPIRAN)||'</a>' as lampiran,
a.C_STATUS_AKTIF as stt,
c.LPJP_JNS_CARUMAN
from p_caruman a, p_penyata_gaji b,LKP_P_JNS_PEMOTONGAN c

where A.C_PG_ID = B.PG_ID
and a.C_JNS_CARUMAN_ID=c.LPJP_ID
and A.C_PG_ID ='{GET|pg_id}'
--and b.pg_stf_id = '{GET|stf_id}'
";

include('repair.php');

$q = repairSQL($sql);
$q = htmlentities($q);

echo '<pre>';
print_r($q);
echo '</pre>';

?>