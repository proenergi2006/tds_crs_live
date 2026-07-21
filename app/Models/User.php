<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPermissions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'id_cabang',
        'id_role',
        'face_image_path',
        'no_telepon',
        'face_descriptor',
        'liveness_passed',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'face_descriptor'   => 'array',
        'liveness_passed'   => 'boolean',
    ];

    // 'permissions' dikirim sebagai array of string ke frontend melalui payload
    // login dan /api/user, sehingga frontend bisa cek can() tanpa roundtrip ke BE.
    protected $appends = ['permissions'];

    // -------------------------------------------------------------------------
    // Permission payload (appended ke JSON serialization)
    // -------------------------------------------------------------------------

    /**
     * Array nama permission yang dimiliki user ini — disertakan di setiap
     * response yang mengembalikan User model (/api/login, /api/user).
     *
     * Admin (id_role=1): SEMUA nama permission di tabel, konsisten dengan
     * Gate::before bypass yang membuat admin true untuk semua ability.
     * Selain admin: hanya permission yang ter-mapping di role_has_permissions.
     * Role tanpa mapping: array kosong.
     *
     * Guard terhadap konflik Eloquent: HasPermissions trait menyediakan
     * relasi 'permissions' (morphToMany ke tabel model_has_permissions).
     * Jika relasi itu ter-load sebelum serialization, Eloquent's toArray()
     * melakukan array_merge(attributesToArray(), relationsToArray()) dan
     * relationsToArray() menimpa accessor karena posisinya di kanan merge.
     * Solusi: unsetRelation() di sini agar relasi Spatie tidak masuk ke output.
     */
    public function getPermissionsAttribute(): array
    {
        $this->unsetRelation('permissions');

        if ($this->permissionsCache !== null) {
            return $this->permissionsCache;
        }

        if ($this->id_role === 1) {
            return $this->permissionsCache = DB::table('permissions')
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        return $this->permissionsCache = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.id_role', $this->id_role)
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->all();
    }

    /** Cache instance-level untuk getPermissionsAttribute(). */
    private ?array $permissionsCache = null;

    // -------------------------------------------------------------------------
    // Permission resolution
    // -------------------------------------------------------------------------

    /**
     * Override Spatie's hasPermissionTo() — tambahkan fallback ke role_has_permissions
     * via users.id_role (bukan via Spatie Role model yang tidak kita pakai).
     *
     * Jalur resolusi:
     *   1. model_has_permissions (direct user permission)
     *   2. role_has_permissions WHERE id_role = $this->id_role
     *
     * Admin bypass (id_role=1) ditangani di Gate::before pada AuthServiceProvider,
     * sehingga method ini tidak pernah dipanggil untuk Administrator.
     *
     * Signature identik dengan parent trait agar $user->can() / $this->authorize()
     * di controller tetap bekerja tanpa perubahan.
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Administrator bypass — safety net di sini karena Spatie mendaftarkan
        // Gate::before-nya lewat callAfterResolving yang terpicu saat Gate pertama
        // di-resolve oleh AuthServiceProvider::boot(), sehingga Spatie's callback
        // masuk ke array lebih dulu dan method ini tetap terpanggil untuk admin.
        // Gate::before di AuthServiceProvider tetap diperlukan untuk cover
        // kasus lain (policy check, dll.) yang tidak lewat hasPermissionTo().
        if ($this->id_role === 1) {
            return true;
        }

        if ($this->getWildcardClass()) {
            return $this->hasWildcardPermission($permission, $guardName);
        }

        // Resolusi nama ke Permission model instance.
        // Lempar PermissionDoesNotExist jika permission tidak ada di tabel;
        // exception ini ditangkap oleh checkPermissionTo() Spatie di Gate callback.
        $permission = $this->filterPermission($permission, $guardName);

        // Jalur 1: direct permission pada user (model_has_permissions)
        if ($this->hasDirectPermission($permission)) {
            return true;
        }

        // Jalur 2: permission lewat id_role → role_has_permissions
        return $this->roleHasPermission($permission->getKey());
    }

    /**
     * Kembalikan semua permission_id milik role user ini dari role_has_permissions.
     * Hasil di-cache di instance (request-scoped) agar tidak N+1 ketika
     * hasPermissionTo() atau getRolePermissionNames() dipanggil berkali-kali
     * dalam satu request (misal saat membangun payload permissions untuk login).
     */
    public function getRolePermissionIds(): array
    {
        if ($this->rolePermissionIds === null) {
            $this->rolePermissionIds = DB::table('role_has_permissions')
                ->where('id_role', $this->id_role)
                ->pluck('permission_id')
                ->all();
        }

        return $this->rolePermissionIds;
    }

    /** Cache instance-level untuk getRolePermissionIds(). */
    private ?array $rolePermissionIds = null;

    private function roleHasPermission(int|string $permissionId): bool
    {
        return in_array($permissionId, $this->getRolePermissionIds(), strict: true);
    }
}
