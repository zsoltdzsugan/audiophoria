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
        $this->dispatch('messageAdded');

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
    hasConfirmed: false,
    currentTime: 0,
    audioMetadataLoaded: false,
    volume: 1,
    startTimestamp: {{ $room->start_time->timestamp }},
    endTimestamp: {{ $room->end_time ? $room->end_time->timestamp : 'null' }},
    countdownText: '',
    copyNotification: false,

    init() {
        this.startCountdown();
        if (this.$refs.audioPlayer && !this.isFinished) {
            this.initializeAudioPlayer();
        }
        // Check if we have a saved permission for this room
        const storageKey = `room_${{{ $room->id }}}_audio_permission`;
        this.hasConfirmed = localStorage.getItem(storageKey) === 'true';
    },

    adjustVolume(event) {
        if (this.audio) {
            const delta = event.deltaY > 0 ? -0.05 : 0.05;
            this.volume = Math.max(0, Math.min(1, this.volume + delta));
            this.audio.volume = this.volume;
        }
    },

    togglePlay() {
        if (this.audio && this.isReady && this.isLive && this.hasConfirmed) {
            if (this.isPlaying) {
                this.audio.pause();
            } else {
                this.audio.play();
            }
        }
    },

    initializeAudioPlayer() {
        this.audio = this.$refs.audioPlayer;
        this.audio.volume = this.volume;

        this.audio.addEventListener('loadedmetadata', () => {
            this.isLoading = false;
            this.audioMetadataLoaded = true;
            if (this.hasConfirmed && this.isLive) {
                this.playAudio();
            }
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
        const storageKey = `room_${{{ $room->id }}}_audio_permission`;

        if (timeUntilStart <= 0) {
            this.isLive = true;
            // Try to play audio if we're live and user has confirmed
            if (this.hasConfirmed && this.audio && !this.isPlaying && !this.isFinished) {
                this.playAudio();
            }
        } else {
            const days = Math.floor(timeUntilStart / 86400);
            const hours = Math.floor((timeUntilStart % 86400) / 3600);
            const minutes = Math.floor((timeUntilStart % 3600) / 60);
            const seconds = timeUntilStart % 60;
            this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        }

        // If room has ended, clear the stored permission
        if (this.endTimestamp && now >= this.endTimestamp) {
            localStorage.removeItem(storageKey);
        }
    },

    playAudio() {
        if (!this.audio || !this.hasConfirmed || this.isFinished) return;

        const now = Math.floor(Date.now() / 1000);
        const elapsedTime = Math.max(0, now - this.startTimestamp);
        this.audio.currentTime = elapsedTime;

        this.audio.play()
            .then(() => {
                this.isPlaying = true;
            })
            .catch(error => {
                console.error('Playback failed: ', error);
                this.isPlaying = false;
            });
    },

    confirmReady() {
        this.hasConfirmed = true;
        // Save the permission in localStorage
        const storageKey = `room_${{{ $room->id }}}_audio_permission`;
        localStorage.setItem(storageKey, 'true');
        
        // If we're already live, start playing immediately
        if (this.isLive && this.audioMetadataLoaded && !this.isFinished) {
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
        <div x-cloak wire:poll.5s class="flex items-center justify-center min-h-screen p-4 bg-surface-50 dark:bg-surface-950">
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
        <audio x-cloak x-ref="audioPlayer" :src="'{{ $room->track->media_url }}'" preload="auto"></audio>
        <div class="flex h-screen p-4 gap-4">
            <!-- Left Column (2/3) -->
            <div class="w-2/3 flex flex-col gap-4">
                <!-- Video/Artwork Area -->
                <div class="flex-1 bg-surface-alt-100 rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-outline-700">
                    <div class="h-full flex flex-col">
                        <!-- Main Content Area -->
                        <div class="flex-1 flex items-center justify-center">
                            <template x-cloak x-if="!isLive">
                                <div class="text-center">
                                    <h2 class="text-2xl font-bold mb-2">Starts in:</h2>
                                    <div class="text-4xl font-bold" x-text="countdownText"></div>
                                </div>
                            </template>
                            <template x-cloak x-if="isLive">
                                <div class="w-full h-full flex flex-col items-center justify-center">
                                    <img src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/400'}}" class="object-contain max-h-[600px] rounded-xl" alt="Media Artwork">
                                </div>
                            </template>
                        </div>

                        <!-- Progress Bar (Only visible when live) -->
                        <div x-cloak x-show="isLive && !isLoading" class="mt-4">
                            <div class="w-full bg-surface-200 rounded-full h-2">
                                <div class="bg-primary-500 h-2 rounded-full" :style="'width: ' + (currentTime / audio?.duration * 100) + '%'"></div>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span x-text="formatTime(currentTime)"></span>
                                <span>
                                    @php
                                        $duration = $room->start_time->diffInSeconds(
                                            $room->end_time,
                                        );
                                        $minutes = floor($duration / 60);
                                        $seconds = $duration % 60;
                                    @endphp
                                    {{ sprintf('%02d:%02d', $minutes, $seconds) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="h-1/3 bg-surface-alt-100 rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950">
                    <div class="flex flex-col h-full">
                        <div class="flex-1">
                            <template x-if="!isLive">
                                <!-- Not Live View -->
                                <div class="flex items-start justify-between gap-4">
                                    <img src="{{ $room->track->media->artwork_url ?? 'https://placehold.co/128'}}" class="w-full h-auto max-w-[208px] rounded-xl object-cover" alt="Media Artwork">
                                    <div class="w-full">
                                        <h2 class="text-2xl font-bold mb-2">{{ $room->name }}</h2>
                                        <p class="text-xl">{{ $room->track->title }}</p>
                                        <p class="text-sm tracking-tight uppercase">{{ $room->media->title }}</p>
                                    </div>
                                    <div class="relative z-20 flex items-center flex-shrink-0">
                                        <button @click="copyToClipboard();" class="flex items-center justify-center w-auto h-8 px-3 py-1 text-xs bg-surface-50 dakr:bg-surface-950 rounded-xl cursor-pointer hover:brightness-110 active:brightness-110 focus:brightness-110 focus:outline-none text-on-surface-600 dark:text-on-surface-100 hover:text-on-surface-strong-800 dark:hover:text-on-surface-strong-100 group">
                                            <span x-show="!copyNotification">Copy to Clipboard</span>
                                            <svg x-show="!copyNotification" class="w-4 h-4 ml-1.5 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                                            <span x-show="copyNotification" class="tracking-tight text-positive brightness-50" x-cloak>Copied to Clipboard</span>
                                            <svg x-show="copyNotification" class="w-4 h-4 ml-1.5 text-positive brightness-50 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" /></svg>
                                        </button>
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
                                                <th class="py-2">Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-primary-100 dark:bg-primary-900/20">
                                                <td class="py-2">{{ $room->track->media->title }}</td>
                                                <td class="py-2">
                                                    <span>
                                                        @php
                                                            $duration = $room->start_time->diffInSeconds(
                                                                $room->end_time,
                                                            );
                                                            $minutes = floor($duration / 60);
                                                            $seconds = $duration % 60;
                                                        @endphp
                                                        {{ sprintf('%02d:%02d', $minutes, $seconds) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>

                        <!-- Player Controls -->
                        <div class="mt-4">
                            <template x-if="!hasConfirmed">
                                <button @click="confirmReady()" type="button" class="w-full rounded-xl px-4 py-2 text-sm font-semibold bg-primary-500 dark:bg-primary-600 text-on-primary-100 hover:brightness-90 active:brightness-90">
                                    Allow audio on this site.
                                </button>
                            </template>
                            <template x-if="hasConfirmed">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-2">
                                        <button @click="togglePlay()" type="button" class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-500 dark:bg-primary-600 text-on-primary-100 hover:brightness-90 active:brightness-90">
                                            <template x-if="!isPlaying">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                                </svg>
                                            </template>
                                            <template x-if="isPlaying">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                                </svg>
                                            </template>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span x-text="Math.round(volume * 100) + '%'" class="text-sm"></span>
                                        <div @wheel.prevent="adjustVolume($event)" class="w-24 h-2 bg-surface-50 dark:bg-surface-950 rounded-xl overflow-hidden cursor-pointer">
                                            <div :style="'width: ' + (volume * 100) + '%'" class="h-full bg-primary-500 dark:bg-primary-600"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column (1/3) - Chat -->
            <div class="w-1/3 bg-surface-alt-100 rounded-xl p-4 outline outline-offset-4 outline-surface-alt-100 dark:outline-surface-alt-950">
                <div class="flex flex-col h-full">
                    <div class="flex-1 overflow-y-auto focus-visible:outline-0 relative" id="message-container" x-ref="scrollToBottom"
                        x-data="{
                            shouldAutoScroll: true,
                            isNearBottom: true,
                            scroll() {
                                if (this.shouldAutoScroll) {
                                    this.$nextTick(() => {
                                        this.$refs.scrollToBottom.scrollTo({
                                            top: this.$refs.scrollToBottom.scrollHeight,
                                            behavior: 'smooth'
                                        });
                                    });
                                }
                            },
                            checkScroll() {
                                const container = this.$refs.scrollToBottom;
                                const atBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
                                this.isNearBottom = atBottom;
                                if (atBottom) {
                                    this.shouldAutoScroll = true;
                                }
                            },
                            handleNewMessage() {
                                // Only auto-scroll if we were already at the bottom
                                if (this.isNearBottom) {
                                    this.scroll();
                                }
                            },
                            init() {
                                this.scroll();
                                this.$refs.scrollToBottom.addEventListener('scroll', () => this.checkScroll());
                                
                                // Listen for Livewire message updates
                                Livewire.on('messageAdded', () => {
                                    this.handleNewMessage();
                                });
                            }
                        }"
                        x-init="init()"
                        wire:poll.5s="$refresh"
                        @scroll-to-bottom.window="shouldAutoScroll = true; scroll()"
                    >
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

                        <!-- Scroll to bottom button -->
                        <div class="fixed bottom-24 right-12 z-50">
                            <button 
                                x-show="!isNearBottom"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform translate-y-2"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100 transform translate-y-0"
                                x-transition:leave-end="opacity-0 transform translate-y-2"
                                @click="shouldAutoScroll = true; scroll()"
                                class="flex items-center justify-center size-10 rounded-full bg-primary text-on-primary shadow-lg hover:bg-primary-dark transition-all dark:bg-primary-dark dark:text-on-primary-dark"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Message Input or Login Button -->
                    <div class="mt-4 relative">
                        @auth
                            <form wire:submit.prevent="sendMessage" class="relative" x-on:submit.prevent="shouldAutoScroll = true; scroll(); $dispatch('message-sent')">
                                <div class="relative w-full">
                                    <input wire:model="message" id="message" type="text"
                                        class="w-full rounded-xl bg-surface-50 dark:bg-surface-950 px-2 py-2 pr-10 text-sm border-none shadow-md focus:ring-0 outline outline-offset-4 outline-surface-50 dark:outline-surface-950 hover:outline-primary-500 dark:hover:outline-primary-600 focus-visible:outline-offset-0 focus-visible:outline-primary-500 dark:focus-visible:outline-primary-600 transition-all duration-100"
                                        placeholder="Type your message..."
                                    >
                                    <button type="submit"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center justify-center size-6 rounded-full bg-primary text-on-primary hover:bg-primary-dark transition-colors dark:bg-primary-dark dark:text-on-primary-dark"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        @else
                            <button
                                wire:click="authenticateUser"
                                class="w-full rounded-xl bg-primary px-4 py-2 text-sm text-on-primary shadow-md hover:bg-primary-dark transition-colors dark:bg-primary-dark dark:text-on-primary-dark"
                            >
                                Login to Chat
                            </button>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
