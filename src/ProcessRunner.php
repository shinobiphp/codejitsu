<?php

declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Contracts\ProcessRunner as ProcessRunnerContract;
use RuntimeException;

final class ProcessRunner implements ProcessRunnerContract
{
    public function run(array $command, string $cwd): ProcessResult
    {
        $pipes = [];
        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $cwd,
        );
        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('Unable to start process [%s].', $command[0] ?? 'unknown'));
        }

        $stdout = '';
        $stderr = '';
        try {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
        } finally {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
        }

        return new ProcessResult(
            proc_close($process),
            (string) $stdout,
            (string) $stderr,
        );
    }
}
