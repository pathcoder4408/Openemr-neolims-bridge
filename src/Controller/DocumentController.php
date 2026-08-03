<?php
namespace OpenEMR\Modules\NeoLimsBridge\Controller;
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfilePolicyService; use OpenEMR\Modules\NeoLimsBridge\Repository\DocumentLinkRepository; use OpenEMR\Modules\NeoLimsBridge\Service\DocumentSyncService;
final class DocumentController { use ControllerTrait;
 private function allow(array $p,string $o):void{(new ProfilePolicyService())->assertAllowed('document',$o,(string)($p['connection_key']??''));}
 public function validate($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->allow($p,'validate');return $this->json((new DocumentSyncService())->validate($p));});}
 public function sync($q){return $this->guarded(function()use($q){$p=$this->body($q);$this->allow($p,'sync');$x=(new DocumentSyncService())->sync($p);return $this->json($x,!empty($x['created'])?201:200);});}
 public function fhir($q,?string $uuid=null){return $this->guarded(function()use($q,$uuid){$f=$this->body($q);$p=(new DocumentSyncService())->fromFhir($f,$uuid);$this->allow($p,'sync');$x=(new DocumentSyncService())->sync($p);return $this->json($x,!empty($x['created'])?201:200);});}
 public function link(string $c,string $l,$q){return $this->guarded(function()use($c,$l){(new ProfilePolicyService())->assertAllowed('document','read',$c);$x=(new DocumentLinkRepository())->find($c,$l);return $x?$this->json($x):$this->json(['error'=>'Document link not found'],404);});}
 private function body($q):array{$x=json_decode($this->rawBody($q),true,512,JSON_THROW_ON_ERROR);if(!is_array($x))throw new \InvalidArgumentException('Request body must be a JSON object.');return $x;}
}
