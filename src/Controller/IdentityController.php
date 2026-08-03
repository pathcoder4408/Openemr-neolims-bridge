<?php
namespace OpenEMR\Modules\NeoLimsBridge\Controller;
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfilePolicyService;
use OpenEMR\Modules\NeoLimsBridge\Repository\IdentityLinkRepository;
use OpenEMR\Modules\NeoLimsBridge\Service\IdentitySyncService;
use Symfony\Component\HttpFoundation\Response;
final class IdentityController { use ControllerTrait;
 private function policy(array $p,string $r,string $o):void{(new ProfilePolicyService())->assertAllowed($r,$o,(string)($p['connection_key']??''));}
 public function resolvePatient($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->policy($p,'patient','resolve');return $this->json((new IdentitySyncService())->resolvePatient($p));});}
 public function syncPatient($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->policy($p,'patient','sync');$r=(new IdentitySyncService())->syncPatient($p);return $this->json($r,!empty($r['created'])?201:200);});}
 public function syncEncounter($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->policy($p,'encounter','sync');$r=(new IdentitySyncService())->syncEncounter($p);return $this->json($r,!empty($r['created'])?201:200);});}
 public function patientLink(string $c,string $l,$q){return $this->guarded(function()use($c,$l){(new ProfilePolicyService())->assertAllowed('patient','read',$c);$r=(new IdentityLinkRepository())->patientLink($c,$l);return $r?$this->json($r):$this->json(['error'=>'Patient link not found'],Response::HTTP_NOT_FOUND);});}
 private function body($q):array{$r=json_decode($this->rawBody($q),true,512,JSON_THROW_ON_ERROR);if(!is_array($r))throw new \InvalidArgumentException('JSON object required.');return $r;}
}
