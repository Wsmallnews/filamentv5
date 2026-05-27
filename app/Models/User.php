<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;
use Wsmallnews\Comment\Models\Concerns\BeReplyer;
use Wsmallnews\Comment\Models\Concerns\Commenter;
use Wsmallnews\Preference\Models\Concerns\Preferencer;
use Wsmallnews\Preference\Models\Concerns\Preferencer\Follower;
use Wsmallnews\Preference\Models\Concerns\Preferencer\Liker;
use Wsmallnews\Preference\Models\Concerns\Preferencer\Viewer;
use Wsmallnews\Support\Concerns\UserIdentifiable;
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Wsmallnews\User\Models\Concerns\TwoFactorAuthenticatable;
use Wsmallnews\User\Userable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar, HasEmailAuthentication, HasName, HasSnIdentifiable, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BeReplyer;

    use Commenter;
    use Follower;
    use HasActivity;
    use HasFactory, Notifiable;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use InteractsWithEmailAuthentication;
    use Liker;
    use Preferencer;
    use TwoFactorAuthenticatable;
    use Userable;
    use UserIdentifiable;
    use Viewer;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? files_url($this->avatar_url) : null;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
