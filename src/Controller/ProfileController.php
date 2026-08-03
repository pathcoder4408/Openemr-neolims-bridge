<?php

namespace OpenEMR\Modules\NeoLimsBridge\Controller;

use OpenEMR\Modules\NeoLimsBridge\Profile\ProfilePolicyService;
use OpenEMR\Modules\NeoLimsBridge\Profile\ProfileRepository;

final class ProfileController
{
    use ControllerTrait;

    public function list($request) { return $this->guarded(fn() => $this->json(['data' => (new ProfileRepository())->list()])); }
    public function read(string $key, $request) { return $this->guarded(function() use ($key) { $p=(new ProfileRepository())->findByKey($key); return $p?$this->json($p):$this->json(['error'=>'Profile not found'],404); }); }
    public function save($request) { return $this->guarded(fn() => $this->json((new ProfileRepository())->upsert($this->body($request)), 201)); }
    public function activate(string $key, $request) { return $this->guarded(fn() => $this->json((new ProfileRepository())->activate($key))); }
    public function capabilities($request) { return $this->guarded(function() use ($request) { $connection=(string)$request->query->get('connection_key',''); return $this->json((new ProfilePolicyService())->capabilities($connection ?: null)); }); }
    private function body($request): array { $x=json_decode($this->rawBody($request),true,512,JSON_THROW_ON_ERROR); if(!is_array($x)) throw new \InvalidArgumentException('JSON object required.'); return $x; }
}
