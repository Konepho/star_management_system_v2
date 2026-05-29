<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): Collection
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->loadMissing('permissions')
                ->flatMap(fn (Role $role) => $role->permissions)
                ->unique('id')
                ->values();
        }

        return Permission::query()
            ->whereHas('roles.users', fn ($query) => $query->whereKey($this->getKey()))
            ->get();
    }

    public function hasRole(string $roleSlug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $roleSlug);
        }

        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->permissions()->contains('slug', $permissionSlug);
        }

        return Permission::query()
            ->where('slug', $permissionSlug)
            ->whereHas('roles.users', fn ($query) => $query->whereKey($this->getKey()))
            ->exists();
    }

    public function hasAnyPermission(iterable $permissionSlugs): bool
    {
        $permissionSlugs = collect($permissionSlugs)
            ->filter(fn (mixed $slug) => is_string($slug) && $slug !== '')
            ->values();

        if ($permissionSlugs->isEmpty()) {
            return false;
        }

        if ($this->relationLoaded('roles')) {
            $granted = $this->permissions()->pluck('slug');

            return $permissionSlugs->intersect($granted)->isNotEmpty();
        }

        return Permission::query()
            ->whereIn('slug', $permissionSlugs->all())
            ->whereHas('roles.users', fn ($query) => $query->whereKey($this->getKey()))
            ->exists();
    }

    public function accessibleAuditLogCategories(): Collection
    {
        $categories = collect();

        if ($this->hasPermission('audit_logs.finance.view')) {
            $categories->push('finance');
        }

        if ($this->hasPermission('audit_logs.academic.view')) {
            $categories->push('academic');
        }

        if ($this->hasPermission('audit_logs.settings.view')) {
            $categories->push('settings');
        }

        return $categories->unique()->values();
    }

    public function requiresSectionScope(): bool
    {
        return $this->hasRole('teacher') || $this->hasRole('section_head');
    }

    public function assignedSectionIds(?int $academicYearId = null): Collection
    {
        return $this->staff?->assignedSectionIds($academicYearId) ?? collect();
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function studentDailyReports(): HasMany
    {
        return $this->hasMany(StudentDailyReport::class, 'reported_by_user_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'performed_by_user_id');
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'performed_by_user_id');
    }
}
