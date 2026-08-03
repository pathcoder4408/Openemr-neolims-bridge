<?php
namespace OpenEMR\Modules\NeoLimsBridge\Controller;
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfilePolicyService;
use OpenEMR\Modules\NeoLimsBridge\Repository\ResultLinkRepository;
use OpenEMR\Modules\NeoLimsBridge\Service\Hl7OruNormalizer;
use OpenEMR\Modules\NeoLimsBridge\Service\ProcedureResultSyncService;
use OpenEMR\Modules\NeoLimsBridge\Transport\Hl7\Hl7Adapter;
use Symfony\Component\HttpFoundation\Response;
final class ResultController
{
    use ControllerTrait;
    private function allow(array $p,string $o):void{(new ProfilePolicyService())->assertAllowed('result',$o,(string)($p['connection_key']??''));}
    public function validate($request){return $this->guarded(function()use($request){$p=$this->body($request);$this->allow($p,'validate');return $this->json((new ProcedureResultSyncService())->validate($p));});}
    public function sync($request){return $this->guarded(function()use($request){$p=$this->body($request);$this->allow($p,'sync');$r=(new ProcedureResultSyncService())->sync($p);return $this->json($r,!empty($r['created'])?Response::HTTP_CREATED:Response::HTTP_OK);});}
    public function fhirObservation($request,?string $uuid=null){return $this->guarded(function()use($request,$uuid){$f=$this->body($request);$p=(new ProcedureResultSyncService())->fromObservation($f,$uuid);$this->allow($p,'sync');$p['dry_run']=!empty($_GET['dry_run']);$r=(new ProcedureResultSyncService())->sync($p);return $this->json($r,!empty($r['created'])?201:200);});}
    public function fhirDiagnosticReport($request,?string $uuid=null){return $this->guarded(function()use($request,$uuid){$f=$this->body($request);$p=(new ProcedureResultSyncService())->fromDiagnosticReport($f,$uuid);$this->allow($p,'sync');$p['dry_run']=!empty($_GET['dry_run']);$r=(new ProcedureResultSyncService())->sync($p);return $this->json($r,!empty($r['created'])?201:200);});}
    public function hl7($request){return $this->guarded(function()use($request){$raw=$this->rawBody($request);$p=(new Hl7OruNormalizer())->normalize($raw);$this->allow($p,'sync');$p['dry_run']=!empty($_GET['dry_run']);$r=(new ProcedureResultSyncService())->sync($p);$ack=(new Hl7Adapter())->ack((string)$p['local_report_id'],empty($r['errors']),empty($r['errors'])?'Accepted':'Rejected');return $this->json(['result'=>$r,'ack'=>$ack],!empty($r['created'])?201:200);});}
    public function resultLink(string $connection,string $local,$request){return $this->guarded(function()use($connection,$local){$r=(new ResultLinkRepository())->find($connection,$local);return $r?$this->json($r):$this->json(['error'=>'Result link not found'],404);});}
    private function body($request):array{$p=json_decode($this->rawBody($request),true,512,JSON_THROW_ON_ERROR);if(!is_array($p))throw new \InvalidArgumentException('JSON object required.');return $p;}
}
