<?php

namespace App\Process;

class ProcessManager
{
    private $maxThreads;
    private $activeProcesses = 0;
    private $shouldStop = false;
    private $taskHandler;

    public function __construct($maxThreads)
    {
        $this->maxThreads = $maxThreads;
    }

    public function runProcesses($tasks, array $constructArgs): void
    {
        pcntl_signal(SIGTERM, [$this, 'handleInterruptSignal']);

        $childPids = [];

        while (true) {
            foreach ($childPids as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                if ($res === -1 || $res > 0) {
                    unset($childPids[$key]);
                }
            }

            if ($this->shouldStop) {
                break;
            }

            if (count($childPids) < $this->maxThreads) {
                $task = $tasks->shift();

                if ($task !== null) {
                    $pid = pcntl_fork();

                    if ($pid == -1) {
                        die('Could not fork');
                    } elseif ($pid) {
                        $childPids[] = $pid;
                        $this->activeProcesses++;
                    } else {
                        call_user_func([new $task['class'](...$constructArgs), $task['method']], $task);
                        exit();
                    }
                }
            }

            if ($tasks->isEmpty()) {
                break;
            }

            sleep(1);
        }

        while (!empty($childPids)) {
            $pid = array_shift($childPids);
            pcntl_waitpid($pid, $status);
            $this->activeProcesses--;
        }
    }

    public function handleInterruptSignal($signo)
    {
        $this->shouldStop = true;
    }
}
