<?php

namespace Ravols\LaraLogsToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

use Ravols\LaraLogsToolkit\Enums\DeleteLogAction;
use Ravols\LaraLogsToolkit\Tools\LogModifier;

class DeleteLogRecordsCommand extends Command
{
    protected $signature = 'lara-logs:delete-logs
                            {--channels=* : Comma-separated list of log channel names to delete}
                            {--action= : Delete action: latest or all}';
    protected $description = 'Delete log records from selected log channels';

    public function handle(): int
    {
        $availableChannels = $this->getAvailableChannels();

        if (empty($availableChannels)) {
            error('No log channels found in logging configuration.');

            return self::FAILURE;
        }

        $selectedChannels = $this->getSelectedChannels($availableChannels);

        if (empty($selectedChannels)) {
            error('No channels selected.');

            return self::FAILURE;
        }

        $action = $this->getDeleteAction();

        if (app()->environment('production')) {
            $confirmed = confirm(
                label: 'Application is in production environment. Are you sure you want to proceed?',
                default: false,
                hint: 'This action cannot be undone'
            );

            if (! $confirmed) {
                info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($selectedChannels as $channelName) {
            try {
                $logger = Log::channel($channelName);
                $logModifier = (new LogModifier())->setLogger($logger);

                $logModifier->delete($action);

                $successCount++;
                info("Successfully processed channel '{$channelName}'.");
            } catch (\Exception $e) {
                error("Error processing channel '{$channelName}': {$e->getMessage()}");
                $errorCount++;
            }
        }

        info("Operation completed. Success: {$successCount}, Errors: {$errorCount}");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function getAvailableChannels(): array
    {
        $channels = config('logging.channels', []);
        $availableChannels = [];

        foreach ($channels as $name => $config) {
            if (is_array($config) && isset($config['driver'])) {
                $availableChannels[$name] = $name;
            }
        }

        return $availableChannels;
    }

    protected function getSelectedChannels(array $availableChannels): array
    {
        $channelsOption = $this->option('channels');

        if (! empty($channelsOption)) {
            $selected = [];

            foreach ($channelsOption as $channelList) {
                $channels = array_map('trim', explode(',', $channelList));

                foreach ($channels as $channel) {
                    if (isset($availableChannels[$channel])) {
                        $selected[] = $channel;
                    } else {
                        warning("Channel '{$channel}' not found in available channels. Skipping.");
                    }
                }
            }

            return array_unique($selected);
        }

        return multisearch(
            label: 'Select log channel(s) to delete records from',
            options: fn (string $value) => strlen($value) > 0
                ? array_filter(
                    $availableChannels,
                    fn (string $channel) => str_contains(strtolower($channel), strtolower($value))
                )
                : $availableChannels,
            required: true,
            placeholder: 'Type to search...',
            hint: 'Use space to select, enter to confirm'
        );
    }

    protected function getDeleteAction(): DeleteLogAction
    {
        $actionOption = $this->option('action');

        if (! empty($actionOption)) {
            try {
                return DeleteLogAction::from($actionOption);
            } catch (\ValueError $e) {
                error("Invalid action '{$actionOption}'. Valid options are: latest, all");

                throw $e;
            }
        }

        return DeleteLogAction::from(
            select(
                label: 'What would you like to do?',
                options: [
                    DeleteLogAction::LATEST->value => DeleteLogAction::LATEST->label(),
                    DeleteLogAction::ALL->value => DeleteLogAction::ALL->label(),
                ],
                required: true
            )
        );
    }
}
