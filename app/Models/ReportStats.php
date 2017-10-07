<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportStats extends Model
{
    protected $table = 'report_stats';
    protected $primaryKey = 'id';
    protected $dates = ['created_at', 'updated_at', 'report_time'];
    public $incrementing = false;

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
        'report_id' //battlereport id that will link to the main report and include map stats ect
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function report()
    {
        return $this->hasOne('App\Models\Report');
    }

    /**
     * get the map name for this round
     * @param $value
     * @return mixed
     */
    public function getMapAttribute($value)
    {
        return $this->report->map;
    }

    /**
     * kdr of player
     * @param $value
     * @return float|int
     */
    public function getKdrAttribute($value)
    {
        return $this->kills / $this->deaths;
    }

    /**
     * hskr of player
     * @param $value
     * @return float|int
     */
    public function getHskrAttribute($value)
    {
        return 100* ($this->headshots / $this->kills);
    }

    /**
     * total time the player was playing
     * @param $value
     * @return float|int
     */
    public function getTimePlayingAttribute($value)
    {
        return $this->score / $this->spm;
    }

    /**
     * Accuracy of the player during the round
     * @param $value
     * @return float|int
     */
    public function getAccuracyAttribute($value)
    {
        return 100* ($this->hit / $this->fired);
    }
}