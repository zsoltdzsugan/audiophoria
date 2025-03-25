<?php

use Illuminate\Http\RedirectResponse;
use Livewire\Volt\Component;
use App\Models\Room;
use App\Models\Message;
use App\Events\NewMessageEvent;

new class extends Component {
    public Room $room;
    public bool $isFinished = false;
    public string $message = '';

    protected array $rules = [
        'message' => 'required|string|max:255',
    ];

    public function authenticateUser(): RedirectResponse
    {
        session()->put('auth_redirect', route('rooms.show', $this->room));
        return redirect()->route('login');
    }

    public function sendMessage(): void
    {
        $this->validate();

        $this->room->messages()->create([
            'user_id' => auth()->id(),
            'message' => $this->message,
        ]);

        event(new NewMessageEvent($this->room->id, $this->message));

        $this->message = '';
    }

    public function getListeners(): array
    {
        return [
            'echo:room.{room.id},.new-message' => 'refresh',
        ];
    }

    public function mount(Room $room): void
    {
        if (!$room->is_active) {
            $this->isFinished = true;
        }
        $this->room = $room->load('track.media');
    }

    public function with(): array
    {
        return [
            'messages' => $this->room->messages()->with('user')->orderBy('created_at', 'asc')->get(),
        ];
    }
}; ?>

<div x-data="{ 
    audio: null,
    isLoading: true,
    isLive: false,
    isPlaying: false,
    isReady: false,
    currentTime: 0,
    audioMetadataLoaded: false,
    volume: 1,
    startTimestamp: {{ $room->start_time->timestamp }},
    endTimestamp: {{ $room->end_time ? $room->end_time->timestamp : 'null' }},
    countdownText: '',
    copyNotification: false,
    
    adjustVolume(event) {
        if (this.audio) {
            const delta = event.deltaY > 0 ? -0.1 : 0.1;
            this.volume = Math.max(0, Math.min(1, this.volume + delta));
            this.audio.volume = this.volume;
        }
    },

    togglePlay() {
        if (this.audio) {
            if (this.isPlaying) {
                this.audio.pause();
            } else {
                this.audio.play();
            }
        }
    },

    init() {
        this.startCountdown();
        if (this.$refs.audioPlayer && !this.isFinished) {
            this.initializeAudioPlayer();
        }
    },

    initializeAudioPlayer() {
        this.audio = this.$refs.audioPlayer;
        this.audio.addEventListener('loadedmetadata', () => {
            this.isLoading = false;
            this.audioMetadataLoaded = true;
            this.checkLiveStatus();
        });

        this.audio.addEventListener('timeupdate', () => {
            this.currentTime = this.audio.currentTime;
            if (this.endTimestamp && this.currentTime >= (this.endTimestamp - this.startTimestamp)) {
                this.endRoom();
            }
        });

        this.audio.addEventListener('play', () => {
            this.isPlaying = true;
            this.isReady = true;
        });

        this.audio.addEventListener('pause', () => {
            this.isPlaying = false;
        });

        this.audio.addEventListener('ended', () => {
            this.endRoom();
        });
    },

    startCountdown() {
        this.checkLiveStatus();
        setInterval(() => this.checkLiveStatus(), 1000);
    },

    checkLiveStatus() {
        const now = Math.floor(Date.now() / 1000);
        const timeUntilStart = this.startTimestamp - now;

        if (timeUntilStart <= 0) {
            this.isLive = true;
            if (this.audio && !this.isPlaying && !this.isFinished) {
                this.playAudio();
            }
        } else {
            const days = Math.floor(timeUntilStart / 86400);
            const hours = Math.floor((timeUntilStart % 86400) / 3600);
            const minutes = Math.floor((timeUntilStart % 3600) / 60);
            const seconds = timeUntilStart % 60;
            this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        }
    },

    playAudio() {
        if (!this.audio) return;
        const now = Math.floor(Date.now() / 1000);
        const elapsedTime = Math.max(0, now - this.startTimestamp);
        this.audio.currentTime = elapsedTime;
        this.audio.play().catch(error => {
            console.error('Playback failed: ', error);
            this.isPlaying = false;
            this.isReady = false;
        });
    },

    confirmReady() {
        this.isReady = true;
        if (this.isLive && this.audio && !this.isFinished) {
            this.playAudio();
        }
    },

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2,'0')}`;
    },

    endRoom() {
        $wire.isFinished = true;
        $wire.$refresh();
        this.isPlaying = false;
        if (this.audio) {
            this.audio.pause();
        }
    },

    copyToClipboard() {
        navigator.clipboard.writeText(window.location.href);
        this.copyNotification = true;
        setTimeout(() => this.copyNotification = false, 2000);
    },

}" x-init="init()" class="min-h-screen bg-surface-50 dark:bg-surface-950">
    @if($room->end_time === null)
        <div wire:poll.5s class="flex items-center justify-center min-h-screen p-4 bg-surface-50 dark:bg-surface-950" x-cloak>
            <div class="w-full max-w-xl shadow-sm bg-surface-alt-100 items-center justify-between rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700">
                <div class="flex space-x-4">
                    <div class="flex items-center py-4 gap-8 w-full">
                        <x-badge class="bg-transparent text-on-primary-100">
                            <x-slot name="prepend" class="relative flex items-center w-20 h-20">
                                <span class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-primary-500 dark:bg-primary-600 animate-ping"></span>
                                <span class="relative inline-flex w-20 h-20 rounded-full bg-primary-500 dark:bg-primary-600 justify-center items-center">
                                    <span class="material-symbols-outlined icon-big text-on-primary"> handshake </span>
                                </span>
                            </x-slot>
                        </x-badge>
                        <div>
                            <h4 class="text-md">Creating your room: <span class="font-serif">{{ $room->name }}</span> </h4>
                            <p class="text-xs">Just a moment it is being put together right now...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($isFinished)
        <div x-cloak class="flex items-center justify-center min-h-screen p-4 bg-surface-50 dark:bg-surface-950">
            <div class="w-full max-w-xl shadow-sm bg-surface-alt-100 items-center justify-between rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700">
                <div class="flex space-x-4">
                    <div class="flex items-center py-4 gap-8 w-full">
                        <x-badge flat class="bg-transparent text-on-negative">
                            <x-slot name="prepend" class="relative flex items-center w-20 h-20">
                                <span class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-negative dark:bg-negative"></span>
                                <span class="relative inline-flex w-20 h-20 rounded-full bg-negative dark:bg-negative justify-center items-center">
                                    <span class="material-symbols-outlined icon-big text-on-negative"> error </span>
                                </span>
                            </x-slot>
                        </x-badge>
                        <div>
                            <h4 class="text-md">Playlist finished!</h4>
                            <p class="text-xs">Sorry, the room you are looking for is not available anymore!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <audio x-ref="audioPlayer" :src="'{{ $room->track->media_url }}'" preload="auto"></audio>
        <div class="flex h-screen p-4 gap-4">
            <!-- Left Column (2/3) -->
            <div class="w-2/3 flex flex-col gap-4">
                <!-- Video/Artwork Area -->
                <div class="flex-1 bg-surface-alt-100 rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700">
                    <div class="h-full flex flex-col">
                        <!-- Main Content Area -->
                        <div class="flex-1 flex items-center justify-center">
                            <template x-if="!isLive">
                                <div class="text-center">
                                    <h2 class="text-2xl font-bold mb-2">Starts in:</h2>
                                    <div class="text-4xl font-bold" x-text="countdownText"></div>
                                </div>
                            </template>
                            <template x-if="isLive">
                                <div class="w-full h-full flex flex-col items-center justify-center">
                                    <img src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/400'}}" class="max-h-[400px] rounded-xl" alt="Media Artwork">
                                </div>
                            </template>
                        </div>
                        
                        <!-- Progress Bar (Only visible when live) -->
                        <div x-show="isLive && !isLoading" class="mt-4">
                            <div class="w-full bg-surface-200 rounded-full h-2">
                                <div class="bg-primary-500 h-2 rounded-full" :style="'width: ' + (currentTime / audio?.duration * 100) + '%'"></div>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span x-text="formatTime(currentTime)"></span>
                                <span x-text="formatTime(audio?.duration || 0)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="h-1/3 bg-surface-alt-100 rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700">
                    <div class="flex flex-col h-full">
                        <div class="flex-1">
                            <template x-if="!isLive">
                                <!-- Not Live View -->
                                <div class="flex items-start gap-4">
                                    <img src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/128'}}" class="w-32 h-32 rounded-xl object-cover" alt="Media Artwork">
                                    <div>
                                        <h2 class="text-2xl font-bold mb-2">{{ $room->name }}</h2>
                                        <p class="text-lg">{{ $room->track->media->title }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $room->track->media->artist }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="isLive">
                                <!-- Live View - Playlist Table -->
                                <div class="overflow-y-auto max-h-[calc(100%-80px)]">
                                    <table class="w-full">
                                        <thead class="text-left">
                                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                                <th class="py-2">Title</th>
                                                <th class="py-2">Artist</th>
                                                <th class="py-2">Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-primary-100 dark:bg-primary-900/20">
                                                <td class="py-2">{{ $room->track->media->title }}</td>
                                                <td class="py-2">{{ $room->track->media->artist }}</td>
                                                <td class="py-2">{{ $room->track->media->duration ?? '0:00' }}</td>
                                            </tr>
                                            <!-- Add more tracks here if needed -->
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Player Controls -->
                        <div class="mt-4">
                            <template x-if="!isLive">
                                <x-button class="w-full" @click="confirmReady()">
                                    <span x-text="isReady ? 'Stay Tuned...' : 'I allow audio and I\'m ready'"></span>
                                </x-button>
                            </template>

                            <template x-if="isLive">
                                <div class="flex items-center gap-4">
                                    <button @click="togglePlay()" class="p-2 rounded-full hover:bg-surface-200">
                                        <span class="material-symbols-outlined text-2xl" x-text="isPlaying ? 'pause' : 'play_arrow'"></span>
                                    </button>
                                    <div class="relative flex-1 group" @wheel.prevent="adjustVolume($event)">
                                        <div class="h-2 bg-surface-200 rounded-full">
                                            <div class="h-full bg-primary-500 rounded-full" :style="'width: ' + (volume * 100) + '%'"></div>
                                        </div>
                                        <div class="absolute -top-8 left-0 w-full opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="text-sm">Volume: <span x-text="Math.round(volume * 100) + '%'"></span></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column (1/3) - Chat -->
            <div class="w-1/3 bg-surface-alt-100 rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700">
                <div class="flex flex-col h-full">
                    <div class="flex-1 overflow-y-auto" id="message-container">
                        <div class="flex flex-col gap-4">
                            @foreach ($messages as $message)
                                @if ($message->user_id === auth()->id())
                                    <!-- Sent Message -->
                                    <div class="flex items-end gap-2">
                                        <div class="ml-auto flex max-w-[70%] flex-col gap-2 rounded-l-radius rounded-tr-radius bg-primary p-4 text-sm text-on-primary dark:bg-primary-dark dark:text-on-primary-dark">
                                            {{ $message->message }}
                                            <span class="ml-auto text-xs">{{ $message->created_at->format('h:i A') }}</span>
                                        </div>
                                        <span class="flex size-8 items-center justify-center overflow-hidden rounded-full border border-outline bg-surface-alt text-sm font-bold tracking-wider text-on-surface dark:border-outline-dark dark:bg-surface-dark-alt dark:text-on-surface-dark">
                                            {{ substr($message->user->name, 0, 2) }}
                                        </span>
                                    </div>
                                @else
                                    <!-- Received Message -->
                                    <div class="flex items-end gap-2">
                                        <img class="size-8 rounded-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode($message->user->name) }}" alt="avatar" />
                                        <div class="mr-auto flex max-w-[70%] flex-col gap-2 rounded-r-radius rounded-tl-radius bg-surface-alt p-4 text-on-surface dark:bg-surface-dark-alt dark:text-on-surface-dark">
                                            <span class="font-semibold text-on-surface-strong dark:text-on-surface-dark-strong">{{ $message->user->name }}</span>
                                            <div class="text-sm">
                                                {{ $message->message }}
                                            </div>
                                            <span class="ml-auto text-xs">{{ $message->created_at->format('h:i A') }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Message Input -->
                    <div class="mt-4">
                        <form wire:submit.prevent="sendMessage" class="flex gap-2">
                            <input type="text" wire:model="message" class="flex-1 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2" placeholder="Type your message...">
                            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600">
                                <span class="material-symbols-outlined">send</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
