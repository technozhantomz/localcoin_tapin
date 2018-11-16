<?php
class Demon {
    const HOST = 'http://localhost:5000/';
    const FILE = 'lastnohupstart.unixtime';
    const EXPIRE = 60 * 2;
    
    public function writeStartTime() {
        if(is_file(static::FILE)) unlink (static::FILE);
        file_put_contents(static::FILE, time());
    }
    
    public function isExpiredByLastStartTime() {
        $lastStarted = (int) (is_file(static::FILE) ? file_get_contents(static::FILE) : 0);
        $lastStarted += static::EXPIRE;
        
        return $lastStarted < time();
    }
    
    public function isWorked() {        
        try {
            $response = file_get_contents(static::HOST);
            return !empty($response);
        } catch (Exception $ex) {}
        
        return false;
    }
}

class Process {    
    public function start($processName) {
        exec("nohup $processName manage.py runserver --host=0.0.0.0 &");
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
    
    $pidsForKill = $process->getPids($processName);
    $process->kill($pidsForKill);
    $process->start($processName);
    $demon->writeStartTime();
}
main();