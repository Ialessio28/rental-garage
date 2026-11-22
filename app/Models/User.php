<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A user of the app. The role is either "admin" or "tenant".
 * New tenants start as "pending" until the admin approves them.
 */
class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['nik', 'name', 'phone', 'email', 'password', 'role', 'status'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Attribute casts (the password is hashed automatically when saved).
     */
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    /**
     * All rentals made by this tenant.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Check whether this user is the admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check whether the admin has accepted this user's registration.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
