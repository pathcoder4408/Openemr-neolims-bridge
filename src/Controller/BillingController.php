<?php
namespace OpenEMR\Modules\NeoLimsBridge\Controller;
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfilePolicyService; use OpenEMR\Modules\NeoLimsBridge\Repository\BillingLinkRepository; use OpenEMR\Modules\NeoLimsBridge\Service\BillingSyncService; use Symfony\Component\HttpFoundation\Response;
final class BillingController { use ControllerTrait;
 private function allow(array $p,string $o):void{(new ProfilePolicyService())->assertAllowed('billing',$o,(string)($p['connection_key']??''));}
 public function validate($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->allow($p,'validate');return $this->json((new BillingSyncService())->validate($p));});}
 public function sync($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->allow($p,'sync');$r=(new BillingSyncService())->sync($p);return $this->json($r,!empty($r['created'])?201:200);});}
 public function link(string $c,string $l,$q){return $this->guarded(function()use($c,$l){(new ProfilePolicyService())->assertAllowed('billing','read',$c);$r=(new BillingLinkRepository())->find($c,$l);return $r?$this->json($r):$this->json(['error'=>'Billing link not found'],Response::HTTP_NOT_FOUND);});}
 private function body($q):array{$p=json_decode($this->rawBody($q),true,512,JSON_THROW_ON_ERROR);if(!is_array($p))throw new \InvalidArgumentException('Request body must be a JSON object.');return $p;}
}
