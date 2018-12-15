<?php
namespace Rumd3x\Scheduler;

use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use Exception;

class Schedule
{
    const LOCK_FILE = 'scheduler.lock';

    private $action;
    private $name = "Default";

    private $lock = null;
    private $cron = [];

    public function __destruct()
    {
        $this->free();
    }

    public function run()
    {
        $valid_action = ($this->action instanceof Closure);
        if ($this->getLock() && $valid_action) {
            try {
                $cron = $this->makeCron();
                $retorno = $this->eval($cron);
            } catch (Exception $e) {
                $retorno = false;
            } finally {
                $this->free();
                return $retorno;
            }
        }
        return false;
    }

    function eval(CronExpression $cron) {
        if ($cron->isDue()) {
            $now_str = Carbon::now()->format('d/m/Y H:i:s');
            print "[$now_str]: Job {$this->name} Started\n";

            $closure = $this->action;
            $retorno = $closure($cron);

            $now_str = Carbon::now()->format('d/m/Y H:i:s');
            print "[$now_str]: Job {$this->name} Finished OK\n";
        } else {
            $retorno = false;
        }

        return $retorno;
    }

    private function getLock()
    {
        if (!file_exists(self::LOCK_FILE)) {
            file_put_contents(self::LOCK_FILE, '');
        }
        chmod(self::LOCK_FILE, 0777);

        $this->lock = fopen('./' . self::LOCK_FILE, 'r+');

        if (!$this->lock) {
            throw new Exception("Job {$this->name} Failed: Error Opening Lock File\n");
        }

        if (!flock($this->lock, LOCK_EX)) {
            throw new Exception("Job {$this->name} Failed: Error Obtaining Lock on Resource\n");
        }

        return true;
    }

    private function free()
    {
        if ($this->lock && is_resource($this->lock) && get_resource_type($this->lock) !== 'Unknown') {
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
        }
        return $this;
    }

    private function makeCron()
    {
        $parsed = [];
        for ($i = 0; $i <= 4; $i++) {
            $parsed[$i] = isset($this->cron[$i]) ? $this->cron[$i] : '*';
        }
        $expression = implode(' ', $parsed);
        return CronExpression::factory($expression);
    }

    public function isDue()
    {
        return $this->makeCron()->isDue();
    }

    public static function action(Closure $call)
    {
        $instance = new static;
        $instance->action = $call;
        return $instance;
    }

    public function cron(String $expression)
    {
        $this->cron = explode(' ', $expression);
        return $this;
    }

    public function at(String $time)
    {
        try {
            $time = explode(':', $time);
            $this->cron[1] = $time[0]; // Hours
            $this->cron[0] = $time[1]; // Minutes
        } catch (\Exception $e) {
            $this->cron[1] = '*';
            $this->cron[0] = '*';
        } finally {
            return $this;
        }
    }

    public function monthly(Int $day = 1)
    {
        $day = in_array($day, range(1, 31)) ? $day : 1;
        $this->cron[0] = '0';
        $this->cron[1] = '0';
        $this->cron[2] = $day;
        return $this;
    }

    public function weekly(Int $weekday = 0)
    {
        $weekday = in_array($weekday, range(0, 6)) ? $weekday : 0;
        $this->cron = ['0', '0', '*', '*', $weekday];
        return $this;
    }

    public function daily()
    {
        $this->cron = ['0', '0', '*', '*', '*'];
        return $this;
    }

    public function hourly(Int $minute = 0)
    {
        $minute = in_array($minute, range(0, 59)) ? $minute : 1;
        $this->cron = [$minute, '*', '*', '*', '*'];
        return $this;
    }

    public function everyThirtyMinutes()
    {
        $this->cron = ['*/30', '*', '*', '*', '*'];
        return $this;
    }

    public function everyFifteenMinutes()
    {
        $this->cron = ['*/15', '*', '*', '*', '*'];
        return $this;
    }

    public function everyTenMinutes()
    {
        $this->cron = ['*/10', '*', '*', '*', '*'];
        return $this;
    }

    public function everyFiveMinutes()
    {
        $this->cron = ['*/5', '*', '*', '*', '*'];
        return $this;
    }

    public function everyMinute()
    {
        $this->cron = ['*', '*', '*', '*', '*'];
        return $this;
    }

    public function setName(String $name)
    {
        $this->name = $name;
        return $this;
    }
}
