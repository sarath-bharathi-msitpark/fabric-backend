@props(['id' => 'modal', 'title' => 'Form'])
<div x-data="{ open: false }" x-cloak @keydown.escape.window="open = false">
    <div @click="open = true" class="inline-block">{{ $slot }}</div>
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 overflow-y-auto">
        <div @click.outside="open = false" class="bg-white rounded-lg shadow-xl max-w-lg w-full my-8">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <div class="p-4">
                {{ $content }}
            </div>
        </div>
    </div>
</div>
