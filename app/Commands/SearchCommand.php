<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use App\Libraries\Battlelog;
use App\Models\ReportStats;

// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Schema;
// use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Facades\File;

class SearchCommand extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'search {name} {limit=1}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Search for players';

    public $pid = 0;
    /**
     * Execute the command. Here goes the code.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->info('Love beautiful code? We do too.');
        if ($user = Battlelog::getUser($this->argument('name'))) {
            $this->pid = $user->personaId;
            $limit = (int)$this->argument('limit');
            $reports = $this->getReportList($this->pid, $limit);
            $report_data = $this->getReports($this->pid, $reports);
            $headers = ['Report Date', 'RT(s)', 'VD', 'BV', 'BVK', 'K', 'D', 'HS', 'fired', 'hit', 'BWK', 'BW', 'S', 'SPM', 'Report ID', 'Rank',  'Map', 'Type', 'Mode', 'KDR', 'HSKR', 'TP', 'ACC', 'KPH'];
            $this->table($headers, $report_data);
        }

        $this->notify('List has completed finding reports for ' . $this->argument('name'), 'Take a look!');
    }

    /**
     * Define the command's schedule.
     *
     * Add the following cron entry:
     *     * * * * * php /path-to-your-project/your-app-name schedule:run >> /dev/null 2>&1
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    /**
     * @param $pid
     * @param $$limit
     * @return array
     */
    private function getReportList($pid, $limit): array
    {
        $report_ids = array();
        $reports = Battlelog::getBattlereportList($pid);
        $last_report = end($reports->data->gameReports);

        if ($reports) {
            foreach ($reports->data->gameReports as $report) {
                array_push($report_ids, $report->gameReportId);
            }
        } else {
            $last_report = false;
            $this->error('No reports found');
        }
        // current itteration in getting the report list
        $current_reportslist = 1;
        // max number if inputs of the report list
        $current_reportlist_max = floor($limit / 10);
        $bar = $this->output->createProgressBar($current_reportlist_max);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $bar->clear();
        $this->info('Fetching reports...');
        $bar->display();
        for ($i = 0; $i < floor($limit / 10); $i++) {
            if ($last_report != false) {
                $reports = Battlelog::getMoreBattlereportList($pid, $last_report->createdAt);
                $bar->advance();
                if ($reports) {
                    if (isset($reports->data->gameReports)) {
                        foreach ($reports->data->gameReports as $report) {
                            array_push($report_ids, $report->gameReportId);
                        }
                        $last_report = end($reports->data->gameReports);
                    } else {
                        $last_report = false;
                    }
                }
            } else {
                $bar->finish();
                $bar->clear();
            }
            $current_reportslist = $current_reportslist + 1;
        }
        $bar->finish();
        $bar->clear();
        return $report_ids;
    }

    /**
     * @param $report_ids
     * @param $pid
     * @return array
     */
    public function getReports($pid, $report_ids): array
    {
        $current_report = 0;
        $current_report_max = count($report_ids);

        //Create the progress bar
        $bar = $this->output->createProgressBar($current_report_max);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $bar->clear();
        $this->info('Analyzing reports...');
        $bar->display();

        $stats = array();
        foreach ($report_ids as $report_id) {
            $current_report = $current_report + 1;
            $bar->advance();
            $report = Battlelog::getBattlereport($report_id, $pid);
            if($report->playerReport === null){
                //player did nothing
                $bar->advance();
                continue;
            }
            $report_stats = $this->getReport($report);
            array_push($stats, $report_stats);
        }
        return $stats;
    }

    /**
     * @param $battlereport
     * @return int
     */
    public function getHeadshots($battlereport) : int
    {
        $awards = $battlereport->playerReport->unlocks->awards;
        if ($awards != null && sizeof($awards) > 0) {
            foreach ($awards as $award) {
                if ($award->unlockId == 'r04') {
                    return $award->timesTaken * 3;
                }
            }
        }
        return 0;
    }

    /**
     * @param $battlereport
     * @return array
     */
    public function getReport($battlereport): array
    {
        $report = new ReportStats;
        $report->report_time = $battlereport->createdAt;
        $report->time = $battlereport->duration;

        $report->vehicles_destroyed = $battlereport->playerReport->stats->vehicle_destroyed;

        if ($battlereport->playerReport->best->vehicle !== null) {
            if ($battlereport->playerReport->best->vehicle->slug) {
                $report->best_vehicle = $battlereport->playerReport->best->vehicle->slug;
            } else {
                $report->best_vehicle = "NONE";
            }
            if ($battlereport->playerReport->best->vehicle->kills) {
                $report->best_vehicle_kills = $battlereport->playerReport->best->vehicle->kills;
            } else {
                $report->best_vehicle_kills = 0;
            }
        } else {
            $report->best_vehicle = "NONE";
            $report->best_vehicle_kills = 0;
        }


        $report->kills = $battlereport->playerReport->stats->kills;
        $report->deaths = $battlereport->playerReport->stats->deaths;
        $report->headshots = $this->getHeadshots($battlereport);

        $report->fired = $battlereport->playerReport->stats->shots_fired;
        $report->hit = $battlereport->playerReport->stats->shots_hit;

        if ($battlereport->playerReport->best->weapon !== null) {
            if ($battlereport->playerReport->best->weapon->kills != 0) {
                $report->best_weapon_kills = $battlereport->playerReport->best->weapon->kills;
            } else {
                $report->best_weapon_kills = 0;
            }
            if ($battlereport->playerReport->best->weapon->slug) {
                $report->best_weapon = $battlereport->playerReport->best->weapon->slug;
            } else {
                $report->best_weapon = "NONE";
            }
        } else {
            $report->best_weapon_kills = 0;
            $report->best_weapon = "NONE";
        }

        $report->score = $battlereport->playerReport->scores->total;
        if(isset($battlereport->playerReport->stats->spm))
        {
            $report->spm = $battlereport->playerReport->stats->spm;
        } else {
            $report->spm = 0;
        }
        $report->report_id = $battlereport->id;
        $report->rank = $battlereport->players->{$this->pid}->rank;
        $report->map = $battlereport->gameServer->map;
        $report->type = $battlereport->gameServer->serverType;
        $report->mode = $battlereport->gameServer->mapMode;
        return $report->toArray();
    }
}
