@props(['status' => 'green', 'value' => null, 'label' => null, 'unit' => '', 'target' => null])
@php
    $border = match($status) { 'green' => 'border-green-500', 'yellow' => 'border-yellow-500', 'red' => 'border-red-500', default => 'border-gray-300' };
    $text = match($status) { 'green' => 'text-green-600', 'yellow' => 'text-yellow-600', 'red' => 'text-red-600', default => 'text-gray-600' };
    $dot = match($status) { 'green' => 'bg-green-500', 'yellow' => 'bg-yellow-500', 'red' => 'bg-red-500', default => 'bg-gray-400' };
@endphp
<div class="bg-white rounded-lg shadow-sm border-l-4 {{ $border }} p-4">
    <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</span>
        <span class="inline-block w-2 h-2 rounded-full {{ $dot }}"></span>
    </div>
    <div class="mt-2 text-2xl font-bold {{ $text }}">
        {{ is_numeric($value) ? number_format((float)$value, ($unit === 'kg' ? 2 : ($value == (int)$value ? 0 : 2))) : $value }}
        <span class="text-sm font-medium text-gray-400">{{ $unit }}</span>
    </div>
    @if($target !== null)
        <div class="mt-1 text-[11px] text-gray-400">Target: {{ number_format((float)$target, 2) }}{{ $unit }}</div>
    @endif
</div>
