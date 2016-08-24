<?php

namespace App;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Countries\Countries;

class Federation extends Model
{

    protected $table = 'federation';
    public $timestamps = true;

    protected $guarded = ['id'];


    public function president()
    {
        return $this->hasOne(User::class, 'id', 'president_id');
    }


    public function associations()
    {
        return $this->hasMany(Association::Class);
    }

    public function country()
    {
        return $this->belongsTo(Countries::Class);
    }

    /**
     * @param $query
     * @param User $user
     * @return Builder
     * @throws AuthorizationException
     */
    public function scopeForUser($query, User $user)
    {
        switch (true) {
            case $user->isSuperAdmin():
                return $query;
            case $user->isFederationPresident() && $user->federationOwned != null:
                return $query->where('id', '=', $user->federationOwned->id);

            case $user->isAssociationPresident() && $user->associationOwned:
                return $query->where('id', '=', $user->associationOwned->federation->id);

            case $user->isClubPresident() && $user->clubOwned:
                return $query->where('id', '=', $user->clubOwned->association->federation->id);
            default:
                throw new AuthorizationException();

        }
    }
}