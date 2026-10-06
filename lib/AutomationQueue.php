<?php

declare(strict_types=1);

final class AutomationQueue
{
    public const JOB_PROCESS_MESSAGE = 'process_message';

    /**
     * @param array<string, mixed> $context
     */
    public function enqueueProcessMessage(array $context): string
    {
        return RepositoryFactory::automationQueue()->enqueue(self::JOB_PROCESS_MESSAGE, $context);
    }

    public function processBatch(int $limit = 20): int
    {
        require_once dirname(__DIR__) . '/jobs/process_message.php';

        $jobs = RepositoryFactory::automationQueue()->claimPending($limit);
        $processed = 0;

        foreach ($jobs as $job) {
            $jobId = (string) ($job['id'] ?? '');
            $jobType = (string) ($job['job_type'] ?? '');

            try {
                if ($jobType !== self::JOB_PROCESS_MESSAGE) {
                    throw new RuntimeException('Unknown job type: ' . $jobType);
                }

                $payload = json_decode((string) ($job['payload'] ?? ''), true);
                if (!is_array($payload)) {
                    throw new RuntimeException('Invalid job payload.');
                }

                $result = process_inbound_message($payload);
                if (($result['ok'] ?? false) !== true) {
                    $error = (string) ($result['error'] ?? 'processing_failed');
                    if (in_array($error, ['subscription_inactive', 'dm_limit_reached', 'tokens_depleted'], true)) {
                        RepositoryFactory::automationQueue()->markCompleted($jobId);
                    } else {
                        RepositoryFactory::automationQueue()->markFailed($jobId, $error);
                    }
                } elseif (isset($result['skipped'])) {
                    RepositoryFactory::automationQueue()->markCompleted($jobId);
                } elseif (($result['reply_sent'] ?? true) !== true) {
                    RepositoryFactory::automationQueue()->markFailed($jobId, 'platform_send_failed');
                } else {
                    RepositoryFactory::automationQueue()->markCompleted($jobId);
                }

                $processed++;
            } catch (Throwable $e) {
                error_log('[AutomationQueue] Job ' . $jobId . ' failed: ' . $e->getMessage());
                RepositoryFactory::automationQueue()->markFailed($jobId, $e->getMessage());
            }
        }

        return $processed;
    }
}
