<?php
namespace OpenEMR\Modules\NeoLimsBridge\Service;
final class Hl7OruNormalizer
{
    public function normalize(string $raw): array
    {
        $raw=str_replace(["\r\n","\n"],"\r",trim($raw));$segments=array_values(array_filter(explode("\r",$raw)));if(!$segments||!str_starts_with($segments[0],'MSH|'))throw new \InvalidArgumentException('HL7 must begin with MSH.');
        $msh=explode('|',$segments[0]);if(!str_contains((string)($msh[8]??''),'ORU'))throw new \InvalidArgumentException('Only ORU result messages are accepted.');
        $control=(string)($msh[9]??'');$order='';$reportCode='';$reportName='';$reportDate=date('Y-m-d H:i:s');$status='prelim';$results=[];$notes=[];
        foreach($segments as $seg){$a=explode('|',$seg);if($a[0]==='ORC'){$order=(string)($a[2]??$a[3]??$order);}elseif($a[0]==='OBR'){$order=(string)($a[2]??$a[3]??$order);$c=explode('^',(string)($a[4]??''));$reportCode=(string)($c[0]??'');$reportName=(string)($c[1]??'');$reportDate=$this->dt((string)($a[22]??$a[7]??''));$status=$this->status((string)($a[25]??''));}elseif($a[0]==='OBX'){$c=explode('^',(string)($a[3]??''));$type=(string)($a[2]??'ST');$value=(string)($a[5]??'');$results[]=['result_data_type'=>$type==='TX'?'L':substr($type,0,1),'result_code'=>(string)($c[0]??''),'result_text'=>(string)($c[1]??''),'date'=>$this->dt((string)($a[14]??$reportDate)),'facility'=>(string)($a[15]??''),'units'=>(string)(explode('^',(string)($a[6]??''))[0]??''),'range'=>(string)($a[7]??''),'abnormal'=>$this->abnormal((string)($a[8]??'')),'result_status'=>$this->status((string)($a[11]??'')),'result'=>$type==='TX'?'':$value,'comments'=>$type==='TX'?$value."\n":'', 'document_id'=>null];}elseif($a[0]==='NTE'){$notes[]=(string)($a[3]??'');}}
        if($control===''||$order==='')throw new \InvalidArgumentException('HL7 MSH-10 and placer/filler order number are required.');
        return ['connection_key'=>'hl7','local_order_id'=>$order,'local_report_id'=>$control,'external_identifier'=>['system'=>'urn:hl7v2:message-control-id','value'=>$control],'report'=>['procedure_code'=>$reportCode,'procedure_name'=>$reportName,'date_collected'=>$reportDate,'date_collected_tz'=>'','date_report'=>$reportDate,'date_report_tz'=>'','report_status'=>$status,'report_notes'=>implode("\n",$notes),'specimen_num'=>''],'results'=>$results,'raw_hl7'=>$raw];
    }
    private function status(string $s): string{return match(strtoupper($s)){'F'=>'final','P'=>'prelim','C'=>'correct','X'=>'error',default=>'prelim'};}
    private function abnormal(string $s): string{return match(strtoupper($s)){'','N'=>'no','A'=>'yes','H'=>'high','L'=>'low','HH'=>'vhigh','LL'=>'vlow',default=>$s};}
    private function dt(string $s): string{$d=preg_replace('/[^0-9]/','',$s);if(strlen($d)<8)return date('Y-m-d H:i:s');return substr($d,0,4).'-'.substr($d,4,2).'-'.substr($d,6,2).' '.(strlen($d)>=12?substr($d,8,2).':'.substr($d,10,2).':'.(strlen($d)>=14?substr($d,12,2):'00'):'00:00:00');}
}
