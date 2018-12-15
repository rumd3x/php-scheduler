<?php
namespace Rumd3x\Scheduler;

use Closure;
use Exception;
use Carbon\Carbon;
use Cron\CronExpression;

class Schedule {

    private $action;
    private $name = "Default";
    
    private static $lock_file = 'scheduler.lock';
    private $lock = NULL;
    private $cron = [];
    
    public function __destruct()
    {
        $this->free();
    }

    public function run() {
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

    private function eval(CronExpression $cron) {
        if ($cron->isDue()) {
            $now_str = Carbon::now()->format('d/m/Y H:i:s');
            print "[$now_str]: Job {$this->name} Started\n";

            $closure = $this->action;
            $retorno = $closure($cron);
            
            $now_str = Carbon::now()->format('d/m/Y H:i:s');
            print "[$now_str]: Job {$this->name} Finished\n"; 
        } else {
            $retorno = false;
        }

        return $retorno;
    }

    private function getLock() {
        if (!file_exists(self::$lock_file)) {
            file_put_contents(self::$lock_file, '');
        }

        $this->lock = fopen('./scheduler.lock', 'r+');
 
        if(!flock($this->lock, LOCK_EX | LOCK_NB)) {
            $now_str = Carbon::now()->format('d/m/Y H:i:s');
            print "[$now_str]: Job {$this->name} Failed: Error Obtaining Lock\n";
            return false;
        }

        return true;        
    }

    private function free() {
        if ($this->lock) {
            fclose($this->lock);
        }
        return $this;
    }

    private function makeCron() {
        $parsed = [];
        for ($i=0; $i <= 4; $i++) { 
            $parsed[$i] = isset($this->cron[$i]) ? $this->cron[$i] : '*';
        }
        $expression = implode(' ', $parsed);
        return CronExpression::factory($expression);
    }
    
    public function isDue()
    {
        return $this->makeCron()->isDue();
    }

    public static function action(Closure $call) {
        $instance = new static;
        $instance->action = $call;
        return $instance;
    }

    public function cron(String $expression) {
        $this->cron = explode(' ', $expression);
        return $this;
    }

    public function at(String $time) {
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

    public function monthly(Int $day = 1) {
        $day = in_array($day, range(1, 31)) ? $day : 1;
        $this->cron[0] = '0';
        $this->cron[1] = '0';
        $this->cron[2] = $day;
        return $this;
    } 

    public function weekly(Int $weekday = 0) {
        $weekday = in_array($weekday, range(0, 6)) ? $weekday : 0;
        $this->cron = ['0', '0', '*', '*', $weekday];
        return $this;
    } 

    public function daily() {
        $this->cron = ['0', '0', '*', '*', '*'];
        return $this;
    } 

    public function hourly(Int $minute = 0) {
        $minute = in_array($minute, range(0, 59)) ? $minute : 1;
        $this->cron = [$minute, '*', '*', '*', '*'];
        return $this;
    }

    public function everyThirtyMinutes() {
        $this->cron = ['*/30', '*', '*', '*', '*'];
        return $this;
    }

    public function everyFifteenMinutes() {
        $this->cron = ['*/15', '*', '*', '*', '*'];
        return $this;
    }

    public function everyTenMinutes() {
        $this->cron = ['*/10', '*', '*', '*', '*'];
        return $this;
    }

    public function everyFiveMinutes() {
        $this->cron = ['*/5', '*', '*', '*', '*'];
        return $this;
    }

    public function everyMinute() {
        $this->cron = ['*', '*', '*', '*', '*'];
        return $this;
    }

    public function setName(String $name) {
        $this->name = $name;
        return $this;
    }
}
