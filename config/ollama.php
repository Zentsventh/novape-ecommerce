<?php
return [
    // URL of the local Ollama server (default for Ollama CLI)
    'endpoint' => env('OLLAMA_ENDPOINT', 'http://127.0.0.1:11434'),

    // Default model to use. "llama3.2" is the latest open‑source 70B model that works well on modern CPUs.
    'model' => env('OLLAMA_MODEL', 'llama3.2'),
];
