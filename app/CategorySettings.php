<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class CategorySettings extends Model
{
    protected $table = 'category_settings';
    public $timestamps = true;
    protected $fillable = [
        "category_tournament_id",
        "isTeam",
        "teamSize",
        "fightDuration",
        "hasEncho",
        "enchoQty",
        "enchoDuration",
        "hasRoundRobin",
        "roundRobinWinner",
        "hasHantei",
        "cost",
    ];

    //option to seed competitors

    public static function getDefaultSettings()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $categorySetting = $user->settings;

            if ($categorySetting == null) {
                $categorySetting = new CategorySettings();
                $categorySetting->fightDuration = Config::get('constants.CAT_FIGHT_DURATION');
                $categorySetting->isTeam = Config::get('constants.CAT_ISTEAM');
                $categorySetting->teamSize = Config::get('constants.CAT_TEAMSIZE');
                $categorySetting->hasRoundRobin = Config::get('constants.CAT_HASROUNDROBIN');
                $categorySetting->roundRobinWinner = Config::get('constants.CAT_ROUNDROBINWINNER');
                $categorySetting->hasEncho = Config::get('constants.CAT_HASENCHO');
                $categorySetting->enchoQty = Config::get('constants.CAT_enchoQty');
                $categorySetting->enchoDuration = Config::get('constants.CAT_ENCHO_DURATION');
                $categorySetting->hasHantei = Config::get('constants.CAT_HASHANTEI');
                $categorySetting->cost = Config::get('constants.CAT_COST');


            } else {
                if ($categorySetting->fightDuration == 0)
                    $categorySetting->fightDuration = Config::get('CAT_FIGHT_DURATION');

                if ($categorySetting->hasRoundRobin && $categorySetting->roundRobinWinner == 0)
                    $categorySetting->roundRobinWinner = Config::get('CAT_ROUNDROBINWINNER');

                if ($categorySetting->hasEncho && $categorySetting->enchoQty == 0)
                    $categorySetting->enchoQty = Config::get('CAT_enchoQty');

                if ($categorySetting->hasEncho && $categorySetting->enchoDuration == 0)
                    $categorySetting->enchoDuration = Config::get('CAT_ENCHO_DURATION');

            }
            return $categorySetting;
        }
    }

}