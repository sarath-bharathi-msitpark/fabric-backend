@props(['id' => 'confirm-modal', 'title' => 'Confirm', 'confirmText' => 'Confirm', 'method' => 'POST', 'action' => '#', 'color' => 'red'])
@php
    $btn = match($color) { 'red' => 'bg-red-600 hover:bg-red-700', 'blue' => 'bg-blue-600 hover:bg-blue-700', 'green' => 'bg-green-600 hover:bg-green-700', default => 'bg-gray-800 hover:bg-gray-900' };
@endphp
<div x-data="{ open: false }" x-cloak @keydown.escape.window="open = false">
    <div @click="open = true" class="inline-block">{{ $slot }}</div>
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div @click.outside="open = false" class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
            <div class="mt-2 text-sm text-gray-600">{{ $content }}</div>
            @if(isset($extra))<div class="mt-3">{{ $extra }}</div>@endif
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="open = false" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Cancel</button>
                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method($method)
                    @if(isset($fields)){{ $fields }}@endif
                    <button type="submit" class="px-4 py-2 text-sm rounded-md text-white {{ $btn }}">{{ $confirmText }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
