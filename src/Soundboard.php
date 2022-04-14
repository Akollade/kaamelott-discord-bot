<?php

namespace App;

use function Safe\file_get_contents;
use function Safe\json_decode;


class Soundboard
{
    private string $soundsPath;

    private array $sounds;

    public function __construct(string $soundsPath)
    {
        $this->soundsPath = $soundsPath;
        $this->sounds = [];
    }

    public function search(string $query): ?string
    {
        $this->loadSounds();

        $sounds = array_filter($this->sounds, function(array $sound) use ($query) {
            return stripos($sound['file'], $query) !== false;
        });

        if (empty($sounds)) {
            return null;
        }

        $sound = $sounds[array_rand($sounds)];

        return sprintf('%s/%s', $this->soundsPath, $sound['file']);
    }

    public function random(): ?string
    {
        $this->loadSounds();

        $sound = $this->sounds[array_rand($this->sounds)];

        return sprintf('%s/%s', $this->soundsPath, $sound['file']);
    }

    private function loadSounds(): void
    {
        if ($this->sounds !== []) {
            return;
        }

        $jsonContent = file_get_contents(sprintf('%s/sounds.json', $this->soundsPath) );
        $this->sounds = json_decode($jsonContent, true);
    }
}
