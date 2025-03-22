<?php

namespace App\Jobs;

use App\Models\Media;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        $itunesNamespace = $namespaces['itunes'];

        $trackLength = $track->children($itunesNamespace)->duration;

        $interval = CarbonInterval::createFromFormat('H:i:s', $trackLength);

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
