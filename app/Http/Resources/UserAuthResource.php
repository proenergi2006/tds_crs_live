<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

// Satu-satunya tempat payload user-auth dibentuk, dipake di 5 titik (Auth + Impersonation controllers) -- jangan reimplement lokal.
class UserAuthResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'permissions'   => $this->getAllPermissions()->pluck('name')->values(),
            'roles'         => $this->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values(),
            'primary_role'  => $this->primaryRole ? ['id' => $this->primaryRole->id, 'name' => $this->primaryRole->name] : null,
            'brand'         => $this->resolveBrand(),
            'impersonation' => $this->when(isset($this->impersonation), $this->impersonation),
        ];
    }

    private function resolveBrand(): string
    {
        return in_array($this->primaryRole?->id, [13, 14, 15, 16], true) ? 'proenergi' : 'tds';
    }
}
