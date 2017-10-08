<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportStats extends Model
{
    protected $table = 'report_stats';
    protected $primaryKey = 'id';
    protected $dates = ['created_at', 'updated_at', 'report_time'];
    public $incrementing = false;
    protected $appends = ['kdr', 'hskr', 'time_playing', 'accuracy', 'kph'];

    protected $fillable = [
        'report_time', //timestamp
        'time', //duration of round
        'rank', //rank the player was during this report
        //'account_age', //age of the account when the report was created

        'vehicles_destroyed', //vehicles destroyed over the round
        'best_vehicle_kills', //kills using the best vehicle
        'best_vehicle', //best vehicle

        'kills',
        'deaths',
        'headshots',
        'fired', //shots fired
        'hit', //shots hit
        'best_weapon_kills',
        'best_weapon',
        'score',
        'spm',
        'report_id', //battlereport id that will link to the main report and include map stats ect
        'mode',
        'type'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function report()
    {
        return $this->hasOne('App\Models\Report');
    }

    /**
     * Game mode
     * @param $value
     * @return mixed
     */
    public function getModeAttribute($value) {
         $modes = [
            '64' => 'Conquest Large',
            '1' => 'Conquest',
            '2097152' => 'Obliteration',
            '1024' => 'Domination',
            '2' => 'Rush',
            '8' => 'Squad Deathmatch',
            '32' => 'Team Deathmatch',
            '16777216' => 'Defuse',
            '8388608' => 'Air Superiority',
            '524288' => 'Capture the Flag',
            '67108864' => 'Carrier Assault Large',
            '134217728' => 'Carrier Assault',
            '34359738368' => 'Chain Link',
            '68719476736' => 'Conquest Ladder',
            '137438953472' => 'Squad Obliteration',
            '512' => 'Gun Master'
        ];
        return $modes[(string)$value];
    }

    /**
     * kdr of player
     * @param $value
     * @return float|int
     */
    public function getKdrAttribute($value)
    {
        if($this->kills == 0) {
            return 0;
        }
        if($this->deaths == 0)
        {
            $this->deaths = 1;
        }
        return round($this->kills / $this->deaths, 2);
    }

    /**
     * hskr of player
     * @param $value
     * @return float|int
     */
    public function getHskrAttribute($value)
    {
        if($this->headshots == 0)
        {
            return 0;
        }
        if($this->kills == 0)
        {
            return 0;
        }
        return round(100* ($this->headshots / $this->kills), 2);
    }

    /**
     * total time the player was playing
     * @param $value
     * @return float|int
     */
    public function getTimePlayingAttribute($value)
    {
        if($this->score == 0)
        {
            $this->score = 1;
        }
        if($this->spm == 0)
        {
            $this->spm = 1;
        }
        return round(($this->score / $this->spm) * 60, 2);
    }

    /**
     * Accuracy of the player during the round
     * @param $value
     * @return float|int
     */
    public function getAccuracyAttribute($value)
    {
        if($this->hit == 0) {
            return 0;
        }
        if($this->fired == 0) {
            return 0;
        }
        return round(100* ($this->hit / $this->fired), 2);
    }

    /**
     * Kills per hit
     * @param $value
     * @return float
     */
    public function getKphAttribute($value)
    {
        if($this->kills == 0)
        {
            return 0;
        }
        if($this->hit == 0)
        {
            $this->hit = 1;
        }
        return round(100 * ($this->kills / $this->hit));
    }

    /**
     * game mode
     * @param $value
     * @return mixed
     */
    public function getTypeAttribute($value)
    {
        $types = [
            '1' => 'O',
            '2' => 'R',
            '4' => 'U',
            '8' => 'P',
        ];
        return $types[(string)$value];
    }
}