<?php
set_time_limit(59);

class Demon {
    const HOST = 'http://localhost:5000/';
    const FILE = 'lastnohupstart.unixtime';
    const EXPIRE = 60 * 2;
    
    public function writeStartTime() {        
        if(is_file(__DIR__ . static::FILE)) unlink (__DIR__ . static::FILE);
        file_put_contents(__DIR__ . static::FILE, time());
    }
    
    public function isExpiredByLastStartTime() {
        $lastStarted = (int) (is_file(static::FILE) ? file_get_contents(__DIR__ . static::FILE) : 0);
        $lastStarted += static::EXPIRE;
        
        return $lastStarted < time();
    }
    
    public function isWorked() {        
        try {
            $response = file_get_contents(__DIR__ . static::HOST);
            return !empty($response);
        } catch (Exception $ex) {}
        
        return false;
    }
}

class Process {    
    public function start($processName) {
        exec("nohup $processName manage.py runserver --host=0.0.0.0 & 2> /dev/null");
    }
    
    public function getPids($processName) {
        $out;
        exec("pidof $processName", $out);
        if(empty($out)) return null;
        $out = reset($out);
        return explode(' ', $out);
    }
    
    public function kill($pids) {
        foreach ($pids as $pid)
            exec("kill 9 $pid"); //exec("kill -15 $pid");
    }
}

function main() {
    $processName = "python";
    
    $demon   = new Demon();
    $process = new Process();
    
    if(!$demon->isExpiredByLastStartTime()) return;
    if($demon->isWorked()) return;
    
    echo "do restart\n";
    
    try {
        $pidsForKill = $process->getPids($processName);
        $process->kill($pidsForKill);
    } catch (Exception $ex) {}
    $process->start($processName);
    $demon->writeStartTime();
}
main();