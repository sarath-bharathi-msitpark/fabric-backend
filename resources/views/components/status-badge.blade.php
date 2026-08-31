@props(['status' => 'green'])
@php
    $map = [
        'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => $label ?? 'OK'],
        'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => $label ?? 'Warning'],
        'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => $label ?? 'Critical'],
        'pending' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $label ?? 'Pending'],
        'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => $label ?? 'Approved'],
        'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => $label ?? 'Rejected'],
        'planning' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => $label ?? 'Planning'],
        'in_progress' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => $label ?? 'In Progress'],
        'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => $label ?? 'Completed'],
        'on_hold' => ['bg' => 'bg-gray-200', 'text' => 'text-gray-700', 'label' => $label ?? 'On Hold'],
        'excellent' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => $label ?? 'Excellent'],
        'good' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => $label ?? 'Good'],
        'average' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => $label ?? 'Average'],
        'poor' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => $label ?? 'Poor'],
        'minor' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => $label ?? 'Minor'],
        'major' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => $label ?? 'Major'],
        'critical' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => $label ?? 'Critical'],
        'validating' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => $label ?? 'Validating'],
        'failed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => $label ?? 'Failed'],
        'admin' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => $label ?? 'Admin'],
        'manager' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => $label ?? 'Manager'],
        'viewer' => ['bg' => 'bg-gray-200', 'text' => 'text-gray-700', 'label' => $label ?? 'Viewer'],
        'delay' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => $label ?? 'Delay'],
        'quality' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => $label ?? 'Quality'],
        'shade' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-800', 'label' => $label ?? 'Shade'],
        'rejection' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => $label ?? 'Rejection'],
    ];
    $cfg = $map[$status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => $label ?? ucfirst($status)];
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cfg['bg'] }} {{ $cfg['text'] }}">{{ $cfg['label'] }}</span>
