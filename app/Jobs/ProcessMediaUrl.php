<?php

namespace App\Jobs;

use App\Models\Media;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMediaUrl implements ShouldQueue
{
    use Queueable;

    public $rssUrl;
    public $room;
    public $track;

    /**
     * Create a new job instance.
     */
    public function __construct($rssUrl, $room, $track)
    {
        $this->rssUrl = $rssUrl;
        $this->room = $room;
        $this->track = $track;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $xml = simplexml_load_file($this->rssUrl);

        $mediaTitle = $xml->channel->title;
        $mediaArtworkUrl = $xml->channel->image->url;

        $track = $xml->channel->item[0];
        $trackTitle = $track->title;
        $trackMediaUrl = (string) $track->enclosure['url'];

        $namespaces = $xml->getNamespaces(true);
        $itunesNamespace = $namespaces['itunes'] ?? null;

        $trackLength = null;

        if ($itunesNamespace) {
            $trackLength = $track->children($itunesNamespace)->duration;
        }

        if (empty($itunesNamespace)) {
            $fileSize = (int) $track->enclosure['length'];
            $bitrate = 128000; // Assumes 128kbps as standard bitrate
            $durationInSeconds = ceil($fileSize * 8 / $bitrate);
            $trackLength = (string) $durationInSeconds;
        }

        try {
            if (str_contains($trackLength, ":")) {
                // duration is in HH:MM:SS or MM:SS format
                $parts = explode(":", $trackLength);
                if (count($parts) == 2) {
                    $interval = CarbonInterval::createFromFormat('i:s', $trackLength);
                } elseif (count($parts) == 3) {
                    $interval = CarbonInterval::createFromFormat('H:i:s', $trackLength);
                } else {
                    throw new \Exception('Unexpected duration format');
                };
            } else {
                $interval = CarbonInterval::seconds((int) $trackLength);
            }
        } catch (\Exception $e) {
            Log::error("Error parsing track duration: " . $e->getMessage());
            $interval = CarbonInterval::hour(); // Default to 1 hour
        }

        $endTime = $this->room->start_time->add($interval);

        $media = Media::updateOrCreate([
            'title' => $mediaTitle,
            'artwork_url' => $mediaArtworkUrl,
            'rss_url' => $this->rssUrl,
        ]);

        $this->track->media()->associate($media);

        $this->track->update([
            'title' => $trackTitle,
            'media_url' => $trackMediaUrl,
        ]);

        $this->room->update([
            'end_time' => $endTime,
        ]);
    }
}
