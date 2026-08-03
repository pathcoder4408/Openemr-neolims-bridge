<?php
namespace OpenEMR\Modules\NeoLimsBridge\Native;

use OpenEMR\Common\Database\QueryUtils;

final class BillingWriter
{
    public function create(array $context,array $charge): array
    {
        QueryUtils::startTransaction();
        try {
            $id=sqlInsert('INSERT INTO billing
                (`date`,code_type,code,pid,provider_id,`user`,groupname,authorized,encounter,code_text,billed,activity,payer_id,bill_process,modifier,units,fee,justify,target,x12_partner_id,notecodes,external_id,pricelevel,revenue_code,chargecat)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[
                $charge['date'],$charge['code_type'],$charge['code'],$context['pid'],$charge['provider_id'],$charge['user_id'],$charge['groupname'],0,$context['encounter_id'],$charge['code_text'],0,1,$charge['payer_id'],0,$charge['modifier'],$charge['units'],$charge['fee'],$charge['justify'],$charge['target'],$charge['x12_partner_id'],$charge['notecodes'],$charge['external_id'],$charge['pricelevel'],$charge['revenue_code'],$charge['chargecat']]);
            if(!$id) throw new \RuntimeException('OpenEMR did not return a billing row ID.');
            QueryUtils::commitTransaction();
            return ['billing_id'=>(int)$id];
        } catch(\Throwable $e){ QueryUtils::rollbackTransaction(); throw $e; }
    }
}
