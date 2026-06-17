@props([
    'icon' => 'information-circle',
    'title' => '',
    'value' => '0',
    'color' => 'gray',
    'alert' => false,
])

@php
    $colors = [
        'primary' => 'bg-primary-50 border-primary-200 text-primary-700',
        'success' => 'bg-success-50 border-success-200 text-success-700',
        'warning' => 'bg-warning-50 border-warning-200 text-warning-700',
        'danger'  => 'bg-danger-50 border-danger-200 text-danger-700',
        'info'    => 'bg-blue-50 border-blue-200 text-blue-700',
        'gray'    => 'bg-gray-50 border-gray-200 text-gray-700',
    ];
    $iconColors = [
        'primary' => 'text-primary-600',
        'success' => 'text-success-600',
        'warning' => 'text-warning-600',
        'danger'  => 'text-danger-600',
        'info'    => 'text-blue-600',
        'gray'    => 'text-gray-500',
    ];
    $cardClass = $colors[$color] ?? $colors['gray'];
    $iconClass = $iconColors[$color] ?? $iconColors['gray'];
@endphp

<div class="relative rounded-xl border p-4 {{ $cardClass }} {{ $alert ? 'ring-2 ring-danger-500/30' : '' }}">
    @if ($alert)
        <span class="absolute -top-1.5 -right-1.5 flex h-4 w-4">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-4 w-4 bg-danger-500 text-white text-xs items-center justify-center font-bold">!</span>
        </span>
    @endif

    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <dt class="text-xs font-medium opacity-75 truncate">{{ $title }}</dt>
            <dd class="mt-1 text-2xl font-bold tracking-tight tabular-nums truncate">{{ $value }}</dd>
        </div>
        <div class="shrink-0 ml-2">
            {{-- Heroicon 动态渲染 --}}
            <x-dynamic-component :component="'heroicon-o-' . $icon" :class="$iconClass . ' w-8 h-8'" />
        </div>
    </div>
</div>
