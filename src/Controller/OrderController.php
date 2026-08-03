<?php
namespace OpenEMR\Modules\NeoLimsBridge\Controller;
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfilePolicyService; use OpenEMR\Modules\NeoLimsBridge\Repository\OrderLinkRepository; use OpenEMR\Modules\NeoLimsBridge\Service\ProcedureOrderSyncService; use Symfony\Component\HttpFoundation\Response;
final class OrderController { use ControllerTrait;
 private function allow(array $p,string $o):void{(new ProfilePolicyService())->assertAllowed('order',$o,(string)($p['connection_key']??''));}
 public function validate($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->allow($p,'validate');return $this->json((new ProcedureOrderSyncService())->validate($p));});}
 public function sync($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->allow($p,'sync');$r=(new ProcedureOrderSyncService())->sync($p);return $this->json($r,!empty($r['created'])?201:200);});}
 public function fhir($q,?string $uuid=null){return $this->guarded(function()use($q,$uuid){$f=$this->body($q);$p=(new ProcedureOrderSyncService())->fromFhir($f,$uuid);$this->allow($p,'sync');$p['dry_run']=!empty($_GET['dry_run']);$r=(new ProcedureOrderSyncService())->sync($p);return $this->json($r,!empty($r['created'])?201:200);});}
 public function orderLink(string $c,string $l,$q){return $this->guarded(function()use($c,$l){(new ProfilePolicyService())->assertAllowed('order','read',$c);$r=(new OrderLinkRepository())->find($c,$l);return $r?$this->json($r):$this->json(['error'=>'Order link not found'],404);});}
 private function body($q):array{$p=json_decode($this->rawBody($q),true,512,JSON_THROW_ON_ERROR);if(!is_array($p))throw new \InvalidArgumentException('JSON object required.');return $p;}
}
